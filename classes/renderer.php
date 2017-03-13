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
 * Arquivo contendo a classe de renderização das telas de administração.
 *
 * Contém a classe que realiza a renderização das telas de administração.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_zoomadmin\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Classe de renderização das telas de administração.
 *
 * Obtém dados da classe zoomadmin{@link local_zoomadmin\zoomadmin} e envia para o template mustache.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Obtém a lista de comandos disponíveis e envia para o template mustache.
     *
     * @param manage_zoom $page Página da lista de comandos, com dados para exibição.
     * @return string Código HTML para exibição do relatório.
     */
    public function render_index(manage_zoom $page) {
        $data = $page->export_for_template('index');
        return $this->render_from_template('local_zoomadmin/index', $data);
    }

    /**
     * Lista todos os usuários do Zoom gerenciados pela instituição.
     *
     * @param manage_zoom $page Página do plugin, com dados para exibição.
     * @return string Código HTML para exibição da página.
     */
    public function render_user_list(manage_zoom $page) {
        $data = $page->export_for_template('user_list', $this);
        return $this->render_from_template('local_zoomadmin/user_list', $data);
    }
}
