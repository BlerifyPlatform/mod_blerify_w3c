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
 * Local library functions for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_blerify\local\credentials;

/**
 * The projects the configured service account can issue under, cached briefly.
 *
 * @return array List of ['id' => string, 'name' => string].
 * @throws \Exception When the list cannot be retrieved.
 */
function blerify_get_projects() {
    // The cache is an optimisation, never a dependency: a store that is not
    // available yet must not stop the activity form from being usable.
    $cache = null;
    try {
        $cache = \cache::make('mod_blerify', 'templates');
        $cached = $cache->get('projects');
        if ($cached !== false) {
            return $cached;
        }
    } catch (\Exception $e) {
        debugging('Blerify: template cache unavailable: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    $projects = (new \mod_blerify\apirest\apirest(new \mod_blerify\client\client()))
        ->get_projects();

    if ($cache) {
        $cache->set('projects', $projects);
    }

    return $projects;
}

/**
 * Whether a project belongs to the organization of the loaded service account.
 *
 * @param string $projectid
 * @return bool True when it belongs, or when the list cannot be checked.
 */
function blerify_project_belongs_to_account($projectid) {
    if (empty($projectid)) {
        return false;
    }

    try {
        $projects = blerify_get_projects();
    } catch (\Exception $e) {
        debugging('Blerify: could not verify project ownership: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return true;
    }

    if (empty($projects)) {
        return true;
    }

    foreach ($projects as $project) {
        if ($project['id'] === $projectid) {
            return true;
        }
    }

    return false;
}

/**
 * Every project mapped to the templates it holds.
 *
 * Both selects in the activity form are filled from this in one go, so picking
 * a project filters its templates in the browser without another round trip.
 *
 * @return array Project UUID => list of templates.
 */
function blerify_get_templates_by_project() {
    $bundle = [];

    foreach (blerify_get_projects() as $project) {
        try {
            $bundle[$project['id']] = blerify_get_templates($project['id']);
        } catch (\Exception $e) {
            debugging('Blerify: templates unavailable for project ' . $project['id'] .
                ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            $bundle[$project['id']] = [];
        }
    }

    return $bundle;
}

/**
 * The credential templates available in a project.
 *
 * @param string $projectid
 * @return array As returned by apirest::get_templates().
 * @throws \Exception When the list cannot be retrieved.
 */
function blerify_get_templates($projectid) {
    return (new \mod_blerify\apirest\apirest(new \mod_blerify\client\client()))
        ->get_templates($projectid);
}

/**
 * A readable explanation for a stored error detail.
 *
 * @param string $detail The code or raw message kept in blerify_credentials.
 * @return string A message the teacher can act on.
 */
function blerify_error_message($detail) {
    $detail = trim((string)$detail);

    if ($detail === '') {
        return get_string('error_reason_unknown', 'blerify');
    }

    $key = 'error_reason_' . $detail;

    if (preg_match('/^[a-z_]+$/', $detail)
            && get_string_manager()->string_exists($key, 'blerify')) {
        return get_string($key, 'blerify');
    }

    return get_string('error_reason_unknown', 'blerify');
}

/**
 * Whether a stored error detail is about how the activity is set up rather than
 * about the issuance itself.
 *
 * @param string $detail
 * @return bool
 */
function blerify_is_config_error($detail) {
    return in_array(trim((string)$detail), [
        'project_not_in_organization',
    ], true);
}

/**
 * The learner's course grade as a percentage of the maximum.
 *
 * @param int $courseid
 * @param int $userid
 * @return float|null The percentage, or null when the learner has no course grade yet.
 */
function blerify_get_course_grade_percentage($courseid, $userid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $courseitem = grade_item::fetch_course_item($courseid);
    if (!$courseitem) {
        return null;
    }

    $grade = $courseitem->get_grade($userid, false);
    if (!$grade || $grade->finalgrade === null || $grade->finalgrade === false) {
        return null;
    }

    $grademax = (float)$courseitem->grademax;
    $grademin = (float)$courseitem->grademin;
    $range = $grademax - $grademin;

    if ($range <= 0) {
        return null;
    }

    return (((float)$grade->finalgrade - $grademin) / $range) * 100;
}

/**
 * Queue issuance in a course for every activity whose grade threshold the
 * learner now meets.
 *
 * The API calls happen in an ad-hoc task, so a bulk regrade queues work instead
 * of holding the grader while Blerify responds.
 *
 * @param int $courseid
 * @param int $userid
 */
function blerify_issue_for_qualified_user($courseid, $userid) {
    global $DB;

    $records = $DB->get_records('blerify', ['course' => $courseid]);
    if (!$records) {
        return;
    }

    $user = $DB->get_record('user', ['id' => $userid], 'id, deleted');
    if (!$user || !empty($user->deleted)) {
        return;
    }

    $manager = new credentials();
    $percentage = null;

    foreach ($records as $record) {
        if (empty($record->completionissue) || empty($record->templateid)) {
            continue;
        }

        // A record already exists for every state worth keeping, including a
        // failed one: retrying a failure is a deliberate teacher action, so a
        // failing API is not retried on every grade change.
        if ($manager->get_credential_for_user($record->id, $userid)) {
            continue;
        }

        if ($percentage === null) {
            $percentage = blerify_get_course_grade_percentage($courseid, $userid);
            if ($percentage === null) {
                return;
            }
        }

        if ($percentage < (float)$record->passgrade) {
            continue;
        }

        \mod_blerify\task\issue_credential::queue($record->id, $userid);
    }
}

/**
 * Grade change handler: issues as soon as the learner reaches the threshold,
 * without waiting for the course to be marked complete.
 *
 * @param \core\event\user_graded $event
 */
function blerify_user_graded_handler($event) {
    if (empty($event->courseid) || empty($event->relateduserid)) {
        return;
    }

    blerify_issue_for_qualified_user($event->courseid, $event->relateduserid);
}

/**
 * Course completion handler.
 *
 * @param \core\event\course_completed $event
 */
function blerify_course_completed_handler($event) {
    blerify_issue_for_qualified_user($event->courseid, $event->relateduserid);
}
