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
 * Finnish language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Suostun lyhyen huoneentarkastusvideoon tallentamiseen tentti-integriteettitarkoituksiin.';
$string['duration'] = 'Huoneentarkastuksen kesto (sekuntia)';
$string['enableproctor'] = 'Ota TAG-valvonta käyttöön';

$string['event_cameradisabled'] = 'Kamera poistettu käytöstä';
$string['event_contextmenu'] = 'Hiiren oikealla painikkeella napsautettu';
$string['event_copypaste'] = 'Kopiointi/liittäminen havaittu';
$string['event_devtools'] = 'Kehittäjätyökalut avattu';
$string['event_faceturn'] = 'Kasvot käännetty';
$string['event_fullscreenexit'] = 'Koko näytön tila suljettu';
$string['event_multiplescreen'] = 'Useita näyttöjä havaittu';
$string['event_nocamera'] = 'Kamera ei käytettävissä';
$string['event_screenshare'] = 'Näytön jakaminen havaittu';
$string['event_screensharestop'] = 'Näytön jakaminen lopetettu';
$string['event_tabswitch'] = 'Välilehden vaihto';
$string['event_visibilityhidden'] = 'Ikkuna piilotettu';
$string['event_windowblur'] = 'Ikkuna menetti kohdistuksen';

$string['violation_tabswitch'] = 'Välilehden vaihto / Kohdistuksen menetys';
$string['violation_visibilityhidden'] = 'Välilehti piilotettu';
$string['violation_fullscreenexit'] = 'Koko näyttö hylätty';
$string['violation_screenshare'] = 'Näytön jakaminen aloitettu';
$string['violation_multiplescreen'] = 'Useita näyttöjä havaittu';
$string['violation_copypaste'] = 'Kopiointi/liittämistoiminto';
$string['violation_contextmenu'] = 'Hiiren oikean painikkeen valikko';
$string['violation_devtools'] = 'Kehittäjätyökalut avattu';
$string['violation_nocamera'] = 'Kamera ei käytettävissä';
$string['violation_noface'] = 'Kasvoja ei havaittu';
$string['violation_faceaway'] = 'Katse käännetty pois näytöltä';
$string['violation_faceturn'] = 'Kasvot käännetty';
$string['violation_multiplefaces'] = 'Useita kasvoja tentin aikana';

$string['idphotorequired'] = 'Sinun on valokuvattava henkilöllisyystodistuksesi ennen aloittamista.';
$string['pluginname'] = 'TAG-valvonta';

$string['report_empty'] = 'Tälle tentille ei löydy valvontatietoja.';
$string['report_export_csv'] = 'Vie CSV';
$string['report_link_caption'] = 'Valvontaraportti';
$string['report_link_label'] = 'Valvontaraportti';

$string['report_risk_factors'] = 'Riskitekijät';
$string['report_risk_high'] = 'Korkea';
$string['report_risk_low'] = 'Matala';
$string['report_risk_medium'] = 'Keskitaso';
$string['report_risk_score'] = 'Riskipisteet: {$a}';
$string['report_risk_suspicious_scan'] = 'Epäilyttäviä esineitä huoneentarkastuksessa';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Yritys';
$string['report_table_header_delete_scan'] = 'Poista skannaus';
$string['report_table_header_email'] = 'Sähköposti';
$string['report_table_header_events'] = 'Tapahtumat';
$string['report_table_header_name'] = 'Opiskelijan nimi';
$string['report_table_header_risk'] = 'Riskitaso';
$string['report_table_header_room_scan'] = 'Huoneentarkastus';
$string['report_table_header_score'] = 'Pisteet';
$string['report_table_header_screenshots'] = 'Kuvakaappaukset';
$string['report_table_header_time_taken'] = 'Kesto';
$string['report_table_header_timefinish'] = 'Valmistui';

$string['report_table_row_no_scan'] = 'Ei skannausta';
$string['report_table_row_no_screenshots'] = 'Ei mitään';
$string['report_table_row_notyetgraded'] = 'Ei vielä arvosteltu';
$string['report_table_row_overdue'] = 'Myöhässä {$a}';
$string['report_table_row_view_scan'] = 'Näytä skannaus';
$string['report_table_row_view_screenshots'] = 'Näytä ({$a})';

$string['scan_detected_objects'] = 'Havaitut esineet';
$string['scan_flagged'] = 'Merkitty — Epäilyttävät esineet';
$string['scan_no_objects'] = 'Epäilyttäviä esineitä ei havaittu';
$string['scan_object_book'] = 'Kirja';
$string['scan_object_cellphone'] = 'Matkapuhelin';
$string['scan_object_extraperson'] = 'Ylimääräinen henkilö huoneessa';
$string['scan_object_laptop'] = 'Kannettava tietokone (ylimääräinen)';
$string['scan_object_remote'] = 'Kaukosäädin';
$string['scan_object_tablet'] = 'Tabletti';

$string['scanrequired'] = 'Sinun on suoritettava huoneentarkastus ennen aloittamista.';
$string['selfierequired'] = 'Sinun on suoritettava selfie-varmistus ennen aloittamista.';

