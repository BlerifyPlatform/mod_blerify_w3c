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
 * Restore task for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/blerify/backup/moodle2/restore_blerify_stepslib.php');

class restore_blerify_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_blerify_activity_structure_step('blerify_structure', 'blerify.xml'));
    }

    public static function define_decode_contents() {
        return [];
    }

    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('BLERIFYVIEWBYID', '/mod/blerify/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('BLERIFYINDEX', '/mod/blerify/index.php?id=$1', 'course');

        return $rules;
    }
}
