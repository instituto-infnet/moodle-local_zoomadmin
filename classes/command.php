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
 * Arquivo contendo a classe de comandos do Zoom.
 *
 * Contém uma classe que armazena informações a respeito de um comando.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_zoomadmin;

/**
 * Classe de comando da REST API do Zoom.
 *
 * Armazena informações a respeito de um comando que pode ser executado na REST API do Zoom.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class command {
    var $endpoint;
    var $method;
    var $showinindex;
    var $url;
    var $category;

    var $endpointstringname;
    var $stringname;
    var $stringdescription;

    public function __construct($endpoint, $method, $showinindex = true) {
        $this->category = $category;
        $this->method = $method;
        $this->showinindex = $showinindex;

        $this->url = './' . implode('_', array($this->endpoint, $this->name)) . '.php';

        if ($showinindex === true) {
            $this->categorystringname = get_string(join('_', array('category', $this->endpoint)), 'local_zoomadmin');
            $this->stringname = get_string(join('_', array('command', $this->endpoint, $this->method)), 'local_zoomadmin');
            $this->stringdescription = get_string(join('_', array('command', $this->endpoint, $this->method, 'description')), 'local_zoomadmin');
        }
    }
}