$string['settings_enableevents'] = 'Ota epäilyttävien tapahtumien seuranta käyttöön';
$string['settings_enableevents_desc'] = 'Kirjaa välilehtien vaihdot, kopiointi/liittäminen, DevTools ja muut epäilyttävät tapahtumat.';
$string['settings_enablescreenshots'] = 'Ota web-kameran kuvakaappaukset käyttöön';
$string['settings_enablescreenshots_desc'] = 'Ota jaksottaisia web-kameran kuvakaappauksia tenttiyrityksen aikana.';
$string['settings_general'] = 'TAG-valvonnan asetukset';
$string['settings_maxscreenshots'] = 'Kuvakaappauksia enintään yritystä kohden';
$string['settings_maxscreenshots_desc'] = 'Suurin tallennettavien web-kameran kuvakaappausten määrä tenttiyrityistä kohden.';
$string['settings_retentiondays'] = 'Tietojen säilytysaika (päivää)';
$string['settings_retentiondays_desc'] = 'Kuinka monta päivää videoita ja kuvakaappauksia säilytetään ennen automaattista poistoa.';
$string['settings_screenshotinterval'] = 'Kuvakaappausväli (sekuntia)';
$string['settings_screenshotinterval_desc'] = 'Kuinka usein (sekunteina) web-kameran kuvakaappaus otetaan yrityksen aikana.';

$string['tagproctoring:viewreport'] = 'Näytä TAG-valvontaraportit';
$string['task_cleanup_files'] = 'TAG-valvonta: poista vanhentuneet skannasvideot ja kuvakaappaukset';

$string['error_no_video'] = 'Videotiedostoa ei vastaanotettu';
$string['error_no_selfie'] = 'Selfieta ei vastaanotettu';
$string['error_no_screenshot'] = 'Kuvakaappausta ei vastaanotettu';
$string['error_no_idphoto'] = 'Henkilöllisyystodistuksen valokuvaa ei vastaanotettu';

$string['deletescan_title'] = 'Poista skannaustiedot';
$string['deletescan_heading'] = 'Poistetaanko {$a->user} skannaustiedot (Tentti: {$a->quiz})?';
$string['deletescan_description'] = 'Tämä poistaa pysyvästi tämän opiskelijan huoneentarkastusvideo, selfie ja henkilöllisyystodistuksen valokuvan. Yritys, kuvakaappaukset ja tapahtumaloki eivät ole vaikuttuneet.';
$string['deletescan_confirm'] = 'Kyllä, poista skannaustiedot';
$string['deletescan_cancel'] = 'Peruuta';
$string['deletescan_success'] = 'Skannaustiedot poistettu.';

$string['screenshots_title'] = 'Kuvakaappaukset — {$a}';
$string['screenshots_inprogress'] = 'Käynnissä';
$string['screenshots_attemptinfo'] = 'Yritys aloitettu: {$a->started} | Valmistui: {$a->finished}';
$string['screenshots_events_heading'] = 'Epäilyttävät tapahtumat';
$string['screenshots_col_time'] = 'Aika';
$string['screenshots_col_event'] = 'Tapahtuma';
$string['screenshots_col_detail'] = 'Tiedot';
$string['screenshots_grid_heading'] = 'Web-kameran kuvakaappaukset ({$a})';
$string['screenshots_none'] = 'Tälle yritykselle ei ole tallennettu kuvakaappauksia.';

$string['report_no_attempts'] = 'Tälle tentille ei löydy valmiita yrityksiä.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Tallentaa huoneentarkastusvideo-, selfie- ja henkilöllisyystodistustiedostojen nimet sekä tekoälyanalyysin tulokset.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'Skannauksen suorittaneen opiskelijan tunnus.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Tentti, johon tämä skannaus kuuluu.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'Yritys, johon tämä skannaus kuuluu.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'Tietueen luontipäivämäärä.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Huoneentarkastusvideotiedoston nimi.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Opiskelijan selfie-tiedoston nimi.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Henkilöllisyystodistuksen valokuvatiedoston nimi.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'JSON-luettelo tekoälyn skannauksessa havaitsemista esineistä.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Ilmaisee, onko tekoäly merkinnyt skannauksen epäilyttäväksi.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Tallentaa jaksottaiset web-kameran kuvakaappaukset yrityksen aikana.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'Opiskelijan tunnus.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Tentti, johon tämä kuvakaappaus kuuluu.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'Yritys, johon tämä kuvakaappaus kuuluu.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'Kuvakaappauksen päivämäärä.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Kuvakaappaustiedoston nimi.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Tallentaa epäilyttävät käyttäytymistapahtumat, jotka on kirjattu yrityksen aikana.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'Tapahtuman laukaisseen opiskelijan tunnus.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Tentti, johon tämä tapahtuma kuuluu.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'Yritys, johon tämä tapahtuma kuuluu.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'Tapahtuman päivämäärä.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Havaitun epäilyttävän tapahtuman tyyppi.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Tapahtuman lisätiedot.';
$string['privacy:path:scans'] = 'Huoneentarkastukset';
$string['privacy:path:screenshots'] = 'Kuvakaappaukset';
$string['privacy:path:events'] = 'Tapahtumalokit';
