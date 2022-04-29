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
 * Arquivo contendo classe para formulário do log do plugin.
 *
 * Contém classe do formulário usado para filtrar o log do plugin.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_zoomadmin\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Classe do formulário de log.
 *
 * Exibe um formulário com os seguintes campos:
 *
 * - Data inicial
 * - Data final
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_form extends \moodleform {
    // var $zoomadmin;

    /**
     * Função herdada de moodleform, define o formulário, incluindo
     * todos os campos e botão para confirmar.
     */
    public function definition() {
        // $this->zoomadmin = new \local_zoomadmin\zoomadmin();
        // print_object($teste);

        $mform = $this->_form;
        /*
        $data = $this->_customdata;
        $action = $data['action'];
        // */

        $today = strtotime('midnight', time());

        $mform->addElement('date_selector', 'from', get_string('from'));
        $mform->setDefault('from', strtotime('1 week ago', $today));
        $mform->addElement('date_selector', 'to', get_string('to'), array('stopyear' => date('Y', $today)));
        $mform->setDefault('to', $today);

        $this->add_action_buttons(false, get_string('get_log', 'local_zoomadmin'));
    }
}
