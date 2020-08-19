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

// namespace local_zoomadmin; // não lembramos pq ficou comentado e não fica em nenhum namespace
// em qual fica? não sabemos direito dizer, talvez em algum default

// Se isso for rodar fora do Moodle, então o PHP para a execução
defined('MOODLE_INTERNAL') || die;

// Esse comando carrega o cliente inteiro de PHP para a API do Google Drive
// Nós não atualizamos este cliente, esta é uma versão antiga, mas não parece haver necessidade de atualizar
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
    const LB = '
';

    // Com isso, estamos definindo propriedades da classe que valerão em todas as funções
    // interessante que como o PHP é fracamente tipado, a gente não precisa dizer o que são essas propriedades
    // $client por exemplo depois vai virar um objeto razoavelmente complexo
    var $client;
    var $service;
    var $rootfolderid;

    // Esta é a função que é chamada toda vez que esta classe é instanciada
    public function __construct() {
        $this->set_credential_consts(); // Função que será definida depois e que define as credenciais para acesso ao Google Drive (GD)
        $this->client = $this->get_client(); // A propriedade client que definimos acima ganha como conteúdo o resultado da execução da função get_client, definida abaixo
        $this->set_token($this->client); // Função que será definida depois e que atribui um token para este client

        // Definimos agora a propriedade service (declarada acima) como sendo o resultado da função Google_Service_Drive para este client
        // Notando que o comando new Google_Service_Drive constrói uma instância daquela classe para este client e Google_Service_Drive está definido no cliente PHP para o Google Drive que importamos acima, na linha 35
        $this->service = new Google_Service_Drive($this->client);
    }

    // Esta função aqui nós construímos para criar um arquivo no Google Drive, alocando os metadados e enviando
    public function create_drive_file($filedata) {
        // Esta estrutura try/catch é para capturar erros, tratar erros
        // Boa para coisas que não dependem do nosso código, que é bem este caso em que temos várias coisas no meio do caminho
        try {
            $simpleupload = true; // Significa que não faremos upload em etapa, que mandamos o arquivo de uma só vez
            $maxsizesimpleupload = 300 * 1024 * 1024; // Define como tamanho máximo para esta situação 300 megabytes
            $uploaddata = null; // zera os dados de upload

            // Instanciamos o objeto DriveFile com o construtor da classe definido na interface PHP para o GD
            $file = new Google_Service_Drive_DriveFile(array(
                'name' => $filedata['name'],
                'parents' => $filedata['parents'],
                'mimeType' => $filedata['mime_type']
            )); // O array permite dois tipos de itens, os numerados e os nomeados, propriedades ou chave, como se fossem um objeto qualquer
                // Esta seta com o => é uma maneira de atribuirmos chaves (keys) no array
                // Notando que o $filedata foi criado lá na API Zoomadmin, com uma combinação do que precisamos para criar o arquivo

            // Se o filedata tem URL, ele cria o arquivo fazendo o upload usando a URL
            // Nao lembramos por que fazemos este if, mas o que esta dentro do IF faz o download do arquivo do Zoom para dentro de uploaddata
            if (isset($filedata['file_url'])) {
                $fileurl = $filedata['file_url'];

                // Detalhamento do HTTP do tamanho do arquivo, para ver se está de acordo com o esperado; se não estiver, quebra
                // Precisa do foreach para achar o tamanho real do arquivo; notando que o protocolo que usamos pra transferir os arquivos é HTTP
                foreach (get_headers($fileurl, true)['Content-Length'] as $filesize) {
                    print_r($this::LB . '$filesize: ' . $filesize);
                    if ($filesize > 0) {
                        break;
                    }
                }

                // se tiver no header http o file data, o usa, senão pega do que veio da API do Zoom
                // basicamente atribui o filesize se variável tiver conteúdo, senão cai no else
                // se filesize tiver conteúdo, não faz nada, daí o ?:
                $filesize = $filesize ?: $filedata['file_size'];
                // iguala os dois
                $filedata['file_size'] = $filesize;

                // verifica se o tamanho é menor, retornando um boleano
                $simpleupload = $filesize <= $maxsizesimpleupload;
                // imprime uma mensagem com quebra de linha LB e algumas informações
                print_r($this::LB . $filesize . ' bytes ' . (($simpleupload) ? '<= ' : '> ') . $maxsizesimpleupload);

                // se o arquivo for pequeno o bastante, faz o download para o GD
                if ($simpleupload === true) {
                    $curl = new \curl();
                    $uploaddata = $curl->download_one($fileurl, null); // baixa o arquivo para a memória RAM do servidor do Moodle, nesta variável $uploaddata
                } else { 
                    
                    // se o arquivo não for pequeno, ele vai fazer o donload para um arquivo temporário
                    // vale notar que não verificamos a existência de memória ou espaço em disco
                    print_r($this::LB . 'creating local temp file');
                    $tempfilepath = $this->create_local_temp_file($filedata); // nesta função, que está a seguir, ele baixa o arquivo do Zoom e devolve o caminho do arquivo criado

                    $client = $this->client; // isto é só um atalho para o nome, não cria uma cópia deste objeto
                    
                    // Call the API with the media upload, defer so it doesn't
                    // immediately return. Isto é: fica determinado que depois o upload será feito com "deferimento"
                    $client->setDefer(true);
                }
            }

            // chamada à API do Google para criar o arquivo no Google Drive
            // notando que o uploaddata pode estar vazio, se o arquivo maior que 300 mega, caso em que usamos o arquivo temporário
            $request = $this->service->files->create($file, array(
                'data' => $uploaddata, // variável que tem os dados do arquivo  baixado do Zoom
                'uploadType' => 'multipart', // usamos sempre o upload em multipart mesmo que seja pequeno
                'fields' => 'id, name, mimeType, parents, webViewLink' // o que precisaremos do arquivo depois
            ));

            print_r($this::LB . 'file created on Google Drive');

            // Verifica se é um simples upload ou entao se tem que trabalhar o envio do arquivo por partes
            if ($simpleupload === true) {
                $result = $request;
            } else {
                $chunksizebytes = 100 * 1024 * 1024; // define que cada parte tem 100 mega

                // aqui usamos uma outra classe oferecida pelo cliente PHP do Google Drive para fazer o upload do arquivo temporário para o GD
                // esse objeto é o que representa o upload
                $media = new Google_Http_MediaFileUpload(
                    $client,
                    $request,
                    $filedata['mime_type'],
                    null,
                    true,
                    $chunksizebytes
                );

                // define o tamanho do arquivo
                $media->setFileSize($filesize);

                // Upload the various chunks. $status will be false
                // until the process is complete.
                $status = false;
                $handle = fopen($tempfilepath, 'rb'); // abre arquivo temporário gravado localmente, fopen é um comando do PHP

                // faz o upload do arquivo para o GD
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunksizebytes);
                    $status = $media->nextChunk($chunk);

                    $progress = (!$status) ? round(100 * ($media->getProgress() / $filesize), 2) : 100;

                    print_r($this::LB .
                        'sucessfully uploaded file up to byte ' . $media->getProgress() .
                        ' which is ' . $progress .
                        '% of the whole file'
                    );
                }

                print_r($this::LB . 'upload done');

                // The final value of $status will be the data from the API
                // for the object that has been uploaded.
                // Isto aqui serve para retornar o status da transferência caso a API retorne ainda um status falso
                $result = false;
                if($status != false) { // se o status for diferente de falso, deu tudo certo
                    $result = $status;
                }

                // fecha o arquivo na memória
                fclose($handle);
                // desmonta o arquivo no disco, apaga do disco
                unlink($tempfilepath);

                // Reset to the client to execute requests
                // immediately in the future.
                $client->setDefer(false);
            }
        } catch (Google_Service_Exception $err) {
            print_r($this::LB . 'google exception:' . $this::LB);
            // print_r($err); // está comentado pois senão ele imprime o arquivo inteiro, enorme, o que torna inviável debugar
        } catch (Exception $err) {
            print_r($this::LB . $err . $this::LB); // caso em que é uma exceção que não é específica do Google Service, uma mais geral
        }

        print_r($this::LB . 'file transfer complete' . $this::LB);
        return $result;
    }

    // Para autenticação no Google Drive; vê se arquivo de token na pasta e caso não exista manda para URL pedindo a permissão
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

    // Função que pega o folder do google drive para o arquivo específico
    public function get_google_drive_folder($foldernamestree) {
        $parentid = ROOT_FOLDER_ID; // pega a raiz que é definida no arquivo de instalação do plugin

        // foldernamestree é o vetor com a árvore de categorias do moodle de cima para baixo
        foreach ($foldernamestree as $foldername) {
            // se não tiver todas as categorias, ele vai adiante
            if (is_null($foldername)) {
                continue;
            }

            // chama a função que está logo abaixo e que traz o folder de dentro da pasta que você está olhando
            $folder = $this->get_folder_by_name_and_parent($foldername, $parentid);

            // se não existe o folder (p. ex. a primeira aula da classe ou da disciplina)
            if (!isset($folder)) {
                $folder = $this->create_drive_file(array( // chama aquela função que já vimos acima sem a url, para criar uma pasta
                    'name' => $foldername,
                    'mime_type' => $this::MIME_TYPE_FOLDER,
                    'parents' => array($parentid)
                ));
            }

            $parentid = $folder->id;
        }

        return $folder;
    }

    // lista os arquivos que atendem à query, o que é útil para ver se o arquivo da gravação já existe no Googe Drive, evitando uma transferência duplicada
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

    // função para pegar as credenciais do arquivo de instalação do plugin
    private function set_credential_consts() {
        // json_decode transforma o arquivo em um ojeto PHP
        $json = json_decode(file_get_contents($this::CREDENTIALS_PATH), true);

        // define cria uma constante ou uma variavel
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

    // cria o cliente PHP para acessar o Google Drive
    private function get_client() {
        $client = new Google_Client(); // instancia o GoogleClient que está no cliente PHP para o Google Drive
        $client->setClientId(CLIENT_ID);
        $client->setClientSecret(CLIENT_SECRET);
        $client->setRedirectUri((new \moodle_url('/local/zoomadmin/oauth2callback.php'))->out());
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');

        return $client;
    }

    // verifica se existe o token e cria se não existir; este token é para acessar o google drive
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

    // Atualmetne não está sendo chamada atualmente, pois agora esta permissão ficou definida na pasta, lá no GD mesmo, então não tem necessidade de fazer isso
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

    // função chamada acima que faz uma query e retorna um folder
    private function get_folder_by_name_and_parent($name, $parentid) {
        try { // query que gera um objeto do tipo lista de arquivos (listfiles) que é oferecido pelo GD
            $filelist = $this->service->files->listFiles(array(
                'q' => '' .
                    'mimeType = "' . $this::MIME_TYPE_FOLDER . '" ' .
                    'and name = "' . $name . '" ' .
                    'and "' . $parentid . '" in parents ' .
                    'and trashed = false' .
                '',
                'spaces' => 'drive', // fonte de onde faz a busca, usamos sempre o drive mesmo
                'fields' => 'files(id)' // pega o id do folder retornado, são os campos que queremos que retorne
            ));

            $files = $filelist->files;

            // Aqui finalmente retorna a primeira pasta encontrada (e só acha uma mesmo...)
            return (count($files) > 0) ? $files[0] : null;
        } catch (Exception $err) {
            print_object($err);
        }
    }

    // Aqui faz-se a criação do arquivo baixado do Zoom temporário para ser transferido em seguida para o drive
    // Usada para os arquivos grandes, maiores que o definido no início deste arquivo. Note que o download do arquivo do Zoom para o servidor
    // do Moodle  é feito de uma só vez. Só particionamos o arquivo para fazer o seu upload
    private function create_local_temp_file($filedata) {
        global $CFG; // puxa a variável global $CFG, que é do Moodle, no caso para achar o diretório moodle data no servidor

        $filepath = $CFG->dataroot . '/temp/local-zoomadmin';

        $parts = explode('/', $filepath); // cria um array parts com o path criado acima separando os nomes da hierarquia do path em um array
        $filepath = ''; // zera o filepath
        
        // cria as pastas caso seja necessário, para se ter certeza de que as pastas previstas no filepath original existam        
        foreach($parts as $part) {
            if (!is_dir($filepath .= '/' . $part)) { // note que o .= remonta o filepath, que ao final volta a ser igual ao que era no início
                mkdir($filepath);
            }
        }

        // aqui acrescenta ao final o id do arquivo no Zoom, que é o nome do arquivo no disco
        $filepath .= '/' . $filedata['id'];

        print_r($this::LB . '$filepath = ' . $filepath);

        $curl = new \curl();

        print_r($this::LB . 'file size on disk: ' . filesize($filepath) . ' / total file size to download: ' . $filedata['file_size']);
        if (filesize($filepath) !== $filedata['file_size']) {
            $file = fopen($filepath, 'w');

            $result = $curl->download_one($filedata['file_url'], null, // essa função downloadone é do curl no moodle, acessa a url e grava num arquivo
                array(
                    'file' => $file,
                    'timeout' => 300,
                    'followlocation' => true, //para poder seguir redirecionamentos automaticamente
                    'maxredirs' => 3 // nao sabemos por que limitado a 3 redirecionamentos
                )
            );
            fclose($file);

            if ($result === true) {
                print_r($this::LB . 'file downloaded');
            } else {
                print_r($this::LB . 'errno: ' . $curl->get_errno());
                print_r($this::LB . $result);
            }
        } else {
            print_r($this::LB . 'file exists: ' . filesize($filepath) . ' = ' . $filedata['file_size']);
        }

        return $filepath; // retorna o endereço do arquivo no disco
    }
}
