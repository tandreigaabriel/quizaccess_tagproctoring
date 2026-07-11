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
 * Risk override AJAX endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../../config.php');
defined('MOODLE_INTERNAL') || die();


header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $attemptid = required_param('attemptid', PARAM_INT);
    $override = required_param('override', PARAM_INT); // 1=mark valid, 0=clear

    global $DB, $USER;

    $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $attempt->quiz, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);

    require_capability('quizaccess/tagproctoring:viewreport', $context);

    $existing = $DB->get_record('quizaccess_tagproctoring_overrides', ['attemptid' => $attemptid]);

    if ($override) {
        if ($existing) {
            $existing->valid_override = 1;
            $existing->overriddenby = $USER->id;
            $existing->timecreated = time();
            $DB->update_record('quizaccess_tagproctoring_overrides', $existing);
        } else {
            $DB->insert_record('quizaccess_tagproctoring_overrides', (object) [
                'attemptid' => $attemptid,
                'valid_override' => 1,
                'overriddenby' => $USER->id,
                'timecreated' => time(),
            ]);
        }
    } else {
        if ($existing) {
            $DB->delete_records('quizaccess_tagproctoring_overrides', ['id' => $existing->id]);
        }
    }

    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => get_string('error_general', 'quizaccess_tagproctoring')]);
}
exit;
