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
 * Textos do plugin em inglês.
 *
 * Contém os textos utilizados pelo plugin, em inglês.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_user'] = 'Create user';
$string['add_meeting'] = 'Create meeting';
$string['add_recording_page'] = 'Add page for recording links';
$string['add_recordings_to_page'] = 'Add to page';
$string['add_all_recordings_to_page'] = 'Add all pending recordings to pages';
$string['auto_delete_cmr_days'] = 'Number of days for auto delete';
$string['category_meeting'] = 'Meeting commands';
$string['category_recording'] = 'Recording commands';
$string['category_user'] = 'User commands';
$string['command_meeting_list'] = 'List meetings';
$string['command_meeting_list_description'] = 'Lists all Zoom meetings with hosts managed by the school.';
$string['command_recording_list'] = 'List recordings';
$string['command_recording_list_description'] = 'Lists all Zoom cloud recordings with hosts managed by the school.';
$string['command_recording_manage_pages'] = 'Manage recording link pages';
$string['command_recording_manage_pages_description'] = 'Allows to associate Zoom meetings to page modules, so recording links can be added automatically.';
$string['command_user_list'] = 'List users';
$string['command_user_list_description'] = 'Lists all Zoom users managed by the school.';
$string['created_at'] = 'Creation date';
$string['department'] = 'Department';
$string['details'] = 'See details';
$string['disable_cancel_meeting_notification'] = 'Disable email notification when a meeting is cancelled';
$string['disable_chat'] = 'Disable chat';
$string['disable_feedback'] = 'Disable feedback to Zoom';
$string['disable_private_chat'] = 'Disable private chat';
$string['disable_recording'] = 'Disable recording';
$string['duration'] = 'Duration (minutes)';
$string['enable_annotation'] = 'Annotations';
$string['enable_auto_delete_cmr'] = 'Auto delete cloud recordings after days';
$string['enable_auto_delete_cmr_help'] = 'Cloud recordings will be moved to trash after it the specified number of days.';
$string['enable_auto_recording'] = 'Automatic recording';
$string['enable_auto_saving_chats'] = 'Auto saving chats';
$string['enable_breakout_room'] = 'Breakout rooms';
$string['enable_closed_caption'] = 'Closed caption';
$string['enable_closed_caption_help'] = 'An attendee must be assigned to type the caption during the meeting.';
$string['enable_cloud_auto_recording'] = 'Automatic recording on cloud.';
$string['enable_cmr'] = 'Cloud recording';
$string['enable_co_host'] = 'Co-host';
$string['enable_e2e_encryption'] = 'End-to-end encryption';
$string['enable_enter_exit_chime'] = 'Play sound on join/leave';
$string['enable_far_end_camera_control'] = 'Far end camera control';
$string['enable_file_transfer'] = 'File transfer';
$string['enable_phone_participants_password'] = 'Generate and require password for participants joining by phone';
$string['enable_polling'] = 'Polling';
$string['enable_remote_support'] = 'Remote support';
$string['enable_share_dual_camera'] = 'Share dual camera';
$string['enable_silent_mode'] = 'Allow host to put attendee on hold';
$string['enable_silent_mode_help'] = 'Attendee on hold allows host to stop video and audio transmission to a participant.';
$string['enable_virtual_background'] = 'Virtual background';
$string['enable_virtual_background_help'] = 'Allows to replace a green (or another solid color) background on the video with an image.';
$string['end_time'] = 'End time';
$string['err_exactlength'] = 'You must enter exactly {$a} characters here.';
$string['error_add_recordings_to_page'] = 'Error adding recording to the page.';
$string['error_no_page_instance_found'] = 'Page for recording links from meeting ID {$a} not found.';
$string['error_no_recordings_found'] = 'No videos over the minimum file size found.';
$string['error_recording_already_added'] = 'Recording was already added to page.';
$string['error_updating_recordpages_table'] = 'Error updating `local_zoomadmin_recordpages` table. Row ID = {$a}';
$string['file_type'] = 'File type';
$string['file_type_'] = 'N/D';
$string['file_type_CHAT'] = 'Chat';
$string['file_type_M4A'] = 'Audio';
$string['file_type_MP4'] = 'Video';
$string['host'] = 'Host';
$string['id'] = 'ID';
$string['last_login_time'] = 'Last login';
$string['meeting_advanced'] = 'Meeting settings (advanced)';
$string['meeting_basic'] = 'Meeting settings (basic)';
$string['meeting_live'] = 'Live meetings';
$string['meeting_past'] = 'Past meetings';
$string['meeting_upcoming'] = 'Upcoming meetings';
$string['meeting_topic'] = 'Topic';
$string['meeting_type_1'] = 'Instant';
$string['meeting_type_2'] = 'Scheduled';
$string['meeting_type_3'] = 'Recurring (no fixed time)';
$string['meeting_type_8'] = 'Recurring (fixed time)';
$string['no_meetings'] = 'No meetings found.';
$string['no_recordings'] = 'No cloud recordings found.';
$string['notification_recording_edit_page_add_error'] = 'Error adding recording page';
$string['notification_recording_edit_page_add_success'] = 'Recording page added successfully';
$string['notification_recording_edit_page_edit_error'] = 'Error editing recording page';
$string['notification_recording_edit_page_edit_success'] = 'Recording page edited successfully';
$string['notification_recording_edit_page_delete_error'] = 'Error removing recording page';
$string['notification_recording_edit_page_delete_success'] = 'Recording page removed successfully';
$string['notification_user_create'] = 'User {$a} created successfully';
$string['notification_user_update'] = 'User {$a} updated successfully';
$string['other'] = 'Other settings';
$string['page_cm_id'] = 'Moodle page module ID';
$string['page_cm_id_form_description'] = 'Obtained from end of the browser address bar when viewing the page.<br />For example: https://lms.infnet.edu.br/moodle/mod/page/view.php?id=<b><i>123456</i></b>';
$string['pluginname'] = 'Zoom administration';
$string['pmi'] = 'Personal Meeting ID';
$string['profile'] = 'Profile info';
$string['recording'] = 'Recording';
$string['recording_edit_page'] = 'Edit recording links page';
$string['recording_edit_page_delete_confirm'] = 'Are you sure you want to remove this recording page?';
$string['recordings_added_to_page'] = 'Meeting recording successfully added to page. <a href="{$a}">Click here to open page.</a>';
$string['recording_part'] = 'part';
$string['recording_status_completed'] = 'Completed';
$string['recording_status_processing'] = 'Processing';
$string['recording_text_MP4'] = 'Class video';
$string['recording_text_CHAT'] = 'Chat transcript';
$string['security'] = 'Security';
$string['size'] = 'Size';
$string['start_time'] = 'Start time';
$string['status'] = 'Status';
$string['type'] = 'Type';
$string['user_details'] = 'User details';
$string['user_pending'] = 'Users pending e-mail confirmation';
$string['user_type'] = 'User type';
$string['user_type_1'] = 'Basic';
$string['user_type_2'] = 'Pro';
$string['user_type_3'] = 'Corp';
$string['view_recording'] = 'View';
$string['zoom'] = 'Zoom';
$string['zoom_meeting_number'] = 'Zoom meeting number';
$string['zoom_meeting_number_form_description'] = '9 or 10 digit number, obtained from Zoom meeting page. Please include only numbers, without hyphens.';
$string['zoomadmin:managezoom'] = 'Manage Zoom';
$string['zoom_command_error'] = 'Zoom API error. Code: {$a->code}, message: "{$a->message}"';
