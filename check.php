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
 * Reports which activities still point at projects the active service account cannot use.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/blerify/locallib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$url = new moodle_url('/mod/blerify/check.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('check_title', 'blerify'));
$PAGE->set_heading(get_string('check_title', 'blerify'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('check_title', 'blerify'));

if (!\mod_blerify\local\service_account::is_enabled()) {
    echo $OUTPUT->notification(get_string('check_account_disabled', 'blerify'),
        \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->footer();
    exit;
}

try {
    $bundle = blerify_get_templates_by_project();
    $organizationid = (new \mod_blerify\apirest\apirest(new \mod_blerify\client\client()))
        ->get_organization_id();
} catch (\Exception $e) {
    echo $OUTPUT->notification(get_string('check_api_unreachable', 'blerify') .
        ' ' . s($e->getMessage()), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('p', get_string('check_intro', 'blerify', s($organizationid)));

$sql = "SELECT cm.id AS cmid, b.id, b.name, b.projectid, b.templateid, b.templatename,
               c.id AS courseid, c.fullname AS coursename
          FROM {blerify} b
          JOIN {course} c ON c.id = b.course
          JOIN {course_modules} cm ON cm.instance = b.id
          JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
      ORDER BY c.fullname ASC, b.name ASC";

$records = $DB->get_records_sql($sql);

if (!$records) {
    echo $OUTPUT->notification(get_string('check_none', 'blerify'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('check_col_course', 'blerify'),
    get_string('check_col_activity', 'blerify'),
    get_string('check_col_project', 'blerify'),
    get_string('check_col_template', 'blerify'),
    get_string('check_col_state', 'blerify'),
    '',
];
$table->attributes['class'] = 'generaltable';

$broken = 0;

foreach ($records as $record) {
    if (empty($record->projectid) || empty($record->templateid)) {
        $state = get_string('check_not_configured', 'blerify');
        $class = 'text-warning';
        $broken++;
    } else if (!array_key_exists($record->projectid, $bundle)) {
        $state = get_string('check_bad_project', 'blerify');
        $class = 'text-danger';
        $broken++;
    } else if (!in_array($record->templateid,
            array_column($bundle[$record->projectid], 'id'), true)) {
        $state = get_string('check_bad_template', 'blerify');
        $class = 'text-danger';
        $broken++;
    } else {
        $state = get_string('check_ok', 'blerify');
        $class = 'text-success';
    }

    $editurl = new moodle_url('/course/modedit.php', ['update' => $record->cmid]);
    $viewurl = new moodle_url('/mod/blerify/view.php', ['id' => $record->cmid]);

    $table->data[] = [
        html_writer::link(new moodle_url('/course/view.php', ['id' => $record->courseid]),
            format_string($record->coursename)),
        html_writer::link($viewurl, format_string($record->name)),
        html_writer::tag('code', s($record->projectid ?: '-')),
        s($record->templatename ?: ($record->templateid ?: '-')),
        html_writer::tag('span', $state, ['class' => $class]),
        html_writer::link($editurl, get_string('check_fix', 'blerify'),
            ['class' => 'btn btn-sm btn-secondary']),
    ];
}

if ($broken > 0) {
    echo $OUTPUT->notification(get_string('check_broken', 'blerify', $broken),
        \core\output\notification::NOTIFY_WARNING);
} else {
    echo $OUTPUT->notification(get_string('check_all_ok', 'blerify'),
        \core\output\notification::NOTIFY_SUCCESS);
}

echo html_writer::table($table);
echo $OUTPUT->footer();
