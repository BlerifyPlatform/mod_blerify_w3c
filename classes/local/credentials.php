<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Credentials management for mod_blerify.
 * Drives the service-account issuance flow and stores its result.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\local;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\client\client;
use mod_blerify\apirest\apirest;
use mod_blerify\task\poll_credential;

class credentials {

    /** Queued locally, not sent to Blerify yet. */
    const STATUS_PENDING = 'pending';
    /** Created and approved; Blerify is still assembling it. */
    const STATUS_ISSUING = 'issuing';
    /** Issued (API status SENT): the PDF exists and the claim code is available. */
    const STATUS_ISSUED = 'issued';
    /** Claimed into a wallet (API status DELIVERED). */
    const STATUS_CLAIMED = 'claimed';
    /** Issuance failed. */
    const STATUS_ERROR = 'error';

    /**
     * Create and approve a credential for a user, then queue the polling task.
     *
     * Issuance is asynchronous: this returns as soon as Blerify accepts the
     * approval, with the record left in STATUS_ISSUING.
     *
     * @param object $blerifyrecord The blerify activity record.
     * @param object $user The Moodle user object.
     * @return object The blerify_credentials record.
     * @throws \Exception
     */
    public function request_issuance($blerifyrecord, $user) {
        global $DB;

        $lock = $this->get_issue_lock($blerifyrecord->id, $user->id);
        try {
            $credrecord = $this->get_or_create_record($blerifyrecord->id, $user->id);

            if (in_array($credrecord->status, [self::STATUS_ISSUED, self::STATUS_CLAIMED], true)) {
                return $credrecord;
            }

            try {
                $api = new apirest(new client());

                $projectid = $this->get_project_id($blerifyrecord);

                if (empty($credrecord->credentialid)) {
                    $credrecord->credentialid = $api->create_credential(
                        $user, $blerifyrecord->templateid, $projectid);
                }

                $credrecord->projectid = $projectid;
                $credrecord->templateid = $blerifyrecord->templateid;

                $api->approve_credential(
                    $projectid,
                    $credrecord->credentialid,
                    $blerifyrecord->templateid,
                    $this->get_receiver_lang($user)
                );

                $credrecord->status = self::STATUS_ISSUING;
                $credrecord->remotestatus = 'PENDING';
                $credrecord->errordetail = null;
                $credrecord->timemodified = time();
                $DB->update_record('blerify_credentials', $credrecord);

            } catch (\Exception $e) {
                $credrecord->status = self::STATUS_ERROR;
                $credrecord->errordetail = $e->getMessage();
                $credrecord->timemodified = time();
                $DB->update_record('blerify_credentials', $credrecord);
                debugging('Blerify issuance failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                throw $e;
            }
        } finally {
            $lock->release();
        }

        poll_credential::queue($credrecord->id);

        return $credrecord;
    }

    /**
     * Read the credential state from Blerify and store what changed.
     *
     * @param object $credrecord The blerify_credentials record.
     * @return object The refreshed record.
     * @throws \Exception
     */
    public function refresh($credrecord) {
        global $DB;

        if (empty($credrecord->credentialid)) {
            return $credrecord;
        }

        $api = new apirest(new client());

        try {
            $result = $api->poll_credential(
                $this->get_scope($credrecord, 'projectid'),
                $credrecord->credentialid,
                $this->get_scope($credrecord, 'templateid'));
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'HTTP 404') !== false) {
                $credrecord->status = self::STATUS_ERROR;
                $credrecord->errordetail = 'credential_not_found';
                $credrecord->timemodified = time();
                $DB->update_record('blerify_credentials', $credrecord);
            }
            throw $e;
        }

