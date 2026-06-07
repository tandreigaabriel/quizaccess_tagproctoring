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
 * Hungarian language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Hozzájárulok egy rövid szobaszken videó rögzítéséhez a vizsga integritásának megőrzése érdekében.';
$string['duration'] = 'Szobaszken időtartama (másodperc)';
$string['enableproctor'] = 'TAG felügyelet engedélyezése';

$string['event_cameradisabled'] = 'Kamera letiltva';
$string['event_contextmenu'] = 'Jobb egérgomb észlelve';
$string['event_copypaste'] = 'Másolás/beillesztés észlelve';
$string['event_devtools'] = 'Fejlesztői eszközök megnyitva';
$string['event_faceturn'] = 'Arc elfordítva';
$string['event_fullscreenexit'] = 'Teljes képernyőből kilépett';
$string['event_multiplescreen'] = 'Több monitor észlelve';
$string['event_nocamera'] = 'Kamera nem elérhető';
$string['event_screenshare'] = 'Képernyőmegosztás észlelve';
$string['event_screensharestop'] = 'Képernyőmegosztás leállítva';
$string['event_tabswitch'] = 'Lapváltás';
$string['event_visibilityhidden'] = 'Ablak elrejtve';
$string['event_windowblur'] = 'Az ablak elvesztette a fókuszt';

$string['violation_tabswitch'] = 'Lapváltás / Fókuszvesztés';
$string['violation_visibilityhidden'] = 'Lap elrejtve';
$string['violation_fullscreenexit'] = 'Teljes képernyő elhagyva';
$string['violation_screenshare'] = 'Képernyőmegosztás elindítva';
$string['violation_multiplescreen'] = 'Több monitor észlelve';
$string['violation_copypaste'] = 'Másolás/beillesztés tevékenység';
$string['violation_contextmenu'] = 'Jobb egérgomb menü';
$string['violation_devtools'] = 'Fejlesztői eszközök megnyitva';
$string['violation_nocamera'] = 'Kamera nem elérhető';
$string['violation_noface'] = 'Nem észlelhető arc';
$string['violation_faceaway'] = 'Tekintet elfordítva a képernyőtől';
$string['violation_faceturn'] = 'Arc elfordítva';
$string['violation_multiplefaces'] = 'Több arc a vizsga alatt';

$string['idphotorequired'] = 'A kezdés előtt le kell fényképeznie személyi igazolványát.';
$string['pluginname'] = 'TAG Felügyelet';

$string['report_empty'] = 'Nem találhatók felügyeleti adatok ehhez a teszthez.';
$string['report_export_csv'] = 'CSV exportálása';
$string['report_link_caption'] = 'Felügyeleti jelentés';
$string['report_link_label'] = 'Felügyeleti jelentés';

$string['report_risk_factors'] = 'Kockázati tényezők';
$string['report_risk_high'] = 'Magas';
$string['report_risk_low'] = 'Alacsony';
$string['report_risk_medium'] = 'Közepes';
$string['report_risk_score'] = 'Kockázati pontszám: {$a}';
$string['report_risk_suspicious_scan'] = 'Gyanús tárgyak a szobaszkenben';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Kísérlet';
$string['report_table_header_delete_scan'] = 'Szkennelés törlése';
$string['report_table_header_email'] = 'E-mail';
$string['report_table_header_events'] = 'Események';
$string['report_table_header_name'] = 'Hallgató neve';
$string['report_table_header_risk'] = 'Kockázati szint';
$string['report_table_header_room_scan'] = 'Szobaszken';
$string['report_table_header_score'] = 'Pontszám';
$string['report_table_header_screenshots'] = 'Képernyőképek';
$string['report_table_header_time_taken'] = 'Időtartam';
$string['report_table_header_timefinish'] = 'Befejezve';

$string['report_table_row_no_scan'] = 'Nincs szkennelés';
$string['report_table_row_no_screenshots'] = 'Nincs';
$string['report_table_row_notyetgraded'] = 'Még nem értékelt';
$string['report_table_row_overdue'] = '{$a} késéssel';
$string['report_table_row_view_scan'] = 'Szkennelés megtekintése';
$string['report_table_row_view_screenshots'] = 'Megtekintés ({$a})';

$string['scan_detected_objects'] = 'Észlelt tárgyak';
$string['scan_flagged'] = 'Megjelölve — Gyanús tárgyak';
$string['scan_no_objects'] = 'Nem észlelhetők gyanús tárgyak';
$string['scan_object_book'] = 'Könyv';
$string['scan_object_cellphone'] = 'Mobiltelefon';
$string['scan_object_extraperson'] = 'Extra személy a szobában';
$string['scan_object_laptop'] = 'Laptop (extra)';
$string['scan_object_remote'] = 'Távirányító';
$string['scan_object_tablet'] = 'Tablet';

