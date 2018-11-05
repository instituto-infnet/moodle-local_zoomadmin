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
 * Página para transferir gravações na nuvem do Zoom para o
 * Google Drive.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$params = array('meeting_id' => optional_param('meeting_id', null, PARAM_TEXT));

$url = new moodle_url('/local/zoomadmin/send_recordings_to_google_drive.php', $params);
$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('pluginname', 'local_zoomadmin') . ' - ' . get_string('send_recordings_to_google_drive', 'local_zoomadmin');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

require_login();
// TODO: verificar se é necessário admin_externalpage_setup
//admin_externalpage_setup('local_zoomadmin_recording_send_to_google_drive');
require_capability('local/zoomadmin:managezoom', $context);

$page = new \local_zoomadmin\output\manage_zoom($params);

echo $OUTPUT->header() . $OUTPUT->heading($title);

echo $page->send_recordings_to_google_drive($params['meeting_id']);

echo $OUTPUT->footer();
