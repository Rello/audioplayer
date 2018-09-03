<?php

namespace duncan3dc\Sonos\Tracks;

use duncan3dc\Sonos\Helper;

/**
 * Representation of a Spotify track.
 */
class Spotify extends Track
{
    const PREFIX = "x-sonos-spotify:";
    const REGION_EU = "2311";
    const REGION_US = "3079";

    /**
     * @var string $region The region code for the Spotify service (the default is EU).
     */
    public static $region = self::REGION_EU;


    /**
     * Create a Spotify track object.
     *
     * @param string $uri The URI of the track or the Spotify ID of the track
     */
    public function __construct($uri)
    {
        # If this is a spotify track ID and not a URI then convert it to a URI now
        if (substr($uri, 0, strlen(self::PREFIX)) !== self::PREFIX) {
            $uri = self::PREFIX . urlencode("spotify:track:{$uri}");
        }

        parent::__construct($uri);
    }


    /**
     * Get the metadata xml for this track.
     *
     * @return string
     */
    public function getMetaData()
    {
        $uri = substr($this->uri, strlen(self::PREFIX));

        return Helper::createMetaDataXml(Helper::TRACK_HASH . "{$uri}", "-1", [
            "dc:title"      =>  "",
            "upnp:class"    =>  "object.item.audioItem.musicTrack",
        ], static::$region);
    }
}
