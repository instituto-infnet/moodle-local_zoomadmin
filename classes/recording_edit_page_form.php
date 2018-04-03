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
 * Arquivo contendo classe para formulário de detalhes de página de
 * links de gravações do Zoom.
 *
 * Contém classe do formulário usado para exibir ou alterar informações
 * de um relacionamento entre reunião do Zoom e módulo de página
 * do Moodle.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Classe do formulário de página de gravações.
 *
 * Exibe um formulário com os seguintes campos:
 *
 * - Reunião do Zoom
 * - Programa
 * - Classe
 * - Bloco
 * - Disciplina
 * - Página
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_edit_page_form extends moodleform {
    var $zoomadmin;

    /**
     * Função herdada de moodleform, define o formulário, incluindo
     * todos os campos e botões para confirmar e cancelar.
     */
    public function definition() {
        $this->zoomadmin = new \local_zoomadmin\zoomadmin();
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', $this->_customdata['action']);
        $this->_form->setType('action', PARAM_TEXT);
        if ($this->_customdata['action'] === 'edit') {
            $mform->addElement('hidden', 'recordpageid');
            $this->_form->setType('recordpageid', PARAM_INT);
        }

        $mform->addElement(
            'static',
            'zoommeetingnumber_form_description',
            get_string('zoom_meeting_number', 'local_zoomadmin'),
            get_string('zoom_meeting_number_form_description', 'local_zoomadmin')
        );
        $mform->addElement(
            'text',
            'zoommeetingnumber',
            null,
            'maxlength="10"'
        );
        $this->_form->setType('zoommeetingnumber', PARAM_INT);

        $mform->addElement(
            'static',
            'page_cm_id_form_description',
            get_string('page_cm_id', 'local_zoomadmin'),
            get_string('page_cm_id_form_description', 'local_zoomadmin')
        );
        $mform->addElement(
            'text',
            'pagecmid',
            null,
            'maxlength="11"'
        );
        $this->_form->setType('pagecmid', PARAM_INT);

        if ($this->_customdata['action'] === 'create') {
            $this->add_action_buttons(true, get_string('add_recording_page', 'local_zoomadmin'));
        } else {
            $this->add_action_buttons();
        }
    }
}
