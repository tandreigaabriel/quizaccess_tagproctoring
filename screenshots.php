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
 * Screenshot review page for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);

$context = context_module::instance($cmid, MUST_EXIST);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'quiz');

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);

require_login($course, true, $cm);
require_capability('quizaccess/tagproctoring:viewreport', $context);

global $DB;

$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $attempt->userid], '*', MUST_EXIST);
$screenshots = $DB->get_records('quizaccess_tagproctoring_screenshots', ['attemptid' => $attemptid], 'timecreated ASC');
$events = $DB->get_records('quizaccess_tagproctoring_events', ['attemptid' => $attemptid], 'timecreated ASC');

$title = get_string('screenshots_title', 'quizaccess_tagproctoring', fullname($user));
$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/tagproctoring/screenshots.php', ['cmid' => $cmid, 'attemptid' => $attemptid]));
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo html_writer::start_div('container-fluid mt-3');
echo html_writer::tag('h4', $title);
$started = userdate($attempt->timestart);
$finished = $attempt->timefinish ? userdate($attempt->timefinish) : get_string('screenshots_inprogress', 'quizaccess_tagproctoring');

$text = get_string('screenshots_attemptinfo', 'quizaccess_tagproctoring', [
    'started'  => $started,
    'finished' => $finished,
]);

echo html_writer::tag('p', $text);

// Events summary.
if ($events) {
    echo html_writer::tag('h5', get_string('screenshots_events_heading', 'quizaccess_tagproctoring'), ['class' => 'mt-4']);
    $table = new html_table();
    $table->head = [
        get_string('screenshots_col_time', 'quizaccess_tagproctoring'),
        get_string('screenshots_col_event', 'quizaccess_tagproctoring'),
        get_string('screenshots_col_detail', 'quizaccess_tagproctoring'),
    ];
    $table->attributes['class'] = 'table table-sm table-bordered';
    foreach ($events as $event) {
        $strkey = 'event_' . $event->eventtype;
        $label = get_string_manager()->string_exists($strkey, 'quizaccess_tagproctoring')
            ? get_string($strkey, 'quizaccess_tagproctoring')
            : $event->eventtype;
        $cls = in_array($event->eventtype, ['screenshare', 'multiplescreen']) ? 'table-danger' : 'table-warning';
        $row = new html_table_row([userdate($event->timecreated, '%H:%M:%S'), $label, $event->detail ?? '']);
        $row->attributes['class'] = $cls;
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}

// Screenshots grid.
if ($screenshots) {
    echo html_writer::tag('h5', get_string('screenshots_grid_heading', 'quizaccess_tagproctoring', count($screenshots)), ['class' => 'mt-4']);
    echo html_writer::start_div('row');
    foreach ($screenshots as $shot) {
        $url = moodle_url::make_pluginfile_url(
            $context->id,
            'quizaccess_tagproctoring',
            'screenshots',
            $shot->userid,
            '/',
            $shot->filename
        );
        $time = userdate($shot->timecreated, '%H:%M:%S');
        $img = html_writer::img($url->out(), $time, ['class' => 'img-fluid rounded', 'style' => 'max-height:200px;']);
        $card = html_writer::div(
            html_writer::div($img, 'card-img-top text-center bg-dark') .
            html_writer::div(html_writer::tag('small', $time, ['class' => 'text-muted']), 'card-body p-1 text-center'),
            'card mb-2'
        );
        echo html_writer::div($card, 'col-md-3 col-sm-4 col-6');
    }
    echo html_writer::end_div();
} else {
    echo html_writer::tag('p', get_string('screenshots_none', 'quizaccess_tagproctoring'), ['class' => 'text-muted mt-3']);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
