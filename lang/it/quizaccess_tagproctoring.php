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
 * Italian language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Acconsento alla registrazione di un breve video di scansione della stanza per garantire l\'integrità dell\'esame.';
$string['duration'] = 'Durata della scansione della stanza (secondi)';
$string['enableproctor'] = 'Abilita sorveglianza TAG';

$string['event_cameradisabled'] = 'Fotocamera disabilitata';
$string['event_contextmenu'] = 'Clic destro rilevato';
$string['event_copypaste'] = 'Copia/incolla rilevato';
$string['event_devtools'] = 'DevTools aperto';
$string['event_faceturn'] = 'Viso girato';
$string['event_fullscreenexit'] = 'Uscita dalla modalità a schermo intero';
$string['event_multiplescreen'] = 'Più schermi rilevati';
$string['event_nocamera'] = 'Fotocamera non disponibile';
$string['event_screenshare'] = 'Condivisione schermo rilevata';
$string['event_screensharestop'] = 'Condivisione schermo interrotta';
$string['event_tabswitch'] = 'Cambio scheda';
$string['event_visibilityhidden'] = 'Finestra nascosta';
$string['event_windowblur'] = 'Finestra ha perso il focus';

$string['violation_tabswitch'] = 'Cambio scheda / Perdita di focus';
$string['violation_visibilityhidden'] = 'Scheda nascosta';
$string['violation_fullscreenexit'] = 'Schermo intero abbandonato';
$string['violation_screenshare'] = 'Condivisione schermo avviata';
$string['violation_multiplescreen'] = 'Più schermi rilevati';
$string['violation_copypaste'] = 'Attività copia/incolla';
$string['violation_contextmenu'] = 'Menu clic destro';
$string['violation_devtools'] = 'Strumenti per sviluppatori aperti';
$string['violation_nocamera'] = 'Fotocamera non disponibile';
$string['violation_noface'] = 'Nessun viso rilevato';
$string['violation_faceaway'] = 'Sguardo lontano dallo schermo';
$string['violation_faceturn'] = 'Viso girato';
$string['violation_multiplefaces'] = 'Più visi durante l\'esame';

$string['idphotorequired'] = 'È necessario fotografare il documento d\'identità prima di iniziare.';
$string['pluginname'] = 'Sorveglianza TAG';

$string['report_empty'] = 'Nessun record di sorveglianza trovato per questo quiz.';
$string['report_export_csv'] = 'Esporta CSV';
$string['report_link_caption'] = 'Rapporto di sorveglianza';
$string['report_link_label'] = 'Rapporto di sorveglianza';

$string['report_risk_factors'] = 'Fattori di rischio';
$string['report_risk_high'] = 'Alto';
$string['report_risk_low'] = 'Basso';
$string['report_risk_medium'] = 'Medio';
$string['report_risk_score'] = 'Punteggio di rischio: {$a}';
$string['report_risk_suspicious_scan'] = 'Oggetti sospetti nella scansione della stanza';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Tentativo';
$string['report_table_header_delete_scan'] = 'Elimina scansione';
$string['report_table_header_email'] = 'E-mail';
$string['report_table_header_events'] = 'Eventi';
$string['report_table_header_name'] = 'Nome studente';
$string['report_table_header_risk'] = 'Livello di rischio';
$string['report_table_header_room_scan'] = 'Scansione stanza';
$string['report_table_header_score'] = 'Punteggio';
$string['report_table_header_screenshots'] = 'Screenshot';
$string['report_table_header_time_taken'] = 'Durata';
$string['report_table_header_timefinish'] = 'Terminato';

$string['report_table_row_no_scan'] = 'Nessuna scansione';
$string['report_table_row_no_screenshots'] = 'Nessuno';
$string['report_table_row_notyetgraded'] = 'Non ancora valutato';
$string['report_table_row_overdue'] = 'Ritardo di {$a}';
$string['report_table_row_view_scan'] = 'Visualizza scansione';
$string['report_table_row_view_screenshots'] = 'Visualizza ({$a})';

$string['scan_detected_objects'] = 'Oggetti rilevati';
$string['scan_flagged'] = 'Segnalato — Oggetti sospetti';
$string['scan_no_objects'] = 'Nessun oggetto sospetto rilevato';
$string['scan_object_book'] = 'Libro';
$string['scan_object_cellphone'] = 'Telefono cellulare';
$string['scan_object_extraperson'] = 'Persona extra nella stanza';
$string['scan_object_laptop'] = 'Laptop (extra)';
$string['scan_object_remote'] = 'Telecomando';
$string['scan_object_tablet'] = 'Tablet';

