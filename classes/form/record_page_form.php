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
 * Arquivo contendo classe para formulário de detalhes de usuário do Zoom.
 *
 * Contém classe do formulário usado para exibir ou alterar informações de um usuário.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Classe do formulário de usuário.
 *
 * Exibe um formulário com os seguintes campos:
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record_page_form extends moodleform {
    /**
     * Função herdada de moodleform, define o formulário, incluindo todos os campos
     * e botões para confirmar e cancelar.
     */
    public function definition(){
        $mform = $this->_form;

        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text','pagecmid', 'Id da pagina de gravação');
        $mform->setType('pagecmid', PARAM_INT);

        $mform->addElement('text','zoommeetingnumber', 'Numero do Zoom');
        $mform->setType('zoommeetingnumber', PARAM_INT);

        $this->add_action_buttons();

    }


}