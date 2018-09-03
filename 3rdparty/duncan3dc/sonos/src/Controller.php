<?php

namespace duncan3dc\Sonos;

use duncan3dc\DomParser\XmlParser;
use duncan3dc\Sonos\Exceptions\SoapException;
use duncan3dc\Sonos\Tracks\Stream;
use duncan3dc\Sonos\Tracks\UriInterface;

/**
 * Allows interaction with the groups of speakers.
 *
 * Although sometimes a Controller is synonymous with a Speaker, when speakers are grouped together only the coordinator can receive events (play/pause/etc)
 */
class Controller extends Speaker
{
    /**
     * No music playing, but not paused.
     *
     * This is a rare state, but can be encountered after an upgrade, or if the queue was cleared
     */
    const STATE_STOPPED = 201;

    /**
     * Currently plating music.
     */
    const STATE_PLAYING = 202;

    /**
     * Music is currently paused.
     */
    const STATE_PAUSED = 203;

    /**
     * The speaker is currently working on either playing or pausing.
     *
     * Check it's state again in a second or two
     */
    const STATE_TRANSITIONING = 204;

    /**
     * The speaker is in an unknown state.
     *
     * This should only happen if Sonos introduce a new state that this code has not been updated to handle.
     */
    const STATE_UNKNOWN = 205;


    /**
     * @var Network $network The network instance this Controller is part of.
     */
    protected $network;


    /**
     * Create a Controller instance from a speaker.
     *
     * The speaker must be a coordinator.
     *
     * @param Speaker $speaker
     */
    public function __construct(Speaker $speaker, Network $network)
    {
        if (!$speaker->isCoordinator()) {
            throw new \InvalidArgumentException("You cannot create a Controller instance from a Speaker that is not the coordinator of it's group");
        }
        $this->ip = $speaker->ip;
        $this->device = $speaker->device;

        $this->network = $network;
        $this->name = $speaker->name;
        $this->room = $speaker->room;
        $this->group = $speaker->getGroup();
        $this->uuid = $speaker->getUuid();
    }


    /**
     * Check if this speaker is the coordinator of it's current group.
     *
     * This method is only here to override the method from the Speaker class.
     * A Controller instance is always the coordinator of it's group.
     *
     * @return bool
     */
    public function isCoordinator()
    {
        return true;
    }


    /**
     * Get the current state of the group of speakers as the string reported by sonos: PLAYING, PAUSED_PLAYBACK, etc
     *
     * @return string
     */
    public function getStateName()
    {
        $data = $this->soap("AVTransport", "GetTransportInfo");
        return $data["CurrentTransportState"];
    }


    /**
     * Get the current state of the group of speakers.
     *
     * @return int One of the class STATE_ constants
     */
    public function getState()
    {
        $name = $this->getStateName();
        switch ($name) {
            case "STOPPED":
                return self::STATE_STOPPED;
            case "PLAYING":
                return self::STATE_PLAYING;
            case "PAUSED_PLAYBACK":
                return self::STATE_PAUSED;
            case "TRANSITIONING":
                return self::STATE_TRANSITIONING;
        }
        return self::STATE_UNKNOWN;
    }


    /**
     * Get attributes about the currently active track in the queue.
     *
     * @return State Track data containing the following elements
     */
    public function getStateDetails()
    {
        $data = $this->soap("AVTransport", "GetPositionInfo");

        # Check for line in mode
        if ($data["TrackMetaData"] === "NOT_IMPLEMENTED") {
            $state = new State($data["TrackURI"]);
            $state->stream = "Line-In";
            return $state;
        }

        # Check for an empty queue
        if (!$data["TrackMetaData"]) {
            return new State;
        }

        $parser = new XmlParser($data["TrackMetaData"]);
        $state = State::createFromXml($parser->getTag("item"), $this);

        if ((string) $parser->getTag("streamContent")) {
            $info = $this->getMediaInfo();
            if (!$state->stream = (string) (new XmlParser($info["CurrentURIMetaData"]))->getTag("title")) {
                $state->stream = (string) $parser->getTag("title");
            }
        }

        $state->queueNumber = (int) $data["Track"];
        $state->duration = $data["TrackDuration"];
        $state->position = $data["RelTime"];

        # If we have a queue number, it'll be one-based, rather than zero-based, so convert it
        if ($state->queueNumber > 0) {
            $state->queueNumber--;
        }

        return $state;
    }


