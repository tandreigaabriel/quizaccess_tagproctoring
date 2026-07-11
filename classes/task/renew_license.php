<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Scheduled task: weekly license token renewal for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\task;

/**
 * Renews the TAG Proctoring license token against the remote API.
 *
 * Runs weekly. Silently skips renewal if the token still has more than 7 days
 * remaining, so the API is only contacted when renewal is actually needed.
 */
class renew_license extends \core\task\scheduled_task {
    /**
     * Return the task display name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_renew_license', 'quizaccess_tagproctoring');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        $result = \quizaccess_tagproctoring\license::renew();
        if ($result) {
            mtrace('TAG Proctoring: license token renewed or still valid.');
        } else {
            mtrace('TAG Proctoring: license renewal failed — no key stored or API error.');
        }
    }
}
