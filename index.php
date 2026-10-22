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
 * Lists all blerify certificate activities in a course.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$strcertificates = get_string('modulenameplural', 'blerify');

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/blerify/index.php', ['id' => $course->id]);
$PAGE->navbar->add($strcertificates);
$PAGE->set_title($strcertificates);
$PAGE->set_heading($course->fullname);

if (!$certificates = get_all_instances_in_course('blerify', $course)) {
    echo $OUTPUT->header();
    notice(get_string('nocertificates', 'blerify'), "$CFG->wwwroot/course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    exit();
}

$table = new html_table();
$table->head = [get_string('name'), get_string('col_date', 'blerify')];

foreach ($certificates as $certificate) {
    $link = html_writer::tag(
        'a',
        format_string($certificate->name),
        ['href' => $CFG->wwwroot . '/mod/blerify/view.php?id=' . $certificate->coursemodule]
    );
    $issued = userdate($certificate->timecreated);
    $table->data[] = [$link, $issued];
}

echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('indexheader', 'blerify', format_string($course->fullname)));
echo html_writer::table($table);
echo $OUTPUT->footer();
