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
 * Contém uma classe para gerenciar ações utilizando a API do Google.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace local_zoomadmin;
defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../google-api/vendor/autoload.php');

/**
 * Classe de acesso à API do Google.
 *
 * Instancia um cliente da API e realiza ações com ele.
 * https://github.com/numsu/google-drive-sdk-api-php-insert-file-parent-example/
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class google_api_controller {
    const CREDENTIALS_PATH = __DIR__ . '/../google-credentials.json';
    const TOKEN_PATH = __DIR__ . '/../google-token.json';
    const MIME_TYPE_FOLDER = 'application/vnd.google-apps.folder';

    var $client;
    var $service;
    var $rootfolderid;

    public function __construct() {
        $this->set_credential_consts();
        $this->client = $this->get_client();
        $this->set_token($this->client);

        $this->service = new Google_Service_Drive($this->client);
    }

    public function create_drive_file($filedata) {
        try {
            $simpleupload = true;
            $maxsizesimpleupload = 500 * 1024 * 1024;
            $uploaddata = null;

            $file = new Google_Service_Drive_DriveFile(array(
                'name' => $filedata['name'],
                'parents' => $filedata['parents'],
                'mimeType' => $filedata['mime_type']
            ));

            if (isset($filedata['file_url'])) {
                $fileurl = $filedata['file_url'];

                foreach (get_headers($fileurl, true)['Content-Length'] as $filesize) {
                    if ($filesize > 0) {
                        break;
                    }
                }

                $simpleupload = $filesize <= $maxsizesimpleupload;

                if ($simpleupload === true) {
                    $uploaddata = file_get_contents($fileurl);
                } else {
                    $tempfilepath = $this->create_local_temp_file($filedata);

                    $client = $this->client;
                    // Call the API with the media upload, defer so it doesn't
                    // immediately return.
                    $client->setDefer(true);
                }
            }

            $request = $this->service->files->create($file, array(
                'data' => $uploaddata,
                'uploadType' => 'multipart',
                'fields' => 'id, name, mimeType, parents, webViewLink'
            ));

            if ($simpleupload === true) {
                $result = $request;
            } else {
                $chunksizebytes = 100 * 1024 * 1024;

                $media = new Google_Http_MediaFileUpload(
                    $client,
                    $request,
                    $filedata['mime_type'],
                    null,
                    true,
                    $chunksizebytes
                );

                $media->setFileSize($filesize);

                // Upload the various chunks. $status will be false
                // until the process is complete.
                $status = false;
                $handle = fopen($tempfilepath, 'rb');

                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunksizebytes);
                    $status = $media->nextChunk($chunk);
                }

                // The final value of $status will be the data from the API
                // for the object that has been uploaded.
                $result = false;
                if($status != false) {
                    $result = $status;
                }

                fclose($handle);

                unlink($tempfilepath);

                // Reset to the client to execute requests
                // immediately in the future.
                $client->setDefer(false);
            }
        } catch (Exception $err) {
            print_object($err->getMessage());
        }

        return $result;
    }

    public function oauth2callback($param) {
        if (!isset($param['verification_code'])) {
            redirect($this->client->createAuthUrl());
        } else {
            if (!file_exists(dirname($this::TOKEN_PATH))) {
                mkdir(dirname($this::TOKEN_PATH), 0700, true);
            }
            file_put_contents($this::TOKEN_PATH, json_encode($client->getAccessToken()));

            redirect($_SESSION['google_api_previous_uri']);
        }
    }

    public function get_google_drive_folder($foldernamestree) {
        $parentid = ROOT_FOLDER_ID;

        foreach ($foldernamestree as $foldername) {
            $folder = $this->get_folder_by_name_and_parent($foldername, $parentid);

            if (!isset($folder)) {
                $folder = $this->create_drive_file(array(
                    'name' => $foldername,
                    'mime_type' => $this::MIME_TYPE_FOLDER,
                    'parents' => array($parentid)
                ));
            }

            $parentid = $folder->id;
        }

        return $folder;
    }

    public function get_google_drive_files_from_folder($folder) {
        try {
            $filelist = $this->service->files->listFiles(array(
                'q' => '' .
                    '"' . $folder->id . '" in parents ' .
                    'and trashed = false' .
                '',
                'spaces' => 'drive',
                'fields' => 'files(id, name, mimeType, parents, webViewLink)'
            ));

            $files = $filelist->files;

            return $filelist->files;
        } catch (Exception $err) {
            print_object($err);
        }
    }

    private function set_credential_consts() {
        $json = json_decode(file_get_contents($this::CREDENTIALS_PATH), true);

        if (!defined('CLIENT_ID')) {
            define('CLIENT_ID', $json['webclient_id']);
        }
        if (!defined('CLIENT_SECRET')) {
            define('CLIENT_SECRET', $json['webclient_key']);
        }
        if (!defined('ROOT_FOLDER_ID')) {
            define('ROOT_FOLDER_ID', $json['root_folder_id']);
        }
    }

    private function get_client() {
        $client = new Google_Client();
        $client->setClientId(CLIENT_ID);
        $client->setClientSecret(CLIENT_SECRET);
        $client->setRedirectUri((new \moodle_url('/local/zoomadmin/oauth2callback.php'))->out());
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');

        return $client;
    }

    private function set_token() {
        if (file_exists($this::TOKEN_PATH)) {
            $token = json_decode(file_get_contents($this::TOKEN_PATH), true);
        }

        if (
            isset($token)
            && (
                time() < $token['created'] + $token['expires_in']
                || isset($token['refresh_token'])
            )
        ) {
            $this->client->setAccessToken($token);
        } else {
            $_SESSION['google_api_previous_uri'] = $_SERVER['REQUEST_URI'];
            redirect(new \moodle_url('/local/zoomadmin/oauth2callback.php'));
        }
    }

    private function share_file_with_anyone($file) {
        $permission = new Google_Service_Drive_Permission();

        $permission->setType('anyone');
        $permission->setRole('reader');

        try {
            $this->service->permissions->create($file->id, $permission);
        } catch (Exception $err) {
            print_object($err);
        }
    }

    private function get_folder_by_name_and_parent($name, $parentid) {
        try {
            $filelist = $this->service->files->listFiles(array(
                'q' => '' .
                    'mimeType = "' . $this::MIME_TYPE_FOLDER . '" ' .
                    'and name = "' . $name . '" ' .
                    'and "' . $parentid . '" in parents ' .
                    'and trashed = false' .
                '',
                'spaces' => 'drive',
                'fields' => 'files(id)'
            ));

            $files = $filelist->files;

            return (count($files) > 0) ? $files[0] : null;
        } catch (Exception $err) {
            print_object($err);
        }
    }

    private function create_local_temp_file($filedata) {
        global $CFG;

        $filepath = $CFG->dataroot . '/temp/local-zoomadmin';

        $parts = explode('/', $filepath);
        $filepath = '';
        foreach($parts as $part) {
            if (!is_dir($filepath .= '/' . $part)) {
                mkdir($filepath);
            }
        }

        $filepath .= '/' . $filedata['id'];

        file_put_contents($filepath, file_get_contents($filedata['file_url']));

        return $filepath;
    }
}
