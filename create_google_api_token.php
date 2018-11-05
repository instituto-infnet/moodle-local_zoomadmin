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
 * Página para criar token de acesso à API do Google.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$params = array('verification_code' => optional_param('verification_code', null, PARAM_TEXT));

$url = new moodle_url('/local/zoomadmin/create_google_api_token.php', $params);
$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('pluginname', 'local_zoomadmin') . ' - ' . get_string('create_google_api_token', 'local_zoomadmin');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

require_login();
// TODO: verificar se é necessário admin_externalpage_setup
//admin_externalpage_setup('local_zoomadmin_add_recordings_course_mod');
require_capability('local/zoomadmin:managezoom', $context);

$page = new \local_zoomadmin\output\manage_zoom($params);

echo $OUTPUT->header() . $OUTPUT->heading($title);;

echo $page->create_google_api_token($params['verification_code']);

echo $OUTPUT->footer();
