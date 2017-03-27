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
 * Exibe um formulário com a seguinte sequência de campos de seleção, usado para
 * filtrar as listas de cursos e estudantes:
 *
 * Perfil
 * - Nome
 * - Sobrenome
 * - Tipo de usuário
 * - Departamento
 * - Zona de fuso horário
 * - ID de reunião pessoal
 * Configurações de reunião (básicas)
 * - Desabilitar chat
 * - Desabilitar chat privado
 * - Gravação automática do chat
 * - Permitir suspender participante
 * - Tocar som quando participante entra ou sai
 * - Tocar som apenas para apresentador
 * - Não enviar feedback ao Zoom após reunião
 * - Co-apresentadores
 * - Anotações
 * - Enquete
 * - Desabilitar notificação por e-mail ao cancelar reunião
 * Configurações de reunião (avançadas)
 * - Subgrupos em reunião
 * - Suporte remoto
 * - Transferência de arquivos
 * - Tela de fundo virtual
 * - Legenda (closed caption)
 * - Controle remoto da câmera
 * - Compartilhamento de câmera dupla
 * Gravação
 * - Desabilitar gravação
 * - Gravação na nuvem
 * - Gravação automática
 * - Gravação automática na nuvem
 * - Exclusão automática de gravação na nuvem
 * - Quantidade de dias para exclusão
 * Segurança
 * - Criptografia da transmissão
 * - Gerar e exigir senha para participação por telefone
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_get_form extends moodleform {
    /**
     * Função herdada de moodleform, define o formulário, incluindo todos os campos
     * e botões para confirmar e cancelar.
     */
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('hidden', 'zoom_command', $this->_customdata['zoom_command']);
        if ($this->_customdata['zoom_command'] !== 'user_create') {
            $mform->addElement('hidden', 'id');
            $mform->addElement('hidden', 'email_hidden');
        }

        $this->add_profile();
        $this->add_meeting_basic();
        $this->add_meeting_advanced();
        $this->add_recording();
        $this->add_security();

        if ($this->_customdata['zoom_command'] === 'user_create') {
            $this->add_action_buttons(true, get_string('add_user', 'local_zoomadmin'));
        } else if ($this->_customdata['zoom_command'] === 'user_update') {
            $this->add_action_buttons();
        }
    }

    private function add_profile() {
        $mform = $this->_form;

        $mform->addElement('header', 'profile', get_string('profile', 'local_zoomadmin'));

        $mform->addElement('text', 'email', get_string('email'));
        $mform->disabledIf('email', 'zoom_command', 'neq', 'user_create');

        $mform->addElement('text', 'first_name', get_string('firstname'));
        $mform->addElement('text', 'last_name', get_string('lastname'));
        $mform->addElement('select', 'type', get_string('usertype', 'local_zoomadmin'), $this->get_user_types());
        $mform->addElement('text', 'dept', get_string('department', 'local_zoomadmin'));
        $mform->addElement('text', 'timezone', get_string('timezone'));

        $iscreate = $this->_customdata['zoom_command'] === 'user_create';
        if ($iscreate === false) {
            $mform->addElement('text', 'pmi', get_string('pmi', 'local_zoomadmin'));
        }

        $mform->closeHeaderBefore('profile');

        $this->set_profile_options($iscreate);
    }

    private function set_profile_options($iscreate) {
        $this->set_element_options('email', array('type' => 'text', 'required' => $iscreate, 'maxlength' => 128, 'email' => true));
        $this->set_element_options('first_name', array('type' => 'text', 'required' => true, 'maxlength' => 64));
        $this->set_element_options('last_name', array('type' => 'text', 'required' => true, 'maxlength' => 63));
        $this->set_element_options('type', array('required' => true));
        $this->set_element_options('dept', array('type' => 'text', 'maxlength' => 40));
        $this->set_element_options('timezone', array('type' => 'text', 'default' => 'America/Sao_Paulo'));

        if ($iscreate === false) {
            $this->set_element_options('pmi', array('type' => 'int', 'required' => true, 'rangelength' => array(10, 10)));
        }
    }

    private function add_meeting_basic() {
        $mform = $this->_form;

        $mform->addElement('header', 'meeting_basic', get_string('meeting_basic', 'local_zoomadmin'));
        $this->add_advcheckbox('disable_chat');
        $this->add_advcheckbox('disable_private_chat');
        $this->add_advcheckbox('enable_auto_saving_chats', true);
        $this->add_advcheckbox('enable_silent_mode', true, 'enable_silent_mode_help');
        $this->add_advcheckbox('enable_enter_exit_chime');
        $this->add_advcheckbox('disable_feedback', true);
        $this->add_advcheckbox('enable_co_host', true);
        $this->add_advcheckbox('enable_annotation', true);

        $mform->closeHeaderBefore('meeting_basic');
        $mform->setExpanded('meeting_basic');
    }

    private function add_meeting_advanced() {
        $mform = $this->_form;

        $mform->addElement('header', 'meeting_advanced', get_string('meeting_advanced', 'local_zoomadmin'));
        $this->add_advcheckbox('enable_breakout_room', true);
        $this->add_advcheckbox('enable_remote_support', true);
        $this->add_advcheckbox('enable_file_transfer', true);
        $this->add_advcheckbox('enable_virtual_background', null, 'enable_virtual_background_help');
        $this->add_advcheckbox('enable_closed_caption', null, 'enable_closed_caption_help');
        $this->add_advcheckbox('enable_far_end_camera_control', true);
        $this->add_advcheckbox('enable_share_dual_camera', true);
        $this->add_advcheckbox('disable_cancel_meeting_notification');

        $mform->closeHeaderBefore('meeting_advanced');
        $mform->setExpanded('meeting_advanced');
    }

    private function add_recording() {
        $mform = $this->_form;

        $mform->addElement('header', 'recording', get_string('recording', 'local_zoomadmin'));
        $this->add_advcheckbox('disable_recording');

        $this->add_advcheckbox('enable_cmr', true);
        $mform->disabledIf('enable_cmr', 'disable_recording', 'checked');

        $this->add_advcheckbox('enable_auto_recording', true);
        $mform->disabledIf('enable_auto_recording', 'disable_recording', 'checked');

        $this->add_advcheckbox('enable_cloud_auto_recording', true);
        $mform->disabledIf('enable_cloud_auto_recording', 'enable_cmr');

        $this->add_advcheckbox('enable_auto_delete_cmr', null, 'enable_auto_delete_cmr_help');

        $mform->addElement(
            'select',
            'auto_delete_cmr_days',
            get_string('auto_delete_cmr_days', 'local_zoomadmin'),
            array(30 => 30, 60 => 60, 90 => 90, 120 => 120)
        );
        $mform->disabledIf('auto_delete_cmr_days', 'enable_auto_delete_cmr');

        $mform->closeHeaderBefore('recording');
        $mform->setExpanded('recording');
    }

    private function add_security() {
        $mform = $this->_form;

        $mform->addElement('header', 'security', get_string('security', 'local_zoomadmin'));
        $this->add_advcheckbox('enable_e2e_encryption');
        $this->add_advcheckbox('enable_phone_participants_password');

        $mform->closeHeaderBefore('security');
        $mform->setExpanded('security');
    }

    private function set_element_options($elementname, $options) {
        $mform = $this->_form;

        foreach ($options as $key => $value) {
            if ($key === 'type') {
                $this->set_element_type($elementname, $value);
            } else if (in_array ($key, array('required', 'email')) && $value == true) {
                $mform->addRule($elementname, null, $key, null, 'client');
            } else if ($key === 'default') {
                $mform->setDefault($elementname, $value);
            } else if ($key === 'maxlength') {
                $mform->addRule($elementname, get_string('maximumchars', '', $value), $key, $value, 'client');
            } else if ($key === 'rangelength') {
                $mform->addRule($elementname, get_string('err_exactlength', 'local_zoomadmin', $value[0]), $key, $value, 'client');
            }
        }
    }

    private function set_element_type($elementname, $typestring) {
        if ($typestring === 'text') {
            $type = PARAM_TEXT;
        } else if ($typestring === 'int') {
            $type = PARAM_INT;
        }

        $this->_form->setType($elementname, $type);
    }

    private function add_advcheckbox($name, $default = null, $text = null) {
        $mform = $this->_form;

        $mform->addElement(
            'advcheckbox',
            $name,
            get_string($name, 'local_zoomadmin'),
            ((isset($text)) ? get_string($text, 'local_zoomadmin') : null)
        );

        if (isset($default)) {
            $mform->setDefault($name, $default);
        }
    }

    private function get_user_types() {
        return array(
            '1' => get_string('type_1', 'local_zoomadmin'),
            '2' => get_string('type_2', 'local_zoomadmin'),
            '3' => get_string('type_3', 'local_zoomadmin')
        );
    }
}
