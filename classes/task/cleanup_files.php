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
 * Scheduled task to delete expired proctoring files.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\task;

/**
 * Scheduled task: delete room scan videos, selfies, ID photos, and webcam
 * screenshots that are older than the configured retention period.
 *
 * Runs daily at 03:00 server time (configurable via Site Admin → Tasks).
 * Retention window is set at:
 * Site Admin → Plugins → Quiz access rules → TAG Proctoring → Data retention period.
 */
class cleanup_files extends \core\task\scheduled_task
{
    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_files', 'quizaccess_tagproctoring');
    }

    /**
     * Execute the scheduled cleanup task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $retentiondays = (int) get_config('quizaccess_tagproctoring', 'retentiondays');

        if ($retentiondays <= 0) {
            // Retention set to 0 means keep data forever.
            mtrace('TAG Proctoring cleanup: retention set to 0, skipping.');
            return;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);

        // Get file storage.
        $fs = get_file_storage();

        // Delete old room scan records and files.
        $scans = $DB->get_records_select(
            'quizaccess_tagproctoring_scan',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );

        $scancount = 0;

        foreach ($scans as $scan) {
            $cm = get_coursemodule_from_instance('quiz', $scan->quizid, 0, false, IGNORE_MISSING);

            if ($cm) {
                $context = \context_module::instance($cm->id);

                $fs->delete_area_files($context->id, 'quizaccess_tagproctoring', 'roomscan', $scan->userid);
                $fs->delete_area_files($context->id, 'quizaccess_tagproctoring', 'selfie', $scan->userid);
                $fs->delete_area_files($context->id, 'quizaccess_tagproctoring', 'idphoto', $scan->userid);
            }

            $DB->delete_records('quizaccess_tagproctoring_scan', ['id' => $scan->id]);
            $scancount++;
        }

        mtrace("TAG Proctoring cleanup: deleted $scancount scan record(s) older than $retentiondays days.");

        // Delete old screenshots and files.
        $sql = "SELECT s.id, s.attemptid, a.quiz
                  FROM {quizaccess_tagproctoring_screenshots} s
                  JOIN {quiz_attempts} a ON a.id = s.attemptid
                 WHERE a.timefinish < :cutoff AND a.timefinish > 0";

        $shots = $DB->get_records_sql($sql, ['cutoff' => $cutoff]);

        $shotcount = 0;

        foreach ($shots as $shot) {
            $cm = get_coursemodule_from_instance('quiz', $shot->quiz, 0, false, IGNORE_MISSING);

            if ($cm) {
                $context = \context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'quizaccess_tagproctoring', 'screenshot', $shot->id);
            }

            $DB->delete_records('quizaccess_tagproctoring_screenshots', ['id' => $shot->id]);
            $shotcount++;
        }

        mtrace("TAG Proctoring cleanup: deleted $shotcount screenshot record(s) older than $retentiondays days.");

        // Delete orphaned event records.
        $oldattemptids = $DB->get_fieldset_select(
            'quiz_attempts',
            'id',
            'timefinish < :cutoff AND timefinish > 0',
            ['cutoff' => $cutoff]
        );

        if ($oldattemptids) {
            [$insql, $inparams] = $DB->get_in_or_equal($oldattemptids);

            $evcount = $DB->count_records_select(
                'quizaccess_tagproctoring_events',
                "attemptid $insql",
                $inparams
            );

            $DB->delete_records_select(
                'quizaccess_tagproctoring_events',
                "attemptid $insql",
                $inparams
            );

            mtrace("TAG Proctoring cleanup: deleted $evcount event record(s) for old attempts.");
        }
    }
}
