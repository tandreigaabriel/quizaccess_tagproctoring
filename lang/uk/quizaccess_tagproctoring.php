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
 * Ukrainian language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'Я погоджуюся на запис короткого відео огляду кімнати для забезпечення чесності іспиту.';
$string['duration'] = 'Тривалість огляду кімнати (секунди)';
$string['enableproctor'] = 'Увімкнути нагляд TAG';

$string['event_cameradisabled'] = 'Камеру вимкнено';
$string['event_contextmenu'] = 'Виявлено правий клік';
$string['event_copypaste'] = 'Виявлено копіювання/вставку';
$string['event_devtools'] = 'Відкрито інструменти розробника';
$string['event_faceturn'] = 'Обличчя відвернуто';
$string['event_fullscreenexit'] = 'Вихід з повноекранного режиму';
$string['event_multiplescreen'] = 'Виявлено кілька моніторів';
$string['event_nocamera'] = 'Камера недоступна';
$string['event_screenshare'] = 'Виявлено спільний доступ до екрана';
$string['event_screensharestop'] = 'Спільний доступ до екрана зупинено';
$string['event_tabswitch'] = 'Перемикання вкладки';
$string['event_visibilityhidden'] = 'Вікно приховано';
$string['event_windowblur'] = 'Вікно втратило фокус';

$string['violation_tabswitch'] = 'Перемикання вкладки / Втрата фокусу';
$string['violation_visibilityhidden'] = 'Вкладку приховано';
$string['violation_fullscreenexit'] = 'Повноекранний режим вимкнено';
$string['violation_screenshare'] = 'Розпочато спільний доступ до екрана';
$string['violation_multiplescreen'] = 'Виявлено кілька моніторів';
$string['violation_copypaste'] = 'Активність копіювання/вставки';
$string['violation_contextmenu'] = 'Контекстне меню (правий клік)';
$string['violation_devtools'] = 'Відкрито інструменти розробника';
$string['violation_nocamera'] = 'Камера недоступна';
$string['violation_noface'] = 'Обличчя не виявлено';
$string['violation_faceaway'] = 'Погляд відведений від екрана';
$string['violation_faceturn'] = 'Обличчя відвернуто';
$string['violation_multiplefaces'] = 'Кілька облич під час іспиту';

$string['idphotorequired'] = 'Перед початком необхідно сфотографувати документ, що посвідчує особу.';
$string['pluginname'] = 'Нагляд TAG';

$string['report_empty'] = 'Не знайдено записів нагляду для цього тесту.';
$string['report_export_csv'] = 'Експортувати CSV';
$string['report_link_caption'] = 'Звіт про нагляд';
$string['report_link_label'] = 'Звіт про нагляд';

$string['report_risk_factors'] = 'Фактори ризику';
$string['report_risk_high'] = 'Високий';
$string['report_risk_low'] = 'Низький';
$string['report_risk_medium'] = 'Середній';
$string['report_risk_score'] = 'Оцінка ризику: {$a}';
$string['report_risk_suspicious_scan'] = 'Підозрілі об\'єкти під час огляду кімнати';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Спроба';
$string['report_table_header_delete_scan'] = 'Видалити сканування';
$string['report_table_header_email'] = 'Електронна пошта';
$string['report_table_header_events'] = 'Події';
$string['report_table_header_name'] = 'Ім\'я студента';
$string['report_table_header_risk'] = 'Рівень ризику';
$string['report_table_header_room_scan'] = 'Огляд кімнати';
$string['report_table_header_score'] = 'Оцінка';
$string['report_table_header_screenshots'] = 'Знімки екрана';
$string['report_table_header_time_taken'] = 'Тривалість';
$string['report_table_header_timefinish'] = 'Завершено';

$string['report_table_row_no_scan'] = 'Немає сканування';
$string['report_table_row_no_screenshots'] = 'Немає';
$string['report_table_row_notyetgraded'] = 'Ще не оцінено';
$string['report_table_row_overdue'] = 'Затримка на {$a}';
$string['report_table_row_view_scan'] = 'Переглянути сканування';
$string['report_table_row_view_screenshots'] = 'Переглянути ({$a})';

$string['scan_detected_objects'] = 'Виявлені об\'єкти';
$string['scan_flagged'] = 'Позначено — Підозрілі об\'єкти';
$string['scan_no_objects'] = 'Підозрілих об\'єктів не виявлено';
$string['scan_object_book'] = 'Книга';
$string['scan_object_cellphone'] = 'Мобільний телефон';
$string['scan_object_extraperson'] = 'Стороння особа в кімнаті';
$string['scan_object_laptop'] = 'Ноутбук (додатковий)';
$string['scan_object_remote'] = 'Пульт дистанційного керування';
$string['scan_object_tablet'] = 'Планшет';

