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
 * Delete student scan data page for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');

$cid = required_param('cid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$qid = required_param('qid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$context = context_module::instance($cmid, MUST_EXIST);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'quiz');

require_login($course, true, $cm);
require_capability('quizaccess/tagproctoring:viewreport', $context);

$reporturl = quizaccess_tagproctoring_get_report_url($cid, $cmid, $qid);

global $DB, $PAGE, $OUTPUT;

// GET: confirmation page.
if (!data_submitted()) {
    $user = core_user::get_user($userid, '*', MUST_EXIST);

    $PAGE->set_context($context);
    $PAGE->set_cm($cm, $course);
    $PAGE->set_url(new moodle_url('/mod/quiz/accessrule/tagproctoring/delete_scan.php', [
        'cid' => $cid,
        'cmid' => $cmid,
        'qid' => $qid,
        'userid' => $userid,
    ]));

    $PAGE->set_pagelayout('report');
    $PAGE->set_title('Delete Scan Data');
    $PAGE->set_heading('Delete Scan Data');

    echo $OUTPUT->header();

    echo html_writer::start_div('container mt-4', ['style' => 'max-width:560px']);

    echo html_writer::tag(
        'h4',
        'Delete scan data for ' . fullname($user) .
        ' (Quiz: ' . format_string($cm->name) . ')?'
    );

    echo html_writer::tag(
        'p',
        'This will permanently delete the <strong>room scan video</strong>, ' .
        '<strong>selfie photo</strong>, and <strong>ID photo</strong> for this student on this quiz. ' .
        'The quiz attempt, screenshots, and event log are not affected.',
        ['class' => 'text-muted']
    );

    $form = html_writer::start_tag('form', ['method' => 'post', 'action' => '']);

    $form .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cid', 'value' => $cid]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cmid', 'value' => $cmid]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'qid', 'value' => $qid]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);

    $form .= html_writer::tag(
        'button',
        'Yes, delete scan data',
        ['type' => 'submit', 'class' => 'btn btn-danger me-2']
    );

    $form .= html_writer::link($reporturl, 'Cancel', ['class' => 'btn btn-secondary']);

    $form .= html_writer::end_tag('form');

    echo $form;

    echo html_writer::end_div();

    echo $OUTPUT->footer();
    exit;
}

// POST: perform deletion.

require_sesskey();

$scan = $DB->get_record('quizaccess_tagproctoring_scan', [
    'quizid' => $qid,
    'userid' => $userid,
]);

if ($scan) {
    $fs = get_file_storage();

    foreach (['roomscan', 'selfie', 'idphoto'] as $filearea) {
        $fs->delete_area_files(
            $context->id,
            'quizaccess_tagproctoring',
            $filearea,
            $userid
        );
    }

    $DB->delete_records('quizaccess_tagproctoring_scan', [
        'id' => $scan->id,
    ]);
}

redirect(
    $reporturl,
    'Scan data deleted.',
    3,
    \core\output\notification::NOTIFY_SUCCESS
);
