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
 * Scheduled task that reads back which issued credentials have reached a wallet.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\task;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\local\credentials;
use mod_blerify\local\service_account;

class sync_claims extends \core\task\scheduled_task {

    const BATCH = 50;

    const MAX_AGE = 90 * DAYSECS;

    public function get_name() {
        return get_string('task_sync_claims', 'blerify');
    }

    public function execute() {
        global $DB;

        if (!service_account::is_enabled()) {
            return;
        }

        $records = $DB->get_records_select('blerify_credentials',
            'status = :status AND credentialid IS NOT NULL AND timeissued > :cutoff',
            [
                'status' => credentials::STATUS_ISSUED,
                'cutoff' => time() - self::MAX_AGE,
            ],
            'timechecked ASC, id ASC', '*', 0, self::BATCH);

        if (!$records) {
            return;
        }

        $manager = new credentials();
        $now = time();
        $claimed = 0;

        foreach ($records as $record) {
            try {
                $updated = $manager->refresh($record);
                if ($updated->status === credentials::STATUS_CLAIMED) {
                    $claimed++;
                }
            } catch (\Exception $e) {
                mtrace('Blerify: could not sync credential ' . $record->id .
                    ': ' . $e->getMessage());
            }

            $DB->set_field('blerify_credentials', 'timechecked', $now, ['id' => $record->id]);
        }

        mtrace('Blerify: checked ' . count($records) . ' issued credentials, ' .
            $claimed . ' reached a wallet');
    }
}
