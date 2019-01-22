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
 * Tarefa para enviar todas as gravações de um curso para o Google Drive.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_zoomadmin\task;
defined('MOODLE_INTERNAL') || die();

class send_course_recordings_to_google_drive_task extends \core\task\adhoc_task {
	public function execute() {
        $zoomadmin = new \local_zoomadmin\zoomadmin();
		$data = $this->get_custom_data();

		$message = $zoomadmin->send_course_recordings_to_google_drive($data);
		// $zoomadmin->add_log('send_course_recordings_to_google_drive_task->execute', $message);
		mtrace($message);
	}
}
