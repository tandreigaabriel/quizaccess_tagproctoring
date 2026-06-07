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
 * German language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Ich stimme der Aufzeichnung eines kurzen Raumscans zur Sicherung der Prüfungsintegrität zu.';
$string['duration'] = 'Dauer des Raumscans (Sekunden)';
$string['enableproctor'] = 'TAG-Prüfungsaufsicht aktivieren';

$string['event_cameradisabled'] = 'Kamera deaktiviert';
$string['event_contextmenu'] = 'Rechtsklick erkannt';
$string['event_copypaste'] = 'Kopieren/Einfügen erkannt';
$string['event_devtools'] = 'Entwicklertools geöffnet';
$string['event_faceturn'] = 'Gesicht abgewendet';
$string['event_fullscreenexit'] = 'Vollbildmodus verlassen';
$string['event_multiplescreen'] = 'Mehrere Bildschirme erkannt';
$string['event_nocamera'] = 'Kamera nicht verfügbar';
$string['event_screenshare'] = 'Bildschirmfreigabe erkannt';
$string['event_screensharestop'] = 'Bildschirmfreigabe beendet';
$string['event_tabswitch'] = 'Tab-Wechsel';
$string['event_visibilityhidden'] = 'Fenster ausgeblendet';
$string['event_windowblur'] = 'Fenster hat Fokus verloren';

$string['violation_tabswitch'] = 'Tab-Wechsel / Fokusverlust';
$string['violation_visibilityhidden'] = 'Tab ausgeblendet';
$string['violation_fullscreenexit'] = 'Vollbild verlassen';
$string['violation_screenshare'] = 'Bildschirmfreigabe gestartet';
$string['violation_multiplescreen'] = 'Mehrere Bildschirme erkannt';
$string['violation_copypaste'] = 'Kopieren/Einfügen-Aktivität';
$string['violation_contextmenu'] = 'Rechtsklick-Menü';
$string['violation_devtools'] = 'Entwicklertools geöffnet';
$string['violation_nocamera'] = 'Kamera nicht verfügbar';
$string['violation_noface'] = 'Kein Gesicht erkannt';
$string['violation_faceaway'] = 'Blick vom Bildschirm abgewendet';
$string['violation_faceturn'] = 'Gesicht abgewendet';
$string['violation_multiplefaces'] = 'Mehrere Gesichter während der Prüfung';

$string['idphotorequired'] = 'Sie müssen Ihren Ausweis fotografieren, bevor Sie beginnen.';
$string['pluginname'] = 'TAG-Prüfungsaufsicht';

$string['report_empty'] = 'Keine Überwachungsdaten für dieses Quiz gefunden.';
$string['report_export_csv'] = 'CSV exportieren';
$string['report_link_caption'] = 'Überwachungsbericht';
$string['report_link_label'] = 'Überwachungsbericht';

$string['report_risk_factors'] = 'Risikofaktoren';
$string['report_risk_high'] = 'Hoch';
$string['report_risk_low'] = 'Niedrig';
$string['report_risk_medium'] = 'Mittel';
$string['report_risk_score'] = 'Risikopunktzahl: {$a}';
$string['report_risk_suspicious_scan'] = 'Verdächtige Objekte im Raumscan';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Versuch';
$string['report_table_header_delete_scan'] = 'Scan löschen';
$string['report_table_header_email'] = 'E-Mail';
$string['report_table_header_events'] = 'Ereignisse';
$string['report_table_header_name'] = 'Studentenname';
$string['report_table_header_risk'] = 'Risikoniveau';
$string['report_table_header_room_scan'] = 'Raumscan';
$string['report_table_header_score'] = 'Punkte';
$string['report_table_header_screenshots'] = 'Screenshots';
$string['report_table_header_time_taken'] = 'Dauer';
$string['report_table_header_timefinish'] = 'Beendet';

$string['report_table_row_no_scan'] = 'Kein Scan';
$string['report_table_row_no_screenshots'] = 'Keine';
$string['report_table_row_notyetgraded'] = 'Noch nicht bewertet';
$string['report_table_row_overdue'] = 'Überschreitung um {$a}';
$string['report_table_row_view_scan'] = 'Scan ansehen';
$string['report_table_row_view_screenshots'] = 'Ansehen ({$a})';

$string['scan_detected_objects'] = 'Erkannte Objekte';
$string['scan_flagged'] = 'Markiert — Verdächtige Objekte';
$string['scan_no_objects'] = 'Keine verdächtigen Objekte erkannt';
$string['scan_object_book'] = 'Buch';
$string['scan_object_cellphone'] = 'Mobiltelefon';
$string['scan_object_extraperson'] = 'Weitere Person im Raum';
$string['scan_object_laptop'] = 'Laptop (zusätzlich)';
$string['scan_object_remote'] = 'Fernbedienung';
$string['scan_object_tablet'] = 'Tablet';

$string['scanrequired'] = 'Sie müssen den Raumscan abschließen, bevor Sie beginnen.';
$string['selfierequired'] = 'Sie müssen den Selfie-Verifizierungsschritt abschließen, bevor Sie beginnen.';

