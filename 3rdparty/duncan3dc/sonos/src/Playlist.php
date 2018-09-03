<?php

namespace duncan3dc\Sonos;

use duncan3dc\DomParser\XmlElement;
use duncan3dc\DomParser\XmlParser;
use duncan3dc\Sonos\Tracks\UriInterface;

/**
 * Provides an interface for managing Sonos playlists on the current network.
 */
class Playlist extends Queue
{
    /**
     * @var string|null $name The name of the playlist.
     */
    protected $name;


    /**
     * Create an instance of the Playlist class.
     *
     * @param string|XmlElement $param The id of the playlist, or an xml element with the relevant attributes
     * @param Controller $controller A controller instance on the playlist's network
     */
    public function __construct($param, Controller $controller)
    {
        parent::__construct($controller);

        if (is_string($param)) {
            $this->id = $param;
        } else {
            $this->id = $param->getAttribute("id");
            $this->name = $param->getTag("title")->nodeValue;
        }
    }


    /**
     * Get the id of the playlist.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Get the name of the playlist.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->name === null) {
            $data = $this->browse("Metadata");
            $xml = new XmlParser($data["Result"]);
            $this->name = $xml->getTag("title")->nodeValue;
        }
        return $this->name;
    }


    /**
     * Calculate the position number to be used to add a track to the end of the playlist.
     *
     * @return int
     */
    protected function getNextPosition()
    {
        return parent::getNextPosition() - 1;
    }


    /**
     * Add a uri to the playlist.
     *
     * @param UriInterface $track The track to add
     * @param int $position The position to insert the track in the playlist (zero-based), by default the track will be added to the end of the playlist
     *
     * @return bool
     */
    protected function addUris(array $tracks, $position = null)
    {
        if ($position === null) {
            $position = $this->getNextPosition();
        }

        foreach ($tracks as $track) {
            $data = $this->soap("AVTransport", "AddURIToSavedQueue", [
                "UpdateID"              =>  $this->updateId,
                "EnqueuedURI"           =>  $track->getUri(),
                "EnqueuedURIMetaData"   =>  $track->getMetaData(),
                "AddAtIndex"            =>  $position,
            ]);
            $this->updateId = $data["NewUpdateID"];

            $position++;

            if ($data["NumTracksAdded"] != 1) {
                return false;
            }
        }

        return true;
    }


    /**
     * Remove tracks from the playlist.
     *
     * @param int[] $positions The zero-based positions of the tracks to remove
     *
     * @return bool
     */
    public function removeTracks(array $positions)
    {
        $data = $this->soap("AVTransport", "ReorderTracksInSavedQueue", [
            "UpdateID"              =>  $this->getUpdateID(),
            "TrackList"             =>  implode(",", $positions),
            "NewPositionList"       =>  "",
        ]);
        $this->updateId = $data["NewUpdateID"];

        return ($data["QueueLengthChange"] == (count($positions) * -1));
    }


    /**
     * Move a track from one position in the playlist to another.
     *
     * @param int $from The current position of the track in the playlist (zero-based)
     * @param int $to The desired position in the playlist (zero-based)
     *
     * @return static
     */
    public function moveTrack($from, $to)
    {
        $data = $this->soap("AVTransport", "ReorderTracksInSavedQueue", [
            "UpdateID"              =>  $this->getUpdateID(),
            "TrackList"             =>  (string) $from,
            "NewPositionList"       =>  (string) $to,
        ]);
        $this->updateId = $data["NewUpdateID"];

        return $this;
    }


    /**
     * Remove all tracks from the queue.
     *
     * @return static
     */
    public function clear()
    {
        $positions = [];
        $max = $this->count();
        for ($i = 0; $i < $max; $i++) {
            $positions[] = $i;
        }
        $this->removeTracks($positions);

        return $this;
    }


    /**
     * Delete this playlist from the network.
     *
     * @return void
     */
    public function delete()
    {
        $this->soap("ContentDirectory", "DestroyObject");
    }
}