$string['scanrequired'] = 'È necessario completare la scansione della stanza prima di iniziare.';
$string['selfierequired'] = 'È necessario completare il passaggio di verifica selfie prima di iniziare.';

$string['settings_enableevents'] = 'Abilita tracciamento eventi sospetti';
$string['settings_enableevents_desc'] = 'Registra cambi di scheda, copia/incolla, DevTools e altri eventi sospetti.';
$string['settings_enablescreenshots'] = 'Abilita screenshot webcam';
$string['settings_enablescreenshots_desc'] = 'Cattura screenshot periodici dalla webcam durante i tentativi di quiz.';
$string['settings_general'] = 'Impostazioni sorveglianza TAG';
$string['settings_maxscreenshots'] = 'Numero massimo di screenshot per tentativo';
$string['settings_maxscreenshots_desc'] = 'Numero massimo di screenshot webcam da memorizzare per tentativo di quiz.';
$string['settings_retentiondays'] = 'Periodo di conservazione dati (giorni)';
$string['settings_retentiondays_desc'] = 'Numero di giorni per conservare i video e gli screenshot prima dell\'eliminazione automatica.';
$string['settings_screenshotinterval'] = 'Intervallo screenshot (secondi)';
$string['settings_screenshotinterval_desc'] = 'Con quale frequenza (in secondi) viene acquisito uno screenshot webcam durante un tentativo.';

$string['tagproctoring:viewreport'] = 'Visualizza rapporti sorveglianza TAG';
$string['task_cleanup_files'] = 'Sorveglianza TAG: elimina video e screenshot scaduti';

$string['error_no_video'] = 'Nessun file video ricevuto';
$string['error_no_selfie'] = 'Nessun selfie ricevuto';
$string['error_no_screenshot'] = 'Nessuno screenshot ricevuto';
$string['error_no_idphoto'] = 'Nessuna foto documento ricevuta';

$string['deletescan_title'] = 'Elimina dati scansione';
$string['deletescan_heading'] = 'Eliminare i dati di scansione per {$a->user} (Quiz: {$a->quiz})?';
$string['deletescan_description'] = 'Verranno eliminati definitivamente il video di scansione, il selfie e la foto del documento per questo studente. Il tentativo, gli screenshot e il registro eventi non saranno interessati.';
$string['deletescan_confirm'] = 'Sì, elimina i dati di scansione';
$string['deletescan_cancel'] = 'Annulla';
$string['deletescan_success'] = 'Dati di scansione eliminati.';

$string['screenshots_title'] = 'Screenshot — {$a}';
$string['screenshots_inprogress'] = 'In corso';
$string['screenshots_attemptinfo'] = 'Tentativo avviato: {$a->started} | Terminato: {$a->finished}';
$string['screenshots_events_heading'] = 'Eventi sospetti';
$string['screenshots_col_time'] = 'Ora';
$string['screenshots_col_event'] = 'Evento';
$string['screenshots_col_detail'] = 'Dettaglio';
$string['screenshots_grid_heading'] = 'Screenshot webcam ({$a})';
$string['screenshots_none'] = 'Nessuno screenshot acquisito per questo tentativo.';

$string['report_no_attempts'] = 'Nessun tentativo completato trovato per questo quiz.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Memorizza i nomi dei file video di scansione, selfie e foto documento, oltre ai risultati dell\'analisi IA.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'L\'ID dello studente che ha effettuato la scansione.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Il quiz a cui appartiene questa scansione.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'Il tentativo a cui appartiene questa scansione.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'La data di creazione del record.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Il nome del file video di scansione.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Il nome del file selfie dello studente.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Il nome del file foto documento.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'Elenco JSON degli oggetti rilevati dall\'IA nella scansione.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Indica se l\'IA ha segnalato la scansione come sospetta.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Memorizza screenshot periodici della webcam durante un tentativo.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'L\'ID dello studente.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Il quiz a cui appartiene questo screenshot.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'Il tentativo a cui appartiene questo screenshot.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'La data dello screenshot.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Il nome del file screenshot.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Memorizza eventi comportamentali sospetti registrati durante un tentativo.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'L\'ID dello studente che ha generato l\'evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Il quiz a cui appartiene questo evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'Il tentativo a cui appartiene questo evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'La data dell\'evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Il tipo di evento sospetto rilevato.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Dettaglio aggiuntivo sull\'evento.';
$string['privacy:path:scans'] = 'Scansioni stanza';
$string['privacy:path:screenshots'] = 'Screenshot';
$string['privacy:path:events'] = 'Registro eventi';
