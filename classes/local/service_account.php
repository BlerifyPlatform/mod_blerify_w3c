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
 * Encrypted storage helper for the Blerify service account JSON.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\local;

defined('MOODLE_INTERNAL') || die();

class service_account {

    /** @var string Config name holding the service account JSON. */
    const CONFIG_NAME = 'service_account_json';

    /** @var string Config name holding the active flag. */
    const ENABLED_NAME = 'service_account_enabled';

    /**
     * Encrypt and store the service account JSON.
     *
     * @param string $json Raw JSON string.
     * @return bool
     */
    public static function store(string $json): bool {
        self::ensure_key();
        $encrypted = \core\encryption::encrypt($json);
        $stored = set_config(self::CONFIG_NAME, base64_encode($encrypted), 'mod_blerify');
        if ($stored) {
            \cache::make('mod_blerify', 'accesstokens')->purge();
        }
        return $stored;
    }

    /**
     * Read and decrypt the stored service account JSON.
     *
     * @return string|null Raw JSON string or null when not configured.
     */
    public static function get_raw(): ?string {
        $stored = get_config('mod_blerify', self::CONFIG_NAME);
        if (empty($stored)) {
            return null;
        }

        $trimmed = trim($stored);
        if ($trimmed !== '' && $trimmed[0] === '{') {
            return $stored;
        }

        $binary = base64_decode($trimmed, true);
        if ($binary === false) {
            return null;
        }

        try {
            return \core\encryption::decrypt($binary);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Read, decrypt and decode the stored service account JSON.
     *
     * @return array|null Decoded associative array or null.
     */
    public static function is_enabled(): bool {
        $enabled = get_config('mod_blerify', self::ENABLED_NAME);

        return $enabled === false || (string) $enabled === '1';
    }

    public static function inspect(): ?array {
        $raw = self::get_raw();
        if (empty($raw)) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function get_decoded(): ?array {
        if (!self::is_enabled()) {
            return null;
        }

        $raw = self::get_raw();
        if (empty($raw)) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Ensure an encryption key exists for this site.
     */
    protected static function ensure_key(): void {
        if (!\core\encryption::key_exists()) {
            \core\encryption::create_key();
        }
    }
}
