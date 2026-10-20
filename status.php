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
 * Credential status endpoint for browser polling.
 *
 * Issuance is asynchronous, so the page polls this while the credential is
 * being assembled and once more after the learner claims it in the wallet.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');

use mod_blerify\local\credentials;

/** Do not call the API more often than this, however fast the browser polls. */
const BLERIFY_REFRESH_INTERVAL = 3;

require_login(0, false);
require_sesskey();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('blerify', $cmid);
if (!$cm) {
    echo json_encode(['status' => 'none']);
    exit;
}

$context = context_module::instance($cm->id);
if (!is_enrolled($context, $USER->id, '', true)) {
    echo json_encode(['status' => 'none']);
    exit;
}

$manager = new credentials();
$credential = $manager->get_credential_for_user($cm->instance, $USER->id);

if (!$credential) {
    echo json_encode(['status' => 'none']);
    exit;
}

$pending = in_array($credential->status,
    [credentials::STATUS_ISSUING, credentials::STATUS_ISSUED, credentials::STATUS_ERROR], true);

if ($pending && (time() - (int)$credential->timemodified) >= BLERIFY_REFRESH_INTERVAL) {
    try {
        $credential = $manager->refresh($credential);
    } catch (\Exception $e) {
        debugging('Blerify: status refresh failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

echo json_encode([
    'status' => $credential->status,
    'remotestatus' => $credential->remotestatus,
    'hascode' => !empty($credential->code),
    'updated' => (int)$credential->timemodified,
]);
