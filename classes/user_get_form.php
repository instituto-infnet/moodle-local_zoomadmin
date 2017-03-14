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
 * Dados básicos
 * - Nome
 * - Sobrenome
 * - Tipo de usuário
 * - Departamento
 * - Fuso horário
 * Configurações de reunião (básicas)
 * - Chat
 * - Chat privado
 * - Gravação automática do chat
 * - Suspender convidado
 * - Som de entrada e saída
 * - Som de entrada e saída para convidados
 * - Feedback para o Zoom
 * - Co-apresentadores
 * - Anotações
 * - Enquete
 * Configurações de reunião (avançadas)
 * - Grupos em reunião
 * - Suporte remoto
 * - Transferência de arquivos
 * - Tela de fundo virtual
 * - Legenda (closed caption)
 * - Controle remoto da câmera
 * - Compartilhamento de câmera dupla
 * Gravação
 * - Gravação
 * - Gravação na nuvem
 * - Gravação automática
 * - Gravação automática na nuvem
 * - Exclusão automática de gravação na nuvem
 * Segurança
 * - Criptografia
 * - Senha para participação por telefone
 * Outras configurações
 * - Notificação por e-mail ao cancelar reunião
 * - ID de reunião pessoal
 * - Nome da sala de reunião pessoal
 * - Chave de apresentador
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
		$this->add_profile();
        $this->add_meeting_basic();
	}

    private function add_profile() {
        $usertypes = array(
            '1' => get_string('type_1', 'local_zoomadmin'),
            '2' => get_string('type_2', 'local_zoomadmin'),
            '3' => get_string('type_3', 'local_zoomadmin')
        );

        $mform = $this->_form;

        $mform->addElement('header', 'profile', get_string('profile', 'local_zoomadmin'));

        $mform->addElement('text', 'first_name', get_string('firstname'));
        $mform->addElement('text', 'last_name', get_string('lastname'));
        $mform->addElement('select', 'type', get_string('usertype', 'local_zoomadmin'), $usertypes);
        $mform->addElement('text', 'dept', get_string('department', 'local_zoomadmin'));
        $mform->addElement('text', 'timezone', get_string('timezone'));

        $mform->closeHeaderBefore('profile');
    }

    private function add_meeting_basic() {
        $mform = $this->_form;

        $mform->addElement('header', 'meeting_basic', get_string('meeting_basic', 'local_zoomadmin'));

        /*
        $mform->addElement('advcheckbox', 'disable_chat', get_string('disable_chat', 'local_zoomadmin'));
        $mform->addElement('text', 'last_name', get_string('lastname'));
        $mform->addElement('select', 'type', get_string('usertype', 'local_zoomadmin'), $usertypes);
        $mform->addElement('text', 'dept', get_string('department', 'local_zoomadmin'));
        $mform->addElement('text', 'timezone', get_string('timezone'));
         */

        $mform->closeHeaderBefore('meeting_basic');
    }

    private function get_default_select_options($key) {
        $options = array(
            'enabled' => array(
                'true' => get_string('enabled'),
                'false' => get_string('disabled')
            ),
            'disabled' => array(
                'true' => get_string('disabled'),
                'false' => get_string('enabled')
            )
        );

        if (isset($key)) {
            return $options[$key];
        } else {
            return $options;
        }
    }
}
