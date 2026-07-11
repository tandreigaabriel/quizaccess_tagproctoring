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
 * Greek language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Συναινώ στην εγγραφή ενός σύντομου βίντεο σάρωσης δωματίου για σκοπούς ακεραιότητας της εξέτασης.';
$string['deletescan_cancel'] = 'Ακύρωση';
$string['deletescan_confirm'] = 'Ναι, διαγραφή δεδομένων σάρωσης';
$string['deletescan_description'] = 'Αυτό θα διαγράψει οριστικά το βίντεο σάρωσης δωματίου, το selfie και τη φωτογραφία ταυτότητας αυτού του φοιτητή. Η απόπειρα, τα στιγμιότυπα και το αρχείο καταγραφής συμβάντων δεν επηρεάζονται.';
$string['deletescan_heading'] = 'Διαγραφή δεδομένων σάρωσης για {$a->user} (Κουίζ: {$a->quiz});';
$string['deletescan_success'] = 'Τα δεδομένα σάρωσης διαγράφηκαν.';
$string['deletescan_title'] = 'Διαγραφή δεδομένων σάρωσης';
$string['duration'] = 'Διάρκεια σάρωσης δωματίου (δευτερόλεπτα)';
$string['enableproctor'] = 'Ενεργοποίηση εποπτείας TAG';

$string['error_no_idphoto'] = 'Δεν ελήφθη φωτογραφία ταυτότητας';
$string['error_no_screenshot'] = 'Δεν ελήφθη στιγμιότυπο οθόνης';
$string['error_no_selfie'] = 'Δεν ελήφθη selfie';
$string['error_no_video'] = 'Δεν ελήφθη αρχείο βίντεο';
$string['event_cameradisabled'] = 'Κάμερα απενεργοποιημένη';
$string['event_contextmenu'] = 'Εντοπίστηκε δεξί κλικ';
$string['event_copypaste'] = 'Εντοπίστηκε αντιγραφή/επικόλληση';
$string['event_devtools'] = 'Εργαλεία προγραμματιστή ανοιχτά';
$string['event_faceturn'] = 'Πρόσωπο στραμμένο';
$string['event_fullscreenexit'] = 'Έξοδος από πλήρη οθόνη';
$string['event_multiplescreen'] = 'Εντοπίστηκαν πολλαπλές οθόνες';
$string['event_nocamera'] = 'Κάμερα μη διαθέσιμη';
$string['event_screenshare'] = 'Εντοπίστηκε κοινή χρήση οθόνης';
$string['event_screensharestop'] = 'Η κοινή χρήση οθόνης σταμάτησε';
$string['event_tabswitch'] = 'Εναλλαγή καρτέλας';
$string['event_visibilityhidden'] = 'Παράθυρο κρυμμένο';
$string['event_windowblur'] = 'Το παράθυρο έχασε την εστίαση';

