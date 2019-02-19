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
 * Página de configurações do plugin.
 *
 * Inclui as páginas do plugin no menu lateral de administração do site, dentro
 * do item Plugins/Plugins locais.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if (has_capability('local/zoomadmin:managezoom', context_system::instance())) {
    $ADMIN->add('localplugins', new admin_category('zoom', get_string('zoom', 'local_zoomadmin')));
    $ADMIN->add(
        'zoom',
        new admin_externalpage(
            'local_zoomadmin_index',
            get_string('pluginname', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/index.php')
        )
    );

    $ADMIN->add(
        'zoom',
        new admin_externalpage(
            'local_zoomadmin_log',
            get_string('log', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/log.php')
        )
    );

    $ADMIN->add('zoom', new admin_category('zoom_category_user', get_string('category_user', 'local_zoomadmin')));
    $ADMIN->add(
        'zoom_category_user',
        new admin_externalpage(
            'local_zoomadmin_user_list',
            get_string('command_user_list', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/user_list.php')
        )
    );

    $ADMIN->add('zoom', new admin_category('zoom_category_meeting', get_string('category_meeting', 'local_zoomadmin')));
    $ADMIN->add(
        'zoom_category_meeting',
        new admin_externalpage(
            'local_zoomadmin_meeting_list',
            get_string('command_meeting_list', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/meeting_list.php')
        )
    );

    $ADMIN->add(
        'zoom_category_meeting',
        new admin_externalpage(
            'local_zoomadmin_participants',
            get_string('participants', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/participants.php')
        )
    );

    $ADMIN->add('zoom', new admin_category('zoom_category_recording', get_string('category_recording', 'local_zoomadmin')));
    $ADMIN->add(
        'zoom_category_recording',
        new admin_externalpage(
            'local_zoomadmin_recording_list',
            get_string('command_recording_list', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/recording_list.php')
        )
    );
    $ADMIN->add(
        'zoom_category_recording',
        new admin_externalpage(
            'local_zoomadmin_recording_manage_pages',
            get_string('command_recording_manage_pages', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/recording_manage_pages.php')
        )
    );
    $ADMIN->add(
        'zoom_category_recording',
        new admin_externalpage(
            'local_zoomadmin_send_course_recordings_to_google_drive',
            get_string('send_course_recordings_to_google_drive', 'local_zoomadmin'),
            new moodle_url('/local/zoomadmin/send_course_recordings_to_google_drive.php')
        )
    );
}
