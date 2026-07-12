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
 * Privacy API provider for quizaccess_tagproctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the TAG Proctoring quiz access rule.
 *
 * This plugin stores room-scan videos, selfie images, ID photos, webcam
 * screenshots, and behavioural event logs — all linked to a specific user
 * and quiz attempt.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data this plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'quizaccess_tagproctoring_scan',
            [
                'userid'          => 'privacy:metadata:quizaccess_tagproctoring_scan:userid',
                'quizid'          => 'privacy:metadata:quizaccess_tagproctoring_scan:quizid',
                'attemptid'       => 'privacy:metadata:quizaccess_tagproctoring_scan:attemptid',
                'timecreated'     => 'privacy:metadata:quizaccess_tagproctoring_scan:timecreated',
                'filename'        => 'privacy:metadata:quizaccess_tagproctoring_scan:filename',
                'selfie_filename' => 'privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename',
                'idphoto_filename' => 'privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename',
                'scan_objects'    => 'privacy:metadata:quizaccess_tagproctoring_scan:scan_objects',
                'scan_suspicious' => 'privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious',
                'needs_rescan' => 'privacy:metadata:quizaccess_tagproctoring_scan:needs_rescan',
                'face_match_verified' => 'privacy:metadata:quizaccess_tagproctoring_scan:face_match_verified',
                'face_match_distance' => 'privacy:metadata:quizaccess_tagproctoring_scan:face_match_distance',
            ],
            'privacy:metadata:quizaccess_tagproctoring_scan'
        );

        $collection->add_database_table(
            'quizaccess_tagproctoring_screenshots',
            [
                'userid'      => 'privacy:metadata:quizaccess_tagproctoring_screenshots:userid',
                'quizid'      => 'privacy:metadata:quizaccess_tagproctoring_screenshots:quizid',
                'attemptid'   => 'privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid',
                'timecreated' => 'privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated',
                'filename'    => 'privacy:metadata:quizaccess_tagproctoring_screenshots:filename',
                'source'      => 'privacy:metadata:quizaccess_tagproctoring_screenshots:source',
            ],
            'privacy:metadata:quizaccess_tagproctoring_screenshots'
        );

        $collection->add_database_table(
            'quizaccess_tagproctoring_events',
            [
                'userid'      => 'privacy:metadata:quizaccess_tagproctoring_events:userid',
                'quizid'      => 'privacy:metadata:quizaccess_tagproctoring_events:quizid',
                'attemptid'   => 'privacy:metadata:quizaccess_tagproctoring_events:attemptid',
                'timecreated' => 'privacy:metadata:quizaccess_tagproctoring_events:timecreated',
                'eventtype'   => 'privacy:metadata:quizaccess_tagproctoring_events:eventtype',
                'detail'      => 'privacy:metadata:quizaccess_tagproctoring_events:detail',
            ],
            'privacy:metadata:quizaccess_tagproctoring_events'
        );

        $collection->add_database_table(
            'quizaccess_tagproctoring_overrides',
            [
                'attemptid' => 'privacy:metadata:quizaccess_tagproctoring_overrides:attemptid',
                'valid_override' => 'privacy:metadata:quizaccess_tagproctoring_overrides:valid_override',
                'overriddenby' => 'privacy:metadata:quizaccess_tagproctoring_overrides:overriddenby',
                'timecreated' => 'privacy:metadata:quizaccess_tagproctoring_overrides:timecreated',
                'reviewed' => 'privacy:metadata:quizaccess_tagproctoring_overrides:reviewed',
                'reviewedby' => 'privacy:metadata:quizaccess_tagproctoring_overrides:reviewedby',
                'timereviewed' => 'privacy:metadata:quizaccess_tagproctoring_overrides:timereviewed',
            ],
            'privacy:metadata:quizaccess_tagproctoring_overrides'
        );

        $collection->add_external_location_link(
            'tandreig_license_server',
            [],
            'privacy:metadata:external:licenseserver'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain personal data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Scan records tie a user to a quiz (module context).
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {quiz} q ON q.id = cm.instance
                  JOIN {quizaccess_tagproctoring_scan} s ON s.quizid = q.id
                 WHERE s.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);

        // Screenshot records.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {quiz} q ON q.id = cm.instance
                  JOIN {quizaccess_tagproctoring_screenshots} sc ON sc.quizid = q.id
                 WHERE sc.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);

        // Event log records.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {quiz} q ON q.id = cm.instance
                  JOIN {quizaccess_tagproctoring_events} e ON e.quizid = q.id
                 WHERE e.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $params = ['contextid' => $context->id, 'contextlevel' => CONTEXT_MODULE];

        $sql = "SELECT s.userid
                  FROM {quizaccess_tagproctoring_scan} s
                  JOIN {quiz} q ON q.id = s.quizid
                  JOIN {course_modules} cm ON cm.instance = q.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ctx.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT sc.userid
                  FROM {quizaccess_tagproctoring_screenshots} sc
                  JOIN {quiz} q ON q.id = sc.quizid
                  JOIN {course_modules} cm ON cm.instance = q.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ctx.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT e.userid
                  FROM {quizaccess_tagproctoring_events} e
                  JOIN {quiz} q ON q.id = e.quizid
                  JOIN {course_modules} cm ON cm.instance = q.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ctx.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for a user in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Collect all valid contexts and build a quizid → context map.
        $contextmap = [];
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('quiz', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $contextmap[$cm->instance] = $context;
        }

        if (empty($contextmap)) {
            return;
        }

        $quizids = array_keys($contextmap);
        [$insql, $inparams] = $DB->get_in_or_equal($quizids, SQL_PARAMS_NAMED, 'qid');
        $inparams['userid'] = $userid;

        // Bulk-load all three tables in a single query each, then group by quizid.
        $scansbyquiz = [];
        $rs = $DB->get_recordset_select(
            'quizaccess_tagproctoring_scan',
            "quizid $insql AND userid = :userid",
            $inparams
        );
        foreach ($rs as $row) {
            $scansbyquiz[$row->quizid][] = $row;
        }
        $rs->close();

        $shotsbyquiz = [];
        $rs = $DB->get_recordset_select(
            'quizaccess_tagproctoring_screenshots',
            "quizid $insql AND userid = :userid",
            $inparams
        );
        foreach ($rs as $row) {
            $shotsbyquiz[$row->quizid][] = $row;
        }
        $rs->close();

        $eventsbyquiz = [];
        $rs = $DB->get_recordset_select(
            'quizaccess_tagproctoring_events',
            "quizid $insql AND userid = :userid",
            $inparams
        );
        foreach ($rs as $row) {
            $eventsbyquiz[$row->quizid][] = $row;
        }
        $rs->close();

        // Export data per context using the in-memory results.
        foreach ($contextmap as $quizid => $context) {
            if (!empty($scansbyquiz[$quizid])) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:scans', 'quizaccess_tagproctoring')],
                    (object)['scans' => $scansbyquiz[$quizid]]
                );
            }
            if (!empty($shotsbyquiz[$quizid])) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:screenshots', 'quizaccess_tagproctoring')],
                    (object)['screenshots' => $shotsbyquiz[$quizid]]
                );
            }
            if (!empty($eventsbyquiz[$quizid])) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:events', 'quizaccess_tagproctoring')],
                    (object)['events' => $eventsbyquiz[$quizid]]
                );
            }
        }
    }

    /**
     * Delete all personal data for all users in the given context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $context->instanceid);
        if (!$cm) {
            return;
        }
        $quizid = $cm->instance;

        $DB->delete_records('quizaccess_tagproctoring_scan', ['quizid' => $quizid]);
        $DB->delete_records('quizaccess_tagproctoring_screenshots', ['quizid' => $quizid]);
        $DB->delete_records('quizaccess_tagproctoring_events', ['quizid' => $quizid]);
    }

    /**
     * Delete personal data for a specific user in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('quiz', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $quizid = $cm->instance;

            $DB->delete_records(
                'quizaccess_tagproctoring_scan',
                ['quizid' => $quizid, 'userid' => $userid]
            );
            $DB->delete_records(
                'quizaccess_tagproctoring_screenshots',
                ['quizid' => $quizid, 'userid' => $userid]
            );
            $DB->delete_records(
                'quizaccess_tagproctoring_events',
                ['quizid' => $quizid, 'userid' => $userid]
            );
        }
    }

    /**
     * Delete personal data for a list of users in the given context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $context->instanceid);
        if (!$cm) {
            return;
        }
        $quizid = $cm->instance;

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['quizid'] = $quizid;

        $DB->delete_records_select(
            'quizaccess_tagproctoring_scan',
            "quizid = :quizid AND userid $insql",
            $params
        );
        $DB->delete_records_select(
            'quizaccess_tagproctoring_screenshots',
            "quizid = :quizid AND userid $insql",
            $params
        );
        $DB->delete_records_select(
            'quizaccess_tagproctoring_events',
            "quizid = :quizid AND userid $insql",
            $params
        );
    }
}
