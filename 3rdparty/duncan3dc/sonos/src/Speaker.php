<?php

namespace duncan3dc\Sonos;

/**
 * Represents an individual Sonos speaker, to allow volume, equalisation, and other settings to be managed.
 */
class Speaker
{
    /**
     * @var string $ip The IP address of the speaker.
     */
    public $ip;

    /**
     * @var Device $device The instance of the Device class to send requests to.
     */
    protected $device;

    /**
     * @var string $name The "Friendly" name reported by the speaker.
     */
    public $name;

    /**
     * @var string $room The room name assigned to this speaker.
     */
    public $room;

    /**
     * @var string $uuid The unique id of this speaker.
     */
    protected $uuid;

    /**
     * @var string $group The group id this speaker is a part of.
     */
    protected $group;

    /**
     * @var bool $coordinator Whether this speaker is the coordinator of it's current group.
     */
    protected $coordinator;

    /**
     * @var bool $topology A flag to indicate whether we have gathered the topology for this speaker or not.
     */
    protected $topology;


    /**
     * Create an instance of the Speaker class.
     *
     * @param Device|string $param An Device instance or the ip address that the speaker is listening on
     */
    public function __construct($param)
    {
        if ($param instanceof Device) {
            $this->device = $param;
            $this->ip = $this->device->ip;
        } else {
            $this->ip = $param;
            $this->device = new Device($this->ip);
        }

        $parser = $this->device->getXml("/xml/device_description.xml");
        $device = $parser->getTag("device");
        $this->name = (string) $device->getTag("friendlyName");
        $this->room = (string) $device->getTag("roomName");

        if (!$this->device->isSpeaker()) {
            throw new \InvalidArgumentException("You cannot create a Speaker instance for this model: " . $this->device->getModel());
        }
    }


    /**
     * Send a soap request to the speaker.
     *
     * @param string $service The service to send the request to
     * @param string $action The action to call
     * @param array $params The parameters to pass
     *
     * @return mixed
     */
    public function soap($service, $action, array $params = [])
    {
        return $this->device->soap($service, $action, $params);
    }


    /**
     * Set the topology of this speaker.
     *
     * @param array $topology The topology attributes as key/value pairs
     *
     * @return static
     */
    public function setTopology(array $topology)
    {
        $this->topology = true;

        $this->group = $topology["group"];
        $this->coordinator = ($topology["coordinator"] === "true");
        $this->uuid = $topology["uuid"];

        return $this;
    }


    /**
     * Get the attributes needed for the classes instance variables.
     *
     * _This method is intended for internal use only_.
     *
     * @return void
     */
    protected function getTopology()
    {
        if ($this->topology) {
            return;
        }

        $topology = $this->device->getXml("/status/topology");
        $players = $topology->getTag("ZonePlayers")->getTags("ZonePlayer");
        foreach ($players as $player) {
            $attributes = $player->getAttributes();
            $ip = parse_url($attributes["location"])["host"];

            if ($ip === $this->ip) {
                $this->setTopology($attributes);
                return;
            }
        }

        throw new \RuntimeException("Failed to lookup the topology info for this speaker");
    }


    /**
     * Get the uuid of the group this speaker is a member of.
     *
     * @return string
     */
    public function getGroup()
    {
        $this->getTopology();
        return $this->group;
    }


    /**
     * Check if this speaker is the coordinator of it's current group.
     *
     * @return bool
     */
    public function isCoordinator()
    {
        $this->getTopology();
        return $this->coordinator;
    }


    /**
     * Get the uuid of this speaker.
     *
     * @return string The uuid of this speaker
     */
    public function getUuid()
    {
        $this->getTopology();
        return $this->uuid;
    }


    /**
     * Get the current volume of this speaker.
     *
     * @param int The current volume between 0 and 100
     *
     * @return int
     */
    public function getVolume()
    {
        return (int) $this->soap("RenderingControl", "GetVolume", [
            "Channel"   =>  "Master",
        ]);
    }


