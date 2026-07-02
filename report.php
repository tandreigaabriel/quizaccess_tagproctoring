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
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
$PAGE->set_title($course->shortname . ': ' . get_string('pluginname', 'quizaccess_tagproctoring'));
$PAGE->set_heading($course->fullname . ': ' . get_string('pluginname', 'quizaccess_tagproctoring'));
$PAGE->navbar->add(get_string('report_link_label', 'quizaccess_tagproctoring'), $reporturl);
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
        html_writer::tag('p', get_string('report_no_attempts', 'quizaccess_tagproctoring'), ['class' => 'text-muted text-center mt-5']),
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
function quizaccess_tagproctoring_risk(array $events, $scan): array {
    $score = 0;

    $highrisk = [
    'screenshare' => 50,
    'screensharestop' => 50,
    'multiplescreen' => 50,
    'multiplefaces' => 40,
    'noface' => 20,
    ];

    $medrisk = [
    'fullscreenexit' => 20,
    'tabswitch' => 10,
    'windowblur' => 10,
    'visibilityhidden' => 10,
    'copypaste' => 10,
    'devtools' => 5,
    'capabilitywarning' => 20,
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

    // Face-match failure is a strong identity signal.
    if ($scan && isset($scan->face_match_verified)) {
        if ((int) $scan->face_match_verified === 0) {
            $score += 40; // clear mismatch
        }
        // -1 (unknown/no face) is a minor flag — invigilator should check manually.
        if ((int) $scan->face_match_verified === -1) {
            $score += 10;
        }
    }

    return [
    'score' => $score,
    'level' => $score >= 50 ? 'invalid' : ($score >= 20 ? 'review' : 'valid'),
    ];
}

/**
 * Generate pluginfile URL.
 */
function quizaccess_tagproctoring_pluginfile_url(context $ctx, string $area, int $itemid, string $filename): string {
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

    $risk = quizaccess_tagproctoring_risk($events, $scan);

    $shotlist = [];

    foreach ($shots as $s) {
        // Use stored source column; fall back to filename prefix for older rows.
        if (!empty($s->source)) {
            $src = $s->source;
        } else {
            $src = (strpos($s->filename, 'screen_') === 0) ? 'screen' : 'webcam';
        }

        $shotlist[] = [
        'url' => quizaccess_tagproctoring_pluginfile_url($context, 'screenshots', $s->userid, $s->filename),
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
        'video_url'          => quizaccess_tagproctoring_pluginfile_url($context, 'roomscan', $scan->userid, $scan->filename ?? ''),
        'selfie_url'         => quizaccess_tagproctoring_pluginfile_url($context, 'selfie', $scan->userid, $scan->selfie_filename ?? ''),
        'idphoto_url'        => quizaccess_tagproctoring_pluginfile_url($context, 'idphoto', $scan->userid, $scan->idphoto_filename ?? ''),
        'objects'            => $scan->scan_objects ? (json_decode($scan->scan_objects, true) ?: []) : [],
        'suspicious'         => !empty($scan->scan_suspicious),
        'face_match_verified' => isset($scan->face_match_verified) ? (int) $scan->face_match_verified : null,
        'face_match_distance' => isset($scan->face_match_distance) ? (float) $scan->face_match_distance : null,
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
    'sumgrades' => isset($attempt->sumgrades) ? (float) $attempt->sumgrades : null,
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

// Pass action URLs only via js_call_amd (small, safe).
// Student data is embedded as a JSON script tag to avoid the 1024-char limit.
$PAGE->requires->js_call_amd('quizaccess_tagproctoring/report', 'init', [
    null, // students loaded from inline JSON
    [
        'setOverrideUrl'  => $setoverrideurl,
        'deleteShotsUrl'  => $deleteshotsurl,
        'deleteEventsUrl' => $deleteeventsurl,
        'deleteScanBase'  => $deletescanbase,
        'sesskey'         => $reportsesskey,
    ],
]);

$quizname = format_string($quizobj->get_quiz_name());
$totalstudents = count($studentslist);
$totalattempts = count($attempts);
$studentsjson = json_encode($studentslist, JSON_HEX_TAG | JSON_HEX_AMP);
?>

<script id="tagrep-data" type="application/json"><?= $studentsjson ?></script>
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
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <input type="text" class="tagrep-search" placeholder="Search by name or email…"
            oninput="tagRepSearch(this.value)">
          <a href="<?= htmlspecialchars((new moodle_url(
              '/mod/quiz/accessrule/tagproctoring/export_csv.php',
              ['cmid' => $cmid, 'qid' => $qid, 'sesskey' => sesskey()]
          ))->out(false)) ?>"
            class="tagrep-btn tagrep-btn--outline"
            style="white-space:nowrap;font-size:12px;padding:6px 12px;">
            &#x1f4e5; Export CSV
          </a>
        </div>
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
          <h5 class="tagrep-title" style="font-size:16px;" id="tagrep-flags-title">Potential Violation of Rules</h5>
          <p class="tagrep-subtitle" id="tagrep-flags-sub"></p>
        </div>
      </div>
      <div class="tagrep-flags-layout">

        <div class="tagrep-flags-left">
          <div class="tagrep-section-hd">Rule Categories</div>
          <div id="tagrep-cat-list"></div>
          <div id="tagrep-scan-section" style="display:none">
            <div class="tagrep-section-hd" style="margin-top:0;border-top:1px solid #f0f0f0;">Room Scan Video</div>
            <video id="tagrep-scan-video" controls muted preload="none" class="tagrep-scan-video"></video>
          </div>
        </div>

        <div class="tagrep-flags-right">
          <div class="tagrep-carousel-hd">
            <span id="tagrep-carousel-title">Screen and Webcam Evidence</span>
            <span id="tagrep-carousel-count"></span>
          </div>
          <div class="tagrep-carousel-area" id="tagrep-carousel-area">
            <div class="tagrep-carousel-empty" id="tagrep-carousel-empty">
              <span style="font-size:48px;display:block;margin-bottom:12px;">&#x1f4f7;</span>
              No tab or window-switch screenshots captured for this attempt
            </div>
            <div class="tagrep-carousel" id="tagrep-carousel" style="display:none"></div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="tagrep-lightbox" id="tagrep-lightbox" style="display:none" onclick="tagRepCloseLightbox()">
    <div class="tagrep-lightbox-inner" onclick="event.stopPropagation()">
      <img id="tagrep-lightbox-img" src="" alt="Screenshot">
      <p id="tagrep-lightbox-caption"></p>
      <button class="tagrep-nav-btn" style="margin-top:4px;" onclick="tagRepCloseLightbox()">&#x2715; Close</button>
    </div>
  </div>

</div>

<?php echo $OUTPUT->footer(); ?>
