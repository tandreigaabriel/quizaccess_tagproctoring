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
 * French language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'J\'accepte l\'enregistrement d\'une courte vidéo de balayage de la salle à des fins d\'intégrité de l\'examen.';
$string['duration'] = 'Durée du balayage de la salle (secondes)';
$string['enableproctor'] = 'Activer la surveillance TAG';

$string['event_cameradisabled'] = 'Caméra désactivée';
$string['event_contextmenu'] = 'Clic droit détecté';
$string['event_copypaste'] = 'Copier/coller détecté';
$string['event_devtools'] = 'DevTools ouvert';
$string['event_faceturn'] = 'Visage détourné';
$string['event_fullscreenexit'] = 'Sortie du plein écran';
$string['event_multiplescreen'] = 'Plusieurs écrans détectés';
$string['event_nocamera'] = 'Caméra indisponible';
$string['event_screenshare'] = 'Partage d\'écran détecté';
$string['event_screensharestop'] = 'Partage d\'écran arrêté';
$string['event_tabswitch'] = 'Changement d\'onglet';
$string['event_visibilityhidden'] = 'Fenêtre masquée';
$string['event_windowblur'] = 'Fenêtre a perdu le focus';

$string['violation_tabswitch'] = 'Changement d\'onglet / Perte de focus';
$string['violation_visibilityhidden'] = 'Onglet masqué';
$string['violation_fullscreenexit'] = 'Plein écran quitté';
$string['violation_screenshare'] = 'Partage d\'écran initié';
$string['violation_multiplescreen'] = 'Plusieurs écrans détectés';
$string['violation_copypaste'] = 'Activité copier/coller';
$string['violation_contextmenu'] = 'Menu clic droit';
$string['violation_devtools'] = 'Outils de développement ouverts';
$string['violation_nocamera'] = 'Caméra indisponible';
$string['violation_noface'] = 'Aucun visage détecté';
$string['violation_faceaway'] = 'Regard détourné de l\'écran';
$string['violation_faceturn'] = 'Visage détourné';
$string['violation_multiplefaces'] = 'Plusieurs visages pendant l\'examen';

$string['idphotorequired'] = 'Vous devez photographier votre pièce d\'identité avant de commencer.';
$string['pluginname'] = 'Surveillance TAG';

$string['report_empty'] = 'Aucun enregistrement de surveillance trouvé pour ce quiz.';
$string['report_export_csv'] = 'Exporter CSV';
$string['report_link_caption'] = 'Rapport de surveillance';
$string['report_link_label'] = 'Rapport de surveillance';

$string['report_risk_factors'] = 'Facteurs de risque';
$string['report_risk_high'] = 'Élevé';
$string['report_risk_low'] = 'Faible';
$string['report_risk_medium'] = 'Moyen';
$string['report_risk_score'] = 'Score de risque : {$a}';
$string['report_risk_suspicious_scan'] = 'Objets suspects lors du balayage de la salle';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Tentative';
$string['report_table_header_delete_scan'] = 'Supprimer le scan';
$string['report_table_header_email'] = 'E-mail';
$string['report_table_header_events'] = 'Événements';
$string['report_table_header_name'] = 'Nom de l\'étudiant';
$string['report_table_header_risk'] = 'Niveau de risque';
$string['report_table_header_room_scan'] = 'Balayage de la salle';
$string['report_table_header_score'] = 'Score';
$string['report_table_header_screenshots'] = 'Captures d\'écran';
$string['report_table_header_time_taken'] = 'Durée';
$string['report_table_header_timefinish'] = 'Terminé';

$string['report_table_row_no_scan'] = 'Aucun scan';
$string['report_table_row_no_screenshots'] = 'Aucune';
$string['report_table_row_notyetgraded'] = 'Pas encore noté';
$string['report_table_row_overdue'] = 'Dépassement de {$a}';
$string['report_table_row_view_scan'] = 'Voir le scan';
$string['report_table_row_view_screenshots'] = 'Voir ({$a})';

$string['scan_detected_objects'] = 'Objets détectés';
$string['scan_flagged'] = 'Signalé — Objets suspects';
$string['scan_no_objects'] = 'Aucun objet suspect détecté';
$string['scan_object_book'] = 'Livre';
$string['scan_object_cellphone'] = 'Téléphone portable';
$string['scan_object_extraperson'] = 'Personne supplémentaire dans la salle';
$string['scan_object_laptop'] = 'Ordinateur portable (supplémentaire)';
$string['scan_object_remote'] = 'Télécommande';
$string['scan_object_tablet'] = 'Tablette';

