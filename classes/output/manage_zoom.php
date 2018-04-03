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
 * Arquivo contendo a classe que define os dados
 * da tela de administração do Zoom.
 *
 * Contém a classe que carrega os dados de administração
 * e exporta para exibição.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_zoomadmin\output;
defined('MOODLE_INTERNAL') || die;

/*
//TODO: tentar usar esses use

use \renderable;
use \templatable;
// */

/**
 * Classe contendo dados para exibição na tela.
 *
 * Carrega os dados que serão utilizados na tela carregada.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_zoom implements \renderable/*, \templatable*/ {
    var $zoomadmin;
    var $params;

    public function __construct($params = array()) {
        $this->zoomadmin = new \local_zoomadmin\zoomadmin();
        $this->params = $params;
    }


    // TODO: definir $renderer como renderer_base
    public function export_for_template($pagename, $renderer = null) {
        $functionname = 'export_' . $pagename . '_for_template';
        return $this->$functionname($renderer);
    }

    /**
     * Carrega a lista de comandos disponíveis e envia ao template, para ser exibida na tela.
     * @return \stdClass Dados a serem utilizados pelo template.
     */
    public function export_index_for_template() {
        $data = new \stdClass();
        $data->categories = $this->zoomadmin->get_index_commands();

        return $data;
    }

    /**
     * Obtém a lista de usuários do Zoom e envia ao template,
     * para ser exibida na tela.
     * @return \stdClass Dados a serem utilizados pelo template.
     */
    public function export_user_list_for_template($renderer) {
        $data = $this->zoomadmin->get_user_list($this->params);

        $data->user_get_url = './user_get.php';
        $data->user_list_url = './user_list.php';
        $data->button_add = $renderer->single_button(
            new \moodle_url('/local/zoomadmin/user_get.php', array('zoom_command' => 'user_create')),
            get_string('add_user', 'local_zoomadmin'),
            'get'
        );

        $data->page_count = max((int)$data->page_count, (int)$pending->page_count);

        foreach (array_merge($data->users, $data->pending) as $user) {
            $user->type_string = get_string('user_type_' . $user->type, 'local_zoomadmin');
            $user->last_login_time_formatted = (new \DateTime($user->lastLoginTime))->format('d/m/Y H:i:s');
            $user->created_at_formatted = (new \DateTime($user->created_at))->format('d/m/Y H:i:s');
        }

        $data->pages = $this->get_pagination((int)$data->page_number, $data->page_count);

        return $data;
    }

    /**
     * Obtém a lista de reuniões do Zoom e envia ao template,
     * para ser exibida na tela.
     * @return \stdClass Dados a serem utilizados pelo template.
     */
    public function export_meeting_list_for_template($renderer) {
        $zoomadmin = $this->zoomadmin;

        $data = $zoomadmin->get_meetings_list($this->params);
        $data->meetings = $zoomadmin->sort_meetings_by_start($data->meetings);

        $data->meeting_get_url = './meeting_get.php';
        $data->meeting_list_url = './meeting_list.php';
        $data->user_get_url = './user_get.php';
        $data->button_add = $renderer->single_button(
            new \moodle_url('/local/zoomadmin/meeting_get.php', array('zoom_command' => 'meeting_create')),
            get_string('add_meeting', 'local_zoomadmin'),
            'get'
        );

        $data->pages = $this->get_pagination((int)$data->page_number, $data->page_count);

        return $data;
    }

    /**
     * Obtém a lista de gravações do Zoom e envia ao template,
     * para ser exibida na tela.
     * @return \stdClass Dados a serem utilizados pelo template.
     */
    public function export_recording_list_for_template($renderer) {
        $data = $this->zoomadmin->get_recording_list($this->params);
        $data->pages = $this->get_pagination((int)$data->page_number, $data->page_count);

        return $data;
    }

    /**
     * Obtém a relação de reuniões do Zoom e páginas associadas
     * e envia ao template, para ser exibida na tela.
     * @return \stdClass Dados a serem utilizados pelo template.
     */
    public function export_recording_manage_pages_for_template($renderer) {
        $data = $this->zoomadmin->get_recording_pages_list();
        $data->button_add = $renderer->single_button(
            new \moodle_url('/local/zoomadmin/recording_edit_page.php', array('action' => 'add')),
            get_string('add_recording_page', 'local_zoomadmin'),
            'get'
        );

        return $data;
    }

    public function add_recordings_to_page($meetingid) {
        $response = $this->zoomadmin->add_recordings_to_page($meetingid);

        if (is_array($response)) {
            $output = '<ul>';
            foreach ($response as $message) {
                $output .= '<li>' . $message . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output = $response;
        }

        return $output;
    }

    private function get_pagination($currentpage, $pagecount) {
        $pages = array();

        $pagenumber = 1;
        while ($pagenumber <= $pagecount) {
            $page = new \stdClass();
            $page->number = $pagenumber;
            $page->current = ($pagenumber === $currentpage);
            $pages[] = $page;

            $pagenumber++;
        }

        return $pages;
    }
}
