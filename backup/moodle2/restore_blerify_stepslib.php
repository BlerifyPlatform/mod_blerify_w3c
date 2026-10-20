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
 * Restore steps for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_blerify_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('blerify', '/activity/blerify');

        if ($userinfo) {
            $paths[] = new restore_path_element('blerify_credential', '/activity/blerify/credentials/credential');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_blerify($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('blerify', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_blerify_credential($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->blerifyid = $this->get_new_parentid('blerify');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $data->credentialid = null;
        $data->organizationid = null;
        $data->code = null;
        $data->remotestatus = null;
        $data->errordetail = null;
        $data->status = 'pending';
        $data->timeissued = 0;
        $data->timeclaimed = 0;
        $data->reissues = 0;
        $data->timechecked = 0;

        $newitemid = $DB->insert_record('blerify_credentials', $data);
        $this->set_mapping('blerify_credential', $oldid, $newitemid);
    }

    protected function after_execute() {
    }
}
