// This file is part of Moodle - http://moodle.org/
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
 * Polls the credential status while Blerify assembles or the learner claims it,
 * and reloads the page once the state changes.
 *
 * @module     mod_blerify/status_poll
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var BASE_INTERVAL = 5000;
    var MAX_INTERVAL = 60000;
    var MAX_CONSECUTIVE_ERRORS = 5;
    var DEADLINE = 10 * 60 * 1000;

    return {
        init: function(config) {
            var timer = null;
            var interval = BASE_INTERVAL;
            var consecutiveErrors = 0;
            var deadline = Date.now() + DEADLINE;
            var currentStatus = config.status || '';

            var stop = function() {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
            };

            var schedule = function() {
                timer = setTimeout(poll, interval);
            };

            var poll = function() {
                if (Date.now() > deadline) {
                    stop();
                    return;
                }

                var url = config.statusUrl +
                    '?sesskey=' + encodeURIComponent(config.sesskey) +
                    '&cmid=' + encodeURIComponent(config.cmid);

                fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'}
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    consecutiveErrors = 0;
                    interval = BASE_INTERVAL;

                    if (data.status && data.status !== currentStatus) {
                        stop();
                        window.location.href = config.refreshUrl;
                        return;
                    }

                    schedule();
                })
                .catch(function() {
                    consecutiveErrors++;
                    if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
                        stop();
                        return;
                    }
                    interval = Math.min(interval * 2, MAX_INTERVAL);
                    schedule();
                });
            };

            schedule();
        }
    };
});
