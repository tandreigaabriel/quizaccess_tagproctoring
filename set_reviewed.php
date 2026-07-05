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
 * AJAX endpoint: set or clear the "reviewed" flag on a proctored attempt.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

ob_start();
header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $attemptid = required_param('attemptid', PARAM_INT);
    $reviewed  = required_param('reviewed', PARAM_INT);

    global $DB, $USER;

    $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    $cm      = get_coursemodule_from_instance('quiz', $attempt->quiz, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('quizaccess/tagproctoring:viewreport', $context);

    $existing = $DB->get_record('quizaccess_tagproctoring_overrides', ['attemptid' => $attemptid]);

    if ($existing) {
        $DB->update_record('quizaccess_tagproctoring_overrides', (object) [
            'id'           => $existing->id,
            'reviewed'     => $reviewed ? 1 : 0,
            'reviewedby'   => (int) $USER->id,
            'timereviewed' => $reviewed ? time() : 0,
        ]);
    } else {
        $DB->insert_record('quizaccess_tagproctoring_overrides', (object) [
            'attemptid'    => $attemptid,
            'valid_override' => 0,
            'overriddenby' => (int) $USER->id,
            'timecreated'  => time(),
            'reviewed'     => $reviewed ? 1 : 0,
            'reviewedby'   => (int) $USER->id,
            'timereviewed' => $reviewed ? time() : 0,
        ]);
    }

    ob_end_clean();
    echo json_encode(['status' => 'ok']);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
