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
 * Página com formulário de informações de usuário.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/user_get_form.php');

$params = array(
    'zoom_command' => optional_param('zoom_command', null, PARAM_TEXT)
);

if ($params['zoom_command'] !== 'user_create') {
    $params['id'] = required_param('id', PARAM_TEXT);
}

$url = new moodle_url('/local/zoomadmin/user_get.php', $params);

$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('pluginname', 'local_zoomadmin') . ' - ' . get_string('user_details', 'local_zoomadmin');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

admin_externalpage_setup('local_zoomadmin_user_list');

require_login();
require_capability('local/zoomadmin:managezoom', $context);

$zoomadmin = new \local_zoomadmin\zoomadmin();
$mform = new user_get_form(null, $params, null, null, null, in_array($params['zoom_command'], array('user_create', 'user_update')));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/zoomadmin/user_list.php'));
} else if (($fromform = $mform->get_data())) {
    if (!isset($fromform->email)) {
        $fromform->email = $fromform->email_hidden;
    }
    
    $response = $zoomadmin->handle_form($fromform);

    if (isset($response->error)) {
        $mform->set_data($fromform);
        \core\notification::add($response->notification->message, $response->notification->type);
    } else {
        redirect(
            new moodle_url('/local/zoomadmin/user_get.php', array('id' => $response->id)),
            $response->notification->message,
            null,
            $response->notification->type
        );
    }
} else if ($params['zoom_command'] !== 'user_create') {
    $userdata = $zoomadmin->request($zoomadmin->commands['user_get'], array('id' => $params['id']));
    $userdata->email_hidden = $userdata->email;
    $mform->set_data($userdata);
}

echo $OUTPUT->header() . $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
