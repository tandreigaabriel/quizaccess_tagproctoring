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
 * Report table class for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\local\report;

use moodle_url;
use table_sql;
use html_writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');

if (class_exists('mod_quiz\quiz_attempt')) {
    class_alias('mod_quiz\quiz_attempt', '\tagproctoring_quiz_attempt_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    class_alias('\quiz_attempt', '\tagproctoring_quiz_attempt_alias');
}

class report_list extends table_sql
{

    protected $cid;
    protected $cmid;
    protected $qid;
    protected $quizobj;
    protected $repurl;
    protected $perpage = 20;
    protected $isrecordexists = false;
    protected $context;

    public function __construct($cid, $cmid, $qid, $quizobj, $context)
    {
        parent::__construct('tagproctoring-report-table');
        $this->cid = $cid;
        $this->cmid = $cmid;
        $this->qid = $qid;
        $this->quizobj = $quizobj;
        $this->context = $context;
        $this->repurl = quizaccess_tagproctoring_get_report_url($cid, $cmid, $qid);
        $this->inittable();
    }

    protected function build_sql_properties()
    {
        global $DB;

        $nameconcatsql = $DB->sql_concat_join("' '", ['u.firstname', 'u.lastname']);

        $fields = "a.id, u.id AS user_id, u.email, $nameconcatsql AS userfullname,
                   a.attempt, a.sumgrades, a.timestart, a.timefinish,
                   a.timefinish - a.timestart AS timetakens, a.preview,
                   s.id AS scanid, s.filename AS scanfilename, s.userid AS scanuserid";

        $from = "{quiz_attempts} a
                   JOIN {user} u ON u.id = a.userid
                   LEFT JOIN {quizaccess_tagproctoring_scan} s
                     ON s.quizid = a.quiz AND s.userid = a.userid";

        $quizid = $this->quizobj->get_quiz()->id;
        $where = "a.quiz = :quizid AND a.state = :state AND a.preview = 0";
        $params = ['quizid' => $quizid, 'state' => \tagproctoring_quiz_attempt_alias::FINISHED];

        return [$fields, $from, $where, $params];
    }

    protected function inittable()
    {
        global $DB;

        [$fields, $from, $where, $params] = $this->build_sql_properties();
        // $fields and $from are developer-defined SQL fragments (table/field names only).
        // $where uses named placeholders; all values are passed via $params.
        // phpcs:ignore moodle.DB.RawSQL.found
        $row = $DB->get_record_sql("SELECT $fields FROM $from WHERE $where", $params, IGNORE_MULTIPLE);
        if (empty($row)) {
            return;
        }

        $this->isrecordexists = true;
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);

        $this->define_baseurl($this->repurl->out(false));
        $this->define_headers([
            get_string('report_table_header_email', 'quizaccess_tagproctoring'),
            get_string('report_table_header_name', 'quizaccess_tagproctoring'),
            get_string('report_table_header_attempt', 'quizaccess_tagproctoring'),
            get_string('report_table_header_score', 'quizaccess_tagproctoring'),
            get_string('report_table_header_timefinish', 'quizaccess_tagproctoring'),
            get_string('report_table_header_time_taken', 'quizaccess_tagproctoring'),
            get_string('report_table_header_risk', 'quizaccess_tagproctoring'),
            get_string('report_table_header_room_scan', 'quizaccess_tagproctoring'),
            get_string('report_table_header_screenshots', 'quizaccess_tagproctoring'),
            get_string('report_table_header_events', 'quizaccess_tagproctoring'),
            get_string('report_table_header_attempt_results', 'quizaccess_tagproctoring'),
            get_string('report_table_header_delete_scan', 'quizaccess_tagproctoring'),
        ]);
        $this->define_columns([
            'email',
            'userfullname',
            'attempt',
            'sumgrades',
            'timefinish',
            'timetakens',
            'risk_level',
            'room_scan',
            'screenshots',
            'events',
            'attempt_results',
            'delete_scan',
        ]);
        $this->sortable(true, 'timefinish', SORT_DESC);
        $this->no_sorting('risk_level');
        $this->no_sorting('room_scan');
        $this->no_sorting('screenshots');
        $this->no_sorting('events');
        $this->no_sorting('attempt_results');
        $this->no_sorting('delete_scan');
        $this->is_collapsible = false;
    }

    public function col_sumgrades($row)
    {
        if (is_null($row->sumgrades)) {
            return html_writer::tag(
                'span',
                get_string('report_table_row_notyetgraded', 'quizaccess_tagproctoring'),
                ['class' => 'text-muted']
            );
        }
        $quiz = $this->quizobj->get_quiz();
        if (empty($quiz->sumgrades)) {
            return '-';
        }
        $pct = format_float($row->sumgrades * 100 / $quiz->sumgrades, $quiz->decimalpoints, true, true);
        $cls = $pct > 80 ? 'text-success' : ($pct > 50 ? 'text-warning' : 'text-danger');
        return html_writer::tag('span', "$pct%", ['class' => $cls]);
    }

    public function col_timefinish($row)
    {
        return $row->timefinish
            ? userdate($row->timefinish, get_string('strftimerecent', 'langconfig'))
            : '-';
    }

    public function col_timetakens($row)
    {
        $timetaken = $row->timefinish - $row->timestart;
        if (!$timetaken)
            return '-';
        $quiz = $this->quizobj->get_quiz();
        $caption = format_time($timetaken);
        $cls = 'text-success';
        if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
            $overtime = format_time($timetaken - $quiz->timelimit);
            $cls = 'text-danger';
            $caption .= '<br/>' . get_string('report_table_row_overdue', 'quizaccess_tagproctoring', $overtime);
        }
        return html_writer::tag('span', $caption, ['class' => $cls]);
    }

    // Risk Level — scored with factor breakdown 
    public function col_risk_level($row)
    {
        global $DB;

        $events = $DB->get_records('quizaccess_tagproctoring_events', ['attemptid' => $row->id]);
        $scan = $DB->get_record('quizaccess_tagproctoring_scan', [
            'quizid' => $this->quizobj->get_quiz()->id,
            'userid' => $row->user_id,
        ]);

        $score = 0;
        $factors = [];

        // Tally events.
        $event_counts = [];
        foreach ($events as $e) {
            $event_counts[$e->eventtype] = ($event_counts[$e->eventtype] ?? 0) + 1;
        }

        $plg = 'quizaccess_tagproctoring';
        $high_risk = [
            'screenshare' => (int) (get_config($plg, 'risk_score_screenshare') ?: 50),
            'multiplescreen' => (int) (get_config($plg, 'risk_score_multiplescreen') ?: 50),
        ];
        $med_risk = [
            'fullscreenexit' => (int) (get_config($plg, 'risk_score_fullscreenexit') ?: 20),
            'tabswitch' => (int) (get_config($plg, 'risk_score_tabswitch') ?: 10),
            'visibilityhidden' => (int) (get_config($plg, 'risk_score_visibilityhidden') ?: 10),
            'copypaste' => (int) (get_config($plg, 'risk_score_copypaste') ?: 10),
            'devtools' => (int) (get_config($plg, 'risk_score_devtools') ?: 5),
        ];

        foreach ($high_risk as $type => $pts) {
            if (!empty($event_counts[$type])) {
                $label = get_string_manager()->string_exists('event_' . $type, 'quizaccess_tagproctoring')
                    ? get_string('event_' . $type, 'quizaccess_tagproctoring') : $type;
                $score += $pts;
                $factors[] = '<span style="display:block;font-size:11px;color:#dc2626;">'
                    . '&#9940; ' . $label . ' &times;' . $event_counts[$type] . '</span>';
            }
        }
        foreach ($med_risk as $type => $pts) {
            if (!empty($event_counts[$type])) {
                $label = get_string_manager()->string_exists('event_' . $type, 'quizaccess_tagproctoring')
                    ? get_string('event_' . $type, 'quizaccess_tagproctoring') : $type;
                $contribution = min($pts * $event_counts[$type], $pts * 3);
                $score += $contribution;
                $factors[] = '<span style="display:block;font-size:11px;color:#d97706;">'
                    . '&#9888; ' . $label . ' &times;' . $event_counts[$type] . '</span>';
            }
        }

        $scanscore = (int) (get_config($plg, 'risk_score_suspicious_scan') ?: 30);
        if ($scan && !empty($scan->scan_suspicious)) {
            $score += $scanscore;
            $factors[] = '<span style="display:block;font-size:11px;color:#d97706;">'
                . '&#9888; ' . get_string('report_risk_suspicious_scan', 'quizaccess_tagproctoring') . '</span>';
        }

        $thresholdhigh = (int) (get_config($plg, 'risk_threshold_high') ?: 50);
        $thresholdmedium = (int) (get_config($plg, 'risk_threshold_medium') ?: 20);
        $risk = $score >= $thresholdhigh ? 'high' : ($score >= $thresholdmedium ? 'medium' : 'low');
        $colors = ['high' => '#dc2626', 'medium' => '#d97706', 'low' => '#16a34a'];
        $color = $colors[$risk];
        $label = get_string('report_risk_' . $risk, 'quizaccess_tagproctoring');

        $out = '<span style="display:inline-block;background:' . $color
            . ';color:#fff;padding:2px 8px;border-radius:4px;font-weight:700;font-size:12px;">'
            . $label . '</span>';
        $out .= '<span style="font-size:11px;color:#666;margin-left:4px;">score: ' . $score . '</span>';

        if ($factors) {
            $out .= '<div style="margin-top:4px;">' . implode('', $factors) . '</div>';
        }

        return $out;
    }

    // Room Scan — inline video + detected objects 
    public function col_room_scan($row)
    {
        global $DB;

        $scan = $DB->get_record('quizaccess_tagproctoring_scan', [
            'quizid' => $this->quizobj->get_quiz()->id,
            'userid' => $row->user_id,
        ]);

        if (!$scan || empty($scan->filename)) {
            return html_writer::tag(
                'span',
                get_string('report_table_row_no_scan', 'quizaccess_tagproctoring'),
                ['class' => 'text-muted']
            );
        }

        $url = moodle_url::make_pluginfile_url(
            $this->context->id,
            'quizaccess_tagproctoring',
            'roomscan',
            $scan->userid,
            '/',
            $scan->filename
        );

        $out = '';

        // Suspicious flag
        if (!empty($scan->scan_suspicious)) {
            $out .= '<div style="background:#dc2626;color:#fff;font-size:10px;font-weight:700;'
                . 'padding:2px 6px;border-radius:3px;margin-bottom:4px;display:inline-block;">'
                . '&#9940; FLAGGED &mdash; Suspicious Objects</div><br>';
        }

        // Compact inline video player
        $out .= '<video src="' . htmlspecialchars($url->out(), ENT_QUOTES) . '" controls muted preload="none" '
            . 'style="width:160px;border-radius:6px;border:1px solid rgba(0,0,0,.15);display:block;margin-bottom:4px;">'
            . '</video>';

        // Detected objects from AI scan
        if (!empty($scan->scan_objects)) {
            $objects = @json_decode($scan->scan_objects, true);
            if (is_array($objects) && !empty($objects)) {
                $danger_items = ['cell phone', 'book', 'tablet', 'remote', 'extra_person'];
                foreach ($objects as $cls => $count) {
                    $bg = in_array($cls, $danger_items) ? '#dc2626' : '#6b7280';
                    $label = str_replace('_', ' ', $cls);
                    $out .= '<span style="background:' . $bg . ';color:#fff;font-size:10px;'
                        . 'padding:1px 5px;border-radius:3px;margin:1px;display:inline-block;">'
                        . htmlspecialchars($label) . ' &times;' . (int) $count . '</span>';
                }
            } else {
                $out .= '<small style="color:#16a34a;display:block;font-size:11px;">&#10003; No suspicious objects detected</small>';
            }
        }

        return $out;
    }

    // ── Screenshots
    public function col_screenshots($row)
    {
        global $DB;
        $count = $DB->count_records('quizaccess_tagproctoring_screenshots', ['attemptid' => $row->id]);
        if (!$count) {
            return html_writer::tag(
                'span',
                get_string('report_table_row_no_screenshots', 'quizaccess_tagproctoring'),
                ['class' => 'text-muted']
            );
        }
        $url = new moodle_url('/mod/quiz/accessrule/tagproctoring/screenshots.php', [
            'cmid' => $this->cmid,
            'attemptid' => $row->id,
        ]);
        return html_writer::link(
            $url,
            get_string('report_table_row_view_screenshots', 'quizaccess_tagproctoring', $count),
            ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-secondary']
        );
    }

    // ── Events — compact timeline
    public function col_events($row)
    {
        global $DB;
        $events = $DB->get_records('quizaccess_tagproctoring_events', ['attemptid' => $row->id], 'timecreated ASC');
        if (!$events) {
            return html_writer::tag('span', 'None', ['class' => 'text-muted small']);
        }

        $hightypes = ['screenshare', 'multiplescreen'];

        // Count by type for the summary line.
        $counts = [];
        foreach ($events as $e) {
            $counts[$e->eventtype] = ($counts[$e->eventtype] ?? 0) + 1;
        }

        // Summary chips (one per type).
        $summary = '<div class="tagp-event-summary">';
        foreach ($counts as $type => $n) {
            $ishigh = in_array($type, $hightypes);
            $bg = $ishigh ? '#dc2626' : '#d97706';
            $strkey = 'event_' . $type;
            $label = get_string_manager()->string_exists($strkey, 'quizaccess_tagproctoring')
                ? get_string($strkey, 'quizaccess_tagproctoring') : $type;
            $summary .= '<span class="tagp-event-chip" style="background:' . $bg . ';">'
                . htmlspecialchars($label) . ' <strong>&times;' . $n . '</strong></span>';
        }
        $summary .= '</div>';

        // Detailed timeline (scrollable, collapsed by default).
        $uid = 'tagp-ev-' . $row->id;
        $rows = '';
        foreach ($events as $event) {
            $strkey = 'event_' . $event->eventtype;
            $label = get_string_manager()->string_exists($strkey, 'quizaccess_tagproctoring')
                ? get_string($strkey, 'quizaccess_tagproctoring') : $event->eventtype;
            $time = userdate($event->timecreated, '%H:%M:%S');
            $ishigh = in_array($event->eventtype, $hightypes);
            $bg = $ishigh ? '#dc2626' : '#d97706';
            $rows .= '<div class="tagp-event-row" style="border-left:3px solid ' . $bg . ';">'
                . '<span class="tagp-event-time">' . $time . '</span>'
                . '<span class="tagp-event-label">' . htmlspecialchars($label) . '</span>'
                . (!empty($event->detail) ? '<span class="tagp-event-detail">' . htmlspecialchars($event->detail) . '</span>' : '')
                . '</div>';
        }

        $detail = '<div id="' . $uid . '" class="tagp-event-detail-wrap" style="display:none;">' . $rows . '</div>';
        $toggle = '<button class="tagp-event-toggle" onclick="var d=document.getElementById(\'' . $uid . '\');'
            . 'd.style.display=d.style.display===\'none\'?\'block\':\'none\';">'
            . count($events) . ' events ▾</button>';

        return $summary . $toggle . $detail;
    }

    public function col_attempt_results($row)
    {
        global $OUTPUT;
        $url = new moodle_url('/mod/quiz/review.php', ['attempt' => $row->id]);
        $icon = html_writer::tag('i', '', ['class' => 'fa fa-external-link']);
        return $OUTPUT->action_link($url, $icon, null, ['target' => '_blank']);
    }

    public function col_delete_scan($row)
    {
        if (empty($row->scanid)) {
            return html_writer::tag('span', '—', ['class' => 'text-muted']);
        }
        $url = new moodle_url('/mod/quiz/accessrule/tagproctoring/delete_scan.php', [
            'cid' => $this->cid,
            'cmid' => $this->cmid,
            'qid' => $this->qid,
            'userid' => $row->user_id,
        ]);
        return html_writer::link(
            $url,
            get_string('report_table_header_delete_scan', 'quizaccess_tagproctoring'),
            ['class' => 'btn btn-sm btn-outline-danger']
        );
    }

    public function show()
    {
        if (!$this->isrecordexists) {
            echo html_writer::tag(
                'p',
                get_string('report_empty', 'quizaccess_tagproctoring'),
                ['class' => 'text-center text-muted mt-4']
            );
            return;
        }
        echo html_writer::start_div('tagproctoring-report');
        $this->out($this->perpage, true);
        echo html_writer::end_div();
    }
}
