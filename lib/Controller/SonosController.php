<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2018 Marcel Scherello
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\ILogger;
use GuzzleHttp\Client;
use PHPSonos;

/**
 * Controller class for main page.
 */
class SonosController extends Controller
{

    private $userId;
    private $configManager;
    private $rootFolder;
    private $logger;
    private $smb_path;
    private $room;
    private $udn;
    private $ip = '192.168.0.27';

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IConfig $configManager,
        ILogger $logger,
        IRootFolder $rootFolder
    )
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->userId = $userId;
        $this->configManager = $configManager;
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
    }

    /**
     * Use array of fileIds to fill the SONOS queue
     * Selecte the queue; select the track; start playback
     *
     * @NoAdminRequired
     * @param $fileArray
     * @param $fileIndex
     */
    public function sonosQueue($fileArray, $fileIndex)
    {

        $sonos = $this->initController();
        $sonos->ClearQueue();

        foreach ($fileArray as $fileId) {
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
            $file = array_shift($nodes);
            $link = $this->getSonosLink($file);
            $sonos->AddToQueue('x-file-cifs://' . $this->smb_path . $link);
        }

        $sonos->SetQueue("x-rincon-queue:" . $this->udn . "#0");
        $sonos->SetTrack($fileIndex + 1);
        $sonos->Play();

    }

    /**
     * @return PHPSonos
     */
    private function initController()
    {
        $this->smb_path = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_smb_path');

        require_once __DIR__ . "/../../3rdparty/PHPSonos.inc.php";

        $this->getControllerByIp($this->ip);
        $sonos = new PHPSonos($this->ip);
        return $sonos;
    }

    /**
     * Get Controller udn & name via IP
     *
     * @param $ip
     * @return string
     */
    private function getControllerByIp($ip)
    {

        $uri = "http://{$ip}:1400/xml/device_description.xml";
        $xml = (string)(new Client)->get($uri)->getBody();
        $xml = simplexml_load_string($xml);
        $udn = $xml->device->UDN;
        $this->room = $xml->device->roomName;

        if (preg_match("/^uuid:(.*)$/", $udn, $matches)) {
            $this->udn = $matches[1];
        } else {
            $this->udn = '';
        }
        return $this->udn;

    }

    /**
     * create the SONOS link
     * for SMB mapping, the first level needs to be cut because this is the self given name of the mount
     * for shared SMB folders, the first level needs to be kept
     *
     * @param object $file
     * @return string
     */
    private function getSonosLink($file)
    {

        $segments = explode(':', $file->getMountPoint()->getStorageId());
        $type = $segments[0];

        $path = $file->getPath();
        $path_segments = explode('/', trim($path, '/'), 3);
        $link = '';

        if ($type === 'smb') $link = $file->getInternalPath();
        if ($type === 'shared') $link = $path_segments[2];

        return rawurlencode($link);
    }

    /**
     * Play a single file; tmp work in progress
     *
     * @NoAdminRequired
     * @param $fileId
     * @return JSONResponse
     */
    public function sonosPlay($fileId)
    {

    }


    /**
     * get the current status of the SONOS controller for debuging purpose
     *
     * @NoAdminRequired
     * @return JSONResponse
     */

    public function sonosStatus()
    {

        $sonos = $this->initController();

        $response = new JSONResponse();
        $response->setData($this->udn);
        return $response;

    }

    /**
     * get the debug information on the SONOS link being created
     * used within the sidebar => SONOS Tab
     *
     * @NoAdminRequired
     * @param $fileId
     * @return JSONResponse
     */
    public function sonosDebug($fileId)
    {
        $smb_path = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_smb_path');
        $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
        $file = array_shift($nodes);

        $link = $this->getSonosLink($file);

        $result = [
            'smb' => $smb_path,
            'filelink' => $link,
            'sonos' => 'x-file-cifs://' . $smb_path . $link
        ];

        $response = new JSONResponse();
        $response->setData($result);
        return $response;
    }

    /**
     * play, pause, next, volume, ... of the SONOS controller
     *
     * @NoAdminRequired
     * @param $action
     */
    public function sonosControl($action)
    {

        $sonos = $this->initController();

        if ($action === 'play') $sonos->play();
        elseif ($action === 'next') $sonos->next();
        elseif ($action === 'previous') $sonos->previous();
        elseif ($action === 'pause') $sonos->pause();
        elseif ($action === 'up') {
            $volume = $sonos->GetVolume();
            $sonos->SetVolume($volume + 3);
        } elseif ($action === 'down') {
            $volume = $sonos->GetVolume();
            $sonos->SetVolume($volume - 3);
        }
    }

}
