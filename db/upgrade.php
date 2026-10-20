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
 * Upgrade steps for mod_blerify.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_blerify_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026042301) {

        $table = new xmldb_table('blerify_wallet_tickets');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hmac_secret', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('consumed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('expires_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('token_idx', XMLDB_INDEX_UNIQUE, ['token']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('blerify_wallet_dids');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('did', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026042301, 'blerify');
    }

    if ($oldversion < 2026042302) {
        $table = new xmldb_table('blerify_wallet_tickets');
        $field = new xmldb_field('otp', XMLDB_TYPE_CHAR, '6', null, null, null, null, 'attempts');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042302, 'blerify');
    }

    if ($oldversion < 2026052100) {
        $table = new xmldb_table('blerify_wallet_tickets');
        $field = new xmldb_field('blerifyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('hmac_secret', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'token');
        $dbman->change_field_notnull($table, $field);

        upgrade_mod_savepoint(true, 2026052100, 'blerify');
    }

    if ($oldversion < 2026060400) {
        $table = new xmldb_table('blerify_wallet_tickets');
        $field = new xmldb_field('otp', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'expires_at');
        $dbman->change_field_precision($table, $field);

        upgrade_mod_savepoint(true, 2026060400, 'blerify');
    }

    if ($oldversion < 2026062600) {

        $table = new xmldb_table('blerify_wallet_lockouts');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('blerifyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('failcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lockeduntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastfailtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('blerifyid_fk', XMLDB_KEY_FOREIGN, ['blerifyid'], 'blerify', ['id']);
        $table->add_index('userid_blerifyid_idx', XMLDB_INDEX_UNIQUE, ['userid', 'blerifyid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026062600, 'blerify');
    }

    if ($oldversion < 2026071400) {

        $table = new xmldb_table('blerify_credentials');
        $fields = [
            new xmldb_field('laststep', XMLDB_TYPE_CHAR, '16', null, null, null, null, 'errordetail'),
            new xmldb_field('signingmessage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'laststep'),
            new xmldb_field('signature', XMLDB_TYPE_TEXT, null, null, null, null, null, 'signingmessage'),
            new xmldb_field('publickey', XMLDB_TYPE_TEXT, null, null, null, null, null, 'signature'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026071400, 'blerify');
    }

    if ($oldversion < 2026082400) {

        // The template is now chosen per activity instead of per course configuration.
        $table = new xmldb_table('blerify');
        $fields = [
            new xmldb_field('templateid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'configid'),
            new xmldb_field('templatename', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'templateid'),
            new xmldb_field('passgrade', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '70', 'completionissue'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Carry the template each activity was already using over from its configuration.
        $configs = new xmldb_table('blerify_configs');
        $legacytemplate = new xmldb_field('templateid');
        if ($dbman->field_exists($configs, $legacytemplate)) {
            $DB->execute("UPDATE {blerify} b
                             SET templateid = COALESCE(
                                     (SELECT c.templateid FROM {blerify_configs} c WHERE c.id = b.configid), '')
                           WHERE b.templateid = ''");
            $dbman->drop_field($configs, $legacytemplate);
        }

        // Issuance is now asynchronous: the local record tracks the API status and
        // the claim code instead of the intermediate signing material.
        $table = new xmldb_table('blerify_credentials');
        $newfields = [
            new xmldb_field('remotestatus', XMLDB_TYPE_CHAR, '32', null, null, null, null, 'status'),
            new xmldb_field('code', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'remotestatus'),
            new xmldb_field('timeissued', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'errordetail'),
            new xmldb_field('timeclaimed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeissued'),
        ];
        foreach ($newfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Credentials already delivered keep their state under the new vocabulary.
        $DB->set_field('blerify_credentials', 'status', 'issued', ['status' => 'assembled']);
        $DB->set_field('blerify_credentials', 'status', 'pending', ['status' => 'authorized']);
        $DB->execute("UPDATE {blerify_credentials} SET timeissued = timemodified
                       WHERE status = 'issued' AND timeissued = 0");

        foreach (['wallet_did', 'deeplinkcode', 'deeplinkurl', 'laststep',
                  'signingmessage', 'signature', 'publickey'] as $obsolete) {
            $field = new xmldb_field($obsolete);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // The OTP + wallet DID claiming flow is replaced by the claim code.
        foreach (['blerify_wallet_tickets', 'blerify_wallet_lockouts', 'blerify_wallet_dids'] as $obsolete) {
            $table = new xmldb_table($obsolete);
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        upgrade_mod_savepoint(true, 2026082400, 'blerify');
    }

    if ($oldversion < 2026090100) {

        // The project is configured once for the site, so an activity no longer
        // needs a per-course configuration row.
        $table = new xmldb_table('blerify');
        $field = new xmldb_field('configid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'course');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }

        upgrade_mod_savepoint(true, 2026090100, 'blerify');
    }

    if ($oldversion < 2026090102) {
        unset_config('organization_name', 'mod_blerify');
        upgrade_mod_savepoint(true, 2026090102, 'blerify');
    }

    if ($oldversion < 2026090200) {

        // The project is now a single site-wide setting, so the per-course
        // configuration page is gone. Carry an existing mapping over first, so
        // a site that had one keeps working without being reconfigured.
        $configs = new xmldb_table('blerify_configs');

        if ($dbman->table_exists($configs)) {
            if (trim((string) get_config('mod_blerify', 'project_id')) === '') {
                $projects = $DB->get_fieldset_sql(
                    'SELECT DISTINCT projectid FROM {blerify_configs} WHERE projectid <> ?', ['']);
                if (count($projects) === 1) {
                    set_config('project_id', reset($projects), 'mod_blerify');
                }
            }
        }

        $table = new xmldb_table('blerify');
        $field = new xmldb_field('configid');

        if ($dbman->field_exists($table, $field)) {
            // The foreign key to blerify_configs owns an index on this column,
            // and a column cannot be dropped while an index depends on it.
            $key = new xmldb_key('configid_fk', XMLDB_KEY_FOREIGN,
                ['configid'], 'blerify_configs', ['id']);
            try {
                $dbman->drop_key($table, $key);
            } catch (Exception $e) {
                // Installs that never had the key just move on to the index below.
                debugging('Blerify: configid_fk not present, ' . $e->getMessage(), DEBUG_DEVELOPER);
            }

            $index = new xmldb_index('configid', XMLDB_INDEX_NOTUNIQUE, ['configid']);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            $dbman->drop_field($table, $field);
        }

        if ($dbman->table_exists($configs)) {
            $dbman->drop_table($configs);
        }

        upgrade_mod_savepoint(true, 2026090200, 'blerify');
    }

    if ($oldversion < 2026090300) {

        // The project moved from a site-wide setting into each activity, so the
        // teacher picks it next to the template. Existing activities keep issuing
        // under whatever was configured before.
        $table = new xmldb_table('blerify');
        $field = new xmldb_field('projectid', XMLDB_TYPE_CHAR, '255', null,
            XMLDB_NOTNULL, null, '', 'course');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $legacy = trim((string) get_config('mod_blerify', 'project_id'));
        if ($legacy !== '') {
            $DB->execute("UPDATE {blerify} SET projectid = ? WHERE projectid = ''", [$legacy]);
        }

        unset_config('project_id', 'mod_blerify');

        upgrade_mod_savepoint(true, 2026090300, 'blerify');
    }

    if ($oldversion < 2026090305) {

        $table = new xmldb_table('blerify_credentials');
        $fields = [
            new xmldb_field('projectid', XMLDB_TYPE_CHAR, '255', null,
                XMLDB_NOTNULL, null, '', 'credentialid'),
            new xmldb_field('templateid', XMLDB_TYPE_CHAR, '255', null,
                XMLDB_NOTNULL, null, '', 'projectid'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $DB->execute("UPDATE {blerify_credentials} bc
                         SET projectid = COALESCE((SELECT b.projectid FROM {blerify} b
                                                    WHERE b.id = bc.blerifyid), ''),
                             templateid = COALESCE((SELECT b.templateid FROM {blerify} b
                                                     WHERE b.id = bc.blerifyid), '')
                       WHERE bc.projectid = ''");

        upgrade_mod_savepoint(true, 2026090305, 'blerify');
    }

    if ($oldversion < 2026090700) {

        $targets = [
            ['blerify', 'projectid', 'course'],
            ['blerify', 'templateid', 'projectid'],
            ['blerify_credentials', 'projectid', 'credentialid'],
            ['blerify_credentials', 'templateid', 'projectid'],
        ];

        foreach ($targets as [$tablename, $fieldname, $previous]) {
            $table = new xmldb_table($tablename);
            $field = new xmldb_field($fieldname, XMLDB_TYPE_CHAR, '255', null, null, null, null, $previous);

            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_notnull($table, $field);
                $dbman->change_field_default($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026090700, 'blerify');
    }

    if ($oldversion < 2026101600) {

        $table = new xmldb_table('blerify_credentials');
        $field = new xmldb_field('organizationid', XMLDB_TYPE_CHAR, '255', null,
            null, null, null, 'credentialid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026101600, 'blerify');
    }

    if ($oldversion < 2026101606) {

        $table = new xmldb_table('blerify_credentials');
        $field = new xmldb_field('reissues', XMLDB_TYPE_INTEGER, '4', null,
            XMLDB_NOTNULL, null, '0', 'errordetail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026101606, 'blerify');
    }

    if ($oldversion < 2026101607) {

        $table = new xmldb_table('blerify_credentials');
        $field = new xmldb_field('timechecked', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0', 'reissues');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026101607, 'blerify');
    }

    return true;
}
