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
 * Ad-hoc task that issues a credential outside the request that triggered it.
 *
 * Issuance makes two blocking calls to Blerify, so it must not run inside the
 * grading request: a bulk regrade would otherwise make one API round trip per
 * learner while the grader waits.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\task;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\local\credentials;

class issue_credential extends \core\task\adhoc_task {

    /**
     * Queue an issuance for one learner in one activity.
     *
     * @param int $blerifyid The blerify activity id.
     * @param int $userid The learner.
     */
    public static function queue($blerifyid, $userid) {
        $task = new self();
        $task->set_custom_data([
            'blerifyid' => $blerifyid,
            'userid' => $userid,
        ]);

        \core\task\manager::queue_adhoc_task($task);
    }

    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        $blerifyrecord = $DB->get_record('blerify', ['id' => $data->blerifyid]);
        $user = $DB->get_record('user', ['id' => $data->userid]);

        if (!$blerifyrecord || !$user || !empty($user->deleted)) {
            return;
        }

        $manager = new credentials();
        $credrecord = $manager->enable_for_user($blerifyrecord->id, $user->id);

        if (in_array($credrecord->status,
                [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true)) {
            return;
        }

        if (!\mod_blerify\local\service_account::is_enabled()) {
            mtrace('Blerify: service account inactive, issuance for user ' . $user->id . ' postponed');
            return;
        }

        require_once($GLOBALS['CFG']->dirroot . '/mod/blerify/locallib.php');

        if (!blerify_project_belongs_to_account($blerifyrecord->projectid)) {
            $manager->mark_error($credrecord, 'project_not_in_organization');
            mtrace('Blerify: project ' . $blerifyrecord->projectid .
                ' does not belong to the configured service account');
            return;
        }

        $manager->request_issuance($blerifyrecord, $user);

        $cm = get_coursemodule_from_instance('blerify', $blerifyrecord->id, $blerifyrecord->course);
        if ($cm) {
            \mod_blerify\event\certificate_created::create([
                'objectid' => $blerifyrecord->id,
                'context' => \context_module::instance($cm->id),
                'relateduserid' => $user->id,
            ])->trigger();
        }

        mtrace('Blerify: issuance requested for user ' . $user->id .
            ' in activity ' . $blerifyrecord->id);
    }
}