        return $this->apply_poll_result($credrecord, $result);
    }

    /**
     * Map an API polling result onto the local record.
     *
     * @param object $credrecord
     * @param array $result As returned by apirest::poll_credential().
     * @return object The refreshed record.
     */
    private function apply_poll_result($credrecord, array $result) {
        global $DB;

        $now = time();
        $before = clone $credrecord;

        $credrecord->remotestatus = $result['status'];

        if (!empty($result['code'])) {
            $credrecord->code = $result['code'];
        }

        switch ($result['status']) {
            case 'SENT':
                if ($credrecord->status !== self::STATUS_ISSUED) {
                    $credrecord->status = self::STATUS_ISSUED;
                    $credrecord->timeissued = $credrecord->timeissued ?: $now;
                }
                break;
            case 'DELIVERED':
                if ($credrecord->status !== self::STATUS_CLAIMED) {
                    $credrecord->status = self::STATUS_CLAIMED;
                    $credrecord->timeissued = $credrecord->timeissued ?: $now;
                    $credrecord->timeclaimed = $now;
                }
                break;
            default:
                $credrecord->status = self::STATUS_ISSUING;
                break;
        }

        // Only touch the record when something actually moved: the page reloads on
        // a change, so bumping timemodified on every poll would reload it forever.
        $changed = ($before->status !== $credrecord->status)
            || ($before->remotestatus !== $credrecord->remotestatus)
            || ($before->code !== $credrecord->code);

        if ($changed) {
            $credrecord->timemodified = $now;
            $DB->update_record('blerify_credentials', $credrecord);
        }

        return $credrecord;
    }

    /**
     * Fetch a fresh signed PDF URL for a credential.
     *
     * The URLs Blerify returns are valid for roughly a minute, so they are read
     * on demand instead of being stored.
     *
     * @param object $credrecord
     * @return array ['pdf' => string|null, 'thumbnail' => string|null]
     * @throws \Exception
     */
    public function get_asset_urls($credrecord) {
        if (empty($credrecord->credentialid)) {
            return ['pdf' => null, 'thumbnail' => null];
        }

        $api = new apirest(new client());
        $result = $api->poll_credential(
            $this->get_scope($credrecord, 'projectid'),
            $credrecord->credentialid,
            $this->get_scope($credrecord, 'templateid'));

        $this->apply_poll_result($credrecord, $result);

        return ['pdf' => $result['pdf'], 'thumbnail' => $result['thumbnail']];
    }

    /**
     * The language Blerify should use for the receiver's notification.
     *
     * @param object $user
     * @return string
     */
    private function get_receiver_lang($user) {
        $lang = !empty($user->lang) ? $user->lang : current_language();
        return substr($lang, 0, 2);
    }

    /**
     * The project this activity issues under.
     *
     * @param object $blerifyrecord
     * @return string
     * @throws \moodle_exception When no project is configured anywhere.
     */
    /**
     * The project or template a credential belongs to.
     *
     * Records created before these were stored fall back to the activity, which
     * is right as long as nobody changed it since.
     *
     * @param object $credrecord
     * @param string $field 'projectid' or 'templateid'.
     * @return string
     */
    private function get_scope($credrecord, $field) {
        global $DB;

        if (!empty($credrecord->{$field})) {
            return $credrecord->{$field};
        }

        $blerifyrecord = $DB->get_record('blerify', ['id' => $credrecord->blerifyid], '*', MUST_EXIST);

        if ($field === 'projectid') {
            return $this->get_project_id($blerifyrecord);
        }

        if (empty($blerifyrecord->templateid)) {
            throw new \moodle_exception('error_no_template_id', 'blerify');
        }

        return $blerifyrecord->templateid;
    }

    private function get_project_id($blerifyrecord) {
        if (empty($blerifyrecord->projectid)) {
            throw new \moodle_exception('error_no_project_id', 'blerify');
        }

        return $blerifyrecord->projectid;
    }

    private function get_issue_lock($blerifyid, $userid) {
        $factory = \core\lock\lock_config::get_lock_factory('mod_blerify_issuance');
        $lock = $factory->get_lock($blerifyid . '_' . $userid, 15);
        if (!$lock) {
            throw new \Exception('Blerify: could not acquire issuance lock for user ' . $userid);
        }
        return $lock;
    }

    private function get_or_create_record($blerifyid, $userid) {
        global $DB;

        $existing = $DB->get_record('blerify_credentials',
            ['blerifyid' => $blerifyid, 'userid' => $userid]);
        if ($existing) {
            return $existing;
        }

        $now = time();
        $credrecord = new \stdClass();
        $credrecord->blerifyid = $blerifyid;
        $credrecord->userid = $userid;
        $credrecord->status = self::STATUS_PENDING;
        $credrecord->timecreated = $now;
        $credrecord->timemodified = $now;

        try {
            $credrecord->id = $DB->insert_record('blerify_credentials', $credrecord);
            return $credrecord;
        } catch (\dml_write_exception $e) {
            return $DB->get_record('blerify_credentials',
                ['blerifyid' => $blerifyid, 'userid' => $userid], '*', MUST_EXIST);
        }
    }

    /**
     * Get credential record for a specific user in a blerify activity.
     *
     * @param int $blerifyid
     * @param int $userid
     * @return object|false
     */
    public function get_credential_for_user($blerifyid, $userid) {
        global $DB;

        return $DB->get_record('blerify_credentials', [
            'blerifyid' => $blerifyid,
            'userid' => $userid,
        ]);
    }

    /**
     * Get all credentials for a blerify activity, joined with user data.
     *
     * @param int $blerifyid
     * @return array
     */
    public function get_all_credentials($blerifyid) {
        global $DB;

        $sql = "SELECT bc.*, u.firstname, u.lastname, u.email
                  FROM {blerify_credentials} bc
                  JOIN {user} u ON u.id = bc.userid
                 WHERE bc.blerifyid = :blerifyid
              ORDER BY bc.timecreated DESC";

        return $DB->get_records_sql($sql, ['blerifyid' => $blerifyid]);
    }
}
