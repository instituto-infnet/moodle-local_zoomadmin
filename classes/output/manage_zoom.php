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
 * Arquivo contendo a classe que define os dados da tela de administração do Zoom.
 *
 * Contém a classe que carrega os dados de administração e exporta para exibição.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_zoomadmin\output;
defined('MOODLE_INTERNAL') || die;

/**
 * Classe contendo dados para o relatório.
 *
 * Carrega os dados de estudantes, competências e conceitos de um curso para
 * gerar o relatório.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_zoom implements \renderable/*, \templatable*/ {
    var $zoomadmin;
    var $params;

    public function __construct($params) {
        $this->zoomadmin = new \local_zoomadmin\zoomadmin();
        $this->params = $params;
    }


    public function export_for_template($pagename) {
        $functionname = 'export_' . $pagename . '_for_template';
        return $this->$functionname();
    }

    /**
     * Carrega a lista de comandos disponíveis e envia ao template, para ser exibida na tela.
     * @return stdClass Dados a serem utilizados pelo template.
     */
    public function export_index_for_template() {
        $data = new \stdClass();
        $data->categories = $this->get_index_commands();

        return $data;
    }

    /**
     * Obtém a lista de usuários do Zoom e envia ao template, para ser exibida na tela.
     * @return stdClass Dados a serem utilizados pelo template.
     */
    public function export_user_list_for_template() {
        $zoomadmin = $this->zoomadmin;
        $userlist = $zoomadmin->request($zoomadmin->commands['user_list'], $this->params);
        $userlist->user_get_url = './user_get.php';
        $userlist->user_list_url = './user_list.php';

        foreach ($userlist->users as $user) {
            $user->type_string = get_string('type_' . $user->type, 'local_zoomadmin');
            $user->last_login_time_formatted = date_create($user->lastLoginTime)->format('d/m/Y H:i:s');
            $user->created_at_formatted = date_create($user->created_at)->format('d/m/Y H:i:s');
        }

        $userlist->pages = array();
        $pagenumber = 1;
        while ($pagenumber <= $userlist->page_count) {
            $page = new \stdClass();
            $page->number = $pagenumber;
            $page->current = ($pagenumber === (int)$userlist->page_number);
            $userlist->pages[] = $page;

            $pagenumber++;
        }

        return $userlist;
    }

    private function get_index_commands() {
        $zoomadmin = $this->zoomadmin;
        $categories = array();

        foreach ($zoomadmin->commands as $cmd) {
            $catindex = 0;
            $cat = null;

            foreach ($categories as $catindex => $category) {
                if ($category->name === $cmd->category) {
                    $cat = $category;
                    break;
                }
            }

            if (!isset($cat)) {
                $cat = $this->create_category($cmd);
                $catindex = count($categories);
            }

            $cat->commands[] = $cmd;
            $categories[$catindex] = $cat;
        }

        return $categories;
    }

    private function create_category($command) {
        $category = new \stdClass();
        $category->name = $command->category;
        $category->stringname = $command->categorystringname;
        $category->commands = array();

        return $category;
    }
}
