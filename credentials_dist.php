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
 * Arquivo que contém as credenciais de acesso do Zoom.
 *
 * Armazena os dados de token do usuário Zoom que irá incluir realizar as ações.
 * Este arquivo deve ser renomeado para credentials.php e as credenciais da REST
 * API do Zoom (obtidas em {@link https://zoom.us/developer/api/credential})
 * devem ser incluídas abaixo.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$CFG->zoom_apikey = 'YOUR_API_KEY';
$CFG->zoom_apisecret = 'YOUR_API_SECRET';
$CFG->zoom_token = 'YOUR_ACCESS_TOKEN';
