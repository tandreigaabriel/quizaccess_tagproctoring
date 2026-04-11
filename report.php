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
 * TAG Proctoring Report Page
 *
 * Displays proctoring data, events, screenshots and risk analysis
 * for quiz attempts.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');

if (class_exists('mod_quiz\quiz_settings')) {
    class_alias('\mod_quiz\quiz_settings', '\tagproctoring_quiz_settings_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    class_alias('\quiz', '\tagproctoring_quiz_settings_alias');
}
if (class_exists('mod_quiz\quiz_attempt')) {
    class_alias('mod_quiz\quiz_attempt', '\tagproctoring_quiz_attempt_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    class_alias('\quiz_attempt', '\tagproctoring_quiz_attempt_alias');
}

$cid = required_param('cid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$qid = required_param('qid', PARAM_INT);

$context = context_module::instance($cmid, MUST_EXIST);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'quiz');

$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);

require_login($course, true, $cm);
require_capability('quizaccess/tagproctoring:viewreport', $context);

$quizobj = tagproctoring_quiz_settings_alias::create($qid);
$quiz = $quizobj->get_quiz();
$reporturl = quizaccess_tagproctoring_get_report_url($cid, $cmid, $qid);

$PAGE->set_url($reporturl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($course->shortname . ': TAG Proctoring Report');
$PAGE->set_heading($course->fullname . ': TAG Proctoring Report');
$PAGE->navbar->add('Proctoring Report', $reporturl);
$PAGE->requires->css(new moodle_url('/mod/quiz/accessrule/tagproctoring/styles.css'));

global $DB;

// Load all finished attempts.
$attempts = $DB->get_records_sql(
    "SELECT a.id, a.userid, a.attempt, a.sumgrades, a.timestart, a.timefinish,
            u.firstname, u.lastname, u.email
       FROM {quiz_attempts} a
       JOIN {user} u ON u.id = a.userid
      WHERE a.quiz = :quizid AND a.state = :state AND a.preview = 0
      ORDER BY u.lastname ASC, u.firstname ASC, a.attempt ASC",
    ['quizid' => $quiz->id, 'state' => \tagproctoring_quiz_attempt_alias::FINISHED]
);

echo $OUTPUT->header();

if (!$attempts) {
    echo html_writer::div(
        html_writer::tag('p', 'No completed attempts found for this quiz.', ['class' => 'text-muted text-center mt-5']),
        'container-fluid'
    );
    echo $OUTPUT->footer();
    exit;
}

// Bulk-load events, screenshots, scans.
$attemptids = array_column((array) $attempts, 'id', 'id');

$eventsbyattempt = [];
$shotsbyattempt = [];

if ($attemptids) {
    [$insql, $inparams] = $DB->get_in_or_equal(array_values($attemptids));

    $eventrecords = $DB->get_records_select(
        'quizaccess_tagproctoring_events',
        "attemptid $insql",
        $inparams,
        'timecreated ASC'
    );

    foreach ($eventrecords as $e) {
        $eventsbyattempt[$e->attemptid][] = $e;
    }

    $shotrecords = $DB->get_records_select(
        'quizaccess_tagproctoring_screenshots',
        "attemptid $insql",
        $inparams,
        'timecreated ASC'
    );

    foreach ($shotrecords as $s) {
        $shotsbyattempt[$s->attemptid][] = $s;
    }
}

$scansbyuser = [];
foreach ($DB->get_records('quizaccess_tagproctoring_scan', ['quizid' => $quiz->id]) as $scan) {
    $scansbyuser[$scan->userid] = $scan;
}

$overridesbyattempt = [];
if ($attemptids) {
    [$insql2, $inparams2] = $DB->get_in_or_equal(array_values($attemptids));

    foreach ($DB->get_records_select('quizaccess_tagproctoring_overrides', "attemptid $insql2", $inparams2) as $ov) {
        $overridesbyattempt[(int) $ov->attemptid] = (bool) $ov->valid_override;
    }
}

$setoverrideurl = (new moodle_url('/mod/quiz/accessrule/tagproctoring/set_override.php'))->out(false);
$deleteshotsurl = (new moodle_url('/mod/quiz/accessrule/tagproctoring/delete_screenshots.php'))->out(false);
$deleteeventsurl = (new moodle_url('/mod/quiz/accessrule/tagproctoring/delete_events.php'))->out(false);
$deletescanbase = (new moodle_url(
    '/mod/quiz/accessrule/tagproctoring/delete_scan.php',
    ['cid' => $cid, 'cmid' => $cmid, 'qid' => $qid]
))->out(false);

$reportsesskey = sesskey();

/**
 * Calculate risk score.
 */
function tagreprisk(array $events, $scan): array {
    $score = 0;

    $highrisk = ['screenshare' => 50, 'multiplescreen' => 50, 'multiplefaces' => 40, 'noface' => 20];

    $medrisk = [
    'fullscreenexit' => 20,
    'tabswitch' => 10,
    'visibilityhidden' => 10,
    'copypaste' => 10,
    'devtools' => 5,
    'faceaway' => 5,
    ];

    $counts = [];

    foreach ($events as $e) {
        $counts[$e->eventtype] = ($counts[$e->eventtype] ?? 0) + 1;
    }

    foreach ($highrisk as $t => $pts) {
        if (!empty($counts[$t])) {
            $score += $pts;
        }
    }

    foreach ($medrisk as $t => $pts) {
        if (!empty($counts[$t])) {
            $score += min($pts * $counts[$t], $pts * 3);
        }
    }

    if ($scan && !empty($scan->scan_suspicious)) {
        $score += 30;
    }

    return [
    'score' => $score,
    'level' => $score >= 50 ? 'invalid' : ($score >= 20 ? 'review' : 'valid'),
    ];
}

/**
 * Generate pluginfile URL.
 */
function tagreppluginfileurl(context $ctx, string $area, int $itemid, string $filename): string {
    if (!$filename) {
        return '';
    }

    return moodle_url::make_pluginfile_url(
        $ctx->id,
        'quizaccess_tagproctoring',
        $area,
        $itemid,
        '/',
        $filename
    )->out();
}

$studentsdata = [];

foreach ($attempts as $attempt) {
    $uid = (int) $attempt->userid;

    $events = $eventsbyattempt[$attempt->id] ?? [];
    $shots = $shotsbyattempt[$attempt->id] ?? [];
    $scan = $scansbyuser[$uid] ?? null;

    $risk = tagreprisk($events, $scan);

    $shotlist = [];

    foreach ($shots as $s) {
        $src = (strpos($s->filename, 'screen_') === 0) ? 'screen' : 'webcam';

        $shotlist[] = [
        'url' => tagreppluginfileurl($context, 'screenshots', $s->userid, $s->filename),
        'time' => (int) $s->timecreated,
        'source' => $src,
        ];
    }

    $eventlist = [];

    foreach ($events as $e) {
        $eventlist[] = [
        'type' => $e->eventtype,
        'time' => (int) $e->timecreated,
        'detail' => $e->detail ?? '',
        ];
    }

    $scanjs = null;

    if ($scan) {
        $scanjs = [
        'video_url' => tagreppluginfileurl($context, 'roomscan', $scan->userid, $scan->filename ?? ''),
        'selfie_url' => tagreppluginfileurl($context, 'selfie', $scan->userid, $scan->selfie_filename ?? ''),
        'idphoto_url' => tagreppluginfileurl($context, 'idphoto', $scan->userid, $scan->idphoto_filename ?? ''),
        'objects' => $scan->scan_objects ? (json_decode($scan->scan_objects, true) ?: []) : [],
        'suspicious' => !empty($scan->scan_suspicious),
        ];
    }

    if (!isset($studentsdata[$uid])) {
        $studentsdata[$uid] = [
        'userid' => $uid,
        'name' => trim($attempt->firstname . ' ' . $attempt->lastname),
        'email' => $attempt->email,
        'attempts' => [],
        ];
    }

    $studentsdata[$uid]['attempts'][] = [
    'id' => (int) $attempt->id,
    'attempt_num' => (int) $attempt->attempt,
    'timestart' => (int) $attempt->timestart,
    'timefinish' => (int) $attempt->timefinish,
    'sumgrades' => (float) ($attempt->sumgrades ?? 0),
    'quiz_sumgrades' => (float) $quiz->sumgrades,
    'risk_score' => $risk['score'],
    'risk_level' => $risk['level'],
    'override_valid' => $overridesbyattempt[(int) $attempt->id] ?? false,
    'review_url' => (new moodle_url('/mod/quiz/review.php', ['attempt' => $attempt->id]))->out(),
    'scan' => $scanjs,
    'events' => $eventlist,
    'screenshots' => $shotlist,
    ];
}

$studentslist = array_values($studentsdata);

$jsondata = json_encode(
    $studentslist,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES
);

$quizname = format_string($quizobj->get_quiz_name());
$totalstudents = count($studentslist);
$totalattempts = count($attempts);
?>

<div class="tagrep-wrap" id="tagrep-root">


  <nav class="tagrep-nav" aria-label="breadcrumb">
    <span class="tagrep-nav-item tagrep-nav-item--active" id="tagrep-bc-students" onclick="tagRepNav('students')">All
      Students</span>
    <span class="tagrep-nav-sep tagrep-bc-hide" id="tagrep-bc-sep1">›</span>
    <span class="tagrep-nav-item tagrep-bc-hide" id="tagrep-bc-sessions" onclick="tagRepNav('sessions')"></span>
    <span class="tagrep-nav-sep tagrep-bc-hide" id="tagrep-bc-sep2">›</span>
    <span class="tagrep-nav-item tagrep-bc-hide" id="tagrep-bc-flags"></span>
  </nav>

  <div class="tagrep-panel" id="tagrep-panel-students">
    <div class="tagrep-card">
      <div class="tagrep-card-hd">
        <div>
          <h4 class="tagrep-title">🎥 <?= htmlspecialchars($quizname) ?></h4>
          <p class="tagrep-subtitle"><?= $totalstudents ?> students &nbsp;·&nbsp; <?= $totalattempts ?>
            completed
            attempts</p>
        </div>
        <input type="text" class="tagrep-search" placeholder="Search by name or email…"
          oninput="tagRepSearch(this.value)">
      </div>
      <div class="tagrep-table-wrap">
        <table class="tagrep-table" id="tagrep-student-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Email</th>
              <th>Attempts</th>
              <th>Last Attempt</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tagrep-tbody"></tbody>
        </table>
        <p class="tagrep-empty" id="tagrep-empty" style="display:none">No students match your search.</p>
      </div>
    </div>
  </div>

  <div class="tagrep-panel" id="tagrep-panel-sessions" style="display:none">
    <div id="tagrep-sessions-content"></div>
  </div>


  <div class="tagrep-panel" id="tagrep-panel-flags" style="display:none">
    <div class="tagrep-card">
      <div class="tagrep-card-hd">
        <div>
          <h5 class="tagrep-title" style="font-size:16px;" id="tagrep-flags-title">Violation Flags</h5>
          <p class="tagrep-subtitle" id="tagrep-flags-sub"></p>
        </div>
      </div>
      <div class="tagrep-flags-layout">

        <div class="tagrep-flags-left">
          <div class="tagrep-section-hd">Violation Categories</div>
          <div id="tagrep-cat-list"></div>
          <div id="tagrep-scan-section" style="display:none">
            <div class="tagrep-section-hd" style="margin-top:0;border-top:1px solid #f0f0f0;">Room Scan
              Video</div>
            <video id="tagrep-scan-video" controls muted preload="none" class="tagrep-scan-video"></video>
          </div>
        </div>

        <div class="tagrep-flags-right">
          <div class="tagrep-viewer-hd" id="tagrep-viewer-hd">
            <span id="tagrep-viewer-title">Select a category to view evidence</span>
          </div>
          <div class="tagrep-shot-area" id="tagrep-shot-area">
            <div class="tagrep-shot-empty" id="tagrep-shot-empty">
              <span style="font-size:48px;display:block;margin-bottom:12px;">🖼</span>
              Select a violation category on the left to view evidence screenshots
            </div>
            <div id="tagrep-shot-content" style="display:none">
              <img id="tagrep-shot-img" class="tagrep-shot-img" src="" alt="Screenshot">
              <p class="tagrep-shot-time" id="tagrep-shot-time"></p>
            </div>
          </div>
          <div class="tagrep-shot-nav" id="tagrep-shot-nav" style="display:none">
            <button class="tagrep-nav-btn" onclick="tagRepShotStep(-1)">&#8592; Prev</button>
            <span class="tagrep-shot-counter" id="tagrep-shot-counter">1 / 1</span>
            <button class="tagrep-nav-btn" onclick="tagRepShotStep(1)">Next &#8594;</button>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  (function () {
    'use strict';

    var DATA = <?= $jsondata ?>;

    var CFG2 = {
      setOverrideUrl: '<?= addslashes($setoverrideurl) ?>',
      deleteShotsUrl: '<?= addslashes($deleteshotsurl) ?>',
      deleteEventsUrl: '<?= addslashes($deleteeventsurl) ?>',
      deleteScanBase: '<?= addslashes($deletescanbase) ?>',
      sesskey: '<?= $reportsesskey ?>',
    };

    var VIOLATIONS = {
      tabswitch: {
        label: 'Tab Switch / Focus Loss',
        icon: '🔄',
        sev: 'high'
      },
      visibilityhidden: {
        label: 'Tab Hidden',
        icon: '👁',
        sev: 'medium'
      },
      fullscreenexit: {
        label: 'Fullscreen Exited',
        icon: '⛶',
        sev: 'medium'
      },
      screenshare: {
        label: 'Screen Sharing Initiated',
        icon: '📺',
        sev: 'critical'
      },
      multiplescreen: {
        label: 'Multiple Screens Detected',
        icon: '🖥',
        sev: 'critical'
      },
      copypaste: {
        label: 'Copy / Paste Activity',
        icon: '📋',
        sev: 'medium'
      },
      contextmenu: {
        label: 'Right-Click Menu',
        icon: '🖱',
        sev: 'low'
      },
      devtools: {
        label: 'Developer Tools Open',
        icon: '🔧',
        sev: 'high'
      },
      nocamera: {
        label: 'Camera Unavailable',
        icon: '📷',
        sev: 'high'
      },
      noface: {
        label: 'No Face Detected',
        icon: '🚫',
        sev: 'high'
      },
      faceaway: {
        label: 'Looking Away from Screen',
        icon: '👤',
        sev: 'medium'
      },
      multiplefaces: {
        label: 'Multiple Faces During Exam',
        icon: '👥',
        sev: 'critical'
      },
      screenshare_ended: {
        label: 'Screen Share Ended',
        icon: '🖥',
        sev: 'medium'
      },
    };

    var SCAN_VIOLATIONS = {
      'cell phone': {
        label: 'Phone Detected',
        icon: '📱',
        sev: 'critical'
      },
      'book': {
        label: 'Book / Notes Detected',
        icon: '📚',
        sev: 'critical'
      },
      'tablet': {
        label: 'Tablet Detected',
        icon: '📱',
        sev: 'critical'
      },
      'remote': {
        label: 'Remote Control Detected',
        icon: '🎮',
        sev: 'high'
      },
      'extra_person': {
        label: 'Extra Person in Room',
        icon: '👥',
        sev: 'critical'
      },
      'face_turned': {
        label: 'Looking Away from Screen',
        icon: '👤',
        sev: 'high'
      },
      'no_face': {
        label: 'No Face Detected',
        icon: '🚫',
        sev: 'high'
      },
      'multiple_faces': {
        label: 'Multiple Faces Detected',
        icon: '👥',
        sev: 'critical'
      },
    };

    var SEV_COLOR = {
      critical: '#dc2626',
      high: '#d97706',
      medium: '#2563eb',
      low: '#6b7280'
    };
    var RISK_COLOR = {
      invalid: '#dc2626',
      review: '#d97706',
      valid: '#16a34a'
    };
    var RISK_LABEL = {
      invalid: 'Invalid',
      review: 'Needs Review',
      valid: 'Valid'
    };
    var SEV_ORDER = {
      critical: 0,
      high: 1,
      medium: 2,
      low: 3
    };

    // State.
    var currentStudent = null;
    var currentAttempt = null;
    var currentShots = [];
    var currentShotIdx = 0;
    var currentCats = [];
    var currentActiveCat = null;

    function el(id) {
      return document.getElementById(id);
    }

    function esc(s) {
      return String(s || '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtDate(ts) {
      if (!ts) return '—';
      return new Date(ts * 1000).toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      });
    }

    function fmtTime(ts) {
      if (!ts) return '—';
      return new Date(ts * 1000).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function fmtDateTime(ts) {
      if (!ts) return '—';
      var d = new Date(ts * 1000);
      return d.toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short'
      }) +
        ' ' + d.toLocaleTimeString([], {
          hour: '2-digit',
          minute: '2-digit'
        });
    }

    function fmtDuration(start, end) {
      if (!start || !end) return '—';
      var m = Math.round((end - start) / 60);
      return m >= 60 ? Math.floor(m / 60) + 'h ' + (m % 60) + 'm' : m + ' min';
    }

    function effectiveRisk(a) {
      return a.override_valid ? 'valid' : a.risk_level;
    }

    function overallRisk(s) {
      var worst = 'valid';
      s.attempts.forEach(function (a) {
        var rl = effectiveRisk(a);
        if (rl === 'invalid') worst = 'invalid';
        else if (rl === 'review' && worst !== 'invalid') worst = 'review';
      });
      return worst;
    }

    function riskBadge(level, extra) {
      var label = extra ? (RISK_LABEL[level] + ' ' + extra) : RISK_LABEL[level];
      return '<span class="tagrep-badge" style="background:' + RISK_COLOR[level] + ';">' +
        label + '</span>';
    }

    //  Navigation.
    window.tagRepNav = function (panel) {
      el('tagrep-panel-students').style.display = panel === 'students' ? '' : 'none';
      el('tagrep-panel-sessions').style.display = panel === 'sessions' ? '' : 'none';
      el('tagrep-panel-flags').style.display = panel === 'flags' ? '' : 'none';

      // Breadcrumb.
      el('tagrep-bc-students').className = 'tagrep-nav-item' +
        (panel === 'students' ? ' tagrep-nav-item--active' : '');

      var showS = panel !== 'students' && currentStudent;
      el('tagrep-bc-sep1').style.display = showS ? '' : 'none';
      el('tagrep-bc-sessions').style.display = showS ? '' : 'none';
      el('tagrep-bc-sessions').className = 'tagrep-nav-item' +
        (panel === 'sessions' ? ' tagrep-nav-item--active' : '');
      if (currentStudent) el('tagrep-bc-sessions').textContent = currentStudent.name;

      var showF = panel === 'flags';
      el('tagrep-bc-sep2').style.display = showF ? '' : 'none';
      el('tagrep-bc-flags').style.display = showF ? '' : 'none';
      if (showF) el('tagrep-bc-flags').textContent = 'Flags — Attempt #' + (currentAttempt ? currentAttempt
        .attempt_num : '');
    };

    // Panel 1: Student list.
    function renderStudentList(data) {
      var tbody = el('tagrep-tbody');
      var empty = el('tagrep-empty');
      if (!data.length) {
        tbody.innerHTML = '';
        empty.style.display = '';
        return;
      }
      empty.style.display = 'none';
      var rows = '';
      data.forEach(function (s) {
        var last = s.attempts[s.attempts.length - 1];
        rows += '<tr>' +
          '<td><strong>' + esc(s.name) + '</strong></td>' +
          '<td class="tagrep-muted">' + esc(s.email) + '</td>' +
          '<td class="tagrep-center">' + s.attempts.length + '</td>' +
          '<td class="tagrep-muted">' + fmtDateTime(last ? last.timestart : 0) + '</td>' +
          '<td>' + riskBadge(overallRisk(s)) + '</td>' +
          '<td class="tagrep-td-action">' +
          '<button class="tagrep-btn tagrep-btn--primary" onclick="tagRepOpenStudent(' + s.userid +
          ')">View Sessions →</button>' +
          '</td>' +
          '</tr>';
      });
      tbody.innerHTML = rows;
    }

    window.tagRepSearch = function (q) {
      q = q.toLowerCase();
      renderStudentList(DATA.filter(function (s) {
        return s.name.toLowerCase().indexOf(q) !== -1 ||
          s.email.toLowerCase().indexOf(q) !== -1;
      }));
    };

    // Panel 2: Student sessions.
    window.tagRepOpenStudent = function (uid) {
      currentStudent = DATA.find(function (s) {
        return s.userid === uid;
      });
      if (!currentStudent) return;
      renderSessions(currentStudent);
      tagRepNav('sessions');
    };

    function renderSessions(student) {
      var html = '<div class="tagrep-card">' +
        '<div class="tagrep-card-hd">' +
        '<div><h4 class="tagrep-title">' + esc(student.name) + '</h4>' +
        '<p class="tagrep-subtitle">' + esc(student.email) +
        ' &nbsp;·&nbsp; ' + student.attempts.length + ' attempt(s)</p></div>' +
        '</div>' +
        '<div class="tagrep-sessions-grid">';

      student.attempts.forEach(function (a) {
        var pct = a.quiz_sumgrades ? Math.round(a.sumgrades * 100 / a.quiz_sumgrades) : null;
        var scan = a.scan;

        html += '<div class="tagrep-session-card">';

        // Card header.
        var dispRisk = effectiveRisk(a);
        html += '<div class="tagrep-session-hd">' +
          '<span class="tagrep-session-num">Attempt #' + a.attempt_num + '</span>' +
          riskBadge(dispRisk, a.override_valid ? '✓ Overridden' : '') +
          '</div>';

        // Card body: photo + info.
        html += '<div class="tagrep-session-body">';

        // Selfie thumbnail.
        html += '<div class="tagrep-session-photo">';
        if (scan && scan.selfie_url) {
          html += '<img src="' + esc(scan.selfie_url) + '" class="tagrep-selfie-img" alt="Selfie" ' +
            'onclick="window.open(\'' + esc(scan.selfie_url) +
            '\',\'_blank\')" style="cursor:zoom-in;">';
        } else {
          html += '<div class="tagrep-selfie-ph">No<br>Photo</div>';
        }
        html += '</div>';

        // Info rows.
        html += '<div class="tagrep-session-info">';
        html += infoRow('Start', fmtDateTime(a.timestart));
        html += infoRow('End', fmtDateTime(a.timefinish));
        html += infoRow('Duration', fmtDuration(a.timestart, a.timefinish));
        html += infoRow('Score', pct !== null ? pct + '%' : '—');
        html += infoRow('Events', a.events.length + ' logged');
        html += infoRow('Screenshots', a.screenshots.length + ' captured');

        html += '</div>';
        html += '</div>';

        // Scan objects chips.
        if (scan && scan.objects && Object.keys(scan.objects).length) {
          html += '<div class="tagrep-scan-chips">';
          Object.keys(scan.objects).forEach(function (cls) {
            var def = SCAN_VIOLATIONS[cls] || {
              label: cls,
              sev: 'medium'
            };
            html += '<span class="tagrep-scan-chip" style="background:' + SEV_COLOR[def
              .sev] + ';">' +
              esc(def.label) + ' &times;' + scan.objects[cls] + '</span>';
          });
          html += '</div>';
        }

        // Actions.
        html += '<div class="tagrep-session-actions">';
        if (a.events.length > 0 || (scan && scan.suspicious)) {
          html += '<button class="tagrep-btn tagrep-btn--danger" ' +
            'onclick="tagRepOpenFlags(' + student.userid + ',' + a.id + ')">' +
            '⚑ View Flags</button> ';
        }
        // Override button.
        if (a.override_valid) {
          html += '<button class="tagrep-btn" style="background:#16a34a;color:#fff;" ' +
            'onclick="tagRepSetOverride(' + student.userid + ',' + a.id + ',0)">' +
            '✓ Valid (Clear Override)</button> ';
        } else {
          html += '<button class="tagrep-btn tagrep-btn--outline" ' +
            'onclick="tagRepSetOverride(' + student.userid + ',' + a.id + ',1)">' +
            '✓ Mark as Valid</button> ';
        }
        html += '<a href="' + esc(a.review_url) +
          '" target="_blank" class="tagrep-btn tagrep-btn--outline">Quiz Results ↗</a>';
        // Delete buttons (small, muted).
        html += '<div style="width:100%;margin-top:6px;display:flex;gap:6px;">';
        if (a.events.length > 0) {
          html +=
            '<button class="tagrep-btn" style="background:#f3f4f6;color:#6b7280;font-size:11px;padding:4px 10px;" ' +
            'onclick="tagRepDeleteEvents(' + student.userid + ',' + a.id + ')">' +
            '🗑 Delete Events (' + a.events.length + ')</button>';
        }
        if (a.screenshots.length > 0) {
          html +=
            '<button class="tagrep-btn" style="background:#f3f4f6;color:#6b7280;font-size:11px;padding:4px 10px;" ' +
            'onclick="tagRepDeleteScreenshots(' + student.userid + ',' + a.id + ')">' +
            '🗑 Delete Screenshots (' + a.screenshots.length + ')</button>';
        }
        if (scan && (scan.video_url || scan.selfie_url)) {
          var durl = CFG2.deleteScanBase + '&userid=' + student.userid;
          html += '<a href="' + esc(durl) + '" class="tagrep-btn" ' +
            'style="background:#f3f4f6;color:#6b7280;font-size:11px;padding:4px 10px;">' +
            '🗑 Delete Scan Data</a>';
        }
        html += '</div>';
        html += '</div>';

        html += '</div>';
      });

      html += '</div></div>';
      el('tagrep-sessions-content').innerHTML = html;
    }

    function infoRow(label, value) {
      return '<div class="tagrep-info-row">' +
        '<span class="tagrep-info-lbl">' + esc(label) + '</span>' +
        '<span class="tagrep-info-val">' + esc(value) + '</span>' +
        '</div>';
    }

    //Panel 3: Flags.
    window.tagRepOpenFlags = function (uid, attemptId) {
      currentStudent = DATA.find(function (s) {
        return s.userid === uid;
      });
      currentAttempt = currentStudent ?
        currentStudent.attempts.find(function (a) {
          return a.id === attemptId;
        }) :
        null;
      if (!currentAttempt) return;

      el('tagrep-flags-title').textContent = currentStudent.name + ' — Attempt #' + currentAttempt
        .attempt_num;
      el('tagrep-flags-sub').textContent = fmtDateTime(currentAttempt.timestart) +
        '  →  ' + fmtDateTime(currentAttempt.timefinish);

      // Build categories.
      currentCats = [];

      // From events (grouped by type).
      var eCounts = {};
      currentAttempt.events.forEach(function (e) {
        eCounts[e.type] = (eCounts[e.type] || 0) + 1;
      });
      Object.keys(eCounts).forEach(function (type) {
        var def = VIOLATIONS[type] || {
          label: type,
          icon: '⚠',
          sev: 'medium'
        };
        currentCats.push({
          id: 'ev_' + type,
          label: def.label,
          icon: def.icon,
          sev: def.sev,
          count: eCounts[type],
          source: 'event',
          event_type: type
        });
      });

      // From scan objects.
      var scan = currentAttempt.scan;
      if (scan && scan.objects) {
        Object.keys(scan.objects).forEach(function (cls) {
          var def = SCAN_VIOLATIONS[cls] || {
            label: cls,
            icon: '⚠',
            sev: 'medium'
          };
          currentCats.push({
            id: 'sc_' + cls.replace(/\s/g, '_'),
            label: def.label,
            icon: def.icon,
            sev: def.sev,
            count: scan.objects[cls],
            source: 'scan',
            scan_class: cls
          });
        });
      }

      // Sort by severity.
      currentCats.sort(function (a, b) {
        return (SEV_ORDER[a.sev] || 9) - (SEV_ORDER[b.sev] || 9);
      });

      // Render category list.
      var listHtml = '';
      if (!currentCats.length) {
        listHtml = '<p class="tagrep-no-flags">No violations detected.</p>';
      } else {
        currentCats.forEach(function (cat, i) {
          listHtml += '<div class="tagrep-cat-item" id="tagrep-cat-' + i +
            '" onclick="tagRepSelectCat(' + i + ')">' +
            '<span class="tagrep-cat-icon">' + cat.icon + '</span>' +
            '<div class="tagrep-cat-text">' +
            '<span class="tagrep-cat-label">' + esc(cat.label) + '</span>' +
            '<span class="tagrep-cat-src">' + (cat.source === 'scan' ? 'Room Scan' :
              'During Exam') + '</span>' +
            '</div>' +
            '<span class="tagrep-cat-badge" style="background:' + SEV_COLOR[cat.sev] + ';">' +
            cat.count + '</span>' +
            '</div>';
        });
      }
      el('tagrep-cat-list').innerHTML = listHtml;

      // Room scan video.
      var scanSec = el('tagrep-scan-section');
      var scanVid = el('tagrep-scan-video');
      if (scan && scan.video_url) {
        scanVid.src = scan.video_url;
        scanSec.style.display = '';
      } else {
        scanSec.style.display = 'none';
        scanVid.src = '';
      }

      // Reset right panel.
      resetViewer();
      tagRepNav('flags');
    };

    function resetViewer() {
      currentShots = [];
      currentShotIdx = 0;
      currentActiveCat = null;
      el('tagrep-shot-empty').style.display = '';
      el('tagrep-shot-content').style.display = 'none';
      el('tagrep-shot-nav').style.display = 'none';
      el('tagrep-shot-empty').innerHTML =
        '<span style="font-size:48px;display:block;margin-bottom:12px;">🖼</span>' +
        'Select a violation category on the left to view evidence';
      el('tagrep-viewer-title').textContent = 'Select a category to view evidence';
    }

    window.tagRepSelectCat = function (idx) {
      // Highlight.
      currentCats.forEach(function (_, i) {
        var el2 = el('tagrep-cat-' + i);
        if (el2) el2.className = 'tagrep-cat-item' + (i === idx ? ' tagrep-cat-item--active' : '');
      });

      var cat = currentCats[idx];
      if (!cat || !currentAttempt) return;
      currentActiveCat = cat;

      el('tagrep-viewer-title').textContent = cat.icon + '  ' + cat.label;

      if (cat.source === 'event') {
        // Screenshots within 90s either side of any event of this type.
        var times = currentAttempt.events
          .filter(function (e) {
            return e.type === cat.event_type;
          })
          .map(function (e) {
            return e.time;
          });

        // Face events → prefer webcam; screen-monitoring events → prefer screen captures.
        var FACE_EVENTS = {
          noface: 1,
          faceaway: 1,
          multiplefaces: 1,
          nocamera: 1
        };
        var preferSource = FACE_EVENTS[cat.event_type] ? 'webcam' : 'screen';

        var allMatched = currentAttempt.screenshots.filter(function (s) {
          return times.some(function (t) {
            return Math.abs(s.time - t) <= 90;
          });
        });
        // Try to show only the preferred source type; fall back to all matched if none.
        currentShots = allMatched.filter(function (s) {
          return s.source === preferSource;
        });
        if (!currentShots.length) currentShots = allMatched;

        // Sort by closest to nearest event
        currentShots.sort(function (a, b) {
          var dA = Math.min.apply(null, times.map(function (t) {
            return Math.abs(a.time - t);
          }));
          var dB = Math.min.apply(null, times.map(function (t) {
            return Math.abs(b.time - t);
          }));
          return dA - dB;
        });
        // Fall back to all screenshots if none match.
        if (!currentShots.length) currentShots = currentAttempt.screenshots.slice();
      } else {
        // Scan violation — evidence is in the room scan video, not attempt screenshots.
        currentShots = [];
      }

      currentShotIdx = 0;
      renderShot();
    };

    function renderShot() {
      if (!currentShots.length) {
        el('tagrep-shot-empty').style.display = '';
        el('tagrep-shot-content').style.display = 'none';
        el('tagrep-shot-nav').style.display = 'none';
        if (currentActiveCat && currentActiveCat.source === 'scan') {
          var hasVid = currentAttempt && currentAttempt.scan && currentAttempt.scan.video_url;
          el('tagrep-shot-empty').innerHTML =
            '<span style="font-size:36px;display:block;margin-bottom:10px;">🎥</span>' +
            '<strong>Evidence in Room Scan Recording</strong><br>' +
            '<span style="color:#6b7280;font-size:12px;">' +
            'This violation was detected by AI during the room scan.<br>' +
            (hasVid ? 'Watch the room scan video on the left panel.' : 'No room scan video available.') +
            '</span>';
        } else {
          el('tagrep-shot-empty').innerHTML =
            '<span style="font-size:48px;display:block;margin-bottom:12px;">📷</span>' +
            'No screenshots captured within 90 seconds of this event.';
        }
        return;
      }

      var shot = currentShots[currentShotIdx];
      var d = new Date(shot.time * 1000);
      var tStr = (shot.source === 'screen' ? 'Screen capture' : 'Webcam') + ' at ' + d.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });

      // Find nearest event for timing context.
      var contextLine = '';
      if (currentActiveCat && currentActiveCat.source === 'event' && currentAttempt) {
        var evTimes = currentAttempt.events
          .filter(function (e) {
            return e.type === currentActiveCat.event_type;
          })
          .map(function (e) {
            return e.time;
          });
        if (evTimes.length) {
          var nearest = evTimes.reduce(function (best, t) {
            return Math.abs(t - shot.time) < Math.abs(best - shot.time) ? t : best;
          }, evTimes[0]);
          var diffSec = Math.round(shot.time - nearest);
          var diffStr = diffSec === 0 ? 'at event time' :
            diffSec > 0 ? diffSec + 's after event' :
              Math.abs(diffSec) + 's before event';
          var evD = new Date(nearest * 1000);
          contextLine = ' — Event at ' + evD.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
          }) +
            ' (' + diffStr + ')';
        }
      }

      el('tagrep-shot-img').src = shot.url;
      el('tagrep-shot-time').textContent = tStr + contextLine;
      el('tagrep-shot-empty').style.display = 'none';
      el('tagrep-shot-content').style.display = '';
      el('tagrep-shot-counter').textContent = (currentShotIdx + 1) + ' / ' + currentShots.length;
      el('tagrep-shot-nav').style.display = currentShots.length > 1 ? '' : 'none';
    }

    window.tagRepShotStep = function (dir) {
      if (!currentShots.length) return;
      currentShotIdx = (currentShotIdx + dir + currentShots.length) % currentShots.length;
      renderShot();
    };

    // Override as Valid.
    window.tagRepSetOverride = function (uid, attemptId, override) {
      var msg = override ?
        'Mark this attempt as Valid? This overrides the automated risk assessment.' :
        'Clear the Valid override? Automated risk assessment will apply again.';
      if (!confirm(msg)) return;

      var fd = new FormData();
      fd.append('attemptid', attemptId);
      fd.append('override', override ? 1 : 0);
      fd.append('sesskey', CFG2.sesskey);

      fetch(CFG2.setOverrideUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.status === 'ok') {
            // Update local DATA state.
            DATA.forEach(function (s) {
              if (s.userid === uid) {
                s.attempts.forEach(function (a) {
                  if (a.id === attemptId) a.override_valid = !!override;
                });
              }
            });
            // Re-render.
            renderStudentList(DATA);
            if (currentStudent && currentStudent.userid === uid) {
              renderSessions(currentStudent);
            }
          } else {
            alert('Error: ' + (data.message || 'unknown'));
          }
        })
        .catch(function () {
          alert('Network error saving override.');
        });
    };

    // Delete Screenshots.
    window.tagRepDeleteScreenshots = function (uid, attemptId) {
      if (!confirm('Permanently delete all webcam screenshots for this attempt?')) return;

      var fd = new FormData();
      fd.append('attemptid', attemptId);
      fd.append('sesskey', CFG2.sesskey);

      fetch(CFG2.deleteShotsUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.status === 'ok') {
            DATA.forEach(function (s) {
              if (s.userid === uid) {
                s.attempts.forEach(function (a) {
                  if (a.id === attemptId) a.screenshots = [];
                });
              }
            });
            if (currentStudent && currentStudent.userid === uid) {
              renderSessions(currentStudent);
            }
          } else {
            alert('Error: ' + (data.message || 'unknown'));
          }
        })
        .catch(function () {
          alert('Network error deleting screenshots.');
        });
    };

    // Delete Events.
    window.tagRepDeleteEvents = function (uid, attemptId) {
      if (!confirm(
        'Permanently delete all logged events for this attempt? This will also clear the risk flags.'))
        return;

      var fd = new FormData();
      fd.append('attemptid', attemptId);
      fd.append('sesskey', CFG2.sesskey);

      fetch(CFG2.deleteEventsUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.status === 'ok') {
            DATA.forEach(function (s) {
              if (s.userid === uid) {
                s.attempts.forEach(function (a) {
                  if (a.id === attemptId) {
                    a.events = [];
                    a.risk_score = 0;
                    a.risk_level = 'valid';
                  }
                });
              }
            });
            renderStudentList(DATA);
            if (currentStudent && currentStudent.userid === uid) {
              renderSessions(currentStudent);
            }
          } else {
            alert('Error: ' + (data.message || 'unknown'));
          }
        })
        .catch(function () {
          alert('Network error deleting events.');
        });
    };

    //Boot.
    renderStudentList(DATA);

  })();
</script>

echo $OUTPUT->footer();