    /**
     * Set the state of the group.
     *
     * @param int $state One of the class STATE_ constants
     *
     * @return static
     */
    public function setState($state)
    {
        switch ($state) {
            case self::STATE_PLAYING:
                return $this->play();
            case self::STATE_PAUSED:
                return $this->pause();
            case self::STATE_STOPPED;
                return $this->pause();
        }
        throw new \InvalidArgumentException("Unknown state: {$state})");
    }


    /**
     * Start playing the active music for this group.
     *
     * @return static
     */
    public function play()
    {
        try {
            $this->soap("AVTransport", "Play", [
                "Speed" =>  1,
            ]);
        } catch (SoapException $e) {
            if (count($this->getQueue()) < 1) {
                $e = new \BadMethodCallException("Cannot play, the current queue is empty");
            }
            throw $e;
        }

        return $this;
    }


    /**
     * Pause the group.
     *
     * @return static
     */
    public function pause()
    {
        $this->soap("AVTransport", "Pause");

        return $this;
    }


    /**
     * Skip to the next track in the current queue.
     *
     * @return static
     */
    public function next()
    {
        $this->soap("AVTransport", "Next");

        return $this;
    }


    /**
     * Skip back to the previous track in the current queue.
     *
     * @return static
     */
    public function previous()
    {
        $this->soap("AVTransport", "Previous");

        return $this;
    }


    /**
     * Skip to the specific track in the current queue.
     *
     * @param int $position The zero-based position of the track to skip to
     *
     * @return static
     */
    public function selectTrack($position)
    {
        $this->soap("AVTransport", "Seek", [
            "Unit"      =>  "TRACK_NR",
            "Target"    =>  $position + 1,
        ]);

        return $this;
    }


    /**
     * Seeks to a specific position within the current track.
     *
     * @param int $seconds The number of seconds to position to in the track
     *
     * @return static
     */
    public function seek($seconds)
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        $this->soap("AVTransport", "Seek", [
            "Unit"      =>  "REL_TIME",
            "Target"    =>  sprintf("%02s:%02s:%02s", $hours, $minutes, $seconds),
        ]);

