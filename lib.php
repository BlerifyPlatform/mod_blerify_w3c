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
 * Library of interface functions and constants for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a new blerify activity instance.
 *
 * @param stdClass $data Form data
 * @param mod_blerify_mod_form $mform
 * @return int New instance id
 */
function blerify_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->completionissue = isset($data->completionissue) ? $data->completionissue : 1;
    $data->passgrade = isset($data->passgrade) ? (int)$data->passgrade : 70;
    $data->templatename = blerify_resolve_template_name($data->projectid, $data->templateid);

    $data->id = $DB->insert_record('blerify', $data);

    return $data->id;
}

/**
 * Update an existing blerify activity instance.
 *
 * @param stdClass $data Form data
 * @param mod_blerify_mod_form $mform
 * @return bool Success
 */
function blerify_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->completionissue = isset($data->completionissue) ? $data->completionissue : 0;
    $data->passgrade = isset($data->passgrade) ? (int)$data->passgrade : 70;
    $data->templatename = blerify_resolve_template_name($data->projectid, $data->templateid);

    return $DB->update_record('blerify', $data);
}

/**
 * Delete a blerify activity instance.
 *
 * @param int $id Instance id
 * @return bool Success
 */
function blerify_delete_instance($id) {
    global $DB;

    if (!$DB->get_record('blerify', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('blerify', $id, 0, false, IGNORE_MISSING);
    if ($cm) {
        get_file_storage()->delete_area_files(context_module::instance($cm->id)->id,
            'mod_blerify', \mod_blerify\local\credentials::FILEAREA);
    }

    $DB->delete_records('blerify_credentials', ['blerifyid' => $id]);
    $DB->delete_records('blerify', ['id' => $id]);

    return true;
}

/**
 * Resolve the display title of a template, so the activity can show it without
 * calling the API on every page load.
 *
 * @param string $projectid The project the template belongs to.
 * @param string $templateid The selected template UUID.
 * @return string The template title, or the id when it cannot be resolved.
 */
function blerify_resolve_template_name($projectid, $templateid) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/blerify/locallib.php');

    if (empty($projectid) || !\mod_blerify\local\service_account::get_decoded()) {
        return $templateid;
    }

    try {
        $templates = blerify_get_templates($projectid);
    } catch (\Exception $e) {
        debugging('Blerify: could not resolve template name: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return $templateid;
    }

    foreach ($templates as $template) {
        if ($template['id'] === $templateid) {
            return $template['title'];
        }
    }

    return $templateid;
}

/**
 * Supported features.
 *
 * @param string $feature FEATURE_xx constant
 * @return bool|null
 */
function blerify_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        default:
            return null;
    }
}