    /**
     * Adjust the volume of this speaker to a specific value.
     *
     * @param int $volume The amount to set the volume to between 0 and 100
     *
     * @return static
     */
    public function setVolume($volume)
    {
        $this->soap("RenderingControl", "SetVolume", [
            "Channel"       =>  "Master",
            "DesiredVolume" =>  $volume,
        ]);

        return $this;
    }


    /**
     * Adjust the volume of this speaker by a relative amount.
     *
     * @param int $adjust The amount to adjust by between -100 and 100
     *
     * @return static
     */
    public function adjustVolume($adjust)
    {
        $this->soap("RenderingControl", "SetRelativeVolume", [
            "Channel"       =>  "Master",
            "Adjustment"    =>  $adjust,
        ]);

        return $this;
    }


    /**
     * Check if this speaker is currently muted.
     *
     * @return bool
     */
    public function isMuted()
    {
        return (bool) $this->soap("RenderingControl", "GetMute", [
            "Channel"   =>  "Master",
        ]);
    }


    /**
     * Mute this speaker.
     *
     * @param bool $mute Whether the speaker should be muted or not
     *
     * @return static
     */
    public function mute($mute = true)
    {
        $this->soap("RenderingControl", "SetMute", [
            "Channel"       =>  "Master",
            "DesiredMute"   =>  $mute ? 1 : 0,
        ]);

        return $this;
    }


    /**
     * Unmute this speaker.
     *
     * @return static
     */
    public function unmute()
    {
        return $this->mute(false);
    }


    /**
     * Turn the indicator light on or off.
     *
     * @param bool $on Whether the indicator should be on or off
     *
     * @return static
     */
    public function setIndicator($on)
    {
        $this->soap("DeviceProperties", "SetLEDState", [
            "DesiredLEDState"   =>  $on ? "On" : "Off",
        ]);

        return $this;
    }


    /**
     * Check whether the indicator light is on or not.
     *
     * @return bool
     */
    public function getIndicator()
    {
        return ($this->soap("DeviceProperties", "GetLEDState") === "On");
    }


    /**
     * Set the bass/treble equalisation level.
     *
     * @param string $type Which setting to update (bass or treble)
     * @param int $value The value to set (between -10 and 10)
     *
     * @return static
     */
    protected function setEqLevel($type, $value)
    {
        if ($value < -10) {
            $value = -10;
        }
        if ($value > 10) {
            $value = 10;
        }

        $type = ucfirst(strtolower($type));
        $this->soap("RenderingControl", "Set{$type}", [
            "Channel"           =>  "Master",
            "Desired{$type}"    =>  $value,
        ]);

        return $this;
    }

    /**
     * Get the treble equalisation level.
     *
     * @return int
     */
    public function getTreble()
    {
        return (int) $this->soap("RenderingControl", "GetTreble", [
            "Channel"           =>  "Master",
        ]);
    }


    /**
     * Set the treble equalisation.
     *
     * @param int $treble The treble level (between -10 and 10)
     *
     * @return static
     */
    public function setTreble($treble)
    {
        return $this->setEqLevel("treble", $treble);
    }


    /**
     * Get the bass equalisation level.
     *
     * @return int
     */
    public function getBass()
    {
        return (int) $this->soap("RenderingControl", "GetBass", [
            "Channel"           =>  "Master",
        ]);
    }


    /**
     * Set the bass equalisation.
     *
     * @param int $bass The bass level (between -10 and 10)
     *
     * @return static
     */
    public function setBass($bass)
    {
        return $this->setEqLevel("bass", $bass);
    }


    /**
     * Check whether loudness normalisation is on or not.
     *
     * @return bool
     */
    public function getLoudness()
    {
        return (bool) $this->soap("RenderingControl", "GetLoudness", [
            "Channel"       =>  "Master",
        ]);
    }


    /**
     * Set whether loudness normalisation is on or not.
     *
     * @param bool $on Whether loudness should be on or not
     *
     * @return static
     */
    public function setLoudness($on)
    {
        $this->soap("RenderingControl", "SetLoudness", [
            "Channel"           =>  "Master",
            "DesiredLoudness"   =>  $on ? 1 : 0,
        ]);

        return $this;
    }
}
