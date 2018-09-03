<?php

namespace duncan3dc\Sonos\Tracks;

/**
 * An interface for objects that repsent some type of Uri.
 */
interface UriInterface
{

    /**
     * Get the URI for this object.
     *
     * @return string
     */
    public function getUri();

    /**
     * Get the metadata xml for this object.
     *
     * @return string
     */
    public function getMetaData();
}
