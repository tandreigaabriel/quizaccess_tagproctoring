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
 * Spanish language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Acepto la grabación de un breve vídeo de escaneo de la habitación con fines de integridad del examen.';
$string['duration'] = 'Duración del escaneo de la habitación (segundos)';
$string['enableproctor'] = 'Activar supervisión TAG';

$string['event_cameradisabled'] = 'Cámara desactivada';
$string['event_contextmenu'] = 'Clic derecho detectado';
$string['event_copypaste'] = 'Copiar/pegar detectado';
$string['event_devtools'] = 'DevTools abierto';
$string['event_faceturn'] = 'Cara girada';
$string['event_fullscreenexit'] = 'Salida de pantalla completa';
$string['event_multiplescreen'] = 'Múltiples pantallas detectadas';
$string['event_nocamera'] = 'Cámara no disponible';
$string['event_screenshare'] = 'Compartición de pantalla detectada';
$string['event_screensharestop'] = 'Compartición de pantalla detenida';
$string['event_tabswitch'] = 'Cambio de pestaña';
$string['event_visibilityhidden'] = 'Ventana oculta';
$string['event_windowblur'] = 'Ventana perdió el foco';

$string['violation_tabswitch'] = 'Cambio de pestaña / Pérdida de foco';
$string['violation_visibilityhidden'] = 'Pestaña oculta';
$string['violation_fullscreenexit'] = 'Pantalla completa abandonada';
$string['violation_screenshare'] = 'Compartición de pantalla iniciada';
$string['violation_multiplescreen'] = 'Múltiples pantallas detectadas';
$string['violation_copypaste'] = 'Actividad de copiar/pegar';
$string['violation_contextmenu'] = 'Menú clic derecho';
$string['violation_devtools'] = 'Herramientas de desarrollador abiertas';
$string['violation_nocamera'] = 'Cámara no disponible';
$string['violation_noface'] = 'No se detectó ningún rostro';
$string['violation_faceaway'] = 'Mirando lejos de la pantalla';
$string['violation_faceturn'] = 'Cara girada';
$string['violation_multiplefaces'] = 'Múltiples rostros durante el examen';

$string['idphotorequired'] = 'Debe fotografiar su documento de identidad antes de comenzar.';
$string['pluginname'] = 'Supervisión TAG';

$string['report_empty'] = 'No se encontraron registros de supervisión para este cuestionario.';
$string['report_export_csv'] = 'Exportar CSV';
$string['report_link_caption'] = 'Informe de supervisión';
$string['report_link_label'] = 'Informe de supervisión';

$string['report_risk_factors'] = 'Factores de riesgo';
$string['report_risk_high'] = 'Alto';
$string['report_risk_low'] = 'Bajo';
$string['report_risk_medium'] = 'Medio';
$string['report_risk_score'] = 'Puntuación de riesgo: {$a}';
$string['report_risk_suspicious_scan'] = 'Objetos sospechosos en el escaneo de la habitación';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Intento';
$string['report_table_header_delete_scan'] = 'Eliminar escaneo';
$string['report_table_header_email'] = 'Correo electrónico';
$string['report_table_header_events'] = 'Eventos';
$string['report_table_header_name'] = 'Nombre del estudiante';
$string['report_table_header_risk'] = 'Nivel de riesgo';
$string['report_table_header_room_scan'] = 'Escaneo de habitación';
$string['report_table_header_score'] = 'Puntuación';
$string['report_table_header_screenshots'] = 'Capturas de pantalla';
$string['report_table_header_time_taken'] = 'Tiempo empleado';
$string['report_table_header_timefinish'] = 'Finalizado';

$string['report_table_row_no_scan'] = 'Sin escaneo';
$string['report_table_row_no_screenshots'] = 'Ninguna';
$string['report_table_row_notyetgraded'] = 'Sin calificar aún';
$string['report_table_row_overdue'] = 'Retraso de {$a}';
$string['report_table_row_view_scan'] = 'Ver escaneo';
$string['report_table_row_view_screenshots'] = 'Ver ({$a})';

$string['scan_detected_objects'] = 'Objetos detectados';
$string['scan_flagged'] = 'Marcado — Objetos sospechosos';
$string['scan_no_objects'] = 'No se detectaron objetos sospechosos';
$string['scan_object_book'] = 'Libro';
$string['scan_object_cellphone'] = 'Teléfono móvil';
$string['scan_object_extraperson'] = 'Persona extra en la habitación';
$string['scan_object_laptop'] = 'Portátil (extra)';
$string['scan_object_remote'] = 'Control remoto';
$string['scan_object_tablet'] = 'Tableta';

