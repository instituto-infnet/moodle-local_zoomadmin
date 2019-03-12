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
 * Arquivo contendo funções externas acessíveis por serviço web.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace local_zoomadmin;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * Classe contendo funções externas.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_zoomadmin_external extends external_api {
    /*
    var $zoomadmin;

    public function __construct() {
        $this->zoomadmin = new \local_zoomadmin\zoomadmin();
    }
    //*/

    /**
     * Retorna descrição dos parâmetros de método
     * @return external_function_parameters
     */
    public static function insert_recording_participant_parameters() {
        return new external_function_parameters(
            array(
                'uuid' => new external_value(PARAM_TEXT, 'UUID da reunião Zoom'),
                'userid' => new external_value(PARAM_INT, 'ID do usuário Moodle')
            )
        );
    }

    /**
     * Descreveria o retorno da função, mas não há retorno.
     */
    public static function insert_recording_participant_returns() {
        return new external_single_structure(array());
    }

    /**
     * Insere um registro na tabela local_zoomadmin_participants, referente ao
     * acesso à gravação de uma sessão do Zoom.
     */
    public static function insert_recording_participant($uuid, $userid) {
        /*
        $params = self::validate_parameters(
            self::insert_recording_participant_parameters(),
            array(
                'uuid' => $uuid,
                'userid' => $userid
            )
        );
        //*/
        (new \local_zoomadmin\zoomadmin())->insert_recording_participant($uuid, $userid);
    }
}
