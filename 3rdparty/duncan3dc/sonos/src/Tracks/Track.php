<?php

namespace duncan3dc\Sonos\Tracks;

use duncan3dc\DomParser\XmlElement;
use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Helper;

/**
 * Representation of a track.
 */
class Track implements UriInterface
{
    /**
     * @var string $uri The uri of the track.
     */
    public $uri = "";

    /**
     * @var string $title The name of the track.
     */
    public $title = "";

    /**
     * @var string $artist The name of the artist of the track.
     */
    public $artist = "";

    /**
     * @var string $album The name of the album of the track.
     */
    public $album = "";

    /**
     * @var int $number The number of the track.
     */
    public $number = 0;

    /**
     * @var string $albumArt The full path to the album art for this track.
     */
    public $albumArt = "";

    /**
     * @var string $queueId The id of the track in the queue.
     */
    protected $queueId = "-1";


    /**
     * Create a Track object.
     *
     * @param string $uri The URI of the track
     */
    public function __construct($uri)
    {
        $this->uri = (string) $uri;
    }


    /**
     * Get the URI for this track.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }


    /**
     * Get the ID of this track.
     *
     * @return string
     */
    public function getId()
    {
        return $this->queueId;
    }


    /**
     * Get the metadata xml for this track.
     *
     * @return string
     */
    public function getMetaData()
    {
        return Helper::createMetaDataXml($this->getId(), "-1", [
            "res"               =>  $this->uri,
            "upnp:albumArtURI"  =>  $this->albumArt,
            "dc:title"          =>  $this->title,
            "upnp:class"        =>  "object.item.audioItem.musicTrack",
            "dc:creator"        =>  $this->artist,
            "upnp:album"        =>  $this->album,
        ]);
    }


    /**
     * Update the track properties using an xml element.
     *
     * @param XmlElement $xml The xml element representing the track meta data
     * @param Controller $controller A controller instance to communicate with
     *
     * @return static
     */
    public static function createFromXml(XmlElement $xml, Controller $controller)
    {
        $track = new static($xml->getTag("res"));

        $track->title = (string) $xml->getTag("title");

        if ($stream = (string) $xml->getTag("streamContent")) {
            $bits = explode(" - ", $stream);
            $track->artist = array_shift($bits);
            $track->title = implode(" - ", $bits);
            $track->album = "";
        } else {
            $track->artist = (string) $xml->getTag("creator");
            $track->album = (string) $xml->getTag("album");
        }

        # Cast the node to a string first (we do this instead of calling nodeValue in case the object is null)
        $number = (string) $xml->getTag("originalTrackNumber");

        # Then convert to a number
        $track->number = (int) $number;

        if ($art = (string) $xml->getTag("albumArtURI")) {
            if (substr($art, 0, 4) !== "http") {
                $art = ltrim($art, "/");
                $art = sprintf("http://%s:1400/%s", $controller->ip, $art);
            }
            $track->albumArt = $art;
        }

        if ($xml->hasAttribute("id")) {
            $track->queueId = $xml->getAttribute("id");
        }

        return $track;
    }
}
