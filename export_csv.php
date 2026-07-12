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
 * CSV export endpoint for TAG Proctoring violation events.
 *
 * Exports all proctoring events for a quiz as a UTF-8 CSV file suitable
 * for invigilator review in Excel or Google Sheets.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');

require_login();
require_sesskey();

$cmid = required_param('cmid', PARAM_INT);
$qid  = required_param('qid', PARAM_INT);

global $DB, $PAGE;

$cm      = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id, MUST_EXIST);
$PAGE->set_context($context);

require_capability('quizaccess/tagproctoring:viewreport', $context);

$quiz = $DB->get_record('quiz', ['id' => $qid], '*', MUST_EXIST);

// Load all finished attempts with user info.
$attempts = $DB->get_records_sql(
    "SELECT a.id, a.attempt, a.userid, a.timestart, a.timefinish,
            u.firstname, u.lastname, u.email
       FROM {quiz_attempts} a
       JOIN {user} u ON u.id = a.userid
      WHERE a.quiz = :quizid AND a.state = 'finished' AND a.preview = 0
      ORDER BY u.lastname ASC, u.firstname ASC, a.attempt ASC",
    ['quizid' => $qid]
);

if (!$attempts) {
    die('No finished attempts found.');
}

$attemptids = array_column((array) $attempts, 'id', 'id');
[$insql, $inparams] = $DB->get_in_or_equal(array_values($attemptids));

$events = $DB->get_records_select(
    'quizaccess_tagproctoring_events',
    "attemptid $insql",
    $inparams,
    'timecreated ASC'
);

// Index attempts by id for fast lookup.
$attemptmap = [];
foreach ($attempts as $a) {
    $attemptmap[$a->id] = $a;
}

// Stream CSV directly to the browser.
$quizslug = preg_replace('/[^a-z0-9_-]/i', '_', $quiz->name);
$filename = 'proctoring_events_' . $quizslug . '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// UTF-8 BOM so Excel auto-detects encoding.
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

fputcsv($out, [
    get_string('csv_student_name', 'quizaccess_tagproctoring'),
    get_string('csv_email', 'quizaccess_tagproctoring'),
    get_string('csv_attempt_num', 'quizaccess_tagproctoring'),
    get_string('csv_attempt_start', 'quizaccess_tagproctoring'),
    get_string('csv_attempt_end', 'quizaccess_tagproctoring'),
    get_string('csv_event_time', 'quizaccess_tagproctoring'),
    get_string('csv_event_type', 'quizaccess_tagproctoring'),
    get_string('csv_detail', 'quizaccess_tagproctoring'),
]);

foreach ($events as $e) {
    $a = $attemptmap[$e->attemptid] ?? null;
    if (!$a) {
        continue;
    }
    fputcsv($out, [
        trim($a->firstname . ' ' . $a->lastname),
        $a->email,
        $a->attempt,
        $a->timestart ? date('Y-m-d H:i:s', $a->timestart) : '',
        $a->timefinish ? date('Y-m-d H:i:s', $a->timefinish) : '',
        date('Y-m-d H:i:s', $e->timecreated),
        $e->eventtype,
        $e->detail ?? '',
    ]);
}

fclose($out);
exit;
