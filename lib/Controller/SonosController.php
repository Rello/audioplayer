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
use duncan3dc\Sonos\Network;


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
     * @NoAdminRequired
     */
    public function sonosStop()
    {

        $controller = $this->initController();
        $queue = $controller->getQueue();
        $queue->clear();
    }

    /**
     * @return \duncan3dc\Sonos\Controller|null
     */
    private function initController()
    {
        require_once __DIR__ . "/../../3rdparty/autoload.php";
        //require_once __DIR__ . "/../../3rdparty/duncan3dc/sonos/src/Network.php";
        $sonos = new Network();

        $controllerName = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_controller');
        $this->smb_path = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_smb_path');
        return $sonos->getControllerByRoom($controllerName);

    }

    /**
     * Get array of fileIds to fill the SONOS queue
     * Selecte the queue; select the track; start playback
     *
     * @NoAdminRequired
     * @param $fileArray
     * @param $fileIndex
     * @throws \duncan3dc\Sonos\Exceptions\SoapException
     */
    public function sonosQueue($fileArray, $fileIndex)
    {

        $controller = $this->initController();
        $queue = $controller->getQueue();
        $queue->clear();

        foreach ($fileArray as $fileId) {
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
            $file = array_shift($nodes);
            $link = $this->getSonosLink($file);
            $queue->addTrack('x-file-cifs://' . $this->smb_path . $link);
        }
        $controller->useQueue();
        $controller->selectTrack($fileIndex);
        $controller->play();
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
     * @throws \duncan3dc\Sonos\Exceptions\SoapException
     * @return JSONResponse
     */
    public function sonosPlay($fileId)
    {

        $controller = $this->initController();
        $queue = $controller->getQueue();

        $this->logger->debug('fileId: ' . $fileId, array('app' => 'audioplayer'));
        $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
        $file = array_shift($nodes);
        $link = $this->getSonosLink($file);

        $this->logger->debug('$link: ' . $link, array('app' => 'audioplayer'));

        //$this->logger->debug('$file: ' . $file->getMountPoint()->getStorageId() . '---' . $file->getMountPoint()->getStorageRootId() . '---' . $file->getMountPoint()->getMountPoint(), array('app' => 'audioplayer'));
        //$link = str_replace(" ", "%20", $file->getInternalPath());

        $queue->clear();
        $queue->addTrack('x-file-cifs://' . $this->smb_path . $link);
        $controller->useQueue();
        $controller->play();

        $response = new JSONResponse();
        $response->setData($file);
        return $response;
    }

    /**
     * get the current status of the SONOS controller for debuging purpose
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function sonosStatus()
    {

        $controller = $this->initController();

        $state = $controller->getStateDetails();
        $queueUrl = $state->stream . $state->getUri() . $state->getMetaData();

        $result = [
            $controller->room => $controller->getStateName(),
            '$state->getUri()' => $state->getUri(),
            '$state->getMetaData()' => $state->getMetaData(),
            '$state->title' => $state->title,
            'stream' => $state->stream,
            'Current queue' => $queueUrl
        ];

        $response = new JSONResponse();
        $response->setData($result);
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
     * @throws \duncan3dc\Sonos\Exceptions\SoapException
     */
    public function sonosControl($action)
    {

        $controller = $this->initController();

        if ($action === 'play') $controller->play();
        elseif ($action === 'next') $controller->next();
        elseif ($action === 'previous') $controller->previous();
        elseif ($action === 'pause') $controller->pause();
        elseif ($action === 'up') $controller->adjustVolume(3);
        elseif ($action === 'down') $controller->adjustVolume(-3);
    }

}
