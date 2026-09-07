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
 * Backup steps for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_blerify_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $blerify = new backup_nested_element('blerify', ['id'], [
            'name', 'course', 'projectid', 'templateid', 'templatename',
            'completionissue', 'passgrade', 'timecreated', 'timemodified',
        ]);

        $credentials = new backup_nested_element('credentials');
        $credential = new backup_nested_element('credential', ['id'], [
            'blerifyid', 'userid', 'credentialid', 'projectid', 'templateid',
            'status', 'remotestatus',
            'timeissued', 'timeclaimed',
            'timecreated', 'timemodified',
        ]);

        $blerify->add_child($credentials);
        $credentials->add_child($credential);

        $blerify->set_source_table('blerify', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $credential->set_source_table('blerify_credentials', ['blerifyid' => backup::VAR_PARENTID]);
        }

        $credential->annotate_ids('user', 'userid');

        return $this->prepare_activity_structure($blerify);
    }
}
