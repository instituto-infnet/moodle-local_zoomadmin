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
require_once(__DIR__ . '/classes/form/record_page_form.php');

$params = array(
    'pagecmid' => optional_param('pagecmid', null, PARAM_INT),
    'update' => optional_param('update', 0,PARAM_BOOL)
);

$url = new moodle_url('/local/zoomadmin/record_page_get.php');

$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('pluginname', 'local_zoomadmin') . ' - ' . get_string('user_details', 'local_zoomadmin');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

require_login();
require_capability('local/zoomadmin:managezoom', $context);
admin_externalpage_setup('local_zoomadmin_record_page_list');
$zoomadmin = new \local_zoomadmin\zoomadmin();

$mform = new record_page_form();
global $DB;

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/zoomadmin/record_page_list.php'));
} else if (($fromform = $mform->get_data())) {

    try{
        if($fromform->id){
            $DB->update_record('local_zoomadmin_recordpages', $fromform);
        } else {
            $DB->insert_record('local_zoomadmin_recordpages', $fromform);
        }
    } catch (\exception $e) {
        \core\notification::add("Ocorreu um erro ao salvar/atuliazar o registro!", \core\output\notification::NOTIFY_ERROR);
    }

    redirect(new moodle_url('/local/zoomadmin/record_page_list.php'));

}

if ($params['update'] == 1) {
    $userdata = $DB->get_record('local_zoomadmin_recordpages', ['pagecmid' => $params['pagecmid']]);
    $mform->set_data($userdata);
}

echo $OUTPUT->header() . $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
