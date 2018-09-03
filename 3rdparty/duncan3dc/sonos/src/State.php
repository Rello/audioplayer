<?php

namespace duncan3dc\Sonos;

use duncan3dc\DomParser\XmlElement;
use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Tracks\Track;

/**
 * Representation of the current state of a controller.
 */
class State extends Track
{
    /**
     * @var string|null $stream The name of the stream currently currently playing (or null if we are not on a stream).
     */
    public $stream;

    /**
     * @var int $trackNumber The number of the track on it's album.
     */
    public $trackNumber = 0;

    /**
     * @var int $queueNumber The zero-based number of the track in the queue.
     */
    public $queueNumber = 0;

    /**
     * @var string $duration The duration of the currently active track (hh:mm:ss).
     */
    public $duration = "";

    /**
     * @var string $position The position of the currently active track (hh:mm:ss).
     */
    public $position = "";

    /**
     * Create a Track object.
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
    }

    /**
     * Update the track properties using an xml element.
     *
     * @param XmlElement $xml The xml element representing the track meta data.
     * @param Controller $controller A controller instance on the playlist's network
     *
     * @return static
     */
    public static function createFromXml(XmlElement $xml, Controller $controller)
    {
        $track = parent::createFromXml($xml, $controller);

        $track->trackNumber = $track->number;

        return $track;
    }
}
