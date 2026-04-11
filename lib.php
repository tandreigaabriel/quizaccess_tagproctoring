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
 * Library functions for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve proctoring files (room scan videos, screenshots).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void|false
 */
function quizaccess_tagproctoring_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login($course, false, $cm);

    if (!has_capability('quizaccess/tagproctoring:viewreport', $context)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'quizaccess_tagproctoring', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Build report URL.
 *
 * @param int $cid
 * @param int $cmid
 * @param int $quizid
 * @return moodle_url
 */
function quizaccess_tagproctoring_get_report_url($cid, $cmid, $quizid) {
    return new moodle_url('/mod/quiz/accessrule/tagproctoring/report.php', [
        'cid' => $cid,
        'cmid' => $cmid,
        'qid' => $quizid,
    ]);
}

/**
 * Calculate risk level based on events.
 *
 * @param array $events
 * @return string
 */
function quizaccess_tagproctoring_get_risk_level($events) {
    if (empty($events)) {
        return 'low';
    }

    $highrisktypes = ['screenshare', 'multiplescreen'];
    $mediumrisktypes = ['tabswitch', 'visibilityhidden', 'copypaste'];

    foreach ($events as $event) {
        if (in_array($event->eventtype, $highrisktypes)) {
            return 'high';
        }
    }

    foreach ($events as $event) {
        if (in_array($event->eventtype, $mediumrisktypes)) {
            return 'medium';
        }
    }

    return 'low';
}

/**
 * Get risk CSS class.
 *
 * @param string $risklevel
 * @return string
 */
function quizaccess_tagproctoring_get_risk_class($risklevel) {
    switch ($risklevel) {
        case 'high':
            return 'text-danger';
        case 'medium':
            return 'text-warning';
        default:
            return 'text-success';
    }
}