$string['scanrequired'] = 'Vous devez effectuer le balayage de la salle avant de commencer.';
$string['selfierequired'] = 'Vous devez effectuer l\'étape de vérification selfie avant de commencer.';

$string['settings_enableevents'] = 'Activer le suivi des événements suspects';
$string['settings_enableevents_desc'] = 'Enregistrer les changements d\'onglet, les copier/coller, les DevTools et autres événements suspects.';
$string['settings_enablescreenshots'] = 'Activer les captures d\'écran de la webcam';
$string['settings_enablescreenshots_desc'] = 'Capturer des captures d\'écran périodiques de la webcam pendant les tentatives de quiz.';
$string['settings_general'] = 'Paramètres de surveillance TAG';
$string['settings_maxscreenshots'] = 'Nombre maximum de captures par tentative';
$string['settings_maxscreenshots_desc'] = 'Nombre maximum de captures d\'écran webcam à stocker par tentative de quiz.';
$string['settings_retentiondays'] = 'Période de conservation des données (jours)';
$string['settings_retentiondays_desc'] = 'Nombre de jours de conservation des vidéos de balayage et captures avant suppression automatique.';
$string['settings_screenshotinterval'] = 'Intervalle de capture (secondes)';
$string['settings_screenshotinterval_desc'] = 'Fréquence (en secondes) de capture d\'écran webcam pendant une tentative.';

$string['tagproctoring:viewreport'] = 'Voir les rapports de surveillance TAG';
$string['task_cleanup_files'] = 'Surveillance TAG : supprimer les vidéos et captures expirées';

$string['error_no_video'] = 'Aucun fichier vidéo reçu';
$string['error_no_selfie'] = 'Aucun selfie reçu';
$string['error_no_screenshot'] = 'Aucune capture d\'écran reçue';
$string['error_no_idphoto'] = 'Aucune photo d\'identité reçue';

$string['deletescan_title'] = 'Supprimer les données de scan';
$string['deletescan_heading'] = 'Supprimer les données de scan pour {$a->user} (Quiz : {$a->quiz}) ?';
$string['deletescan_description'] = 'Cela supprimera définitivement la vidéo de balayage, le selfie et la photo d\'identité de cet étudiant pour ce quiz. La tentative, les captures d\'écran et le journal des événements ne seront pas affectés.';
$string['deletescan_confirm'] = 'Oui, supprimer les données de scan';
$string['deletescan_cancel'] = 'Annuler';
$string['deletescan_success'] = 'Données de scan supprimées.';

$string['screenshots_title'] = 'Captures d\'écran — {$a}';
$string['screenshots_inprogress'] = 'En cours';
$string['screenshots_attemptinfo'] = 'Tentative démarrée : {$a->started} | Terminée : {$a->finished}';
$string['screenshots_events_heading'] = 'Événements suspects';
$string['screenshots_col_time'] = 'Heure';
$string['screenshots_col_event'] = 'Événement';
$string['screenshots_col_detail'] = 'Détail';
$string['screenshots_grid_heading'] = 'Captures webcam ({$a})';
$string['screenshots_none'] = 'Aucune capture d\'écran pour cette tentative.';

$string['report_no_attempts'] = 'Aucune tentative terminée trouvée pour ce quiz.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Stocke les noms de fichiers vidéo de balayage, de selfie et de photo d\'identité, ainsi que les résultats de l\'analyse IA.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'L\'identifiant de l\'étudiant ayant effectué le balayage.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Le quiz auquel appartient ce scan.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'La tentative à laquelle appartient ce scan.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'La date de création de l\'enregistrement.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Le nom du fichier vidéo de balayage.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Le nom du fichier selfie de l\'étudiant.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Le nom du fichier photo d\'identité.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'Liste JSON des objets détectés dans le balayage par l\'IA.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Indique si l\'IA a signalé ce scan comme suspect.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Stocke les captures d\'écran périodiques de la webcam pendant une tentative.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'L\'identifiant de l\'étudiant.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Le quiz auquel appartient cette capture.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'La tentative à laquelle appartient cette capture.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'La date de la capture.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Le nom du fichier capture.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Stocke les événements comportementaux suspects enregistrés pendant une tentative.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'L\'identifiant de l\'étudiant ayant déclenché l\'événement.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Le quiz auquel appartient cet événement.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'La tentative à laquelle appartient cet événement.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'La date de l\'événement.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Le type d\'événement suspect détecté.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Détail supplémentaire sur l\'événement.';
$string['privacy:path:scans'] = 'Balayages de salle';
$string['privacy:path:screenshots'] = 'Captures d\'écran';
$string['privacy:path:events'] = 'Journal des événements';