$string['scanrequired'] = 'A kezdés előtt el kell végeznie a szobaszkenelést.';
$string['selfierequired'] = 'A kezdés előtt el kell végeznie a szelfi-ellenőrzési lépést.';

$string['settings_enableevents'] = 'Gyanús eseménykövetés engedélyezése';
$string['settings_enableevents_desc'] = 'Lapváltások, másolás/beillesztés, DevTools és egyéb gyanús események naplózása.';
$string['settings_enablescreenshots'] = 'Webkamera-képernyőképek engedélyezése';
$string['settings_enablescreenshots_desc'] = 'Időszakos webkamera-képernyőképek rögzítése a tesztkísérletek során.';
$string['settings_general'] = 'TAG Felügyelet beállításai';
$string['settings_maxscreenshots'] = 'Maximális képernyőképek kísérletenként';
$string['settings_maxscreenshots_desc'] = 'Tesztkísérletenként tárolandó webkamera-képernyőképek maximális száma.';
$string['settings_retentiondays'] = 'Adatmegőrzési időszak (napok)';
$string['settings_retentiondays_desc'] = 'Az automatikus törlés előtt megőrzendő videók és képernyőképek napjainak száma.';
$string['settings_screenshotinterval'] = 'Képernyőkép-intervallum (másodperc)';
$string['settings_screenshotinterval_desc'] = 'Milyen gyakran (másodpercben) készül webkamera-képernyőkép kísérlet közben.';

$string['tagproctoring:viewreport'] = 'TAG felügyeleti jelentések megtekintése';
$string['task_cleanup_files'] = 'TAG Felügyelet: lejárt szkenvideók és képernyőképek törlése';

$string['error_no_video'] = 'Nem érkezett videofájl';
$string['error_no_selfie'] = 'Nem érkezett szelfi';
$string['error_no_screenshot'] = 'Nem érkezett képernyőkép';
$string['error_no_idphoto'] = 'Nem érkezett személyi igazolvány fotó';

$string['deletescan_title'] = 'Szkennelési adatok törlése';
$string['deletescan_heading'] = 'Törli {$a->user} szkennelési adatait (Teszt: {$a->quiz})?';
$string['deletescan_description'] = 'Ez véglegesen törli a szobaszken videót, a szelfit és a személyi igazolvány fotót ennél a hallgatónál. A kísérlet, a képernyőképek és az eseménynapló nem érintett.';
$string['deletescan_confirm'] = 'Igen, törölje a szkennelési adatokat';
$string['deletescan_cancel'] = 'Mégse';
$string['deletescan_success'] = 'Szkennelési adatok törölve.';

$string['screenshots_title'] = 'Képernyőképek — {$a}';
$string['screenshots_inprogress'] = 'Folyamatban';
$string['screenshots_attemptinfo'] = 'Kísérlet elindítva: {$a->started} | Befejezve: {$a->finished}';
$string['screenshots_events_heading'] = 'Gyanús események';
$string['screenshots_col_time'] = 'Idő';
$string['screenshots_col_event'] = 'Esemény';
$string['screenshots_col_detail'] = 'Részlet';
$string['screenshots_grid_heading'] = 'Webkamera-képernyőképek ({$a})';
$string['screenshots_none'] = 'Nincs rögzített képernyőkép ehhez a kísérlethez.';

$string['report_no_attempts'] = 'Nem találhatók befejezett kísérletek ehhez a teszthez.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Tárolja a szobaszken videó, szelfi és személyi igazolvány fájlneveit, valamint a mesterséges intelligencia elemzési eredményeit.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'A szkennelést végző hallgató azonosítója.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'A teszt, amelyhez ez a szken tartozik.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'A kísérlet, amelyhez ez a szken tartozik.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'A rekord létrehozásának dátuma.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'A szobaszken videó fájlneve.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'A hallgató szelfijének fájlneve.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'A személyi igazolvány fotó fájlneve.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'A mesterséges intelligencia által a szkenben észlelt objektumok JSON-listája.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Jelzi, hogy a mesterséges intelligencia gyanúsnak jelölte-e a szkennelést.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Kísérlet során készített időszakos webkamera-képernyőképeket tárol.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'A hallgató azonosítója.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'A teszt, amelyhez ez a képernyőkép tartozik.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'A kísérlet, amelyhez ez a képernyőkép tartozik.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'A képernyőkép készítésének dátuma.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'A képernyőkép fájlneve.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Kísérlet során naplózott gyanús viselkedési eseményeket tárol.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'Az eseményt kiváltó hallgató azonosítója.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'A teszt, amelyhez ez az esemény tartozik.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'A kísérlet, amelyhez ez az esemény tartozik.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'Az esemény dátuma.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Az észlelt gyanús esemény típusa.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Az esemény további részletei.';
$string['privacy:path:scans'] = 'Szobaszkenek';
$string['privacy:path:screenshots'] = 'Képernyőképek';
$string['privacy:path:events'] = 'Eseménynapló';