$string['settings_enableevents'] = 'Verdächtige Ereignisverfolgung aktivieren';
$string['settings_enableevents_desc'] = 'Tab-Wechsel, Kopieren/Einfügen, DevTools und andere verdächtige Ereignisse protokollieren.';
$string['settings_enablescreenshots'] = 'Webcam-Screenshots aktivieren';
$string['settings_enablescreenshots_desc'] = 'Regelmäßige Webcam-Screenshots während Quiz-Versuchen aufnehmen.';
$string['settings_general'] = 'TAG-Prüfungsaufsicht Einstellungen';
$string['settings_maxscreenshots'] = 'Maximale Screenshots pro Versuch';
$string['settings_maxscreenshots_desc'] = 'Maximale Anzahl an Webcam-Screenshots, die pro Quiz-Versuch gespeichert werden.';
$string['settings_retentiondays'] = 'Datenspeicherungsdauer (Tage)';
$string['settings_retentiondays_desc'] = 'Anzahl der Tage, für die Videos und Screenshots vor der automatischen Löschung aufbewahrt werden.';
$string['settings_screenshotinterval'] = 'Screenshot-Intervall (Sekunden)';
$string['settings_screenshotinterval_desc'] = 'Wie oft (in Sekunden) während eines Versuchs ein Webcam-Screenshot aufgenommen wird.';

$string['tagproctoring:viewreport'] = 'TAG-Überwachungsberichte anzeigen';
$string['task_cleanup_files'] = 'TAG-Prüfungsaufsicht: Abgelaufene Scan-Videos und Screenshots löschen';

$string['error_no_video'] = 'Keine Videodatei empfangen';
$string['error_no_selfie'] = 'Kein Selfie empfangen';
$string['error_no_screenshot'] = 'Kein Screenshot empfangen';
$string['error_no_idphoto'] = 'Kein Ausweis-Foto empfangen';

$string['deletescan_title'] = 'Scan-Daten löschen';
$string['deletescan_heading'] = 'Scan-Daten für {$a->user} löschen (Quiz: {$a->quiz})?';
$string['deletescan_description'] = 'Damit werden das Raumscan-Video, das Selfie und das Ausweisfoto dieses Studenten dauerhaft gelöscht. Versuch, Screenshots und Ereignisprotokoll sind nicht betroffen.';
$string['deletescan_confirm'] = 'Ja, Scan-Daten löschen';
$string['deletescan_cancel'] = 'Abbrechen';
$string['deletescan_success'] = 'Scan-Daten gelöscht.';

$string['screenshots_title'] = 'Screenshots — {$a}';
$string['screenshots_inprogress'] = 'In Bearbeitung';
$string['screenshots_attemptinfo'] = 'Versuch gestartet: {$a->started} | Beendet: {$a->finished}';
$string['screenshots_events_heading'] = 'Verdächtige Ereignisse';
$string['screenshots_col_time'] = 'Uhrzeit';
$string['screenshots_col_event'] = 'Ereignis';
$string['screenshots_col_detail'] = 'Details';
$string['screenshots_grid_heading'] = 'Webcam-Screenshots ({$a})';
$string['screenshots_none'] = 'Keine Screenshots für diesen Versuch aufgezeichnet.';

$string['report_no_attempts'] = 'Keine abgeschlossenen Versuche für dieses Quiz gefunden.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Speichert Dateinamen für Raumscan-Video, Selfie und Ausweisfoto sowie KI-Analyseergebnisse.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'Die ID des Studenten, der den Scan durchgeführt hat.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Das Quiz, zu dem dieser Scan gehört.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'Der Versuch, zu dem dieser Scan gehört.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'Das Erstellungsdatum des Datensatzes.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Der Dateiname des Raumscan-Videos.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Der Dateiname des Studenten-Selfies.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Der Dateiname des Ausweisfotos.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'JSON-Liste der vom KI im Scan erkannten Objekte.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Gibt an, ob der KI den Scan als verdächtig markiert hat.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Speichert regelmäßige Webcam-Screenshots während eines Versuchs.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'Die ID des Studenten.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Das Quiz, zu dem dieser Screenshot gehört.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'Der Versuch, zu dem dieser Screenshot gehört.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'Das Datum des Screenshots.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Der Dateiname des Screenshots.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Speichert verdächtige Verhaltensevents, die während eines Versuchs protokolliert wurden.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'Die ID des Studenten, der das Ereignis ausgelöst hat.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Das Quiz, zu dem dieses Ereignis gehört.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'Der Versuch, zu dem dieses Ereignis gehört.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'Das Datum des Ereignisses.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Der Typ des erkannten verdächtigen Ereignisses.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Zusätzliche Details zum Ereignis.';
$string['privacy:path:scans'] = 'Raumscans';
$string['privacy:path:screenshots'] = 'Screenshots';
$string['privacy:path:events'] = 'Ereignisprotokoll';
