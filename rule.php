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
 * Quiz access rule class for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');

if (class_exists('\mod_quiz\local\access_rule_base')) {
    class_alias('\mod_quiz\local\access_rule_base', '\quizaccess_tagproctoring_parent_class_alias');
    class_alias('\mod_quiz\form\preflight_check_form', '\quizaccess_tagproctoring_preflight_form_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
    class_alias('\quiz_access_rule_base', '\quizaccess_tagproctoring_parent_class_alias');
    class_alias('\mod_quiz_preflight_check_form', '\quizaccess_tagproctoring_preflight_form_alias');
}

/**
 * TAG Proctoring access rule.
 *
 * Handles quiz access restrictions and preflight checks.
 */
class quizaccess_tagproctoring extends quizaccess_tagproctoring_parent_class_alias
{
    /**
     * Add settings fields to quiz form.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        global $DB;

        // Load existing saved settings so the form shows current values when editing.
        $existing = null;
        $quizid = $quizform->get_instance();
        if ($quizid) {
            $existing = $DB->get_record('quizaccess_tagproctoring_settings', ['quizid' => $quizid]);
        }

        $mform->addElement('advcheckbox', 'tagproctoring_enabled', get_string('enableproctor', 'quizaccess_tagproctoring'));
        $mform->setDefault('tagproctoring_enabled', $existing ? (int) $existing->enabled : 0);

        $mform->addElement('advcheckbox', 'tagproctoring_enableroomscan', get_string('enableroomscan', 'quizaccess_tagproctoring'));
        $mform->setDefault('tagproctoring_enableroomscan', $existing ? (int) $existing->enableroomscan : 1);
        $mform->disabledIf('tagproctoring_enableroomscan', 'tagproctoring_enabled', 'notchecked');

        $mform->addElement(
            'text',
            'tagproctoring_durationsec',
            get_string('duration', 'quizaccess_tagproctoring'),
            ['size' => 4]
        );

        $mform->setType('tagproctoring_durationsec', PARAM_INT);
        $mform->setDefault('tagproctoring_durationsec', $existing ? (int) $existing->durationsec : 45);
        $mform->disabledIf('tagproctoring_durationsec', 'tagproctoring_enabled', 'notchecked');
        $mform->disabledIf('tagproctoring_durationsec', 'tagproctoring_enableroomscan', 'notchecked');
    }

    /**
     * Save plugin settings.
     *
     * @param stdClass $quiz
     */
    public static function save_settings($quiz) {
        global $DB;

        $record = $DB->get_record('quizaccess_tagproctoring_settings', ['quizid' => $quiz->id]);

        $data = (object) [
        'quizid'          => $quiz->id,
        'enabled'         => !empty($quiz->tagproctoring_enabled) ? 1 : 0,
        'enableroomscan'  => !empty($quiz->tagproctoring_enableroomscan) ? 1 : 0,
        'durationsec'     => !empty($quiz->tagproctoring_durationsec)
        ? (int) $quiz->tagproctoring_durationsec
        : 45,
        ];

        if ($record) {
            $data->id = $record->id;
            $DB->update_record('quizaccess_tagproctoring_settings', $data);
        } else {
            $DB->insert_record('quizaccess_tagproctoring_settings', $data);
        }
    }

    /**
     * Create access rule instance.
     *
     * @param quiz $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return self|null
     */
    public static function make($quizobj, $timenow, $canignoretimelimits) {
        global $DB;

        $setting = $DB->get_record(
            'quizaccess_tagproctoring_settings',
            ['quizid' => $quizobj->get_quiz()->id]
        );

        if (empty($setting) || empty($setting->enabled)) {
            return null;
        }

        // Disable plugin if license is expired or not activated.
        $licstatus = \quizaccess_tagproctoring\license::get_status();
        if ($licstatus === \quizaccess_tagproctoring\license::STATUS_EXPIRED ||
            $licstatus === \quizaccess_tagproctoring\license::STATUS_UNLICENSED) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Determine if preflight check is required.
     *
     * @param int $attemptid
     * @return bool
     */
    public function is_preflight_check_required($attemptid) {
        global $USER, $DB;

        if (is_siteadmin($USER)) {
            return false;
        }

        if (has_capability('moodle/course:manageactivities', $this->quizobj->get_context())) {
            return false;
        }

        $quizid = (int) $this->quizobj->get_quiz()->id;

        $scan = $DB->get_record(
            'quizaccess_tagproctoring_scan',
            [
            'quizid' => $quizid,
            'userid' => (int) $USER->id,
            ]
        );

        // Require preflight unless all required media items were collected.
        $quizsetting = $DB->get_record('quizaccess_tagproctoring_settings', ['quizid' => $quizid]);
        $perquizrs = ($quizsetting && isset($quizsetting->enableroomscan)) ? (bool) $quizsetting->enableroomscan : true;
        $roomscanrequired = $perquizrs;

        $scanok = !$roomscanrequired || !empty($scan->filename);

        if ($scan && $scanok && !empty($scan->selfie_filename) && !empty($scan->idphoto_filename)) {
            return false;
        }

        return true;
    }

    /**
     * Add the proctoring wizard HTML and hidden consent fields to the preflight form.
     *
     * @param object $quizform The quiz preflight form object.
     * @param MoodleQuickForm $mform The Moodle quick form.
     * @param int $attemptid The attempt ID (0 for a new attempt).
     */
    public function add_preflight_check_form_fields($quizform, $mform, $attemptid) {
        global $DB, $PAGE, $OUTPUT;

        // Hidden fields set by JS before form submission.
        // advcheckbox generates both a hidden (value=0) and a visible checkbox (value=1).
        // recorder.js sets .checked=true on id_tagproctoring_consent before submit.
        $mform->addElement('advcheckbox', 'tagproctoring_consent', '', '', [], [0, 1]);
        $mform->setType('tagproctoring_consent', PARAM_BOOL);

        $mform->addElement('hidden', 'tagproctoring_complete', 0);
        $mform->setType('tagproctoring_complete', PARAM_INT);

        $quiz = $this->quizobj->get_quiz();

        // Render the wizard from the Mustache template.
        $wizardhtml = $OUTPUT->render_from_template('quizaccess_tagproctoring/wizard', []);

        $mform->addElement('html', $wizardhtml);

        // Hide the Moodle submit button and the advcheckbox — both are controlled by wizard JS.
        $mform->addElement('html',
            '<style>
                #id_submitbutton { display:none !important; }
                .fitem:has(#id_tagproctoring_consent) { display:none !important; }
            </style>'
        );

        // Read per-quiz settings for room scan toggle and duration.
        $quizsetting = $DB->get_record('quizaccess_tagproctoring_settings', ['quizid' => $quiz->id]);

        // Per-quiz toggle controls room scan independently.
        $perquizrs = ($quizsetting && isset($quizsetting->enableroomscan)) ? (int) $quizsetting->enableroomscan : 1;
        $enableroomscan = $perquizrs ? 1 : 0;

        $scanduration = ($quizsetting && !empty($quizsetting->durationsec)) ? (int) $quizsetting->durationsec : 45;

        $aiconfidence = (float) (get_config('quizaccess_tagproctoring', 'ai_confidence_threshold') ?: 0.35);
        $rawflagged = get_config('quizaccess_tagproctoring', 'ai_flagged_objects');
        $flaggedobjects = $rawflagged
            ? array_values(array_filter(array_map('trim', explode("\n", $rawflagged))))
            : ['cell phone', 'book', 'tablet', 'remote', 'extra_person', 'laptop'];

        $PAGE->requires->js_call_amd(
            'quizaccess_tagproctoring/recorder',
            'init',
            [[
                'quizid'         => (int) $quiz->id,
                'uploadurl'      => (new moodle_url(
                    '/mod/quiz/accessrule/tagproctoring/upload.php'
                ))->out(false),
                'selfieurl'      => (new moodle_url(
                    '/mod/quiz/accessrule/tagproctoring/selfie_upload.php'
                ))->out(false),
                'idphotourl'     => (new moodle_url(
                    '/mod/quiz/accessrule/tagproctoring/id_upload.php'
                ))->out(false),
                'vendorBase'     => (new moodle_url(
                    '/mod/quiz/accessrule/tagproctoring/vendor'
                ))->out(false),
                'enableRoomScan'  => $enableroomscan,
                'scanDuration'    => $scanduration,
                'aiConfidence'    => $aiconfidence,
                'flaggedObjects'  => $flaggedobjects,
            ]]
        );
    }

    /**
     * Validate preflight check data.
     *
     * @param array $data
     * @param array $files
     * @param array $errors
     * @param int $attemptid
     * @return array
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        global $DB, $USER;

        if (!empty($errors)) {
            return $errors;
        }

        // Tagproctoring consent is set by JS before form submission.
        if (empty($data['tagproctoring_consent'])) {
            $errors['tagproctoring_consent'] = get_string('required');
            return $errors;
        }

        $quizid = (int) $this->quizobj->get_quiz()->id;

        $scan = $DB->get_record(
            'quizaccess_tagproctoring_scan',
            [
            'quizid' => $quizid,
            'userid' => (int) $USER->id,
            ]
        );

        $quizsetting2 = $DB->get_record('quizaccess_tagproctoring_settings', ['quizid' => $quizid]);
        $perquizrs2 = ($quizsetting2 && isset($quizsetting2->enableroomscan)) ? (bool) $quizsetting2->enableroomscan : true;
        $roomscanrequired = $perquizrs2;

        if ($roomscanrequired && (!$scan || empty($scan->filename))) {
            $errors['tagproctoring_complete'] =
            get_string('scanrequired', 'quizaccess_tagproctoring');
            return $errors;
        }

        if (empty($scan->selfie_filename)) {
            $errors['tagproctoring_complete'] =
            get_string('selfierequired', 'quizaccess_tagproctoring');
            return $errors;
        }

        if (empty($scan->idphoto_filename)) {
            $errors['tagproctoring_complete'] =
            get_string('idphotorequired', 'quizaccess_tagproctoring');
            return $errors;
        }

        return $errors;
    }

    /**
     * Setup attempt page JS.
     *
     * @param moodle_page $page
     */
    public function setup_attempt_page($page) {
        global $USER;

        if (is_siteadmin($USER)) {
            return;
        }

        if (has_capability('moodle/course:manageactivities', $this->quizobj->get_context())) {
            return;
        }

        $attemptid = $page->url->get_param('attempt');

        if (empty($attemptid)) {
            return;
        }

        $interval = (int) get_config('quizaccess_tagproctoring', 'screenshotinterval') ?: 30;

        $rawss = get_config('quizaccess_tagproctoring', 'enablescreenshots');
        $enablescreenshots = ($rawss !== false) ? (int) $rawss : 1;

        $rawev = get_config('quizaccess_tagproctoring', 'enableevents');
        $enableevents = ($rawev !== false) ? (int) $rawev : 1;

        $faceturnsensitivity = (float) (get_config('quizaccess_tagproctoring', 'faceturn_sensitivity') ?: 0.30);

        $page->requires->js_call_amd(
            'quizaccess_tagproctoring/monitor',
            'init',
            [
            [
            'cmid'                => (int) $this->quizobj->get_cm()->id,
            'attemptid'           => (int) $attemptid,
            'interval'            => $interval,
            'enablescreenshots'   => $enablescreenshots,
            'enableevents'        => $enableevents,
            'faceTurnSensitivity' => $faceturnsensitivity,
            'vendorBase'          => (new moodle_url(
                '/mod/quiz/accessrule/tagproctoring/vendor'
            ))->out(false),
            ],
            ]
        );
    }

    /**
     * Return plugin description for quiz page.
     *
     * @return array
     */
    public function description() {
        global $OUTPUT;

        $context = $this->quizobj->get_context();

        if (!has_capability('quizaccess/tagproctoring:viewreport', $context)) {
            return [];
        }

        $quiz = $this->quizobj->get_quiz();

        $reporturl = quizaccess_tagproctoring_get_report_url(
            $quiz->course,
            $this->quizobj->get_cm()->id,
            $quiz->id
        );

        $icon = html_writer::tag('i', '', ['class' => 'fa fa-bar-chart mr-1']);

        $link = $OUTPUT->action_link(
            $reporturl,
            $icon . ' ' . get_string('report_link_label', 'quizaccess_tagproctoring'),
            null,
            ['class' => 'btn btn-outline-primary btn-sm']
        );

        return [html_writer::div($link, 'mt-2')];
    }
}
