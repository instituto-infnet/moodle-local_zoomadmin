<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Página com formulário para relacionar reunião do Zoom e módulo
 * de página do Moodle, enviando as gravações para o Google Drive
 * e substituindo os links na página.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/form/recording_edit_page_form.php');

$params = array(
    'action' => 'send_recording_to_google_drive',
    'zoommeetingnumber' => optional_param('zoommeetingnumber', null, PARAM_INT),
    'pagecmid' => optional_param('pagecmid', null, PARAM_INT)
);

$url = new moodle_url('/local/zoomadmin/send_course_recordings_to_google_drive.php'/*, $params*/);

$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('pluginname', 'local_zoomadmin') . ' - ' . get_string('send_course_recordings_to_google_drive', 'local_zoomadmin');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

admin_externalpage_setup('local_zoomadmin_send_course_recordings_to_google_drive');

require_login();
require_capability('local/zoomadmin:managezoom', $context);

$mform = new recording_edit_page_form(null, $params);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/zoomadmin/index.php'));
}

echo $OUTPUT->header() . $OUTPUT->heading($title);

$formdata = $mform->get_data();
if ($formdata && ($formdata->zoommeetingnumber || $formdata->pagecmid)) {
    global $DB;

    $task = new local_zoomadmin\task\send_course_recordings_to_google_drive_task();
    $task->set_custom_data($formdata);
    $taskid = \core\task\manager::queue_adhoc_task($task);

    $exectime = $formdata->execute_time;

    $DB->execute('
        update {task_adhoc}
        set nextruntime = ' . $exectime . '
        where id = ' . $taskid . '
    ', $record);

    $message = get_string(
        'send_recordings_task_created',
        'local_zoomadmin',
        array(
            'zoommeetingnumber' => $formdata->zoommeetingnumber,
            'pagecmid' => $formdata->pagecmid,
            'exectime' => date('Y-m-d h:i:s',$exectime)
        )
    );

    (new \local_zoomadmin\zoomadmin())->add_log('send_course_recordings_to_google_drive.php', $message);
    echo $message;
} else {
    $formdata = $params;
}

$mform->set_data($formdata);

$mform->display();

echo $OUTPUT->footer();
