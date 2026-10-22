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
 * Helper class for blerify activity DB operations.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\local;

defined('MOODLE_INTERNAL') || die();

class blerify {

    /**
     * Get a blerify activity record by ID.
     *
     * @param int $id
     * @return object|false
     */
    public function get_record($id) {
        global $DB;
        return $DB->get_record('blerify', ['id' => $id]);
    }

    /**
     * Get all blerify activities in a course.
     *
     * @param int $courseid
     * @return array
     */
    public function get_records_by_course($courseid) {
        global $DB;
        return $DB->get_records('blerify', ['course' => $courseid]);
    }
}
