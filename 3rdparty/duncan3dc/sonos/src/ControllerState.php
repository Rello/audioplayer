<?php

namespace duncan3dc\Sonos;

use duncan3dc\Sonos\Tracks\Stream;
use duncan3dc\Sonos\Tracks\Track;

/**
 * Representation of the current state of a controller.
 */
class ControllerState
{
    /**
     * @var int One of the Controller STATE_ constants
     */
    public $state;

    /**
     * @var int $track The zero-based number of the track in the queue.
     */
    public $track;

    /**
     * @var string $position The position of the currently active track (hh:mm:ss).
     */
    public $position;

    /**
     * @var bool $repeat Whether repeat mode is currently active.
     */
    public $repeat;

    /**
     * @var bool $shuffle Whether shuffle is currently active.
     */
    public $shuffle;

    /**
     * @var bool $crossfade Whether crossfade is currently active.
     */
    public $crossfade;

    /**
     * @var array $speakers Each speaker that is managed by this controller.
     */
    public $speakers;

    /**
     * @var Track[] $tracks An array of tracks from the queue.
     */
    public $tracks;

    /**
     * @var Stream $stream A stream object (if the controller is currently streaming).
     */
    public $stream;

    /**
     * Create a ControllerState object.
     *
     * @param Controller $controller The Controller to grab the state of
     */
    public function __construct(Controller $controller)
    {
        $this
            ->getState($controller)
            ->getMode($controller)
            ->getVolume($controller)
            ->getTracks($controller);
    }


    /**
     * Get the current playing attributes (stream/position/etc).
     *
     * @param Controller $controller The Controller to grab the state of
     *
     * @return static
     */
    protected function getState(Controller $controller)
    {
        $this->state = $controller->getState();

        $details = $controller->getStateDetails();
        $this->track = $details->queueNumber;
        $this->position = $details->position;

        return $this;
    }


    /**
     * Get the current playing mode (repeat/shuffle/etc).
     *
     * @param Controller $controller The Controller to grab the state of
     *
     * @return static
     */
    protected function getMode(Controller $controller)
    {
        $mode = $controller->getMode();
        $this->repeat = $mode["repeat"];
        $this->shuffle = $mode["shuffle"];

        $this->crossfade = $controller->getCrossfade();

        return $this;
    }


    /**
     * Get the current volume of all the speakers in this group.
     *
     * @param Controller $controller The Controller to grab the state of
     *
     * @return static
     */
    protected function getVolume(Controller $controller)
    {
        $this->speakers = [];
        foreach ($controller->getSpeakers() as $speaker) {
            $this->speakers[$speaker->getUuid()] = $speaker->getVolume();
        }

        return $this;
    }


    /**
     * Get the current tracks in the queue.
     *
     * @param Controller $controller The Controller to grab the state of
     *
     * @return static
     */
    protected function getTracks(Controller $controller)
    {
        $this->tracks = $controller->getQueue()->getTracks();

        if ($controller->isStreaming()) {
            $media = $controller->getMediaInfo();
            $this->stream = new Stream($media["CurrentURI"]);
        }

        return $this;
    }
}
