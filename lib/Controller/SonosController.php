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

    protected $multicastAddress = "239.255.255.250";
    protected $networkInterface;
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
     * @return PHPSonos
     */
    private function initController()
    {
        require_once __DIR__ . "/../../3rdparty/PHPSonos.inc.php";

        $this->smb_path = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_smb_path');
        $this->ip = $this->configManager->getUserValue($this->userId, 'audioplayer', 'sonos_controller');

        $device = $this->getDeviceByIp($this->ip);
        $this->udn = $device[1];
        $this->room = $device[2];
        $sonos = new PHPSonos($this->ip);
        return $sonos;
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
            $link = $this->smbFilePath($file);
            $sonos->AddToQueue($link);
        }

        $sonos->SetQueue("x-rincon-queue:" . $this->udn . "#0");
        $sonos->SetTrack($fileIndex + 1);
        $sonos->Play();

    }

    /**
     * Get Controller udn & name via IP
     *
     * @param $ip
     * @return array
     */
    private function getDeviceByIp($ip)
    {

        $uri = "http://{$ip}:1400/xml/device_description.xml";
        $xml = (string)(new Client)->get($uri)->getBody();
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
     * create the SONOS link
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
     * Get list of all devices with ip, udn, room
     *
     * @NoAdminRequired
     * @return array
     */
    public function getDeviceList()
    {

        $devices = [];
        $ips = $this->getDevices();

        foreach ($ips as $ip) {
            $device = $this->getDeviceByIp($ip);
            $devices[] = $device;
        }

        return $devices;

    }

    /**
     * Get all the devices on the current network.
     *
     * @return string[] An array of ip addresses
     */
    private function getDevices()
    {
        $port = 1900;
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $level = getprotobyname("ip");
        socket_set_option($sock, $level, IP_MULTICAST_TTL, 2);
        if ($this->networkInterface !== null) {
            socket_set_option($sock, $level, IP_MULTICAST_IF, $this->networkInterface);
        }
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

    public function sonosStatus()
    {

        $sonos = $this->initController();
        $test = $this->getDevices();

        $response = new JSONResponse();
        $response->setData($test);
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
