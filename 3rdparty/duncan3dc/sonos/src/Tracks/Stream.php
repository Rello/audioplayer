<?php

namespace duncan3dc\Sonos\Tracks;

use duncan3dc\DomParser\XmlElement;
use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Helper;

/**
 * Representation of a stream.
 */
class Stream implements UriInterface
{
    const PREFIX = "x-sonosapi-stream";

    /**
     * @var string $uri The uri of the stream.
     */
    protected $uri = "";

    /**
     * @var string $name The name of the stream.
     */
    protected $name = "";


    /**
     * Create a Stream object.
     *
     * @param string $uri The URI of the stream
     */
    public function __construct($uri, $name = "")
    {
        $this->uri = (string) $uri;
        $this->name = (string) $name;
    }


    /**
     * Get the URI for this stream.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }


    /**
     * Get the name for this stream.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Get the name for this stream.
     *
     * @return string
     */
    public function getTitle()
    {
        trigger_error("The getTitle() method is deprecated in favour of getName()", \E_USER_DEPRECATED);
        return $this->getName();
    }


    /**
     * Get the metadata xml for this stream.
     *
     * @return string
     */
    public function getMetaData()
    {
        return Helper::createMetaDataXml("-1", "-1", [
            "dc:title"          =>  $this->getName() ?: "Stream",
            "upnp:class"        =>  "object.item.audioItem.audioBroadcast",
            "desc"              =>  [
                "_attributes"       =>  [
                    "id"        =>  "cdudn",
                    "nameSpace" =>  "urn:schemas-rinconnetworks-com:metadata-1-0/",
                ],
                "_value"            =>  "SA_RINCON65031_",
            ],
        ]);
    }


    /**
     * Create a stream from an xml element.
     *
     * @param XmlElement $xml The xml element representing the track meta data
     * @param Controller $controller A controller instance to communicate with
     *
     * @return static
     */
    public static function createFromXml(XmlElement $xml, Controller $controller)
    {
        return new static($xml->getTag("res")->nodeValue, $xml->getTag("title")->nodeValue);
    }
}
