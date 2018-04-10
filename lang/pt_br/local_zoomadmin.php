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
 * Textos do plugin em português do Brasil.
 *
 * Contém os textos utilizados pelo plugin, em português do Brasil.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_user'] = 'Criar usuário';
$string['add_meeting'] = 'Criar reunião';
$string['add_recording_page'] = 'Adicionar página para gravações';
$string['add_recordings_to_page'] = 'Adicionar à página';
$string['add_all_recordings_to_page'] = 'Adicionar todas as gravações pendentes às páginas';
$string['auto_delete_cmr_days'] = 'Quantidade de dias para exclusão';
$string['category_meeting'] = 'Comandos de reunião';
$string['category_recording'] = 'Comandos de gravação';
$string['category_user'] = 'Comandos de usuário';
$string['command_meeting_list'] = 'Listar reuniões';
$string['command_meeting_list_description'] = 'Lista todas as reuniões do Zoom com apresentadores gerenciados pela instituição.';
$string['command_recording_list'] = 'Listar gravações';
$string['command_recording_list_description'] = 'Lista todas as gravações na nuvem do Zoom.';
$string['command_recording_manage_pages'] = 'Gerenciar páginas de links de gravações';
$string['command_recording_manage_pages_description'] = 'Permite associar reuniões do Zoom a módulos de página, para incluir links das gravações atuomaticamente.';
$string['command_user_list'] = 'Listar usuários';
$string['command_user_list_description'] = 'Lista todos os usuários do Zoom gerenciados pela instituição.';
$string['created_at'] = 'Data de criação';
$string['department'] = 'Departamento';
$string['details'] = 'Ver detalhes';
$string['disable_cancel_meeting_notification'] = 'Desabilitar notificação por e-mail ao cancelar reunião';
$string['disable_chat'] = 'Desabilitar chat';
$string['disable_feedback'] = 'Não enviar feedback ao Zoom após reunião';
$string['disable_private_chat'] = 'Desabilitar chat privado';
$string['disable_recording'] = 'Desabilitar gravação';
$string['duration'] = 'Duração (minutos)';
$string['enable_annotation'] = 'Anotações';
$string['enable_auto_delete_cmr'] = 'Exclusão automática de gravação na nuvem';
$string['enable_auto_delete_cmr_help'] = 'Mover gravações para lixeira automaticamente após a quantidade de dias definida';
$string['enable_auto_recording'] = 'Gravação automática';
$string['enable_auto_saving_chats'] = 'Gravação automática do chat';
$string['enable_breakout_room'] = 'Subgrupos em reunião';
$string['enable_closed_caption'] = 'Legenda (closed caption)';
$string['enable_closed_caption_help'] = 'É necessário definir um participante para redigir a legenda durante a reunião.';
$string['enable_cloud_auto_recording'] = 'Gravação automática na nuvem';
$string['enable_cmr'] = 'Gravação na nuvem';
$string['enable_co_host'] = 'Co-apresentadores';
$string['enable_e2e_encryption'] = 'Criptografia da transmissão';
$string['enable_enter_exit_chime'] = 'Tocar som quando participante entra ou sai';
$string['enable_far_end_camera_control'] = 'Controle remoto da câmera';
$string['enable_file_transfer'] = 'Transferência de arquivos';
$string['enable_phone_participants_password'] = 'Gerar e exigir senha para participação por telefone';
$string['enable_polling'] = 'Enquetes';
$string['enable_remote_support'] = 'Suporte remoto';
$string['enable_share_dual_camera'] = 'Compartilhamento de câmera dupla';
$string['enable_silent_mode'] = 'Permitir suspender participante';
$string['enable_silent_mode_help'] = 'Permite que o apresentador interrompa a transmissão de áudio e vídeo para um participante.';
$string['enable_virtual_background'] = 'Tela de fundo virtual';
$string['enable_virtual_background_help'] = 'Permite substituir um fundo verde (ou outra cor uniforme) no vídeo por uma imagem.';
$string['end_time'] = 'Fim';
$string['err_exactlength'] = 'Inserir exatamente {$a} números.';
$string['error_add_recordings_to_page'] = 'Erro ao adicionar gravação à página.';
$string['error_no_page_instance_found'] = 'Página para gravações da reunião de ID {$a} não encontrada.';
$string['error_no_recordings_found'] = 'Não foi encontrado nenhum vídeo acima do tamanho mínimo ({$a}).';
$string['error_recording_already_added'] = 'Gravação já havia sido adicionada à página.';
$string['error_updating_recordpages_table'] = 'Erro ao atualizar tabela `local_zoomadmin_recordpages`. ID do registro = {$a}';
$string['file_type'] = 'Tipo';
$string['file_type_'] = 'N/D';
$string['file_type_CHAT'] = 'Chat';
$string['file_type_M4A'] = 'Áudio';
$string['file_type_MP4'] = 'Vídeo';
$string['host'] = 'Apresentador';
$string['id'] = 'ID';
$string['last_login_time'] = 'Último acesso';
$string['meeting_advanced'] = 'Configurações de reunião (avançadas)';
$string['meeting_basic'] = 'Configurações de reunião (básicas)';
$string['meeting_live'] = 'Reuniões em andamento';
$string['meeting_past'] = 'Reuniões realizadas';
$string['meeting_upcoming'] = 'Reuniões futuras';
$string['meeting_topic'] = 'Título';
$string['meeting_type_1'] = 'Instantânea';
$string['meeting_type_2'] = 'Agendada';
$string['meeting_type_3'] = 'Recorrente (sem horário fixo)';
$string['meeting_type_8'] = 'Recorrente (com horário fixo)';
$string['no_meetings'] = 'Nenhuma reunião encontrada.';
$string['no_recordings'] = 'Nenhuma gravação na nuvem encontrada.';
$string['notification_recording_edit_page_add_error'] = 'Erro ao adicionar página de gravações';
$string['notification_recording_edit_page_add_success'] = 'Página de gravações adicionada com sucesso';
$string['notification_recording_edit_page_edit_error'] = 'Erro ao editar página de gravações';
$string['notification_recording_edit_page_edit_success'] = 'Página de gravações editada com sucesso';
$string['notification_recording_edit_page_delete_error'] = 'Erro ao remover página de gravações';
$string['notification_recording_edit_page_delete_success'] = 'Página de gravações removida com sucesso';
$string['notification_user_create'] = 'Usuário {$a} criado com sucesso';
$string['notification_user_update'] = 'Usuário {$a} atualizado com sucesso';
$string['other'] = 'Outras configurações';
$string['page_cm_id'] = 'ID do módulo de página do Moodle';
$string['page_cm_id_form_description'] = 'Obtido no final da barra de endereço do navegador ao visualizar a página.<br />Por exemplo: https://lms.infnet.edu.br/moodle/mod/page/view.php?id=<b><i>123456</i></b>';
$string['pluginname'] = 'Administração do Zoom';
$string['pmi'] = 'ID de reunião pessoal';
$string['profile'] = 'Informações de perfil';
$string['recording'] = 'Gravação';
$string['recording_edit_page'] = 'Editar página de links de gravações';
$string['recording_edit_page_delete_confirm'] = 'Tem certeza de que deseja revomer esta página de links de gravações?';
$string['recordings_added_to_page'] = 'Gravação adicionada à página com sucesso. <a href="{$a}">Clique aqui para abrir a página.</a>';
$string['recording_part'] = 'parte';
$string['recording_status_completed'] = 'Disponível';
$string['recording_status_processing'] = 'Em processamento';
$string['recording_text_MP4'] = 'Vídeo da aula';
$string['recording_text_CHAT'] = 'Transcrição do chat';
$string['security'] = 'Segurança';
$string['size'] = 'Tamanho';
$string['start_time'] = 'Início';
$string['status'] = 'Situação';
$string['type'] = 'Tipo';
$string['user_details'] = 'Detalhes de usuário';
$string['user_pending'] = 'Usuários com confirmação de e-mail pendente';
$string['user_type'] = 'Tipo de usuário';
$string['user_type_1'] = 'Básico';
$string['user_type_2'] = 'Profissional';
$string['user_type_3'] = 'Corporativo';
$string['view_recording'] = 'Visualizar';
$string['zoom'] = 'Zoom';
$string['zoom_meeting_number'] = 'Número de reunião do Zoom';
$string['zoom_meeting_number_form_description'] = 'Número de 9 ou 10 dígitos, obtido na página da reunião do Zoom. Por favor, inclua apenas os números, sem hífens.';
$string['zoomadmin:managezoom'] = 'Administrar Zoom';
$string['zoom_command_error'] = 'Erro na API do Zoom. Código: {$a->code}, mensagem: "{$a->message}"';
