<?php

namespace duncan3dc\Sonos;

use Doctrine\Common\Cache\Cache as CacheInterface;
use duncan3dc\DomParser\XmlParser;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Make http requests to a Sonos device.
 */
class Device
{
    /**
     * @var string $ip The IP address of the device.
     */
    public $ip;

    /**
     * @var string $model The model of the device.
     */
    protected $model;

    /**
     * @var CacheInterface $cache The long-lived cache object from the Network instance.
     */
    protected $cache;

    /**
     * @var LoggerInterface $logger The logging object.
     */
    protected $logger;


    /**
     * Create an instance of the Device class.
     *
     * @param string $ip The ip address that the device is listening on
     * @param CacheInterface $cache The cache object to use for the expensive multicast discover to find Sonos devices on the network
     * @param LoggerInterface $logger A logging object
     */
    public function __construct($ip, CacheInterface $cache = null, LoggerInterface $logger = null)
    {
        $this->ip = $ip;

        if ($cache === null) {
            $cache = new Cache;
        }
        $this->cache = $cache;

        if ($logger === null) {
            $logger = new NullLogger;
        }
        $this->logger = $logger;
    }


    /**
     * Retrieve some xml from the device.
     *
     * @param string $url The url to retrieve
     *
     * @return XmlParser
     */
    public function getXml($url)
    {
        $uri = "http://{$this->ip}:1400{$url}";

        if ($this->cache->contains($uri)) {
            $this->logger->info("getting xml from cache: {$uri}");
            $xml = $this->cache->fetch($uri);
        } else {
            $this->logger->notice("requesting xml from: {$uri}");
            $xml = (string) (new Client)->get($uri)->getBody();
            $this->cache->save($uri, $xml, Cache::DAY);
        }

        return new XmlParser($xml);
    }


    /**
     * Send a soap request to the device.
     *
     * @param string $service The service to send the request to
     * @param string $action The action to call
     * @param array $params The parameters to pass
     *
     * @return mixed
     */
    public function soap($service, $action, array $params = [])
    {
        switch ($service) {
            case "AVTransport";
            case "RenderingControl":
                $path = "MediaRenderer";
                break;
            case "ContentDirectory":
                $path = "MediaServer";
                break;
            case "AlarmClock":
            case "DeviceProperties":
                $path = null;
                break;
            default:
                throw new \InvalidArgumentException("Unknown service: {$service}");
        }

        $location = "http://{$this->ip}:1400/";
        if (is_string($path)) {
            $location .= "{$path}/";
        }
        $location .= "{$service}/Control";

        $this->logger->info("sending soap request to: {$location}", $params);

        $soap = new \SoapClient(null, [
            "location"  =>  $location,
            "uri"       =>  "urn:schemas-upnp-org:service:{$service}:1",
            "trace"     =>  true,
        ]);

        $soapParams = [];
        $params["InstanceID"] = 0;
        foreach ($params as $key => $val) {
            $soapParams[] = new \SoapParam(new \SoapVar($val, \XSD_STRING), $key);
        }

        try {
            $result = $soap->__soapCall($action, $soapParams);
            $this->logger->debug("REQUEST: " . $soap->__getLastRequest());
            $this->logger->debug("RESPONSE: " . $soap->__getLastResponse());
        } catch (\SoapFault $e) {
            $this->logger->debug("REQUEST: " . $soap->__getLastRequest());
            $this->logger->debug("RESPONSE: " . $soap->__getLastResponse());
            throw new Exceptions\SoapException($e, $soap);
        }

        return $result;
    }


    /**
     * Get the model of this device.
     *
     * @return string
     */
    public function getModel()
    {
        if ($this->model === null) {
            $parser = $this->getXml("/xml/device_description.xml");

            if ($device = $parser->getTag("device")) {
                $this->model = (string) $device->getTag("modelNumber");
            }

            if (!is_string($this->model) || strlen($this->model) === 0) {
                $this->model = "UNKNOWN";
            }

            $this->logger->debug("{$this->ip} model: {$this->model}");
        }

        return $this->model;
    }


    /**
     * Check if this sonos device is a speaker.
     *
     * @return bool
     */
    public function isSpeaker()
    {
        $model = $this->getModel();

        $models = [
            "S1"    =>  "PLAY:1",
            "S12"   =>  "PLAY:1",
            "S3"    =>  "PLAY:3",
            "S5"    =>  "PLAY:5",
            "S6"    =>  "PLAY:5",
            "S9"    =>  "PLAYBAR",
            "S11"   =>  "PLAYBASE",
            "S13"   =>  "ONE",
            "ZP80"  =>  "ZONEPLAYER",
            "ZP90"  =>  "CONNECT",
            "ZP100" =>  "CONNECT:AMP",
            "ZP120" =>  "CONNECT:AMP",
        ];

        return array_key_exists($model, $models);
    }
}
