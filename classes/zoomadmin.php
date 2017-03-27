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
 * Arquivo contendo a principal classe do plugin.
 *
 * Contém a classe que interage com a REST API do Zoom.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_zoomadmin;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../credentials.php');

/**
 * Classe de interação com a REST API do Zoom.
 *
 * Determina comandos a serem executados na API e retorna os resultados.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class zoomadmin {
    const BASE_URL = 'https://api.zoom.us/v1';

    var $commands = array();

    public function __construct() {
        $this->populate_commands();
    }

    public function request(command $command, $params = array()) {
        $ch = curl_init($this->get_api_url($command));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge($this->get_credentials(), $params), null, '&'));

        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        return $response;
    }

    public function handle_form(\stdClass $formdata) {
        confirm_sesskey();

        $response = $this->request($this->commands[$formdata->zoom_command], get_object_vars($formdata));

        if (isset($response->error)) {
            $response->notification->type = \core\output\notification::NOTIFY_ERROR;
            $response->notification->message = get_string('zoom_command_error', 'local_zoomadmin', $response->error);
        } else {
            $response->notification->type = \core\output\notification::NOTIFY_SUCCESS;
            $response->notification->message = get_string(
                'notification_' . $formdata->zoom_command,
                'local_zoomadmin',
                $formdata->first_name . ' ' . $formdata->last_name
            );
        }

        $response->formdata = $formdata;
        return $response;
    }

    private function populate_commands() {
        $this->commands['user_list'] = new command('user', 'list');
        $this->commands['meeting_list'] = new command('meeting', 'list');

        $this->commands['user_pending'] = new command('user', 'pending', false);
        $this->commands['user_get'] = new command('user', 'get', false);
        $this->commands['user_create'] = new command('user', 'create', false);
        $this->commands['user_update'] = new command('user', 'update', false);
    }

    private function get_credentials() {
        global $CFG;

        return array(
            'api_key' => $CFG->zoom_apikey,
            'api_secret' => $CFG->zoom_apisecret
        );
    }

    private function get_api_url($command) {
        return join('/', array($this::BASE_URL, $command->category, $command->name));
    }
}
