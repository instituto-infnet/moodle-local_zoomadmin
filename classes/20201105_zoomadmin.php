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

require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once(__DIR__ . '/../zoom-credentials.php');
require_once(__DIR__ . '/google_api_controller.php');

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
    const BASE_URL = 'https://api.zoom.us/v2/';
    /**
     * @var int ERR_MAX_REQUESTS Código de erro de quantidade máxima
     *                           de requisições excedida
     */
    const ERR_MAX_REQUESTS = 403;
    const MAX_PAGE_SIZE = 300;
    const KBYTE_BYTES = 1024;
    const MIN_VIDEO_SIZE = self::KBYTE_BYTES * self::KBYTE_BYTES * 20;
    const INITIAL_RECORDING_DATE = '2018-11-01';

    const LB = '
';

    var $commands = array();

    private function request($endpoint, $params = array(), $method = 'get', $attemptcount = 1) {
        /**
         * @var int $attemptsleeptime Tempo (microssegundos) que deve ser
         *                            aguardado para tentar novamente quando o
         *                            máximo de requisições for atingido
         */
        $attemptsleeptime = 100 * 1000;
        /** @var int $maxattemptcount Número máximo de novas tentativas */
        $maxattemptcount = 10;
        $postcommandnames = array(
            'create',
            'update',
            'delete'
        );

        $credentials = $this->get_credentials();
        $curl = new \curl();

        $payload = array(
            'iss' => $credentials['api_key'],
            'exp' => time() + (1000 * 60)
        );
        $token = \Firebase\JWT\JWT::encode($payload, $credentials['api_secret']);
        $curl->setHeader('Authorization: Bearer ' . $token);

        if ($method !== 'get') {
            $curl->setHeader('content-type: application/json');
            $params = is_array($params) ? json_encode($params) : $params;
        }

        $url = $this::BASE_URL . $endpoint;
        $response = call_user_func_array(array($curl, $method), array($url, $params));

        if ($curl->get_errno()) {
            $errormsg = $curl->error;
        }

        $response = json_decode($response);

        $httpstatus = $curl->get_info()['http_code'];
        if ($httpstatus >= 400) {
            if ($response) {
                $errormsg = $response->message;
            } else {
                $errormsg = "HTTP Status $httpstatus";
            }
        }

        if (false && isset($errormsg)) {
            print_object(get_string('error_curl', 'local_zoomadmin', $errormsg));
            print_object((object) array(
                'url' => $url,
                'params' => $params,
                'method' => $method
            ));
        }

        if (!isset($response)) {
            $response = $httpstatus;
        }

        return $response;
    }

    public function get_index_commands() {
        $indexcommands = array_filter($this->commands, function($cmd){return $cmd->showinindex === true;}) ;

        $categories = array();

        foreach ($indexcommands as $cmd) {
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

    public function get_log() {
        $mform = new log_form();
        if (!$fromform = $mform->get_data()) {
            $today = strtotime('midnight', time());

            $fromform = new \stdClass();
            $fromform->from = strtotime('1 week ago', $today);
            $fromform->to = $today;
        }
        $fromform->to = strtotime('tomorrow', $fromform->to) - 1;

        $mform->set_data($fromform);
        $mform->display();

        $logrecords = $this->get_log_data($fromform);

        $data = new \stdClass();
        $data->log_records = array();

        foreach ($logrecords as $record) {
            $record->timestamp_formatted = date("Y-m-d G:i:s", $record->timestamp);
            $data->log_records[] = $record;
        }

        return $data;
    }

    public function handle_form(\stdClass $formdata) {
        confirm_sesskey();

        $response = $this->request(
            $formdata->endpoint,
            get_object_vars($formdata),
            $formdata->method
        );

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

    public function get_user_list($params) {
        $data = $this->request('users', $params);

        $params['status'] = 'pending';
        $pending = $this->request('users', $params);
        $data->pending = $pending->users;

        $data->users = $this->sort_users_by_name($data->users);
        $data->pending = $this->sort_users_by_name($data->pending);

        return $data;
    }

    public function get_user($userid) {
        return $this->request('users/' . $userid);
    }

    public function get_meetings_list($params = array()) {
        $meetingsdata = new \stdClass();
        $meetingsdata->meetings = array();
        $meetingsdata->live = array();
        $meetingsdata->total_records = 0;
        $meetingsdata->page_count = 0;

        $params['page_size'] = $this::MAX_PAGE_SIZE;
        $userdata = $this->request('users', $params);
        $users = $userdata->users;

        foreach ($users as $user) {
            $params['type'] = 'scheduled';
            $usermeetings = $this->request(
                implode('/', array('users', $user->id, 'meetings')),
                $params
            );
            $usermeetings->total_records = $usermeetings->total_records;

            if ($usermeetings->total_records > 0) {
                foreach($usermeetings->meetings as $index => $meeting) {
                    $usermeetings->meetings[$index]->host = $user;
                }

                $meetingsdata->total_records += $usermeetings->total_records;
                $meetingsdata->page_count = max($meetingsdata->page_count, $usermeetings->page_count);
                $meetingsdata->meetings = array_merge($meetingsdata->meetings, $usermeetings->meetings);
            }

            $params['type'] = 'live';
            $usermeetings = $this->request(
                implode('/', array('users', $user->id, 'meetings')),
                $params
            );
            $usermeetings->total_records = $usermeetings->total_records;

            if ($usermeetings->total_records > 0) {
                foreach($usermeetings->meetings as $index => $meeting) {
                    $usermeetings->meetings[$index]->host = $user;
                }

                $meetingsdata->total_records += $usermeetings->total_records;
                $meetingsdata->page_count = max($meetingsdata->page_count, $usermeetings->page_count);
                $meetingsdata->live = array_merge($meetingsdata->live, $usermeetings->meetings);
            }
        }

        $meetingsdata->live = $this->set_meetings_data($meetingsdata->live);
        $meetingsdata->meetings = $this->set_meetings_data($meetingsdata->meetings, true);

        $meetingsdata->live = $this->sort_meetings_by_start($meetingsdata->live);
        $meetingsdata->meetings->past = $this->sort_meetings_by_start($meetingsdata->meetings->past, false);
        $meetingsdata->meetings->upcoming = $this->sort_meetings_by_start($meetingsdata->meetings->upcoming);

        return $meetingsdata;
    }

    public function sort_meetings_by_start($meetings, $ascending = true) {
        $asc = function($meeting1, $meeting2) {
            if ($meeting1->start_time == $meeting2->start_time) {
                return 0;
            }
            return ($meeting1->start_time < $meeting2->start_time) ? -1 : 1;
        };

        $desc = function($meeting1, $meeting2) {
            if ($meeting1->start_time == $meeting2->start_time) {
                return 0;
            }
            return ($meeting1->start_time > $meeting2->start_time) ? -1 : 1;
        };

        usort($meetings, ($ascending === true) ? $asc : $desc);

        return $meetings;
    }

    // Função não mais utilizada, pois retorna todas as gravações do servidor.
    public function get_recording_list($params = array()) {
        $userdata = $this->request('users', array('page_size' => $this::MAX_PAGE_SIZE));
        $users = $userdata->users;

        $recordingsdata = new \stdClass();
        $recordingsdata->user_get_url = './user_get.php';
        $recordingsdata->add_recordings_to_page_url = './add_recordings_to_page.php';
        $recordingsdata->send_recordings_to_google_drive_url = './send_recordings_to_google_drive.php';
        $recordingsdata->participants_url = './participants.php';

        $recordingsdata->meetings = array();

        foreach ($users as $user) {
            $params['page_size'] = $this::MAX_PAGE_SIZE;
            $params['from'] = $this::INITIAL_RECORDING_DATE;

            $userrecordings = $this->request(
                implode('/', array('users', $user->id, 'recordings')),
                $params
            );

            $recordingsdata->total_records = $userrecordings->total_records;

            if ($recordingsdata->total_records > 0) {
                foreach($userrecordings->meetings as $index => $meeting) {
                    $userrecordings->meetings[$index]->host = $user;
                }

                $recordingsdata->total_records += $userrecordings->total_records;

                $recordingpagecount = (isset($recordingsdata->page_count)) ? $recordingsdata->page_count : 1;
                $userpagecount = (isset($userrecordings->page_count)) ? $userrecordings->page_count : 1;
                $recordingsdata->page_count = max($recordingpagecount, $userpagecount);

                $recordingsdata->meetings = $this->set_recordings_data(array_merge($recordingsdata->meetings, $userrecordings->meetings));

                if (isset($params['meeting_number'])) {
                    break;
                }
            }
        }

        $recordingsdata->meetings = $this->sort_meetings_by_start($recordingsdata->meetings, false);

        return $recordingsdata;
    }

    public function add_recordings_to_page($meetingid) {
        if ($meetingid == null) {
            return $this->add_all_recordings_to_page();
        } else {
            $meetingrecordings = $this->get_recording($meetingid);
            $meetingnumber = $meetingrecordings->id;
            $pagedatalist = $this->get_recordings_page_data(array('meetingnumber' => $meetingnumber));
            $pagedata = array_pop($pagedatalist);

            if (($meetingid !== null && $meetingnumber === null) || $pagedata === null) {
                return get_string('error_no_page_instance_found', 'local_zoomadmin', $this->format_meeting_number($meetingnumber));
            }

            return $this->update_recordings_page($meetingrecordings, $pagedata);
        }
    }

    // Aparentemente não é utilizada
    public function get_recording_pages_list() {
        $pagesdata = $this->get_recordings_page_data(array('lastmonth' => true));
        $meetingsdata = $this->get_meetings_list();
        $meetings = array_merge($meetingsdata->meetings->past, $meetingsdata->meetings->upcoming);

        $data = new \stdClass();
        $data->user_get_url = './user_get.php';
        $data->recording_edit_page_url = './recording_edit_page.php';

        $data->pagesdata = array();
        foreach ($pagesdata as $dbpagedata) {
            $pagedata = new \stdClass();
            $pagedata->record_page_id = $dbpagedata->recordpageid;
            $meetingnumber = $dbpagedata->zoommeetingnumber;
            $pagedata->meeting_number = $this->format_meeting_number($meetingnumber);

            foreach ($meetings as $meeting) {
                if ($meeting->id == $meetingnumber) {
                    $pagedata->topic = $meeting->topic;
                    // $pagedata->host = $meeting->host;
                    break;
                }
                // print_object($meeting);
            }

            $pagedata->pagecourselink = $this->format_course_path_links(
                array($dbpagedata->cat2name, $dbpagedata->catname, $dbpagedata->coursename),
                array($dbpagedata->cat2id, $dbpagedata->catid, $dbpagedata->courseid)
            );

            $pagedata->pagelink = $this->surround_with_anchor(
                $dbpagedata->name,
                (new \moodle_url('/mod/page/view.php', array('id' => $dbpagedata->cmid)))->out(),
                true
            );

            $pagedata->recordinglocation = $dbpagedata->recordinglocation;

            if (strpos($pagedata->recordinglocation, 'Z') !== false) {
                $pagedata->sendtogdrivelink = $this->surround_with_anchor(
                    get_string('send_recording_to_google_drive', 'local_zoomadmin'),
                    (
                        new \moodle_url('/local/zoomadmin/send_course_recordings_to_google_drive.php',
                            array(
                                'zoommeetingnumber' => $dbpagedata->zoommeetingnumber,
                                'pagecmid' => $dbpagedata->cmid
                            )
                        )
                    )->out(),
                    true
                );
            }

            $data->pagesdata[] = $pagedata;
        }

        return $data;
    }

    public function get_recordings_page_data_by_id($recordpageid) {
        $pagedata = $this->get_recordings_page_data(array('recordpageid' => $recordpageid));
        return (!empty($pagedata)) ? array_pop($pagedata) : $pagedata;
    }

    public function recording_edit_page($formdata) {
        global $DB;

        $action = (is_array($formdata)) ? $formdata['action'] : $formdata->action;
        $tablename = 'local_zoomadmin_recordpages';

        $success = false;
        $message = '';

        if ($action === 'edit') {
            $formdata->id = $formdata->recordpageid;
            if ($DB->update_record($tablename, $formdata) == 1) {
                $success = true;
            } else {
                $success = false;
            }
        } else if ($action === 'add') {
            if ($DB->insert_record($tablename, $formdata) > 0) {
                $success = true;
            } else {
                $success = false;
            }
        } else if (
            $action === 'delete'
            && isset($formdata['delete_confirm'])
            && $formdata['delete_confirm'] == true
        ) {
            $deleteresponse = $DB->delete_records(
                $tablename,
                array('id' => $formdata['recordpageid'])
            );
            if ($deleteresponse === true) {
                $success = true;
            } else {
                $success = false;
            }
        }

        $response = new \stdClass();
        $response->success = $success;
        $response->notification = $this->get_notification(
            $success,
            get_string(
                'notification_recording_edit_page_' .
                    $action .
                    '_' .
                    (($success === true) ? 'success' : 'error'),
                'local_zoomadmin'
            )
        );

        return $response;
    }

    public function send_recordings_to_google_drive($meetingid, $pagedata = null) {
        $meetingrecordings = $this->get_recording($meetingid);
        $meetingnumber = $meetingrecordings->id;

        if (!isset($pagedata)) {
            $pagesdata = $this->get_recordings_page_data(array('meetingnumber' => $meetingnumber));
            $pagedata = array_pop($pagesdata);
        }

        if (($meetingid !== null && $meetingnumber === null) || $pagedata === null) {
            return get_string('error_no_page_instance_found', 'local_zoomadmin', $this->format_meeting_number($meetingnumber));
        }

        print_r($this::LB . '$meetingid = [' . $meetingid . ']');
        $gdrivefiles = $this->create_google_drive_files($meetingrecordings, $pagedata);

        $response = '<ul>';

        foreach ($gdrivefiles as $file) {
            if (isset($file->webViewLink)) {
                $gdrivefilemsg = get_string('google_drive_upload_success', 'local_zoomadmin');
                $deleted = $this->delete_recording($file->zoomfile->meeting_id, $file->zoomfile->id);
            } else {
                $gdrivefilemsg = get_string('google_drive_upload_error', 'local_zoomadmin');
            }

            $response .= '<li>' .
                $gdrivefilemsg .
                ': <a href="' .
                $file->webViewLink .
                '" target="_blank">' .
                $file->name .
                '</a>.' .
                $file->link_replaced_message .
                (($deleted === 204) ? 'Arquivo excluído do Zoom.' : 'Arquivo não excluído do Zoom.') .
                '</li>'
            ;
        }

        $response .= '</ul>';

        $this->add_log('zoomadmin->send_recordings_to_google_drive', $response);

        return $response;
    }

    public function create_google_api_token($params) {
        $googlecontroller = new \google_api_controller();
        return $googlecontroller->create_google_api_token($params);
    }

    public function oauth2callback($params) {
        $googlecontroller = new \google_api_controller();
        return $googlecontroller->oauth2callback($params);
    }

    public function send_course_recordings_to_google_drive($formdata) {
        $formdata = (array) $formdata;
        $meetingsanduuids = array();
        $meetings = array();
        $messages = array();

        if ($formdata['pagecmid']) {
            $pagedata = $this->get_mod_page_data($formdata['pagecmid']);
            $meetingsanduuids = $this->get_page_meeting_instances($pagedata);
            $meetings = $meetingsanduuids['meetings'];
        }

        if ($formdata['zoommeetingnumber']) {
            $meetings = array_merge($meetings, $this->get_meeting_occurrences($formdata['zoommeetingnumber'], $meetingsanduuids['uuids']));
        }

        $messages[] = print_r($meetingsanduuids['uuids'], true);
        $messages[] = print_r($meetings, true);
        $meetings = $this->sort_meetings_by_start($meetings);
        print_r($meetings);

        foreach ($meetings as $meeting) {
            $messages[] = $this->send_recordings_to_google_drive($meeting->uuid, $pagedata);
        }

        return implode($messages);
    }

    public function add_log($classfunction, $message) {
        global $DB;

        $record = new \stdClass();
        $record->timestamp = time();
        $record->classfunction = $classfunction;
        $record->message = $message;

        $DB->insert_record('local_zoomadmin_log', $record);
    }

    public function get_participants_report($meetinguuid) {
        $data = $this->get_participants_data($meetinguuid);
        return $data;
    }

    public function get_participants_all_instances_report($meetinguuid) {
        $data = $this->get_participants_all_instances_data($meetinguuid);
        return $data;
    }

    public function insert_recording_participant($uuid, $userid) {
        $data = $this->insert_recording_viewed_participant_data($uuid, $userid);
        $this->add_log(
            'zoomadmin->insert_recording_participant',
            'Registrado acesso à gravação de uuid "' . $uuid .
                '" pelo usuário ' . $data->username .
                ' (id Moodle ' . $userid .
                ', e-mail ' . $data->useremail .
                ').'
        );
    }

    private function populate_commands() {
        $this->commands['user_list'] = new command('user', 'list');
        $this->commands['user_pending'] = new command('user', 'pending', false);
        $this->commands['user_get'] = new command('user', 'get', false);
        $this->commands['user_create'] = new command('user', 'create', false);
        $this->commands['user_update'] = new command('user', 'update', false);

        $this->commands['meeting_list'] = new command('meeting', 'list');
        $this->commands['meeting_live'] = new command('meeting', 'live', false);
        $this->commands['meeting_get'] = new command('meeting', 'get', false);
        $this->commands['meeting_create'] = new command('meeting', 'create', false);
        $this->commands['meeting_update'] = new command('meeting', 'update', false);

        $this->commands['recording_list'] = new command('recording', 'list');
        $this->commands['recording_get'] = new command('recording', 'get', false);
        $this->commands['recording_delete'] = new command('recording', 'delete', false);
        $this->commands['recording_manage_pages'] = new command('recording', 'manage_pages');
    }

    private function get_credentials() {
        global $CFG;

        return array(
            'api_key' => $CFG->zoom_apikey,
            'api_secret' => $CFG->zoom_apisecret,
            'token' => $CFG->zoom_token,
        );
    }

    private function get_api_url($command) {
        return join('/', array($this::BASE_URL, $command->category, $command->name));
    }

    private function get_log_data($formdata) {
        global $DB;

        return $DB->get_records_sql('
                select *
                from {local_zoomadmin_log}
                where timestamp >= ?
                    and timestamp <= ?
                order by timestamp desc
            ',
            array(
                $formdata->from,
                $formdata->to
            )
        );
    }

    private function set_recordings_data($meetings) {
        foreach($meetings as $meetingindex => $meeting) {
            $timezone = $this->get_meeting_timezone($meeting);

            $meetings[$meetingindex]->encoded_uuid = urlencode($meeting->uuid);
            $meetings[$meetingindex]->start_time_unix = strtotime($meeting->start_time);

            foreach($meeting->recording_files as $fileindex => $file) {
                $recordingstarttime = (new \DateTime($file->recording_start))->setTimezone($timezone);
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_start_formatted = $recordingstarttime->format('d/m/Y H:i:s');
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_start_formatted_for_download = $recordingstarttime->format('Y-m-d H:i:s');

                $recordingendtime = (new \DateTime($file->recording_end))->setTimezone($timezone);
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_end_formatted = $recordingendtime->format('d/m/Y H:i:s');

                $timediff = $recordingstarttime->diff($recordingendtime);
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_duration = sprintf('%02d', $timediff->h) . ':' . sprintf('%02d', $timediff->i) . ':' . sprintf('%02d', $timediff->s);

                $meetings[$meetingindex]->recording_files[$fileindex]->meeting_number_formatted = $this->format_meeting_number($meeting->id);

                $meetings[$meetingindex]->recording_files[$fileindex]->file_size_formatted = (isset($file->file_size)) ? $this->format_file_size($file->file_size) : null;

                $meetings[$meetingindex]->recording_files[$fileindex]->file_type_string = get_string('file_type_' . $file->file_type, 'local_zoomadmin');
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_status_string = (isset($file->status)) ? get_string('recording_status_' . $file->status, 'local_zoomadmin') : null;
            }
        }

        return $meetings;
    }

    private function get_meeting_timezone($meeting) {
        $timezone = 'America/Sao_Paulo';
        if (isset($meeting->timezone) && $meeting->timezone !== '') {
            $timezone = $meeting->timezone;
        } else if (isset($meeting->host->timezone) && $meeting->host->timezone !== '') {
            $timezone = $meeting->host->timezone;
        }

        return new \DateTimeZone($timezone);
    }

    private function format_meeting_number($meetingnumber) {
        return number_format($meetingnumber, 0, '', '-');
    }

    private function format_file_size($filesize) {
        $kb = $this::KBYTE_BYTES;
        $mb = pow($kb, 2);
        $gb = pow($kb, 3);

        if ($filesize < $kb) {
            return $filesize . ' B';
        } else if ($filesize < $mb) {
            return floor($filesize / $kb) . ' KB';
        } else if ($filesize < $gb) {
            return floor($filesize / $mb) . ' MB';
        } else {
            return floor($filesize / $gb) . ' GB';
        }
    }

    private function set_meetings_data($meetings, $separatepastupcoming = false) {
        $meetingswithoccurrences = array();
        $meetingsbydate = new \stdClass();
        $meetingsbydate->past = array();
        $meetingsbydate->upcoming = array();

        $now = new \DateTime();

        foreach ($meetings as $index => $meeting) {
            $meeting->type_string = get_string('meeting_type_' . $meeting->type, 'local_zoomadmin');
            $meeting->id_formatted = $this->format_meeting_number($meeting->id);

            if (!in_array($meeting->type, array(3, 8))) {
                $meetingswithoccurrences[] = $meeting;
            } else {
                $occurrences = $this->get_meeting_occurrences($meeting);
                $meetingswithoccurrences = array_merge($meetingswithoccurrences, $occurrences);
            }
        }

        foreach ($meetingswithoccurrences as $index => $meeting) {
            if ($meeting->start_time !== '') {
                $meetingstarttime = new \DateTime($meeting->start_time);
                $meeting->start_time_formatted = $meetingstarttime->format('d/m/Y H:i:s');

                if ($separatepastupcoming === true) {
                    if ($meetingstarttime < $now) {
                        $meetingsbydate->past[] = $meeting;
                    } else {
                        $meetingsbydate->upcoming[] = $meeting;
                    }
                }
            } else if ($separatepastupcoming === true) {
                $meetingsbydate->past[] = $meeting;
            }
        }

        if ($separatepastupcoming === true) {
            return $meetingsbydate;
        } else {
            return $meetingswithoccurrences;
        }
    }

    private function get_meeting_occurrences($meeting, $ignoreduuids = array()) {
        $meetingid = isset($meeting->id) ? $meeting->id : $meeting;
        $occurrences = array();

        $meetingdata = $this->request('past_meetings/' . $meetingid . '/instances');

        if (empty($meetingdata->meetings)) {
            if (isset($meeting->id) && !in_array($meeting->uuid, $ignoreduuids)) {
                $occurrences[] = $meeting;
            } else if (!in_array($meeting->id, $ignoreduuids)) {
                $occurrence = $this->get_meeting_data($meeting);
                if (!in_array($occurrence->uuid, $ignoreduuids)) {
                    $occurrences[] = $occurrence;
                }
            }
        } else {
            foreach ($meetingdata->meetings as $occurrence) {
                if (!in_array($occurrence->uuid, $ignoreduuids)) {
                    $occurrences[] = $this->get_meeting_occurence_data($occurrence->uuid);
                }
            }
        }

        return $occurrences;
    }

    private function get_meeting_data($meetingnumber) {
        return $this->request('meetings/' . $meetingnumber);
    }

    private function get_meeting_occurence_data($uuid) {
        return $this->request('past_meetings/' . urlencode(urlencode($uuid)));
    }

    private function get_recording($meetingid) {
        $commands = $this->commands;

        $recordingmeeting = $this->request(implode('/', array('meetings', urlencode(urlencode($meetingid)), 'recordings')));
        $recordingmeeting->host = $this->get_user($recordingmeeting->host_id);

        $recordingsdata = $this->set_recordings_data(array($recordingmeeting));
        $recordingmeeting = array_pop($recordingsdata);

        return $recordingmeeting;
    }

    private function get_user_recordings($userid) {
        $response = $this->request(
            implode('/', array('users', $userid, 'recordings')),
            array('from' => $this::INITIAL_RECORDING_DATE)
        );
        return $response;
    }

    private function delete_recording($meetingid, $fileid) {
        $response = $this->request(implode('/', array('meetings', urlencode(urlencode($meetingid)), 'recordings', urlencode(urlencode($fileid)))), null, 'delete');
        return $response;
    }

    private function get_recordings_page_data($params = array()) {
        global $DB;

        $sqlstring = "
            select rp.id recordpageid,
                cm.id cmid,
                rp.pagecmid,
                p.*,
                CONCAT_WS(
                    '/',
                    case when p.content like '%api.zoom.us%' then 'Z' end,
                    case when p.content like '%drive.google.com%' then 'G' end
                ) recordinglocation,
                rp.zoommeetingnumber,
                rp.lastaddedtimestamp,
                cm.course courseid,
                c.fullname coursename,
                cc.id catid,
                cc.name catname,
                cc2.id cat2id,
                cc2.name cat2name,
                cc3.id cat3id,
                cc3.name cat3name,
                cc4.id cat4id,
                cc4.name cat4name
            from {local_zoomadmin_recordpages} rp
                left join {course_modules} cm on cm.id = rp.pagecmid
                left join {modules} m on m.id = cm.module
                    and m.name = 'page'
                left join {page} p on p.id = cm.instance
                left join {course} c on c.id = cm.course
                left join {course_categories} cc on cc.id = c.category
                left join {course_categories} cc2 on cc2.id = cc.parent
                left join {course_categories} cc3 on cc3.id = cc2.parent
                left join {course_categories} cc4 on cc4.id = cc3.parent
            where 1 = 1
        ";

        $tokens = array();
        if (isset($params['meetingnumber'])) {
            $sqlstring .= "
                and rp.zoommeetingnumber = ?
            ";
            $tokens[] = $params['meetingnumber'];
        }

        if (isset($params['recordpageid'])) {
            $sqlstring .= "
                and rp.id = ?
            ";
            $tokens[] = $params['recordpageid'];
        }

        if (isset($params['lastmonth']) && $params['lastmonth'] == true) {
            $sqlstring .= "
                and c.enddate > UNIX_TIMESTAMP(DATE_ADD(CURDATE(), interval -1 month))
            ";
        }

        return $DB->get_records_sql($sqlstring, $tokens);
    }

    private function get_mod_page_data($cmid) {
        global $DB;

        $sqlstring = "
            select cm.id cmid,
                p.*,
                cm.course courseid,
                c.fullname coursename,
                cc.id catid,
                cc.name catname,
                cc2.id cat2id,
                cc2.name cat2name,
                cc3.id cat3id,
                cc3.name cat3name,
                cc4.id cat4id,
                cc4.name cat4name
            from {course_modules} cm
                join {modules} m on m.id = cm.module
                    and m.name = 'page'
                join {page} p on p.id = cm.instance
                join {course} c on c.id = cm.course
                left join {course_categories} cc on cc.id = c.category
                left join {course_categories} cc2 on cc2.id = cc.parent
                left join {course_categories} cc3 on cc3.id = cc2.parent
                left join {course_categories} cc4 on cc4.id = cc3.parent
            where cm.id = ?
        ";

        return $DB->get_record_sql($sqlstring, array($cmid));
    }

    private function get_page_meeting_instances($pagedata) {
        $uuidpattern = '/data-uuid="(.+?)"/';
        $matches = array();
        $content = $pagedata->content;
        $uuids = array();
        $meetings = array();

        if (preg_match_all($uuidpattern, $content, $matches)) {
            foreach($matches[1] as $uuid) {
                if (!in_array($uuid, $uuids)) {
                    $uuids[] = $uuid;
                    $meetings[] = $this->get_meeting_occurence_data($uuid);
                }
            }
        }

        return array('meetings' => $meetings, 'uuids' => $uuids);
    }


    private function get_new_recordings_page_content($pagedata, $meetingrecordings) {
        $content = $pagedata->content;
        $recordingurls = $this->get_recording_urls_for_page($meetingrecordings->recording_files);
        $recordingcount = count($recordingurls);

        $doc = new \DOMDocument();

        if ($recordingcount > 0) {
            $urlul = $doc->createElement('ul');
            $uuid = $meetingrecordings->uuid;
            $multiplevideos = ($recordingurls[$recordingcount - 1]['videoindex'] > 1);

            foreach ($recordingurls as $url) {
                if (strpos($content, $url['url']) !== false) {
                    return 'error_recording_already_added';
                }

                $anchortext = $url['text'] . (($multiplevideos) ? (' - ' . get_string('recording_part', 'local_zoomadmin') . ' ' . $url['videoindex']) : '');

                $li = $urlul->appendChild($doc->createElement('li'));
                $a = $li->appendChild($doc->createElement('a', $anchortext));
                $a->setAttribute('data-uuid', $uuid);
                $a->setAttribute('data-filetype', $url['filetype']);
                $a->setAttribute('href', $url['url']);
                $a->setAttribute('target', '_blank');
            }

            $li = $urlul->appendChild($doc->createElement('li'));
            $li->setAttribute('class', 'disciplina_codes');
            $a = $li->appendChild($doc->createElement('a', get_string('participants', 'local_zoomadmin')));
            $a->setAttribute('data-uuid', $uuid);
            $a->setAttribute('data-filetype', 'participants');
            $a->setAttribute(
                'href',
                (new \moodle_url(
                    '/local/zoomadmin/participants.php',
                    array('meetinguuid' => $meetingrecordings->uuid)
                ))
            );
            $a->setAttribute('target', '_blank');

            $classnumber = 1;

            $doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

            $classdate = (new \DateTime($meetingrecordings->start_time))->setTimezone($this->get_meeting_timezone($meetingrecordings))->format('d/m/Y');

            $h2list = $doc->getElementsByTagName('h2');
            $h2length = $h2list->length;

            if ($h2length) {
                $lastclasstitle = $h2list->item($h2length - 1)->textContent;
                $lasttitletextarray = explode(' ', $lastclasstitle);
                $lastclassnumber = array_pop($lasttitletextarray);

                if (filter_var($lastclassnumber, FILTER_VALIDATE_INT) !== false) {
                    $classnumber = $lastclassnumber + 1;
                }
            }

            $doc->appendChild($doc->createElement('h2', $classdate . ' - Aula ' . $classnumber));
            $urlul = $doc->importNode($urlul, true);
            $doc->appendChild($urlul);

            return $doc->saveHTML();
        } else {
            return 'error_no_recordings_found';
        }
    }

    private function get_recording_urls_for_page($recordings) {
        $recordinglist = array();
        $ignoredvideo = true;
        $videoindex = 0;

        foreach ($recordings as $index => $recording) {
            $filetype = $recording->file_type;

            if ($filetype === 'MP4') {
                if ($recording->file_size >= $this::MIN_VIDEO_SIZE) {
                    $videoindex++;

                    $recordinglist[] = array(
                        'filetype' => $recording->file_type,
                        'text' => get_string('recording_text_' . $filetype, 'local_zoomadmin'),
                        'url' => $recording->play_url,
                        'videoindex' => $videoindex
                    );

                    $ignoredvideo = false;
                } else {
                    $ignoredvideo = true;
                }
            } else if ($filetype === 'CHAT' && $ignoredvideo === false) {
                $recordinglist[] = array(
                    'filetype' => $recording->file_type,
                    'text' => get_string('recording_text_' . $filetype, 'local_zoomadmin'),
                    'url' => $recording->download_url,
                    'videoindex' => $videoindex
                );
            }
        }

        return $recordinglist;
    }

    private function update_recordings_page($meetingrecordings, $pagedata){
        $this->retrieve_participants_data($meetingrecordings);

        $newcontent = $this->get_new_recordings_page_content($pagedata, $meetingrecordings);

        if ($newcontent === 'error_recording_already_added') {
            $this->update_recordpage_timestamp($pagedata->recordpageid, $meetingrecordings->start_time_unix);
        }

        if (substr($newcontent, 0, 5) === 'error') {
            return get_string($newcontent, 'local_zoomadmin', $this->format_file_size($this::MIN_VIDEO_SIZE));
        }

        $pageupdated = $this->update_page_content($pagedata, $newcontent);

        if ($pageupdated === true) {
            $this->update_recordpage_timestamp($pagedata->recordpageid, $meetingrecordings->start_time_unix);
            $recordingpageurl = new \moodle_url('/mod/page/view.php', array('id' => $pagedata->cmid));
            return get_string('recordings_added_to_page', 'local_zoomadmin', $recordingpageurl->out());
        } else {
            return get_string('error_add_recordings_to_page', 'local_zoomadmin', $recordingpageurl->out());
        }
    }

    private function update_page_content($pagedata, $newcontent) {
        global $USER, $DB;

        $timestamp = (new \DateTime())->getTimestamp();

        $pagedata->content = $newcontent;
        $pagedata->usermodified = $USER->id;
        $pagedata->timemodified = $timestamp;

        $pageupdated = $DB->update_record('page', $pagedata);

        return $pageupdated;
    }

    private function update_recordpage_timestamp($id, $lastaddedtimestamp) {
        global $DB;

        return $DB->update_record(
            'local_zoomadmin_recordpages',
            array(
                'id' => $id,
                'lastaddedtimestamp' => $lastaddedtimestamp
            )
        );
    }

    private function add_all_recordings_to_page() {
        $pagesdata = $this->get_recordings_page_data(array('lastmonth' => true));

        $recordingsdata = array();
        $responses = array();

        foreach ($pagesdata as $pagedata) {
            $meetingdata = $this->get_meeting_data($pagedata->zoommeetingnumber);
            $hostid = $meetingdata->host_id;

            if (isset($recordingsdata[$hostid])) {
                $hostrecordings = $recordingsdata[$hostid];
            } else {
                $hostrecordings = $this->get_user_recordings($hostid);
                $hostrecordings->host = $this->get_user($hostid);
                $hostrecordings->meetings = $this->sort_meetings_by_start($hostrecordings->meetings);
                $recordingsdata[$hostid] = $hostrecordings;
            }

            foreach ($hostrecordings->meetings as $meeting) {
                $meeting->host = $hostrecordings->host;
                $meetingdata = array_pop($this->set_recordings_data(array($meeting)));

                if (
                    $meetingdata->id == $pagedata->zoommeetingnumber
                    && $meetingdata->start_time_unix > $pagedata->lastaddedtimestamp
                ) {
                    $response = '<a href="https://www.zoom.us/recording/management/detail?meeting_id=' .
                        $meetingdata->encoded_uuid .
                        '" target="_blank">' .
                        $meetingdata->topic .
                        ' - ' .
                        $meetingdata->recording_files[0]->recording_start_formatted .
                        '</a> - ' .
                        $this->update_recordings_page($meetingdata, $pagedata)
                    ;
                    $responses[] = $response;
                }
            }
        }

        return $responses;
    }

    private function sort_users_by_name($users) {
        usort($users, function($user1, $user2) {
            $firstname = strcoll($user1->first_name, $user2->first_name);

            if ($firstname === 0) {
                return strcoll($user1->last_name, $user2->last_name);
            }

            return $firstname;
        });

        return $users;
    }

    private function format_course_path_links($contents, $ids) {
        $links = array();
        $lastindex = sizeof($contents) - 1;

        foreach ($contents as $index => $content) {
            if ($index === $lastindex) {
                $href = new \moodle_url('/course/view.php', array('id' => $ids[$index]));
            } else {
                $href = new \moodle_url('/course/index.php', array('categoryid' => $ids[$index]));
            }

            $links[] = $this->surround_with_anchor($content, $href->out(), true);
        }

        return join(
            ' / ',
            $links
        );
    }

    private function surround_with_anchor($content, $href, $newwindow) {
        return '<a href="' . $href . '"' .
            (($newwindow === true) ? 'target="_blank"' : '') .
            '>' . $content .
            '</a>'
        ;
    }

    private function get_notification($success = true, $message = '') {
        $notification = new \stdClass();
        $notification->type = ($success === true) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR;
        $notification->message = $message;

        return $notification;
    }

    private function create_google_drive_files($meetingrecordings, $pagedata, $googlecontroller = null) {
        $files = $meetingrecordings->recording_files;
        $filecount = count($files);

        if ($filecount === 0) {
            return 'error_no_recordings_found';
        }

        $drivefiles = array();

        if (!isset($googlecontroller)) {
            $googlecontroller = new \google_api_controller();
        }

        $folder = $this->get_google_drive_folder($googlecontroller, $pagedata);

        $folderfiles = $googlecontroller->get_google_drive_files_from_folder($folder);
        $filenames = array_column($folderfiles, 'name');

        foreach ($files as $file) {
            $filedata = $this->get_file_data_for_google_drive($file, $pagedata);

            $filekey = array_search($filedata['name'], $filenames);

            if ($filekey === false) {
                $filedata['parents'] = array($folder->id);
                $drivefile = $googlecontroller->create_drive_file($filedata);
            } else {
                $drivefile = $folderfiles[$filekey];
            }

            $file->uuid = $meetingrecordings->uuid;
            $drivefile->link_replaced_message = $this->replace_recordings_page_links($pagedata, $file, $drivefile->webViewLink);
            $drivefile->zoomfile = $file;
            $drivefiles[] = $drivefile;
        }

        return $drivefiles;
    }

    private function get_google_drive_folder($googlecontroller, $pagedata) {
        $foldernamestree = array(
            $pagedata->cat4name,
            $pagedata->cat3name,
            $pagedata->cat2name,
            $pagedata->catname,
            $pagedata->coursename
        );

        $folder = $googlecontroller->get_google_drive_folder($foldernamestree);

        return $folder;
    }

    private function get_file_data_for_google_drive($file, $pagedata) {
        $filetypes = array(
            'MP4' => array(
                'mime_type' => 'video/mp4',
                'extension' => 'mp4'
            ),
            'CHAT' => array(
                'mime_type' => 'text/plain',
                'extension' => 'txt'
            ),
            'M4A' => array(
                'mime_type' => 'audio/mp4',
                'extension' => 'm4a'
            )
        );

        $type = $file->file_type;
        $typevalues = $filetypes[$type];

        $filedata = array(
            'id' => $file->id,
            'name' => $file->recording_start_formatted_for_download .
                ' - ' .
                $pagedata->coursename .
                ' (' .
                get_string('file_type_' . $type, 'local_zoomadmin') .
                ').' .
                $typevalues['extension'],
            'file_url' => $file->download_url,
            'mime_type' => $typevalues['mime_type'],
            'file_size' => $file->file_size
        );

        return $filedata;
    }

    private function replace_recordings_page_links($pagedata, $zoomfile, $driveurl) {
        $replacepattern = "/(" .
            preg_quote("data-uuid=\"{$zoomfile->uuid}\"", "/") .
            ".*?" .
            preg_quote("data-filetype=\"{$zoomfile->file_type}\"", "/") .
            ".*?href\=)\"(.*?)\"/"
        ;

        $newcontent = str_replace(
            $zoomfile->download_url,
            $driveurl,
            str_replace(
                $zoomfile->play_url,
                $driveurl,
                preg_replace(
                    $replacepattern,
                    "$1\"" . $driveurl . "\"",
                    $pagedata->content
                )
            )
        );

        $pageupdated = $this->update_page_content($pagedata, $newcontent);

        if ($pageupdated === true) {
            $recordingpageurl = new \moodle_url('/mod/page/view.php', array('id' => $pagedata->cmid));
            return get_string('recordings_url_replaced_in_page', 'local_zoomadmin', $recordingpageurl->out());
        } else {
            return get_string('error_recordings_replace_url_in_page', 'local_zoomadmin', $recordingpageurl->out());
        }
    }

    private function get_participants_data($meetinguuid) {
        $filteremail = $this->get_filter_email_for_participants_report();

        $data = $this->get_meeting_occurence_data($meetinguuid);
        $data->host = $this->get_user($data->host_id);

        $timezone = $this->get_meeting_timezone($data);
        $meetingstarttime = (new \DateTime($data->start_time))->setTimezone($timezone);
        $data->start_time_formatted = $meetingstarttime->format('d/m/Y H:i:s');
        $meetingendtime = (new \DateTime($data->end_time))->setTimezone($timezone);
        $data->end_time_formatted = $meetingendtime->format('H:i:s');

        $data->participants = $this->get_stored_participants_data($meetinguuid);

        if (empty($data->participants)) {
            $this->retrieve_participants_data($data);
            $data->participants = $this->get_stored_participants_data($meetinguuid);
        }

        $indexedparticipants = array();

        foreach ($data->participants as $participant) {
            if (
                (!isset($filteremail) || $participant->useremail === $filteremail)
                && (!isset($participant->useremail) || $participant->useremail !== $data->host->email)
            ) {
                $participant->viewed_recording = false;
                $participant->session_participant = false;

                $participant->join_leave_times = explode(',', $participant->join_leave_times);
                foreach ($participant->join_leave_times as $index => $times) {
                    if (strpos($times, '*') !== false) {
                        $participant->viewed_recording = true;
                    } else {
                        $participant->session_participant = true;
                    }

                    $participant->join_leave_times[$index] = new \stdClass();
                    $participant->join_leave_times[$index]->times = $times;
                }

                if (
                    $participant->viewed_recording === true
                    && $participant->session_participant === false
                ) {
                    $participant->sum_duration = 'N/D';
                    $participant->percent_duration = '';
                    $participant->avg_attentiveness = 'N/D';
                }

                $indexedparticipants[] = $participant;
            }
        }

        $data->participants = $indexedparticipants;
        $data->hasdata = !empty($data->participants);
        if (!$data->hasdata) {
            $data->nodatamsg = get_string((isset($filteremail) ? 'report_no_permission' : 'report_no_data'), 'local_zoomadmin');
        }

        return $data;
    }

    private function get_filter_email_for_participants_report() {
        global $USER;
        $context = \context_system::instance();

        return (
            !has_capability('local/zoomadmin:managezoom', $context) &&
            strpos($USER->email, '@prof.infnet.edu.br') === false &&
            strpos($USER->email, '@infnet.edu.br') === false
        ) ? $USER->email : null;
    }

    private function get_stored_participants_data($meetinguuid) {
        global $DB;

        $sqlstring = "
            select p.id,
                p.username,
                p.useremail,
                GROUP_CONCAT(
                    case
                        when p.recording = 0 then
                            CONCAT_WS(
                                ' - ',
                                FROM_UNIXTIME(p.jointime, '%k:%i:%s'),
                                FROM_UNIXTIME(p.leavetime, '%k:%i:%s')
                            )
                        else CONCAT(FROM_UNIXTIME(p.jointime, '%d/%m/%Y %k:%i:%s'), '*')
                    end
                    SEPARATOR ', '
                ) join_leave_times,
                CEILING(SUM(p.duration)/60) sum_duration,
                CONCAT(
                    '(',
                    CAST(SUM(p.duration)/(
                        select MAX(p2.leavetime) - MIN(p2.jointime)
                        from {local_zoomadmin_participants} p2
                        where p2.meetinguuid = p.meetinguuid
                            and p2.recording = 0
                    ) * 100 as UNSIGNED),
                    '%',
                    ')'
                ) percent_duration,
                CONCAT(CAST(AVG(p.attentiveness) as UNSIGNED), '%') avg_attentiveness
            from {local_zoomadmin_participants} p
            where p.meetinguuid = ?
            group by p.username,
                p.useremail
            order by p.username,
                p.useremail,
                p.jointime
        ";

        return $DB->get_records_sql($sqlstring, array($meetinguuid));
    }

    private function get_stored_all_participants_data($meetingnumber) {
        global $DB;

        $data = new \stdClass();

        $sqlselectparticipantsonly = "
            select distinct CONCAT_WS(
                    '_',
                    COALESCE(CONCAT(u.firstname, ' ', u.lastname), p.username),
                    COALESCE(u.email, p.useremail)
                ) user_key,
                COALESCE(CONCAT(u.firstname, ' ', u.lastname), p.username) username,
                COALESCE(u.email, p.useremail) email
        ";

        $sqlselectalldata = "
            select distinct CONCAT_WS(
                    '_',
                    COALESCE(CONCAT(u.firstname, ' ', u.lastname), p.username),
                    COALESCE(u.email, p.useremail),
                    p.meetinguuid
                ) user_key,
                COALESCE(CONCAT(u.firstname, ' ', u.lastname), p.username) username,
                COALESCE(u.email, p.useremail) email,
                p.meetingnumber,
                p.meetinguuid,
                case
                    when SUM(p.duration) > 0 then CEILING(SUM(p.duration)/60)
                end sum_duration,
                case
                    when SUM(p.duration) > 0 then
                        CAST(SUM(p.duration)/(
                            select MAX(p2.leavetime) - MIN(p2.jointime)
                            from {local_zoomadmin_participants} p2
                            where p2.meetinguuid = p.meetinguuid
                                and p2.recording = 0
                        ) * 100 as UNSIGNED)
                end percent_duration,
                case
                    when exists (
                        select 1
                        from {local_zoomadmin_participants} p2
                        where p2.meetinguuid = p.meetinguuid
                            and p2.username = p.username
                            and p2.useremail = p.useremail
                            and p2.recording = 1
                    ) = 1 then 'Sim'
                    else 'Não'
                end accessed_recording
        ";

        $sqlfromgroup1 = "
            from {user} u
                join {role_assignments} ra on u.id = ra.userid
                join {role} r on r.id = ra.roleid
                    and r.archetype = 'student'
                join {context} cx on ra.contextid = cx.id
                    and cx.contextlevel = '50'
                join {course_modules} cm on cx.instanceid = cm.course
                join {modules} m on m.id = cm.module
                    and m.name = 'page'
                join {local_zoomadmin_recordpages} rp on cm.id = rp.pagecmid
                left join {local_zoomadmin_participants} p on p.meetingnumber = rp.zoommeetingnumber
                    and (
                        u.email = p.useremail
                        or CONCAT(u.firstname, ' ', u.lastname) = p.username
                    )
                where rp.zoommeetingnumber = ?
            group by user_key
            union
        ";

        $sqlfromgroup2 = "
            from {local_zoomadmin_participants} p
                left join (
                    {local_zoomadmin_recordpages} rp
                    join {course_modules} cm on cm.id = rp.pagecmid
                    join {modules} m on m.id = cm.module
                        and m.name = 'page'
                    join {context} cx on cx.instanceid = cm.course
                        and cx.contextlevel = '50'
                    join {role_assignments} ra on ra.contextid = cx.id
                    join {role} r on r.id = ra.roleid
                        and r.archetype = 'student'
                    join {user} u on u.id = ra.userid
                ) on rp.zoommeetingnumber = p.meetingnumber
                    and (
                        u.email = p.useremail
                        or CONCAT(u.firstname, ' ', u.lastname) = p.username
                    )
                where p.meetingnumber = ?
            group by user_key
            order by user_key
        ";

        $data->participants = $DB->get_records_sql(
            $sqlselectparticipantsonly .
            $sqlfromgroup1 .
            $sqlselectparticipantsonly .
            $sqlfromgroup2
            ,
            array($meetingnumber, $meetingnumber)
        );

        $data->participantsdata = $DB->get_records_sql(
            $sqlselectalldata .
            $sqlfromgroup1 .
            $sqlselectalldata .
            $sqlfromgroup2
            ,
            array($meetingnumber, $meetingnumber)
        );

        return $data;
    }

    private function retrieve_participants_data($meetingdata) {
        $retrieveduuids = $this->get_retrieved_participants_instances($meetingdata->id);
        if (isset($retrieveduuids[$meetingdata->uuid])) {
            return null;
        }

        $data = new \stdClass();
        $data->meetinguuid = $meetingdata->uuid;
        $data->meetingnumber = $meetingdata->id;
        $data->recording = 0;

        $rowstoinsert = array();

        $participantsdata = $this->request('report/meetings/' . urlencode(urlencode($data->meetinguuid)) . '/participants');


        foreach ($participantsdata->participants as $participant) {
            $rowdata = clone $data;

            $rowdata->useruuid = $participant->id;
            $rowdata->username = $participant->name;
            $rowdata->useremail = $participant->user_email;
            $rowdata->jointime = strtotime($participant->join_time);
            $rowdata->leavetime = strtotime($participant->leave_time);
            $rowdata->duration = $participant->duration;
            $rowdata->attentiveness = (double) str_replace('%', '', $participant->attentiveness_score);
            $rowdata->userid = $participant->user_id;

            $rowstoinsert[] = $rowdata;
        }

        $this->insert_participants_data($rowstoinsert);

        return $rowstoinsert;
    }

    private function get_retrieved_participants_instances($meetingnumber) {
        global $DB;

        $sqlstring = "
            select distinct p.meetinguuid
            from {local_zoomadmin_participants} p
            where p.meetingnumber = ?
        ";

        return $DB->get_records_sql($sqlstring, array($meetingnumber));
    }


    private function insert_participants_data($data) {
        global $DB;

        $DB->insert_records('local_zoomadmin_participants', $data);
    }

    private function insert_recording_viewed_participant_data($uuid, $userid) {
        $data = new \stdClass();

        $meetingdata = $this->get_meeting_occurence_data($uuid);
        $userdata = $this->get_moodle_user_data($userid);

        $data->meetinguuid = $uuid;
        $data->meetingnumber = $meetingdata->id;
        $data->username = $userdata->firstname . ' ' . $userdata->lastname;
        $data->useremail = $userdata->email;
        $data->jointime = time();
        $data->leavetime = $data->jointime;
        $data->duration = 0;
        $data->userid = $userid;
        $data->recording = 1;

        $this->insert_participants_data([$data]);

        return $data;
    }

    private function get_moodle_user_data($userid) {
        global $DB;
        return $DB->get_record('user', array('id' => $userid));
    }

    private function get_participants_all_instances_data($meetingnumber) {
        $filteremail = $this->get_filter_email_for_participants_report();
        $meetingnumber = str_replace('-', '', $meetingnumber);

        $data = $this->get_meeting_data($meetingnumber);
        $data->host = $this->get_user($data->host_id);

        $recordingpagedata = $this->get_recordings_page_data(array('meetingnumber' => $meetingnumber));
        $recordingpagedata = reset($recordingpagedata);

        $data->coursename = preg_replace("/\[[^\]]+\]/","",$recordingpagedata->coursename);
        $data->catname = preg_replace("/\[[^\]]+\]/","",$recordingpagedata->catname);

        $data->occurrences = array();

        $timezone = $this->get_meeting_timezone($data);
        $occurrences = $this->get_meeting_occurrences($meetingnumber);
        foreach ($occurrences as $occurrence) {
            $uuid = $occurrence->uuid;

            $this->retrieve_participants_data($this->get_recording($uuid));

            $occurrencedata = $this->get_meeting_occurence_data($uuid);
            $meetingstarttime = (new \DateTime($occurrence->start_time))->setTimezone($timezone);
            $occurrencedata->start_time_formatted = $meetingstarttime->format('d/m/Y<b\r/>H:i:s');

            $data->occurrences[] = $occurrencedata;
        }
        $data->occurrences = $this->sort_meetings_by_start($data->occurrences);
        $data->occurrencescount = sizeof($data->occurrences);
        $data->totalcols = $data->occurrencescount + 5;

        $participantsdata = $this->get_stored_all_participants_data($meetingnumber);

        $data->participants = array();

        $nonuniquefirstnames = $this->get_non_unique_first_names_from_course($meetingnumber);
        $previousfirstname = '';
        $backgroundclass = '';
        foreach ($participantsdata->participants as $key => $participantdata) {
            if (
                (!isset($filteremail) || $participantdata->email === $filteremail)
                && (!isset($participantdata->email) || $participantdata->email !== $data->host->email)
            ) {
                $participantdata->occurrences = array();
                $participantdata->attended = 0;
                $participantdata->recording = 0;
                $participantdata->avg_duration = 0;

                $firstname = str_word_count(strtolower($this->remove_accents($participantdata->username)), 1)[0];
                if (in_array($firstname, $nonuniquefirstnames) || ($firstname !== $previousfirstname)) {
                    $backgroundclass = ($backgroundclass === '') ? 'off-color' : '';
                }
                $participantdata->backgroundclass = $backgroundclass;
                $previousfirstname = $firstname;

                foreach ($data->occurrences as $occurrence) {
                    $datakey = $key . '_' . $occurrence->uuid;
                    if (isset($participantsdata->participantsdata[$datakey])) {
                        $participantoccurrencedata = $participantsdata->participantsdata[$datakey];
                        $participantoccurrencedata->displaytext = '';

                        if ($participantoccurrencedata->sum_duration) {
                            $participantoccurrencedata->displaytext = $participantoccurrencedata->sum_duration . ' (' . $participantoccurrencedata->percent_duration . '%)';

                            $participantdata->attended++;
                            $participantdata->avg_duration += $participantoccurrencedata->percent_duration;
                        }

                        if ($participantoccurrencedata->accessed_recording === 'Sim') {
                            $participantdata->recording++;
                            $participantoccurrencedata->displaytext .= $participantoccurrencedata->accessed_recording === 'Sim' ? ' <span class="recording-indicator">G</span>' : '';
                        }
                    } else {
                        $participantoccurrencedata = new \stdClass();
                        $participantoccurrencedata->displaytext = '';
                    }

                    $participantdata->occurrences[] = $participantoccurrencedata;
                }

                $participantdata->avg_duration = ($participantdata->attended) ? round($participantdata->avg_duration / $participantdata->attended) : 0;
                $participantdata->attended_or_recording = $participantdata->attended + $participantdata->recording;
                $data->participants[] = $participantdata;
            }
        }

        $data->hasdata = !empty($data->participants);
        if (!$data->hasdata) {
            $data->nodatamsg = get_string((isset($filteremail) ? 'report_no_permission' : 'report_no_data'), 'local_zoomadmin');
        }

        return $data;
    }

    /**
     * Replace accented characters with non accented
     *
     * @param $str
     * @return mixed
     * @link http://myshadowself.com/coding/php-function-to-convert-accented-characters-to-their-non-accented-equivalant/
     */
    private function remove_accents($str) {
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
        return str_replace($a, $b, $str);
    }

    private function get_non_unique_first_names_from_course($meetingnumber) {
        global $DB;

        $sqlstring = "
            select LOWER(SUBSTRING_INDEX(u.firstname, ' ', 1)) firstword
            from {local_zoomadmin_recordpages} rp
                join {course_modules} cm on cm.id = rp.pagecmid
                join {modules} m on m.id = cm.module
                    and m.name = 'page'
                join {context} cx on cx.instanceid = cm.course
                    and cx.contextlevel = '50'
                join {role_assignments} ra on ra.contextid = cx.id
                join {role} r on r.id = ra.roleid
                    and r.archetype = 'student'
                join {user} u on u.id = ra.userid
            where rp.zoommeetingnumber = ?
            group by firstword
            having COUNT(1) > 1
            order by u.firstname
        ";

        $names = $DB->get_records_sql($sqlstring, array($meetingnumber));

        $nameswithoutaccent = array();
        foreach ($names as $name) {
            $nameswithoutaccent[] = $this->remove_accents($name->firstword);
        }

        return $nameswithoutaccent;
    }
}
