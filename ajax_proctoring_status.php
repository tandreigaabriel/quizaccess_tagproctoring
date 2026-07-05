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
 * AJAX endpoint: returns proctoring risk data for all attempts in a quiz.
 *
 * Response: JSON object keyed by quiz_attempts.id
 *   { "<attemptid>": { "risk": "high"|"medium"|"low", "score": <int> }, ... }
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');

ob_start();
header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $cmid = required_param('cmid', PARAM_INT);

    global $DB;

    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('quizaccess/tagproctoring:viewreport', $context);

    $quizid = (int) $cm->instance;

    // Get all events for this quiz, grouped by attemptid and eventtype.
    $events = $DB->get_records('quizaccess_tagproctoring_events', ['quizid' => $quizid]);

    $eventcountsbyattempt = [];
    foreach ($events as $e) {
        if (!isset($eventcountsbyattempt[$e->attemptid])) {
            $eventcountsbyattempt[$e->attemptid] = [];
        }
        $eventcountsbyattempt[$e->attemptid][$e->eventtype] =
            ($eventcountsbyattempt[$e->attemptid][$e->eventtype] ?? 0) + 1;
    }

    // Get scan suspicious flag per userid for this quiz.
    $scans = $DB->get_records('quizaccess_tagproctoring_scan', ['quizid' => $quizid], '', 'userid, scan_suspicious');
    $suspiciousbyuser = [];
    foreach ($scans as $s) {
        $suspiciousbyuser[(int) $s->userid] = !empty($s->scan_suspicious);
    }

    // Get all attempts (all states) for this quiz to cover what the grades report shows.
    $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quizid], '', 'id, userid');

    $result = [];
    foreach ($attempts as $attempt) {
        $eventcounts = $eventcountsbyattempt[$attempt->id] ?? [];
        $scansuspicious = $suspiciousbyuser[(int) $attempt->userid] ?? false;
        $result[(string) $attempt->id] = quizaccess_tagproctoring_calculate_attempt_risk(
            $eventcounts,
            $scansuspicious
        );
    }

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
a