$string['scanrequired'] = 'Debe completar el escaneo de la habitación antes de comenzar.';
$string['selfierequired'] = 'Debe completar el paso de verificación de selfie antes de comenzar.';

$string['settings_enableevents'] = 'Activar seguimiento de eventos sospechosos';
$string['settings_enableevents_desc'] = 'Registrar cambios de pestaña, copiar/pegar, DevTools y otros eventos sospechosos.';
$string['settings_enablescreenshots'] = 'Activar capturas de webcam';
$string['settings_enablescreenshots_desc'] = 'Capturar capturas periódicas de la webcam durante los intentos de cuestionario.';
$string['settings_general'] = 'Configuración de supervisión TAG';
$string['settings_maxscreenshots'] = 'Máximo de capturas por intento';
$string['settings_maxscreenshots_desc'] = 'Número máximo de capturas de webcam a almacenar por intento de cuestionario.';
$string['settings_retentiondays'] = 'Período de retención de datos (días)';
$string['settings_retentiondays_desc'] = 'Número de días para conservar los vídeos y capturas antes de la eliminación automática.';
$string['settings_screenshotinterval'] = 'Intervalo de captura (segundos)';
$string['settings_screenshotinterval_desc'] = 'Con qué frecuencia (en segundos) se captura una imagen de la webcam durante un intento.';

$string['tagproctoring:viewreport'] = 'Ver informes de supervisión TAG';
$string['task_cleanup_files'] = 'Supervisión TAG: eliminar vídeos y capturas caducados';

$string['error_no_video'] = 'No se recibió ningún archivo de vídeo';
$string['error_no_selfie'] = 'No se recibió ningún selfie';
$string['error_no_screenshot'] = 'No se recibió ninguna captura de pantalla';
$string['error_no_idphoto'] = 'No se recibió ninguna foto de documento';

$string['deletescan_title'] = 'Eliminar datos de escaneo';
$string['deletescan_heading'] = '¿Eliminar los datos de escaneo de {$a->user} (Cuestionario: {$a->quiz})?';
$string['deletescan_description'] = 'Esto eliminará permanentemente el vídeo de escaneo, el selfie y la foto del documento de este estudiante. El intento, las capturas y el registro de eventos no se verán afectados.';
$string['deletescan_confirm'] = 'Sí, eliminar datos de escaneo';
$string['deletescan_cancel'] = 'Cancelar';
$string['deletescan_success'] = 'Datos de escaneo eliminados.';

$string['screenshots_title'] = 'Capturas de pantalla — {$a}';
$string['screenshots_inprogress'] = 'En curso';
$string['screenshots_attemptinfo'] = 'Intento iniciado: {$a->started} | Finalizado: {$a->finished}';
$string['screenshots_events_heading'] = 'Eventos sospechosos';
$string['screenshots_col_time'] = 'Hora';
$string['screenshots_col_event'] = 'Evento';
$string['screenshots_col_detail'] = 'Detalle';
$string['screenshots_grid_heading'] = 'Capturas de webcam ({$a})';
$string['screenshots_none'] = 'No se capturaron imágenes para este intento.';

$string['report_no_attempts'] = 'No se encontraron intentos completados para este cuestionario.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Almacena los nombres de archivo del vídeo de escaneo, selfie y foto de documento, además de los resultados del análisis de IA.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'El ID del estudiante que realizó el escaneo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'El cuestionario al que pertenece este escaneo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'El intento al que pertenece este escaneo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'La fecha de creación del registro.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'El nombre del archivo de vídeo de escaneo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'El nombre del archivo selfie del estudiante.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'El nombre del archivo de foto de documento.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'Lista JSON de objetos detectados por la IA en el escaneo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Indica si la IA marcó el escaneo como sospechoso.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Almacena capturas periódicas de la webcam durante un intento.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'El ID del estudiante.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'El cuestionario al que pertenece esta captura.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'El intento al que pertenece esta captura.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'La fecha de la captura.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'El nombre del archivo de captura.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Almacena eventos de comportamiento sospechoso registrados durante un intento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'El ID del estudiante que generó el evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'El cuestionario al que pertenece este evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'El intento al que pertenece este evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'La fecha del evento.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'El tipo de evento sospechoso detectado.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Detalle adicional sobre el evento.';
$string['privacy:path:scans'] = 'Escaneos de habitación';
$string['privacy:path:screenshots'] = 'Capturas de pantalla';
$string['privacy:path:events'] = 'Registro de eventos';
