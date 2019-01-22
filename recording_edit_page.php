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
 * Página com formulário de associação entre reunião do Zoom e módulo
 * de página do Moodle.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/recording_edit_page_form.php');

$params = array(
    'action' => required_param('action', PARAM_TEXT),
    'delete_confirm' => optional_param('delete_confirm', false, PARAM_BOOL)
);

if ($params['action'] !== 'add') {
    $params['recordpageid'] = required_param('recordpageid', PARAM_INT);
}

$url = new moodle_url('/local/zoomadmin/recording_edit_page.php', $params);

$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('pluginname', 'local_zoomadmin') . ' - ' . get_string('recording_edit_page', 'local_zoomadmin');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

admin_externalpage_setup('local_zoomadmin_recording_manage_pages');

require_login();
require_capability('local/zoomadmin:managezoom', $context);

$zoomadmin = new \local_zoomadmin\zoomadmin();
$recordingpageslist = new moodle_url('/local/zoomadmin/recording_manage_pages.php');

if ($params['action'] !== 'delete') {
    $mform = new recording_edit_page_form(null, $params);

    if ($mform->is_cancelled()) {
        redirect($recordingpageslist);
    } else if ($fromform = $mform->get_data()) {
        $response = $zoomadmin->recording_edit_page($fromform);

        if ($response->success !== true) {
            $mform->set_data($fromform);
            \core\notification::add(
                $response->notification->message,
                $response->notification->type
            );
        } else {
            redirect(
                $recordingpageslist,
                $response->notification->message,
                null,
                $response->notification->type
            );
        }
    } else if ($params['action'] === 'edit') {
        $pagedata = $zoomadmin->get_recordings_page_data_by_id($params['recordpageid']);
        $mform->set_data($pagedata);
    }

    echo $OUTPUT->header() . $OUTPUT->heading($title);
    $mform->display();
} else {
    if (isset($params['delete_confirm']) && $params['delete_confirm'] == true) {
        $response = $zoomadmin->recording_edit_page($params);
        redirect(
            $recordingpageslist,
            $response->notification->message,
            null,
            $response->notification->type
        );
    } else {
        $url->param('delete_confirm', true);

        echo $OUTPUT->header() . $OUTPUT->heading($title);
        echo $OUTPUT->confirm(
            get_string('recording_edit_page_delete_confirm', 'local_zoomadmin'),
            $url,
            $recordingpageslist
        );
    }
}

echo $OUTPUT->footer();
