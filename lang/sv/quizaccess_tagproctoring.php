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
 * Swedish language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Jag samtycker till inspelning av en kort rumsgenomsökning för att säkerställa tentans integritet.';
$string['duration'] = 'Rumsgenomsökningens längd (sekunder)';
$string['enableproctor'] = 'Aktivera TAG-övervakning';

$string['event_cameradisabled'] = 'Kamera inaktiverad';
$string['event_contextmenu'] = 'Högerklick upptäckt';
$string['event_copypaste'] = 'Kopiering/inklistring upptäckt';
$string['event_devtools'] = 'Utvecklarverktyg öppnade';
$string['event_faceturn'] = 'Ansikte bortvändt';
$string['event_fullscreenexit'] = 'Lämnade helskärmsläge';
$string['event_multiplescreen'] = 'Flera skärmar upptäckta';
$string['event_nocamera'] = 'Kamera ej tillgänglig';
$string['event_screenshare'] = 'Skärmdelning upptäckt';
$string['event_screensharestop'] = 'Skärmdelning avslutad';
$string['event_tabswitch'] = 'Flikbyte';
$string['event_visibilityhidden'] = 'Fönster dolt';
$string['event_windowblur'] = 'Fönstret förlorade fokus';

$string['violation_tabswitch'] = 'Flikbyte / Fokusförlust';
$string['violation_visibilityhidden'] = 'Flik dold';
$string['violation_fullscreenexit'] = 'Helskärm avslutad';
$string['violation_screenshare'] = 'Skärmdelning startad';
$string['violation_multiplescreen'] = 'Flera skärmar upptäckta';
$string['violation_copypaste'] = 'Kopiering/inklistringsaktivitet';
$string['violation_contextmenu'] = 'Högerklicksmeny';
$string['violation_devtools'] = 'Utvecklarverktyg öppnade';
$string['violation_nocamera'] = 'Kamera ej tillgänglig';
$string['violation_noface'] = 'Inget ansikte upptäckt';
$string['violation_faceaway'] = 'Blicken bortvänd från skärmen';
$string['violation_faceturn'] = 'Ansikte bortvändt';
$string['violation_multiplefaces'] = 'Flera ansikten under tentan';

$string['idphotorequired'] = 'Du måste fotografera din legitimation innan du börjar.';
$string['pluginname'] = 'TAG-övervakning';

$string['report_empty'] = 'Inga övervakningsposter hittades för detta quiz.';
$string['report_export_csv'] = 'Exportera CSV';
$string['report_link_caption'] = 'Övervakningsrapport';
$string['report_link_label'] = 'Övervakningsrapport';

$string['report_risk_factors'] = 'Riskfaktorer';
$string['report_risk_high'] = 'Hög';
$string['report_risk_low'] = 'Låg';
$string['report_risk_medium'] = 'Medium';
$string['report_risk_score'] = 'Riskpoäng: {$a}';
$string['report_risk_suspicious_scan'] = 'Misstänkta föremål i rumsgenomsökningen';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Försök';
$string['report_table_header_delete_scan'] = 'Ta bort genomsökning';
$string['report_table_header_email'] = 'E-post';
$string['report_table_header_events'] = 'Händelser';
$string['report_table_header_name'] = 'Studentens namn';
$string['report_table_header_risk'] = 'Risknivå';
$string['report_table_header_room_scan'] = 'Rumsgenomsökning';
$string['report_table_header_score'] = 'Poäng';
$string['report_table_header_screenshots'] = 'Skärmdumpar';
$string['report_table_header_time_taken'] = 'Tid';
$string['report_table_header_timefinish'] = 'Avslutad';

$string['report_table_row_no_scan'] = 'Ingen genomsökning';
$string['report_table_row_no_screenshots'] = 'Inga';
$string['report_table_row_notyetgraded'] = 'Inte betygsatt än';
$string['report_table_row_overdue'] = 'Försenat med {$a}';
$string['report_table_row_view_scan'] = 'Visa genomsökning';
$string['report_table_row_view_screenshots'] = 'Visa ({$a})';

$string['scan_detected_objects'] = 'Upptäckta föremål';
$string['scan_flagged'] = 'Flaggad — Misstänkta föremål';
$string['scan_no_objects'] = 'Inga misstänkta föremål upptäckta';
$string['scan_object_book'] = 'Bok';
$string['scan_object_cellphone'] = 'Mobiltelefon';
$string['scan_object_extraperson'] = 'Extra person i rummet';
$string['scan_object_laptop'] = 'Bärbar dator (extra)';
$string['scan_object_remote'] = 'Fjärrkontroll';
$string['scan_object_tablet'] = 'Surfplatta';

