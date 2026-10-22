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
 * Privacy provider for mod_blerify.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_blerify\local\credentials;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the stored user data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('blerify_credentials', [
            'userid' => 'privacy:metadata:blerify_credentials:userid',
            'credentialid' => 'privacy:metadata:blerify_credentials:credentialid',
            'projectid' => 'privacy:metadata:blerify_credentials:projectid',
            'templateid' => 'privacy:metadata:blerify_credentials:templateid',
            'status' => 'privacy:metadata:blerify_credentials:status',
            'remotestatus' => 'privacy:metadata:blerify_credentials:remotestatus',
            'code' => 'privacy:metadata:blerify_credentials:code',
            'errordetail' => 'privacy:metadata:blerify_credentials:errordetail',
            'timeissued' => 'privacy:metadata:blerify_credentials:timeissued',
            'timeclaimed' => 'privacy:metadata:blerify_credentials:timeclaimed',
            'timecreated' => 'privacy:metadata:blerify_credentials:timecreated',
        ], 'privacy:metadata:blerify_credentials');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        $collection->add_external_location_link('blerify_api', [
            'email' => 'privacy:metadata:blerify_api:email',
            'fullname' => 'privacy:metadata:blerify_api:fullname',
        ], 'privacy:metadata:blerify_api');

        return $collection;
    }

    /**
     * Contexts holding data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {blerify} b ON b.id = cm.instance
                  JOIN {blerify_credentials} bc ON bc.blerifyid = b.id
                 WHERE bc.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'blerify',
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Users holding data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT bc.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                  JOIN {blerify} b ON b.id = cm.instance
                  JOIN {blerify_credentials} bc ON bc.blerifyid = b.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('blerify', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $credentials = $DB->get_records('blerify_credentials', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);

            foreach ($credentials as $credential) {
                $data = (object) [
                    'credentialid' => $credential->credentialid,
                    'projectid' => $credential->projectid,
                    'templateid' => $credential->templateid,
                    'status' => $credential->status,
                    'remotestatus' => $credential->remotestatus,
                    'code' => $credential->code,
                    'errordetail' => $credential->errordetail,
                    'timeissued' => $credential->timeissued
                        ? transform::datetime($credential->timeissued) : null,
                    'timeclaimed' => $credential->timeclaimed
                        ? transform::datetime($credential->timeclaimed) : null,
                    'timecreated' => transform::datetime($credential->timecreated),
                ];
                $subcontext = [get_string('pluginname', 'blerify'), 'credentials'];

                writer::with_context($context)->export_data($subcontext, $data);
                writer::with_context($context)->export_area_files($subcontext,
                    'mod_blerify', credentials::FILEAREA, $credential->id);
            }
        }
    }

    /**
     * Delete all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('blerify', $context->instanceid);
        if (!$cm) {
            return;
        }

        get_file_storage()->delete_area_files($context->id, 'mod_blerify', credentials::FILEAREA);

        $DB->delete_records('blerify_credentials', ['blerifyid' => $cm->instance]);
    }

    private static function delete_files(\context $context, array $recordids) {
        $fs = get_file_storage();

        foreach ($recordids as $recordid) {
            $fs->delete_area_files($context->id, 'mod_blerify', credentials::FILEAREA, $recordid);
        }
    }

    /**
     * Delete a user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('blerify', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $recordids = $DB->get_fieldset_select('blerify_credentials', 'id',
                'blerifyid = :blerifyid AND userid = :userid',
                ['blerifyid' => $cm->instance, 'userid' => $userid]);

            self::delete_files($context, $recordids);

            $DB->delete_records('blerify_credentials', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Delete data for several users in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('blerify', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['blerifyid'] = $cm->instance;

        $recordids = $DB->get_fieldset_select('blerify_credentials', 'id',
            "blerifyid = :blerifyid AND userid $insql", $params);

        self::delete_files($context, $recordids);

        $DB->delete_records_select('blerify_credentials',
            "blerifyid = :blerifyid AND userid $insql", $params);
    }
}