        return $this;
    }


    /**
     * Get the currently active media info.
     *
     * @return array
     */
    public function getMediaInfo()
    {
        return $this->soap("AVTransport", "GetMediaInfo");
    }


    /**
     * Check if this controller is currently playing a stream.
     *
     * @return bool
     */
    public function isStreaming()
    {
        $media = $this->getMediaInfo();

        $uri = $media["CurrentURI"];

        # Standard streams
        if (substr($uri, 0, 18) === "x-sonosapi-stream:") {
            return true;
        }

        # Line in
        if (substr($uri, 0, 16) === "x-rincon-stream:") {
            return true;
        }

        # Line in (playbar)
        if (substr($uri, 0, 18) === "x-sonos-htastream:") {
            return true;
        }

        return false;
    }


    /**
     * Play a stream on this controller.
     *
     * @param Stream $stream The Stream object to play
     *
     * @return static
     */
    public function useStream(Stream $stream)
    {
        $this->soap("AVTransport", "SetAVTransportURI", [
            "CurrentURI"            =>  $stream->getUri(),
            "CurrentURIMetaData"    =>  $stream->getMetaData(),
        ]);

        return $this;
    }


    /**
     * Play a line-in from a speaker.
     *
     * If no speaker is passed then the current controller's is used.
     *
     * @param Speaker|null $speaker The speaker to get the line-in from
     *
     * @return static
     */
    public function useLineIn(Speaker $speaker = null)
    {
        if ($speaker === null) {
            $speaker = $this;
        }

        $uri = "x-rincon-stream:" . $speaker->getUuid();
        $stream = new Stream($uri, "Line-In");

        return $this->useStream($stream);
    }


    /**
     * Check if this controller is currently using its queue.
     *
     * @return bool
     */
    public function isUsingQueue()
    {
        $media = $this->getMediaInfo();

        return (substr($media["CurrentURI"], 0, 15) === "x-rincon-queue:");
    }


    /**
     * Set this controller to use its queue (rather than a stream).
     *
     * @return static
     */
    public function useQueue()
    {
        $this->soap("AVTransport", "SetAVTransportURI", [
            "CurrentURI"            =>  "x-rincon-queue:" . $this->getUuid() . "#0",
            "CurrentURIMetaData"    =>  "",
        ]);

        return $this;
    }


    /**
     * Get the speakers that are in the group of this controller.
     *
     * @return Speaker[]
     */
    public function getSpeakers()
    {
        $group = [];
        $speakers = $this->network->getSpeakers();
        foreach ($speakers as $speaker) {
            if ($speaker->getGroup() === $this->getGroup()) {
                $group[] = $speaker;
            }
        }
        return $group;
    }


    /**
     * Adds the specified speaker to the group of this Controller.
     *
     * @param Speaker $speaker The speaker to add to the group
     *
     * @return static
     */
    public function addSpeaker(Speaker $speaker)
    {
        if ($speaker->getUuid() === $this->getUuid()) {
            return $this;
        }
        $speaker->soap("AVTransport", "SetAVTransportURI", [
            "CurrentURI"            =>  "x-rincon:" . $this->getUuid(),
            "CurrentURIMetaData"    =>  "",
        ]);

        $this->network->clearTopology();

        return $this;
    }


    /**
     * Removes the specified speaker from the group of this Controller.
     *
     * @param Speaker $speaker The speaker to remove from the group
     *
     * @return static
     */
    public function removeSpeaker(Speaker $speaker)
    {
        $speaker->soap("AVTransport", "BecomeCoordinatorOfStandaloneGroup");

        $this->network->clearTopology();

        return $this;
    }


    /**
     * Set the current volume of all the speakers controlled by this Controller.
     *
     * @param int $volume An amount between 0 and 100
     *
     * @return static
     */
    public function setVolume($volume)
    {
        $speakers = $this->getSpeakers();
        foreach ($speakers as $speaker) {
            $speaker->setVolume($volume);
        }

        return $this;
    }


    /**
     * Adjust the volume of all the speakers controlled by this Controller.
     *
     * @param int $adjust A relative amount between -100 and 100
     *
     * @return static
     */
    public function adjustVolume($adjust)
    {
        $speakers = $this->getSpeakers();
        foreach ($speakers as $speaker) {
            $speaker->adjustVolume($adjust);
        }

        return $this;
    }


    /**
     * Get the current play mode settings.
     *
     * @return array An array with 2 boolean elements (shuffle and repeat)
     */
    public function getMode()
    {
        $data = $this->soap("AVTransport", "GetTransportSettings");
        return Helper::getMode($data["PlayMode"]);
    }


    /**
     * Set the current play mode settings.
     *
     * @param array $options An array with 2 boolean elements (shuffle and repeat)
     *
     * @return static
     */
    public function setMode(array $options)
    {
        $this->soap("AVTransport", "SetPlayMode", [
            "NewPlayMode"   =>  Helper::setMode($options),
        ]);

        return $this;
    }


    /**
     * Get a particular PlayMode.
     *
     * @param string $type The play mode attribute to get
     *
     * @return bool
     */
    protected function getPlayMode($type)
    {
        $mode = $this->getMode();
        return $mode[$type];
    }


    /**
     * Set a particular PlayMode.
     *
     * @param string $type The play mode attribute to update
     * @param bool $value The value to set the attribute to
     *
     * @return static
     */
    protected function setPlayMode($type, $value)
    {
        $value = (bool) $value;

        $mode = $this->getMode();
        if ($mode[$type] === $value) {
            return $this;
        }

        $mode[$type] = $value;
        $this->setMode($mode);

        return $this;
    }


    /**
     * Check if repeat is currently active.
     *
     * @return bool
     */
    public function getRepeat()
    {
        return $this->getPlayMode("repeat");
    }


    /**
     * Turn repeat mode on or off.
     *
     * @param bool $repeat Whether repeat should be on or not
     *
     * @return static
     */
    public function setRepeat($repeat)
    {
        return $this->setPlayMode("repeat", $repeat);
    }


    /**
     * Check if shuffle is currently active.
     *
     * @return bool
     */
    public function getShuffle()
    {
        return $this->getPlayMode("shuffle");
    }


    /**
     * Turn shuffle mode on or off.
     *
     * @param bool $shuffle Whether shuffle should be on or not
     *
     * @return static
     */
    public function setShuffle($shuffle)
    {
        return $this->setPlayMode("shuffle", $shuffle);
    }


    /**
     * Check if crossfade is currently active.
     *
     * @return bool
     */
    public function getCrossfade()
    {
        return (bool) $this->soap("AVTransport", "GetCrossfadeMode");
    }


    /**
     * Turn crossfade on or off.
     *
     * @param bool $crossfade Whether crossfade should be on or not
     *
     * @return static
     */
    public function setCrossfade($crossfade)
    {
        $this->soap("AVTransport", "SetCrossfadeMode", [
            "CrossfadeMode" =>  (bool) $crossfade,
        ]);

        return $this;
    }


    /**
     * Get the queue for this controller.
     *
     * @return Queue
     */
    public function getQueue()
    {
        return new Queue($this);
    }


    /**
     * Grab the current state of the Controller (including it's queue and playing attributes).
     *
     * @param bool $pause Whether to pause the controller or not
     *
     * @return ControllerState
     */
    public function exportState($pause = true)
    {
        if ($pause) {
            $state = $this->getState();
            if ($state === self::STATE_PLAYING) {
                $this->pause();
            }
        }

        $export = new ControllerState($this);

        if ($pause) {
            $export->state = $state;
        }

        return $export;
    }


    /**
     * Restore the Controller to a previously exported state.
     *
     * @param ControllerState $state The state to be restored
     *
     * @return static
     */
    public function restoreState(ControllerState $state)
    {
        $queue = $this->getQueue();
        $queue->clear();
        if (count($state->tracks) > 0) {
            $queue->addTracks($state->tracks);
        }

        if (count($state->tracks) > 0) {
            $this->selectTrack($state->track);

            if ($state->position) {
                list($hours, $minutes, $seconds) = explode(":", $state->position);
                $time = ((($hours * 60) + $minutes) * 60) + $seconds;
                $this->seek($time);
            }
        }

        $this->setShuffle($state->shuffle);
        $this->setRepeat($state->repeat);
        $this->setCrossfade($state->crossfade);

        if ($state->stream) {
            $this->useStream($state->stream);
        }

        $speakers = [];
        foreach ($this->getSpeakers() as $speaker) {
            $speakers[$speaker->getUuid()] = $speaker;
        }
        foreach ($state->speakers as $uuid => $volume) {
            if (array_key_exists($uuid, $speakers)) {
                $speakers[$uuid]->setVolume($volume);
            }
        }

        # If the exported state was playing then start it playing now
        if ($state->state === self::STATE_PLAYING) {
            $this->play();

        # If the exported state was stopped and we are playing then stop it now
        } elseif ($this->getState() === self::STATE_PLAYING) {
            $this->pause();
        }

        return $this;
    }


    /**
     * Interrupt the current audio with a track.
     *
     * The current state of the controller is stored,
     * the passed track is played, and then when it has finished
     * the previous state of the controller is restored.
     * This is useful for making announcements over the Sonos network.
     *
     * @param UriInterface $track The track to play
     * @param int $volume The volume to play the track at
     *
     * @return static
     */
    public function interrupt(UriInterface $track, $volume = null)
    {
        /**
         * Ensure the track has been generated.
         * If it's a TextToSpeech then the api call is done lazily when the uri is required.
         * So it's better to do this here, rather than after the controller has been paused.
         */
        $track->getUri();

        $state = $this->exportState();

        # Replace the current queue with the passed track
        $this->useQueue()->getQueue()->clear()->addTrack($track);

        # Ensure repeat is not on, or else this track would just play indefinitely
        $this->setRepeat(false);

        # If a volume was passed then use it
        if ($volume !== null) {
            $this->setVolume($volume);
        }

        # Play the track
        $this->play();

        # Sleep first so that the track has a chance to at least start
        sleep(1);

        # Wait for the track to finish
        while ($this->getState() === self::STATE_PLAYING) {
            usleep(500000);
        }

        # Restore the previous state of this controller
        $this->restoreState($state);

        return $this;
    }


    /**
     * Get the network instance used by this controller.
     *
     * @return Network
     */
    public function getNetwork()
    {
        return $this->network;
    }
}
