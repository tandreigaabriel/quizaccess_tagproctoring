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
 * Admin settings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'quizaccess_tagproctoring/general',
        get_string('settings_general', 'quizaccess_tagproctoring'),
        ''
    ));

    // Retention days for videos and screenshots.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/retentiondays',
        get_string('settings_retentiondays', 'quizaccess_tagproctoring'),
        get_string('settings_retentiondays_desc', 'quizaccess_tagproctoring'),
        30,
        PARAM_INT
    ));

    // Screenshot interval.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/screenshotinterval',
        get_string('settings_screenshotinterval', 'quizaccess_tagproctoring'),
        get_string('settings_screenshotinterval_desc', 'quizaccess_tagproctoring'),
        30,
        PARAM_INT
    ));

    // Enable screenshots.
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_tagproctoring/enablescreenshots',
        get_string('settings_enablescreenshots', 'quizaccess_tagproctoring'),
        get_string('settings_enablescreenshots_desc', 'quizaccess_tagproctoring'),
        1
    ));

    // Enable event tracking.
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_tagproctoring/enableevents',
        get_string('settings_enableevents', 'quizaccess_tagproctoring'),
        get_string('settings_enableevents_desc', 'quizaccess_tagproctoring'),
        1
    ));

    // Max screenshots per attempt.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/maxscreenshots',
        get_string('settings_maxscreenshots', 'quizaccess_tagproctoring'),
        get_string('settings_maxscreenshots_desc', 'quizaccess_tagproctoring'),
        20,
        PARAM_INT
    ));
}
