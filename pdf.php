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
 * Serves a learner's credential PDF or thumbnail.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');

use mod_blerify\local\credentials;

$cmid = required_param('id', PARAM_INT);
$asset = optional_param('asset', 'pdf', PARAM_ALPHA);
$download = optional_param('download', 0, PARAM_BOOL);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('blerify', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/blerify:view', $context);

if ($userid && $userid !== (int)$USER->id) {
    require_capability('mod/blerify:manage', $context);
} else {
    $userid = (int)$USER->id;
}

if (!in_array($asset, ['pdf', 'thumbnail'], true)) {
    throw new moodle_exception('invalidparameter', 'error');
}

function blerify_asset_failure($reason, $context = null) {
    debugging('Blerify asset unavailable: ' . $reason, DEBUG_DEVELOPER);
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo get_string('error_credential_not_ready', 'blerify');

    if ($context && has_capability('mod/blerify:manage', $context)) {
        echo "\n\n" . $reason;
    }

    exit;
}

$manager = new credentials();
$credential = $manager->get_credential_for_user($cm->instance, $userid);

if (!$credential || !in_array($credential->status,
        [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true)) {
    blerify_asset_failure('credential not in a readable state: ' .
        ($credential ? $credential->status : 'no record'), $context);
}

try {
    $file = $manager->get_asset_file($credential, $asset);
} catch (\Exception $e) {
    blerify_asset_failure($e->getMessage(), $context);
}

if (!$file) {
    blerify_asset_failure('no stored ' . $asset . ' for this credential', $context);
}

$filename = clean_filename(format_string($cm->name)) .
    ($asset === 'pdf' ? '.pdf' : '.png');

send_stored_file($file, 30, 0, $download, ['filename' => $filename]);