$string['scanrequired'] = 'Du måste slutföra rumsgenomsökningen innan du börjar.';
$string['selfierequired'] = 'Du måste slutföra selfie-verifieringssteget innan du börjar.';

$string['settings_enableevents'] = 'Aktivera spårning av misstänkta händelser';
$string['settings_enableevents_desc'] = 'Logga flikbyten, kopiering/inklistring, DevTools och andra misstänkta händelser.';
$string['settings_enablescreenshots'] = 'Aktivera webbkameraskärmdumpar';
$string['settings_enablescreenshots_desc'] = 'Ta periodiska webbkameraskärmdumpar under quizförsök.';
$string['settings_general'] = 'Inställningar för TAG-övervakning';
$string['settings_maxscreenshots'] = 'Max skärmdumpar per försök';
$string['settings_maxscreenshots_desc'] = 'Maximalt antal webbkameraskärmdumpar att lagra per quizförsök.';
$string['settings_retentiondays'] = 'Datalagringperiod (dagar)';
$string['settings_retentiondays_desc'] = 'Antal dagar att behålla videor och skärmdumpar innan automatisk borttagning.';
$string['settings_screenshotinterval'] = 'Skärmdumpsintervall (sekunder)';
$string['settings_screenshotinterval_desc'] = 'Hur ofta (i sekunder) en webbkameraskärmdump tas under ett försök.';

$string['tagproctoring:viewreport'] = 'Visa TAG-övervakningsrapporter';
$string['task_cleanup_files'] = 'TAG-övervakning: ta bort utgångna genomsökningsvideor och skärmdumpar';

$string['error_no_video'] = 'Ingen videofil mottagen';
$string['error_no_selfie'] = 'Ingen selfie mottagen';
$string['error_no_screenshot'] = 'Ingen skärmdump mottagen';
$string['error_no_idphoto'] = 'Inget legitimationsfoto mottaget';

$string['deletescan_title'] = 'Ta bort genomsökningsdata';
$string['deletescan_heading'] = 'Ta bort genomsökningsdata för {$a->user} (Quiz: {$a->quiz})?';
$string['deletescan_description'] = 'Detta tar permanent bort rumsgenomsökningsvideo, selfie och legitimationsfoton för denna student. Försöket, skärmdumparna och händelseloggen påverkas inte.';
$string['deletescan_confirm'] = 'Ja, ta bort genomsökningsdata';
$string['deletescan_cancel'] = 'Avbryt';
$string['deletescan_success'] = 'Genomsökningsdata borttagen.';

$string['screenshots_title'] = 'Skärmdumpar — {$a}';
$string['screenshots_inprogress'] = 'Pågår';
$string['screenshots_attemptinfo'] = 'Försök startat: {$a->started} | Avslutat: {$a->finished}';
$string['screenshots_events_heading'] = 'Misstänkta händelser';
$string['screenshots_col_time'] = 'Tid';
$string['screenshots_col_event'] = 'Händelse';
$string['screenshots_col_detail'] = 'Detalj';
$string['screenshots_grid_heading'] = 'Webbkameraskärmdumpar ({$a})';
$string['screenshots_none'] = 'Inga skärmdumpar tagna för detta försök.';

$string['report_no_attempts'] = 'Inga avslutade försök hittades för detta quiz.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Lagrar filnamn för rumsgenomsökningsvideo, selfie och legitimationsfoto samt AI-analysresultat.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'ID för studenten som utförde genomsökningen.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Quizet som denna genomsökning tillhör.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'Försöket som denna genomsökning tillhör.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'Datum då posten skapades.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Filnamnet på rumsgenomsökningsvideon.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Filnamnet på studentens selfie.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Filnamnet på legitimationsfotot.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'JSON-lista över föremål som AI:n upptäckte i genomsökningen.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Anger om AI:n flaggade genomsökningen som misstänkt.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Lagrar periodiska webbkameraskärmdumpar under ett försök.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'Studentens ID.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Quizet som denna skärmdump tillhör.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'Försöket som denna skärmdump tillhör.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'Datum för skärmdumpen.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Filnamnet på skärmdumpen.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Lagrar misstänkta beteendehändelser loggade under ett försök.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'ID för studenten som utlöste händelsen.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Quizet som denna händelse tillhör.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'Försöket som denna händelse tillhör.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'Datum för händelsen.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Typen av misstänkt händelse som upptäcktes.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Ytterligare detalj om händelsen.';
$string['privacy:path:scans'] = 'Rumsgenomsökningar';
$string['privacy:path:screenshots'] = 'Skärmdumpar';
$string['privacy:path:events'] = 'Händelselogg';
