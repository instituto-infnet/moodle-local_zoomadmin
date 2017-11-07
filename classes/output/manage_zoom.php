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
    const MAX_PAGE_SIZE = 300;

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
     * @return stdClass Dados a serem utilizados pelo template.
     */
    public function export_index_for_template() {
        $data = new \stdClass();
        $data->categories = $this->get_index_commands();

        return $data;
    }

    /**
     * Obtém a lista de usuários do Zoom e envia ao template,
     * para ser exibida na tela.
     * @return stdClass Dados a serem utilizados pelo template.
     */
    public function export_user_list_for_template($renderer) {
        $zoomadmin = $this->zoomadmin;
        $data = $zoomadmin->request($zoomadmin->commands['user_list'], $this->params);
        $pending = $zoomadmin->request($zoomadmin->commands['user_pending'], $this->params);

        $data->user_get_url = './user_get.php';
        $data->user_list_url = './user_list.php';
        $data->button_add = $renderer->single_button(
            new \moodle_url('/local/zoomadmin/user_get.php', array('zoom_command' => 'user_create')),
            get_string('add_user', 'local_zoomadmin'),
            'get'
        );

        $data->page_count = max((int)$data->page_count, (int)$pending->page_count);
        $data->pending = $pending->users;
        foreach (array_merge($data->users, $data->pending) as $user) {
            $user->type_string = get_string('user_type_' . $user->type, 'local_zoomadmin');
            $user->last_login_time_formatted = (new \DateTime($user->lastLoginTime))->format('d/m/Y H:i:s');
            $user->created_at_formatted = (new \DateTime($user->created_at))->format('d/m/Y H:i:s');
        }

        $data->users = $this->sort_users_by_name($data->users);
        $data->pending = $this->sort_users_by_name($data->pending);
        
        $data->pages = $this->get_pagination((int)$data->page_number, $data->page_count);

        return $data;
    }

    /**
     * Obtém a lista de reuniões do Zoom e envia ao template,
     * para ser exibida na tela.
     * @return stdClass Dados a serem utilizados pelo template.
     */
    public function export_meeting_list_for_template($renderer) {
        $data = $this->get_meetings_list();
        
        $data->meeting_get_url = './meeting_get.php';
        $data->meeting_list_url = './meeting_list.php';
        $data->user_get_url = './user_get.php';
        $data->button_add = $renderer->single_button(
            new \moodle_url('/local/zoomadmin/meeting_get.php', array('zoom_command' => 'meeting_create')),
            get_string('add_meeting', 'local_zoomadmin'),
            'get'
        );

        $data->live = $this->set_meetings_data($data->live->meetings);
        $data->meetings = $this->set_meetings_data($data->meetings, true);
        
        $data->live = $this->sort_meetings_by_start($data->live);
        $data->meetings->past = $this->sort_meetings_by_start($data->meetings->past, false);
        $data->meetings->upcoming = $this->sort_meetings_by_start($data->meetings->upcoming);
        
        $data->pages = $this->get_pagination((int)$data->page_number, $data->page_count);

        return $data;
    }

    /**
     * Obtém a lista de gravações do Zoom e envia ao template,
     * para ser exibida na tela.
     * @return stdClass Dados a serem utilizados pelo template.
     */
    public function export_recording_list_for_template($renderer) {
        $data = $this->get_recordings_list();
        
        $data->recording_list_url = './recording_list.php';
        $data->recording_get_url = './recording_get.php';
        $data->user_get_url = './user_get.php';
        
        $data->meetings = $this->sort_meetings_by_start($data->meetings, false);
        $data->pages = $this->get_pagination((int)$data->page_number, $data->page_count);

        return $data;
    }

    private function get_index_commands() {
        $indexcommands = array_filter($this->zoomadmin->commands, function($cmd){return $cmd->showinindex === true;}) ;

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

    private function get_meetings_list() {
        $zoomadmin = $this->zoomadmin;

        $meetingsdata = new \stdClass();
        
        $meetingsdata = $zoomadmin->request($zoomadmin->commands['meeting_live'], $this->params);
        $meetingsdata->live = $meetingsdata->meetings;
        
        $userdata = $zoomadmin->request($zoomadmin->commands['user_list'], array('page_size' => $this::MAX_PAGE_SIZE));
        $users = $userdata->users;
        
        $meetingsdata->meetings = array();
        
        foreach ($users as $user) {
            $this->params['host_id'] = $user->id;
            
            $usermeetings = $zoomadmin->request($zoomadmin->commands['meeting_list'], $this->params);
            $usermeetings->total_records = (int)$usermeetings->total_records;
            
            if ($usermeetings->total_records > 0) {
                foreach($usermeetings->meetings as $index => $meeting) {
                    $usermeetings->meetings[$index]->host = $user;
                }

                $meetingsdata->total_records = (int)$meetingsdata->total_records + (int)$usermeetings->total_records;
                $meetingsdata->page_count = max((int)$meetingsdata->page_count, (int)$usermeetings->page_count);
                
                $meetingsdata->meetings = array_merge($meetingsdata->meetings, $usermeetings->meetings);
            }
            
        }

        foreach ($meetingsdata->live as $index => $meeting) {
            foreach ($users as $user) {
                if ($user->id === $meeting->host_id) {
                    $meetingsdata->live[$index]->host = $user;
                    break;
                }
            }
        }
        
        return $meetingsdata;
    }
    
    private function get_recordings_list() {
        $zoomadmin = $this->zoomadmin;

        $userdata = $zoomadmin->request($zoomadmin->commands['user_list'], array('page_size' => $this::MAX_PAGE_SIZE));
        $users = $userdata->users;
        
        $recordingsdata = new \stdClass();
        $recordingsdata->meetings = array();
        
        foreach ($users as $user) {
            $this->params['host_id'] = $user->id;
            
            $userrecordings = $zoomadmin->request($zoomadmin->commands['recording_list'], $this->params);
            $recordingsdata->total_records = (int)$userrecordings->total_records;
            
            if ($recordingsdata->total_records > 0) {
                foreach($userrecordings->meetings as $index => $meeting) {
                    $userrecordings->meetings[$index]->host = $user;
                }

                $recordingsdata->total_records += (int)$userrecordings->total_records;
                $recordingsdata->page_count = max((int)$recordingsdata->page_count, (int)$userrecordings->page_count);
                
                $recordingsdata->meetings = $this->set_recordings_data(array_merge($recordingsdata->meetings, $userrecordings->meetings));
            }
            
        }

        return $recordingsdata;
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
                $meetingswithoccurrences = array_merge($meetingswithoccurrences, $this->get_meeting_occurrences($meeting));
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
            }
        }
        
        if ($separatepastupcoming === true) {
            return $meetingsbydate;
        } else {
            return $meetingswithoccurrences;
        }
    }

    private function set_recordings_data($meetings) {
        foreach($meetings as $meetingindex => $meeting) {
            $timezone =  new \DateTimeZone((isset($meeting->timezone)) ? $meeting->timezone : (isset($meeting->host->timezone)) ? $meeting->host->timezone : 'America/Sao_Paulo');
                
            foreach($meeting->recording_files as $fileindex => $file) {
                $recordingstarttime = (new \DateTime($file->recording_start))->setTimezone($timezone);
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_start_formatted = $recordingstarttime->format('d/m/Y H:i:s');
                
                $recordingendtime = (new \DateTime($file->recording_end))->setTimezone($timezone);
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_end_formatted = $recordingendtime->format('d/m/Y H:i:s');
                
                $timediff = $recordingstarttime->diff($recordingendtime);
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_duration = sprintf('%02d', $timediff->h) . ':' . sprintf('%02d', $timediff->i) . ':' . sprintf('%02d', $timediff->s);
                
                $meetings[$meetingindex]->recording_files[$fileindex]->meeting_number_formatted = $this->format_meeting_number($meetings[$meetingindex]->meeting_number);
                
                $meetings[$meetingindex]->recording_files[$fileindex]->file_size_formatted = $this->format_file_size($meetings[$meetingindex]->recording_files[$fileindex]->file_size);
                
                $meetings[$meetingindex]->recording_files[$fileindex]->file_type_string = get_string('file_type_' . $file->file_type, 'local_zoomadmin');
                $meetings[$meetingindex]->recording_files[$fileindex]->recording_status_string = get_string('recording_status_' . $file->status, 'local_zoomadmin');
            }
        }
        
        return $meetings;
    }
    
    private function format_meeting_number($meetingnumber) {
        return number_format($meetingnumber, 0, '', '-');
    }
    
    private function format_file_size($filesize) {
        if ($filesize < 1024) {
            return $filesize . ' B';
        } else if ($filesize < 1048576) {
            return floor($filesize / 1024) . ' KB';
        } else if ($filesize < 1073741824) {
            return floor($filesize / 1048576) . ' MB';
        } else {
            return floor($filesize / 1073741824) . ' GB';
        }
    }
    
    private function get_meeting_occurrences($meeting) {
        $zoomadmin = $this->zoomadmin;
        
        $occurrences = array();
        
        $meetingdata = $zoomadmin->request($zoomadmin->commands['meeting_get'], array('id' => $meeting->id, 'host_id' => $meeting->host_id));
        
        foreach ($meetingdata->occurrences as $occurrence) {
            $occurrencewithdata = clone $meeting;
            
            foreach ($occurrence as $key => $value) {
                $occurrencewithdata->$key = $value;
            }

            $occurrences[] = $occurrencewithdata;
        }
        
        return $occurrences;
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
    
    private function sort_meetings_by_start($meetings, $ascending = true) {
        usort($meetings, function($meeting1, $meeting2) {
            if ($meeting1->start_time == $meeting2->start_time) {
                return 0;
            }
            
            if ($ascending === true) {
                return ($meeting1->start_time < $meeting2->start_time) ? -1 : 1;
            } else {
                return ($meeting1->start_time > $meeting2->start_time) ? -1 : 1;
            }
        });
        
        return $meetings;
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
