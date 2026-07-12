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
 * Ad-hoc task: send proctoring alert email after a quiz attempt is submitted.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\task;

/**
 * Sends a proctoring-alert email to configured recipients for a single attempt.
 *
 * Queued by the attempt_submitted event observer; all heavy DB work runs here
 * so the observer itself stays lightweight.
 */
class send_attempt_alert extends \core\task\adhoc_task {

    /**
     * Execute the task: load attempt data, evaluate risk, and send the alert email.
     */
    public function execute(): void {
        global $DB, $CFG;

        if (!get_config('quizaccess_tagproctoring', 'emailalerts')) {
            return;
        }

        $data = $this->get_custom_data();
        $attemptid = (int) $data->attemptid;
        $quizid    = (int) $data->quizid;
        $userid    = (int) $data->userid;
        $courseid  = (int) $data->courseid;
        $cmid      = (int) $data->cmid;

        // Aggregate event counts for this attempt.
        $eventcounts = [];
        foreach ($DB->get_records('quizaccess_tagproctoring_events', ['attemptid' => $attemptid]) as $e) {
            $eventcounts[$e->eventtype] = ($eventcounts[$e->eventtype] ?? 0) + 1;
        }

        // Get scan data.
        $scan           = $DB->get_record('quizaccess_tagproctoring_scan', ['quizid' => $quizid, 'userid' => $userid]);
        $scansuspicious = $scan && !empty($scan->scan_suspicious);
        $facematch      = ($scan && isset($scan->face_match_verified)) ? (int) $scan->face_match_verified : null;

        require_once($CFG->dirroot . '/mod/quiz/accessrule/tagproctoring/lib.php');
        $risk = quizaccess_tagproctoring_calculate_attempt_risk($eventcounts, $scansuspicious, $facematch);

        $hasflags = !empty($eventcounts) || $scansuspicious || $facematch === 0 || $facematch === -1;
        if (!$hasflags || $risk['risk'] === 'low') {
            return;
        }

        $context    = \context_module::instance($cmid);
        $recipients = $this->get_alert_recipients($context);

        if (empty($recipients)) {
            return;
        }

        $student = $DB->get_record('user', ['id' => $userid]);
        $quiz    = $DB->get_record('quiz', ['id' => $quizid], 'id, name');
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id, attempt');

        if (!$student || !$quiz) {
            return;
        }

        $eventsummary    = $this->format_event_summary($eventcounts);
        $screenshotcount = $DB->count_records('quizaccess_tagproctoring_screenshots', ['attemptid' => $attemptid]);

        $reporturl = new \moodle_url('/mod/quiz/accessrule/tagproctoring/report.php', [
            'cid'  => $courseid,
            'cmid' => $cmid,
            'qid'  => $quizid,
        ]);

        $a              = new \stdClass();
        $a->student     = fullname($student);
        $a->quiz        = format_string($quiz->name);
        $a->attempt     = (int) ($attempt->attempt ?? 1);
        $a->risk        = get_string('report_risk_' . $risk['risk'], 'quizaccess_tagproctoring');
        $a->score       = (int) $risk['score'];
        $a->events      = $eventsummary ?: get_string('email_no_events', 'quizaccess_tagproctoring');
        $a->screenshots = $screenshotcount;
        $a->reporturl   = $reporturl->out(false);

        $subject = get_string('email_attempt_subject', 'quizaccess_tagproctoring', $a);
        $body    = get_string('email_attempt_body', 'quizaccess_tagproctoring', $a);
        $noreply = \core_user::get_noreply_user();

        foreach ($recipients as $recipient) {
            email_to_user($recipient, $noreply, $subject, $body);
        }
    }

    /**
     * Get configured alert recipients, falling back to quiz teachers.
     *
     * @param \context $context Quiz module context.
     * @return array Recipient records suitable for email_to_user().
     */
    private function get_alert_recipients(\context $context): array {
        $configured = trim((string) get_config('quizaccess_tagproctoring', 'emailrecipients'));

        if ($configured !== '') {
            $emails     = preg_split('/[\s,;]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
            $recipients = [];

            foreach (array_unique($emails) as $email) {
                $email = trim($email);
                if (!validate_email($email)) {
                    continue;
                }

                $recipient                  = new \stdClass();
                $recipient->id              = -99;
                $recipient->email           = $email;
                $recipient->firstname       = get_string('pluginname', 'quizaccess_tagproctoring');
                $recipient->lastname        = get_string('email_recipient_lastname', 'quizaccess_tagproctoring');
                $recipient->firstnamephonetic = '';
                $recipient->lastnamephonetic  = '';
                $recipient->middlename        = '';
                $recipient->alternatename     = '';
                $recipient->mailformat        = FORMAT_PLAIN;
                $recipient->maildisplay       = 1;
                $recipient->lang              = current_language();
                $recipient->deleted           = 0;
                $recipient->suspended         = 0;
                $recipients[]               = $recipient;
            }

            return $recipients;
        }

        return get_users_by_capability(
            $context,
            'quizaccess/tagproctoring:viewreport',
            'u.*',
            'u.lastname ASC'
        );
    }

    /**
     * Format event counts as a human-readable summary for the email body.
     *
     * @param array $eventcounts Associative array of eventtype => count.
     * @return string Multi-line summary string.
     */
    private function format_event_summary(array $eventcounts): string {
        if (empty($eventcounts)) {
            return '';
        }

        ksort($eventcounts);
        $lines = [];

        foreach ($eventcounts as $eventtype => $count) {
            $stringid = 'event_' . $eventtype;
            if (get_string_manager()->string_exists($stringid, 'quizaccess_tagproctoring')) {
                $label = get_string($stringid, 'quizaccess_tagproctoring');
            } else {
                $label = ucfirst(str_replace(['_', '-'], ' ', $eventtype));
            }
            $lines[] = '- ' . $label . ': ' . (int) $count;
        }

        return implode("\n", $lines);
    }
}