$string['idphotorequired'] = 'Πρέπει να φωτογραφίσετε την ταυτότητά σας πριν ξεκινήσετε.';
$string['pluginname'] = 'Εποπτεία TAG';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Αποθηκεύει ύποπτα συμπεριφορικά συμβάντα που καταγράφηκαν κατά τη διάρκεια απόπειρας.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'Η απόπειρα στην οποία ανήκει αυτό το συμβάν.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Πρόσθετη λεπτομέρεια για το συμβάν.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Ο τύπος του ύποπτου συμβάντος που εντοπίστηκε.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Το κουίζ στο οποίο ανήκει αυτό το συμβάν.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'Η ημερομηνία του συμβάντος.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'Το αναγνωριστικό του φοιτητή που δημιούργησε το συμβάν.';
$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Αποθηκεύει ονόματα αρχείων βίντεο σάρωσης, selfie και φωτογραφίας ταυτότητας, καθώς και αποτελέσματα ανάλυσης AI.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'Η απόπειρα στην οποία ανήκει αυτή η σάρωση.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Το όνομα αρχείου βίντεο σάρωσης.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Το όνομα αρχείου φωτογραφίας ταυτότητας.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Το κουίζ στο οποίο ανήκει αυτή η σάρωση.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'Λίστα JSON αντικειμένων που εντοπίστηκαν από το AI στη σάρωση.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Δείχνει αν το AI επισήμανε τη σάρωση ως ύποπτη.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Το όνομα αρχείου selfie του φοιτητή.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'Η ημερομηνία δημιουργίας της εγγραφής.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'Το αναγνωριστικό του φοιτητή που πραγματοποίησε τη σάρωση.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Αποθηκεύει περιοδικά στιγμιότυπα webcam κατά τη διάρκεια απόπειρας.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'Η απόπειρα στην οποία ανήκει αυτό το στιγμιότυπο.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Το όνομα αρχείου στιγμιότυπου.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Το κουίζ στο οποίο ανήκει αυτό το στιγμιότυπο.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'Η ημερομηνία του στιγμιότυπου.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'Το αναγνωριστικό του φοιτητή.';
$string['privacy:path:events'] = 'Αρχείο καταγραφής συμβάντων';
$string['privacy:path:scans'] = 'Σαρώσεις δωματίου';
$string['privacy:path:screenshots'] = 'Στιγμιότυπα οθόνης';
$string['report_empty'] = 'Δεν βρέθηκαν εγγραφές εποπτείας για αυτό το κουίζ.';
$string['report_export_csv'] = 'Εξαγωγή CSV';
$string['report_link_caption'] = 'Αναφορά εποπτείας';
$string['report_link_label'] = 'Αναφορά εποπτείας';
$string['report_no_attempts'] = 'Δεν βρέθηκαν ολοκληρωμένες απόπειρες για αυτό το κουίζ.';
$string['report_risk_factors'] = 'Παράγοντες κινδύνου';
$string['report_risk_high'] = 'Υψηλός';
$string['report_risk_low'] = 'Χαμηλός';
$string['report_risk_medium'] = 'Μεσαίος';
$string['report_risk_score'] = 'Βαθμολογία κινδύνου: {$a}';
$string['report_risk_suspicious_scan'] = 'Ύποπτα αντικείμενα στη σάρωση δωματίου';
$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Απόπειρα';
$string['report_table_header_delete_scan'] = 'Διαγραφή σάρωσης';
$string['report_table_header_email'] = 'Email';
$string['report_table_header_events'] = 'Συμβάντα';
$string['report_table_header_name'] = 'Όνομα φοιτητή';
$string['report_table_header_risk'] = 'Επίπεδο κινδύνου';
$string['report_table_header_room_scan'] = 'Σάρωση δωματίου';
$string['report_table_header_score'] = 'Βαθμός';
$string['report_table_header_screenshots'] = 'Στιγμιότυπα οθόνης';
$string['report_table_header_time_taken'] = 'Διάρκεια';
$string['report_table_header_timefinish'] = 'Ολοκληρώθηκε';
$string['report_table_row_no_scan'] = 'Χωρίς σάρωση';
$string['report_table_row_no_screenshots'] = 'Κανένα';
$string['report_table_row_notyetgraded'] = 'Δεν έχει βαθμολογηθεί ακόμα';
$string['report_table_row_overdue'] = 'Υπέρβαση κατά {$a}';
$string['report_table_row_view_scan'] = 'Προβολή σάρωσης';
$string['report_table_row_view_screenshots'] = 'Προβολή ({$a})';
$string['scan_detected_objects'] = 'Εντοπισμένα αντικείμενα';
$string['scan_flagged'] = 'Επισημάνθηκε — Ύποπτα αντικείμενα';
$string['scan_no_objects'] = 'Δεν εντοπίστηκαν ύποπτα αντικείμενα';
$string['scan_object_book'] = 'Βιβλίο';
$string['scan_object_cellphone'] = 'Κινητό τηλέφωνο';
$string['scan_object_extraperson'] = 'Επιπλέον άτομο στο δωμάτιο';
$string['scan_object_laptop'] = 'Φορητός υπολογιστής (επιπλέον)';
$string['scan_object_remote'] = 'Τηλεχειριστήριο';
$string['scan_object_tablet'] = 'Tablet';
$string['scanrequired'] = 'Πρέπει να ολοκληρώσετε τη σάρωση δωματίου πριν ξεκινήσετε.';
$string['screenshots_attemptinfo'] = 'Η απόπειρα ξεκίνησε: {$a->started} | Ολοκληρώθηκε: {$a->finished}';
$string['screenshots_col_detail'] = 'Λεπτομέρεια';
$string['screenshots_col_event'] = 'Συμβάν';
$string['screenshots_col_time'] = 'Ώρα';
$string['screenshots_events_heading'] = 'Ύποπτα συμβάντα';
$string['screenshots_grid_heading'] = 'Στιγμιότυπα webcam ({$a})';
$string['screenshots_inprogress'] = 'Σε εξέλιξη';
$string['screenshots_none'] = 'Δεν καταγράφηκαν στιγμιότυπα για αυτήν την απόπειρα.';
$string['screenshots_title'] = 'Στιγμιότυπα οθόνης — {$a}';
$string['selfierequired'] = 'Πρέπει να ολοκληρώσετε το βήμα επαλήθευσης selfie πριν ξεκινήσετε.';
$string['settings_enableevents'] = 'Ενεργοποίηση παρακολούθησης ύποπτων συμβάντων';
$string['settings_enableevents_desc'] = 'Καταγραφή εναλλαγών καρτελών, αντιγραφής/επικόλλησης, DevTools και άλλων ύποπτων συμβάντων.';
$string['settings_enablescreenshots'] = 'Ενεργοποίηση στιγμιότυπων webcam';
$string['settings_enablescreenshots_desc'] = 'Λήψη περιοδικών στιγμιότυπων webcam κατά τη διάρκεια απόπειρων κουίζ.';
$string['settings_general'] = 'Ρυθμίσεις εποπτείας TAG';
$string['settings_maxscreenshots'] = 'Μέγιστα στιγμιότυπα ανά απόπειρα';
$string['settings_maxscreenshots_desc'] = 'Μέγιστος αριθμός στιγμιότυπων webcam που αποθηκεύονται ανά απόπειρα κουίζ.';
$string['settings_retentiondays'] = 'Περίοδος διατήρησης δεδομένων (ημέρες)';
$string['settings_retentiondays_desc'] = 'Αριθμός ημερών διατήρησης βίντεο και στιγμιότυπων πριν την αυτόματη διαγραφή.';
$string['settings_screenshotinterval'] = 'Διάστημα στιγμιότυπου (δευτερόλεπτα)';
$string['settings_screenshotinterval_desc'] = 'Πόσο συχνά (σε δευτερόλεπτα) λαμβάνεται στιγμιότυπο webcam κατά τη διάρκεια απόπειρας.';
$string['tagproctoring:viewreport'] = 'Προβολή αναφορών εποπτείας TAG';
$string['task_cleanup_files'] = 'Εποπτεία TAG: διαγραφή ληγμένων βίντεο και στιγμιότυπων';
$string['violation_contextmenu'] = 'Μενού δεξιού κλικ';
$string['violation_copypaste'] = 'Δραστηριότητα αντιγραφής/επικόλλησης';
$string['violation_devtools'] = 'Εργαλεία προγραμματιστή ανοιχτά';
$string['violation_faceaway'] = 'Βλέμμα μακριά από την οθόνη';
$string['violation_faceturn'] = 'Πρόσωπο στραμμένο';
$string['violation_fullscreenexit'] = 'Εγκατάλειψη πλήρους οθόνης';
$string['violation_multiplefaces'] = 'Πολλαπλά πρόσωπα κατά τη διάρκεια της εξέτασης';
$string['violation_multiplescreen'] = 'Πολλαπλές οθόνες εντοπίστηκαν';
$string['violation_nocamera'] = 'Κάμερα μη διαθέσιμη';
$string['violation_noface'] = 'Δεν εντοπίστηκε πρόσωπο';
$string['violation_screenshare'] = 'Κοινή χρήση οθόνης ξεκίνησε';
$string['violation_tabswitch'] = 'Εναλλαγή καρτέλας / Απώλεια εστίασης';
$string['violation_visibilityhidden'] = 'Καρτέλα κρυμμένη';
