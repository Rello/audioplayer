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

use GuzzleHttp\Client;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;

/**
 * SONOS controller
 */
class SonosController extends Controller
{

    protected $multicastAddress = "239.255.255.250";
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
     * @return bool
     */
    public function setQueue($fileArray, $fileIndex)
    {

        $sonos = $this->initController();
        if ($sonos === false) return false;
        $sonos->ClearQueue();

        foreach ($fileArray as $fileId) {
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
            $file = array_shift($nodes);
            $link = $this->smbFilePath($file);
            $sonos->AddToQueue($link);
        }

        $sonos->SetQueue("x-rincon-queue:" . $this->udn . "#0");
        $sonos->SetTrack($fileIndex + 1);
        $sonos->Play();
        return true;

    }

    /**
     * Init the SONOS controller and deliver one SONOS instance for the player-ip from user settings
     *
     * @return \PHPSonos|bool
     */
    private function initController()
    {
        require_once __DIR__ . "/../../3rdparty/PHPSonos.inc.php";

        $this->smb_path = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_smb_path');
        $this->ip = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_controller');

        $device = $this->getDeviceByIp($this->ip);

        if (! empty($device)) {
            $this->udn = $device[1];
            $this->room = $device[2];
            $sonos = new \PHPSonos($this->ip);
            return $sonos;
        } else {
            return false;
        }
    }

    /**
     * Get the device details like room & udn from xml/device_description.xml
     *
     * @param $ip
     * @return array|bool
     */
    private function getDeviceByIp($ip)
    {

        if (!$ip) return false;
        $uri = "http://{$ip}:1400/xml/device_description.xml";
        $xml = (string)(new Client)->get($uri)->getBody();
        if (!$xml) return false;
        $xml = simplexml_load_string($xml);
        $udn = $xml->device->UDN;
        $room = $xml->device->roomName;

        if (preg_match("/^uuid:(.*)$/", $udn, $matches)) {
            $udn = $matches[1];
        } else {
            $udn = '';
        }
        return array($ip, $udn, $room);

    }

    /**
     * create the SONOS SMB path
     * for SMB mapping, the first level needs to be cut because this is the self given name of the mount
     * for shared SMB folders, the first level needs to be kept
     *
     * @param object $file
     * @return string
     */
    private function smbFilePath($file)
    {

        $segments = explode(':', $file->getMountPoint()->getStorageId());
        $type = $segments[0];

        $path = $file->getPath();
        $path_segments = explode('/', trim($path, '/'), 3);
        $link = '';

        if ($type === 'smb') $link = $file->getInternalPath();
        if ($type === 'shared') $link = $path_segments[2];

        return 'x-file-cifs://' . $this->smb_path . rawurlencode($link);
    }

    /**
     * Get list of all known devices with ip, udn, room
     *
     * @NoAdminRequired
     * @return array
     */
    public function getDeviceList()
    {

        $devices = [];
        $ips = $this->discoverDevices();

        foreach ($ips as $ip) {
            $device = $this->getDeviceByIp($ip);
            $devices[] = $device;
        }

        return $devices;

    }

    /**
     * Discover all the devices on the current network via multicast
     *
     * @return string[] An array of ip addresses
     */
    private function discoverDevices()
    {
        $port = 1900;
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $level = getprotobyname("ip");
        socket_set_option($sock, $level, IP_MULTICAST_TTL, 2);
        $data = "M-SEARCH * HTTP/1.1\r\n";
        $data .= "HOST: {$this->multicastAddress}:reservedSSDPport\r\n";
        $data .= "MAN: ssdp:discover\r\n";
        $data .= "MX: 1\r\n";
        $data .= "ST: urn:schemas-upnp-org:device:ZonePlayer:1\r\n";

        socket_sendto($sock, $data, strlen($data), null, $this->multicastAddress, $port);
        $read = [$sock];
        $write = [];
        $except = [];
        $name = null;
        $port = null;
        $tmp = "";
        $response = "";
        while (socket_select($read, $write, $except, 1)) {
            socket_recvfrom($sock, $tmp, 2048, null, $name, $port);
            $response .= $tmp;
        }

        $devices = [];
        foreach (explode("\r\n\r\n", $response) as $reply) {
            if (!$reply) {
                continue;
            }
            $data = [];
            foreach (explode("\r\n", $reply) as $line) {
                if (!$pos = strpos($line, ":")) {
                    continue;
                }
                $key = strtolower(substr($line, 0, $pos));
                $val = trim(substr($line, $pos + 1));
                $data[$key] = $val;
            }
            $devices[] = $data;
        }
        $return = [];
        $unique = [];
        foreach ($devices as $device) {
            if ($device["st"] !== "urn:schemas-upnp-org:device:ZonePlayer:1") {
                continue;
            }
            if (in_array($device["usn"], $unique)) {
                continue;
            }
            $url = parse_url($device["location"]);
            $ip = $url["host"];
            $return[] = $ip;
            $unique[] = $device["usn"];
        }
        return $return;
    }

    /**
     * get the current status of the SONOS controller for debuging purpose
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getStatus()
    {

        $sonos = $this->initController();
        //$test = $this->getDevices();
        if ($sonos === false) $sonos = array('no controller found');

        $response = new JSONResponse();
        $response->setData($sonos);
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
    public function getDebugInfo($fileId)
    {
        $smb_path = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_smb_path');
        $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
        $file = array_shift($nodes);

        $link = $this->smbFilePath($file);

        $result = [
            'smb' => $smb_path,
            'filelink' => $link,
            'sonos' => $link
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
     * @return bool
     */
    public function setAction($action)
    {

        $sonos = $this->initController();
        if ($sonos === false) return false;

        if ($action === 'play') {
            $sonos->play();
            return true;
        }
        if ($action === 'next') {
            $sonos->next();
            return true;
        }
        if ($action === 'previous') {
            $sonos->previous();
            return true;
        }
        if ($action === 'pause') {
            $sonos->pause();
            return true;
        }
        if ($action === 'up') {
            $volume = (int)$sonos->GetVolume();
            $sonos->SetVolume($volume + 3);
            return true;
        }
        if ($action === 'down') {
            $volume = $sonos->GetVolume();
            $sonos->SetVolume($volume - 3);
            return true;
        }
    }
}
