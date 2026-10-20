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
 * View page for mod_blerify.
 * Shows the issuing table to teachers and the certificate to learners.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/blerify/lib.php');
require_once($CFG->dirroot . '/mod/blerify/locallib.php');

use mod_blerify\local\credentials;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('blerify', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$blerify = $DB->get_record('blerify', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/blerify:view', $context);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/blerify/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($blerify->name));
$PAGE->set_heading(format_string($course->fullname));

$manager = new credentials();
$action = optional_param('action', '', PARAM_ALPHA);
$viewurl = new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]);

if (has_capability('mod/blerify:manage', $context) && in_array($action, ['issue', 'retry'], true)) {
    require_sesskey();

    if (empty($blerify->projectid)) {
        redirect($viewurl, get_string('error_no_project_id', 'blerify'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    $targets = ($action === 'retry')
        ? [required_param('retryuserid', PARAM_INT)]
        : optional_param_array('selectedusers', [], PARAM_INT);

    if (empty($targets)) {
        redirect($viewurl, get_string('issue_no_selection', 'blerify'), null,
            \core\output\notification::NOTIFY_WARNING);
    }

    $success = 0;
    $fail = 0;

    foreach ($targets as $userid) {
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || !is_enrolled($context, $userid)) {
            $fail++;
            continue;
        }

        $existing = $manager->get_credential_for_user($blerify->id, $userid);

        if ($existing) {
            $stored = $manager->stored_assets($existing);
            $hasassets = $stored['pdf'] || $stored['thumbnail'];
            $settled = in_array($existing->status,
                [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true);

            if ($settled && $hasassets) {
                continue;
            }

            if (!$settled && $existing->status !== credentials::STATUS_ERROR) {
                continue;
            }
        }

        if ($existing && !empty($existing->credentialid)) {
            try {
                $recovered = $manager->refresh($existing);
                $stored = $manager->stored_assets($recovered);
                if ($recovered->status !== credentials::STATUS_ERROR
                        && ($stored['pdf'] || $stored['thumbnail'])) {
                    $success++;
                    continue;
                }
            } catch (\Exception $e) {
                debugging('Blerify: could not recover credential ' . $existing->id .
                    ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        if ($existing) {
            get_file_storage()->delete_area_files($context->id, 'mod_blerify',
                credentials::FILEAREA, $existing->id);
            $DB->delete_records('blerify_credentials', ['id' => $existing->id]);
        }

        $manager->enable_for_user($blerify->id, $userid);

        \mod_blerify\task\issue_credential::queue($blerify->id, $userid);

        \mod_blerify\event\credential_issued_manual::create([
            'context' => $context,
            'objectid' => $blerify->id,
            'relateduserid' => $userid,
        ])->trigger();

        $success++;
    }

    if ($fail > 0) {
        $message = get_string('issue_error_partial', 'blerify',
            (object)['success' => $success, 'fail' => $fail]);
        $type = $success > 0
            ? \core\output\notification::NOTIFY_WARNING
            : \core\output\notification::NOTIFY_ERROR;
    } else {
        $message = get_string('issue_success', 'blerify', $success);
        $type = \core\output\notification::NOTIFY_SUCCESS;
    }

    redirect($viewurl, $message, null, $type);
}

if (has_capability('mod/blerify:manage', $context)) {

    $allcredentials = $manager->get_all_credentials($blerify->id);

    $checked = 0;
    foreach ($allcredentials as $key => $cred) {
        if ($checked >= 25) {
            break;
        }
        if ($cred->status !== credentials::STATUS_ISSUED) {
            continue;
        }
        if ((time() - (int)$cred->timechecked) < credentials::STALE_AFTER) {
            continue;
        }
        $refreshed = $manager->refresh_if_stale($cred);
        $refreshed->firstname = $cred->firstname;
        $refreshed->lastname = $cred->lastname;
        $refreshed->email = $cred->email;
        $allcredentials[$key] = $refreshed;
        $checked++;
    }

    $templatedata = [
        'activityname' => format_string($blerify->name),
        'projectid' => $blerify->projectid ?: '-',
        'templatename' => $blerify->templatename ?: $blerify->templateid,
        'passgrade' => $blerify->passgrade,
        'hascredentials' => !empty($allcredentials),
        'credentials' => [],
        'cmid' => $cm->id,
        'sesskey' => sesskey(),
    ];

    foreach ($allcredentials as $cred) {
        $isready = in_array($cred->status,
            [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true);

        $templatedata['credentials'][] = [
            'fullname' => fullname($cred),
            'email' => $cred->email,
            'status' => get_string('status_' . $cred->status, 'blerify'),
            'errordetail' => $cred->errordetail ?: '',
            'errormessage' => $cred->errordetail
                ? blerify_error_message($cred->errordetail) : '',
            'is_error' => ($cred->status === credentials::STATUS_ERROR),
            'is_ready' => $isready,
            'userid' => $cred->userid,
            'credentialid' => $cred->credentialid ?: '-',
            'date' => $cred->timeissued ? userdate($cred->timeissued) : '-',
            'pdf_url' => ($isready && ($manager->get_stored_asset($cred, 'pdf')
                    || $cred->remotestatus !== 'DELIVERED'))
                ? (new moodle_url('/mod/blerify/pdf.php',
                    ['id' => $cm->id, 'userid' => $cred->userid]))->out(false)
                : '',
            'cmid' => $cm->id,
            'sesskey' => sesskey(),
        ];
    }

    $issued = [];
    foreach ($allcredentials as $cred) {
        $issued[$cred->userid] = true;
    }

    $availableusers = [];
    foreach (get_enrolled_users($context, 'mod/blerify:view', 0, 'u.*', 'u.lastname ASC') as $user) {
        if (isset($issued[$user->id]) || has_capability('mod/blerify:manage', $context, $user)) {
            continue;
        }
        $availableusers[] = [
            'userid' => $user->id,
            'fullname' => fullname($user),
            'email' => $user->email,
        ];
    }

    $templatedata['projectmismatch'] = !empty($blerify->projectid)
        && !blerify_project_belongs_to_account($blerify->projectid);
    $templatedata['editurl'] = (new moodle_url('/course/modedit.php',
        ['update' => $cm->id]))->out(false);

    $templatedata['hasavailableusers'] = !empty($availableusers);
    $templatedata['availableusers'] = $availableusers;

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_blerify/view_teacher', $templatedata);
    echo $OUTPUT->footer();

} else {

    $credential = $manager->get_credential_for_user($blerify->id, $USER->id);

    if ($credential) {
        $credential = $manager->refresh_if_stale($credential);
    }

    if ($credential && in_array($credential->status,
            [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true)) {
        $manager->ensure_assets($credential);

        if ($manager->needs_reissue($credential)) {
            $credential = $manager->reissue($credential);
        }
    }

    $status = $credential ? $credential->status : 'none';

    $templatedata = [
        'activityname' => format_string($blerify->name),
        'templatename' => $blerify->templatename ?: '',
        'passgrade' => $blerify->passgrade,
        'is_processing' => ($status === credentials::STATUS_ISSUING
            || $status === credentials::STATUS_PENDING),
        'is_ready' => in_array($status,
            [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true),
        'is_claimed' => ($status === credentials::STATUS_CLAIMED),
        'is_error' => ($status === credentials::STATUS_ERROR
            && !blerify_is_config_error($credential->errordetail)),
        'is_misconfigured' => (empty($blerify->projectid) || empty($blerify->templateid))
            || ($status === credentials::STATUS_ERROR
                && blerify_is_config_error($credential->errordetail)),
        'not_yet' => ($status === 'none'),
        'cmid' => $cm->id,
        'sesskey' => sesskey(),
    ];

    if ($templatedata['is_ready'] || $templatedata['is_processing']) {
        $templatedata['is_misconfigured'] = false;
    }

    if ($templatedata['is_misconfigured']) {
        $templatedata['not_yet'] = false;
        $templatedata['is_error'] = false;
    }

    if ($templatedata['not_yet']) {
        $percentage = blerify_get_course_grade_percentage($course->id, $USER->id);
        $templatedata['current_grade'] = ($percentage === null)
            ? null
            : format_float($percentage, 1);
    }

    if ($templatedata['is_ready']) {
        $available = $manager->stored_assets($credential);

        $templatedata['has_thumbnail'] = $available['thumbnail'];
        $templatedata['has_pdf'] = $available['pdf'];
        $templatedata['no_assets'] = !$available['thumbnail'] && !$available['pdf'];

        $templatedata['thumbnail_url'] = (new moodle_url('/mod/blerify/pdf.php',
            ['id' => $cm->id, 'asset' => 'thumbnail']))->out(false);
        $templatedata['pdf_url'] = (new moodle_url('/mod/blerify/pdf.php',
            ['id' => $cm->id]))->out(false);
        $templatedata['download_url'] = (new moodle_url('/mod/blerify/pdf.php',
            ['id' => $cm->id, 'download' => 1]))->out(false);
    }

    if ($templatedata['is_processing'] || ($templatedata['is_ready'] && !$templatedata['is_claimed'])) {
        $PAGE->requires->js_call_amd('mod_blerify/status_poll', 'init', [[
            'statusUrl' => (new moodle_url('/mod/blerify/status.php'))->out(false),
            'refreshUrl' => $viewurl->out(false),
            'sesskey' => sesskey(),
            'cmid' => $cm->id,
            'status' => $status,
        ]]);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_blerify/view_student', $templatedata);
    echo $OUTPUT->footer();
}