$string['scanrequired'] = 'Перед початком необхідно завершити огляд кімнати.';
$string['selfierequired'] = 'Перед початком необхідно завершити крок перевірки селфі.';

$string['settings_enableevents'] = 'Увімкнути відстеження підозрілих подій';
$string['settings_enableevents_desc'] = 'Реєструвати перемикання вкладок, копіювання/вставку, DevTools та інші підозрілі події.';
$string['settings_enablescreenshots'] = 'Увімкнути знімки з веб-камери';
$string['settings_enablescreenshots_desc'] = 'Робити періодичні знімки з веб-камери під час спроб тесту.';
$string['settings_general'] = 'Налаштування нагляду TAG';
$string['settings_maxscreenshots'] = 'Максимальна кількість знімків на спробу';
$string['settings_maxscreenshots_desc'] = 'Максимальна кількість знімків веб-камери для зберігання за одну спробу тесту.';
$string['settings_retentiondays'] = 'Термін зберігання даних (дні)';
$string['settings_retentiondays_desc'] = 'Кількість днів зберігання відео та знімків перед автоматичним видаленням.';
$string['settings_screenshotinterval'] = 'Інтервал знімків (секунди)';
$string['settings_screenshotinterval_desc'] = 'Як часто (в секундах) робиться знімок веб-камери під час спроби.';

$string['tagproctoring:viewreport'] = 'Переглядати звіти нагляду TAG';
$string['task_cleanup_files'] = 'Нагляд TAG: видалення застарілих відео та знімків';

$string['error_no_video'] = 'Відеофайл не отримано';
$string['error_no_selfie'] = 'Селфі не отримано';
$string['error_no_screenshot'] = 'Знімок екрана не отримано';
$string['error_no_idphoto'] = 'Фото документа не отримано';

$string['deletescan_title'] = 'Видалити дані сканування';
$string['deletescan_heading'] = 'Видалити дані сканування для {$a->user} (Тест: {$a->quiz})?';
$string['deletescan_description'] = 'Це назавжди видалить відео огляду кімнати, селфі та фото документа цього студента. Спроба, знімки екрана та журнал подій не постраждають.';
$string['deletescan_confirm'] = 'Так, видалити дані сканування';
$string['deletescan_cancel'] = 'Скасувати';
$string['deletescan_success'] = 'Дані сканування видалено.';

$string['screenshots_title'] = 'Знімки екрана — {$a}';
$string['screenshots_inprogress'] = 'Виконується';
$string['screenshots_attemptinfo'] = 'Спробу розпочато: {$a->started} | Завершено: {$a->finished}';
$string['screenshots_events_heading'] = 'Підозрілі події';
$string['screenshots_col_time'] = 'Час';
$string['screenshots_col_event'] = 'Подія';
$string['screenshots_col_detail'] = 'Деталі';
$string['screenshots_grid_heading'] = 'Знімки веб-камери ({$a})';
$string['screenshots_none'] = 'Знімки екрана для цієї спроби відсутні.';

$string['report_no_attempts'] = 'Не знайдено завершених спроб для цього тесту.';

$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Зберігає імена файлів відео огляду кімнати, селфі та фото документа, а також результати аналізу ШІ.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'Ідентифікатор студента, який виконав огляд.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'Тест, до якого належить це сканування.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'Спроба, до якої належить це сканування.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'Дата створення запису.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'Ім\'я файлу відео огляду кімнати.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'Ім\'я файлу селфі студента.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'Ім\'я файлу фото документа.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'JSON-список об\'єктів, виявлених ШІ під час сканування.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Вказує, чи позначив ШІ сканування як підозріле.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Зберігає періодичні знімки веб-камери під час спроби.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'Ідентифікатор студента.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'Тест, до якого належить цей знімок.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'Спроба, до якої належить цей знімок.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'Дата знімка.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'Ім\'я файлу знімка.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Зберігає підозрілі поведінкові події, зафіксовані під час спроби.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'Ідентифікатор студента, який викликав подію.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'Тест, до якого належить ця подія.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'Спроба, до якої належить ця подія.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'Дата події.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'Тип виявленої підозрілої події.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Додаткові деталі події.';
$string['privacy:path:scans'] = 'Огляди кімнат';
$string['privacy:path:screenshots'] = 'Знімки екрана';
$string['privacy:path:events'] = 'Журнал подій';
