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
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
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
        $mform->addElement('advcheckbox', 'tagproctoring_enabled', get_string('enableproctor', 'quizaccess_tagproctoring'));

        $mform->addElement(
            'text',
            'tagproctoring_durationsec',
            get_string('duration', 'quizaccess_tagproctoring'),
            ['size' => 4]
        );

        $mform->setType('tagproctoring_durationsec', PARAM_INT);
        $mform->setDefault('tagproctoring_durationsec', 15);
        $mform->disabledIf('tagproctoring_durationsec', 'tagproctoring_enabled', 'notchecked');
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
        'quizid' => $quiz->id,
        'enabled' => !empty($quiz->tagproctoring_enabled) ? 1 : 0,
        'durationsec' => !empty($quiz->tagproctoring_durationsec)
        ? (int) $quiz->tagproctoring_durationsec
        : 15,
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

        // Require preflight unless all three media items were collected.
        if (
            $scan && !empty($scan->filename) &&
            !empty($scan->selfie_filename) &&
            !empty($scan->idphoto_filename)
        ) {
            return false;
        }

        return true;
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

        if (!$scan || empty($scan->filename)) {
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

        $page->requires->js_call_amd(
            'quizaccess_tagproctoring/monitor',
            'init',
            [
            [
            'cmid' => (int) $this->quizobj->get_cm()->id,
            'attemptid' => (int) $attemptid,
            'interval' => $interval,
            'enablescreenshots' => $enablescreenshots,
            'enableevents' => $enableevents,
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
            $quiz->cmid,
            $quiz->id
        );

        $icon = html_writer::tag('i', '', ['class' => 'fa fa-bar-chart mr-1']);

        $link = $OUTPUT->action_link(
            $reporturl,
            $icon . ' Proctoring Report',
            null,
            ['class' => 'btn btn-outline-primary btn-sm']
        );

        return [html_writer::div($link, 'mt-2')];
    }
}
