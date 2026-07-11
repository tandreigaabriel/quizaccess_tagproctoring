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
 * Hook callbacks for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring;

use core\hook\output\before_footer_html_generation;

/**
 * Hook callback class.
 */
class hook_callbacks {
    /**
     * Inject proctoring status column into quiz overview report.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $DB;

        // Only act on the quiz overview report page.
        $path = $PAGE->url->get_path();
        if (substr($path, -strlen('/mod/quiz/report.php')) !== '/mod/quiz/report.php') {
            return;
        }

        $cmid = (int) $PAGE->url->get_param('id');
        if (!$cmid) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $cmid);
        if (!$cm) {
            return;
        }

        $context = \context_module::instance($cm->id);
        if (!has_capability('quizaccess/tagproctoring:viewreport', $context)) {
            return;
        }

        // Only inject if proctoring is enabled for this quiz.
        $setting = $DB->get_record('quizaccess_tagproctoring_settings', ['quizid' => $cm->instance]);
        if (!$setting || empty($setting->enabled)) {
            return;
        }

        $ajaxurl = (new \moodle_url(
            '/mod/quiz/accessrule/tagproctoring/ajax_proctoring_status.php'
        ))->out(false);

        $reporturl = (new \moodle_url('/mod/quiz/accessrule/tagproctoring/report.php', [
            'cid'  => (int) $cm->course,
            'cmid' => $cmid,
            'qid'  => (int) $cm->instance,
        ]))->out(false);

        $PAGE->requires->js_call_amd(
            'quizaccess_tagproctoring/grades_column',
            'init',
            [[
                'cmid'      => $cmid,
                'ajaxurl'   => $ajaxurl,
                'sesskey'   => sesskey(),
                'reporturl' => $reporturl,
                'strings'   => [
                    'header' => get_string('grades_col_proctoring', 'quizaccess_tagproctoring'),
                    'high'   => get_string('grades_status_high', 'quizaccess_tagproctoring'),
                    'medium' => get_string('grades_status_medium', 'quizaccess_tagproctoring'),
                    'low'    => get_string('grades_status_low', 'quizaccess_tagproctoring'),
                ],
            ]]
        );
    }
}
