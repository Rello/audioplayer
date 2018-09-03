<?php

namespace duncan3dc\Sonos;

use duncan3dc\DomParser\XmlElement;

/**
 * Provides an interface for managing the alarms on the network.
 */
class Alarm
{
    const ONCE      =   0;
    const MONDAY    =   1;
    const TUESDAY   =   self::MONDAY    * 2;
    const WEDNESDAY =   self::TUESDAY   * 2;
    const THURSDAY  =   self::WEDNESDAY * 2;
    const FRIDAY    =   self::THURSDAY  * 2;
    const SATURDAY  =   self::FRIDAY    * 2;
    const SUNDAY    =   self::SATURDAY  * 2;
    const DAILY     =   (self::SUNDAY   * 2) - 1;

    /**
     * @var array $days An mapping of php day values to our day constants.
     */
    protected $days = [
        "0" =>  self::SUNDAY,
        "1" =>  self::MONDAY,
        "2" =>  self::TUESDAY,
        "3" =>  self::WEDNESDAY,
        "4" =>  self::THURSDAY,
        "5" =>  self::FRIDAY,
        "6" =>  self::SATURDAY,
    ];

    /**
     * @var string $id The unique id of the alarm
     */
    protected $id;

    /**
     * @var array $attributes The attributes of the alarm
     */
    protected $attributes;

    /**
     * @var Network $network A Network instance this alarm is from.
     */
    protected $network;

    /**
     * Create an instance of the Alarm class.
     *
     * @param XmlElement $xml The xml element with the relevant attributes
     * @param Network $network A Network instance this alarm is from
     */
    public function __construct(XmlElement $xml, Network $network)
    {
        $this->id = $xml->getAttribute("ID");
        $this->attributes = $xml->getAttributes();
        $this->network = $network;
    }


    /**
     * Send a soap request to the speaker for this alarm.
     *
     * @param string $service The service to send the request to
     * @param string $action The action to call
     * @param array $params The parameters to pass
     *
     * @return mixed
     */
    protected function soap($service, $action, $params = [])
    {
        $params["ID"] = $this->id;

        return $this->getSpeaker()->soap($service, $action, $params);
    }


    /**
     * Get the id of the alarm.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }


    /**
     * Get the room of the alarm.
     *
     * @return string
     */
    public function getRoom()
    {
        return $this->attributes["RoomUUID"];
    }


    /**
     * Set the room of the alarm.
     *
     * @param string $uuid The unique id of the room (eg, RINCON_B8E93758723601400)
     *
     * @return static
     */
    public function setRoom($uuid)
    {
        $this->attributes["RoomUUID"] = $uuid;
        return $this->save();
    }


    /**
     * Get the speaker of the alarm.
     *
     * @return Speaker
     */
    public function getSpeaker()
    {
        foreach ($this->network->getSpeakers() as $speaker) {
            if ($speaker->getUuid() === $this->getRoom()) {
                return $speaker;
            }
        }

        throw new \RuntimeException("Unable to find a speaker for this alarm");
    }


    /**
     * Set the speaker of the alarm.
     *
     * @param Speaker $speaker The speaker to attach this alarm to
     *
     * @return static
     */
    public function setSpeaker(Speaker $speaker)
    {
        return $this->setRoom($speaker->getUuid());
    }


    /**
     * Get the start time of the alarm.
     *
     * @return string
     */
    public function getTime()
    {
        list($hours, $minutes) = explode(":", $this->attributes["StartTime"]);
        return sprintf("%02s:%02s", $hours, $minutes);
    }


    /**
     * Set the start time of the alarm.
     *
     * @param string $time The time to set the alarm for (hh:mm)
     *
     * @return static
     */
    public function setTime($time)
    {
        $exception = new \InvalidArgumentException("Invalid time specified, time must be in the format hh:mm");
        if (!preg_match("/^([0-9]{1,2}):([0-9]{1,2})$/", $time, $matches)) {
            throw $exception;
        }
        $hours = $matches[1];
        $minutes = $matches[2];

        if ($hours > 23 || $minutes > 59) {
            throw $exception;
        }

        $this->attributes["StartTime"] = sprintf("%02s:%02s:%02s", $hours, $minutes, 0);

        return $this->save();
    }


    /**
     * Get the duration of the alarm.
     *
     * @return int The duration in minutes
     */
    public function getDuration()
    {
        list($hours, $minutes) = explode(":", $this->attributes["Duration"]);
        return (int) ($hours * 60) + $minutes;
    }


    /**
     * Set the duration of the alarm.
     *
     * @param int The duration in minutes
     *
     * @return static
     */
    public function setDuration($duration)
    {
        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        $this->attributes["Duration"] = sprintf("%02s:%02s:%02s", $hours, $minutes, 0);

        return $this->save();
    }


    /**
     * Get the frequency of the alarm.
     *
     * The result is an integer which can be compared using the bitwise operators and the class constants for each day.
     * If the alarm is a one time only alarm then it will not match any of the day constants, but will be equal to the class constant ONCE.
     *
     * @return int
     */
    public function getFrequency()
    {
        $data = $this->attributes["Recurrence"];
        if ($data === "ONCE") {
            return self::ONCE;
        }
        if ($data === "DAILY") {
            $data = "ON_0123456";
        } elseif ($data === "WEEKDAYS") {
            $data = "ON_12345";
        } elseif ($data === "WEEKENDS") {
            $data = "ON_06";
        }
        if (!preg_match("/^ON_([0-9]+)$/", $data, $matches)) {
            throw new \RuntimeException("Unrecognised frequency for alarm ({$data}), please report this issue at github.com/duncan3dc/sonos/issues");
        }

        $data = $matches[1];
        $days = 0;
        foreach ($this->days as $key => $val) {
            if (strpos($data, (string) $key) !== false) {
                $days = $days | $val;
            }
        }

        return $days;
    }


    /**
     * Set the frequency of the alarm.
     *
     * @param int $frequency The integer representing the frequency (using the bitwise class constants)
     *
     * @return static
     */
    public function setFrequency($frequency)
    {
        $recurrence = "ON_";
        foreach ($this->days as $key => $val) {
            if ($frequency & $val) {
                $recurrence .= $key;
            }
        }

        if ($recurrence === "ON_") {
            $recurrence = "ONCE";
        } elseif ($recurrence === "ON_0123456") {
            $recurrence = "DAILY";
        } elseif ($recurrence === "ON_01234") {
            $recurrence = "WEEKDAYS";
        } elseif ($recurrence === "ON_56") {
            $recurrence = "WEEKENDS";
        }

        $this->attributes["Recurrence"] = $recurrence;

        return $this->save();
    }


    /**
     * Check or set whether this alarm is active on a particular day.
     *
     * @param int $day Which day to check/set
     * @param bool $set Set this alarm to be active or not on the specified day
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    protected function onHandler($day, $set = null)
    {
        $frequency = $this->getFrequency();
        if ($set === null) {
            return (bool) ($frequency & $day);
        }
        if ($set && $frequency ^ $day) {
            return $this->setFrequency($frequency | $day);
        }
        if (!$set && $frequency & $day) {
            return $this->setFrequency($frequency ^ $day);
        }

        return $this;
    }


    /**
     * Check or set whether this alarm is active on mondays.
     *
     * @param bool $set Set this alarm to be active or not on mondays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onMonday($set = null)
    {
        return $this->onHandler(self::MONDAY, $set);
    }


    /**
     * Check or set whether this alarm is active on tuesdays.
     *
     * @param bool $set Set this alarm to be active or not on tuesdays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onTuesday($set = null)
    {
        return $this->onHandler(self::TUESDAY, $set);
    }


    /**
     * Check or set whether this alarm is active on wednesdays.
     *
     * @param bool $set Set this alarm to be active or not on wednesdays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onWednesday($set = null)
    {
        return $this->onHandler(self::WEDNESDAY, $set);
    }


    /**
     * Check or set whether this alarm is active on thursdays.
     *
     * @param bool $set Set this alarm to be active or not on thursdays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onThursday($set = null)
    {
        return $this->onHandler(self::THURSDAY, $set);
    }


    /**
     * Check or set whether this alarm is active on fridays.
     *
     * @param bool $set Set this alarm to be active or not on fridays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onFriday($set = null)
    {
        return $this->onHandler(self::FRIDAY, $set);
    }


    /**
     * Check or set whether this alarm is active on saturdays.
     *
     * @param bool $set Set this alarm to be active or not on saturdays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onSaturday($set = null)
    {
        return $this->onHandler(self::SATURDAY, $set);
    }


    /**
     * Check or set whether this alarm is active on sundays.
     *
     * @param bool $set Set this alarm to be active or not on sundays
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function onSunday($set = null)
    {
        return $this->onHandler(self::SUNDAY, $set);
    }


    /**
     * Check or set whether this alarm is a one time only alarm.
     *
     * @param bool $set Set this alarm to be a one time only alarm
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function once($set = null)
    {
        if ($set) {
            return $this->setFrequency(self::ONCE);
        }
        return $this->getFrequency() === self::ONCE;
    }


    /**
     * Check or set whether this alarm runs every day or not.
     *
     * @param bool $set Set this alarm to be active every day
     *
     * @return bool|static Returns true/false when checking, or static when setting
     */
    public function daily($set = null)
    {
        if ($set) {
            return $this->setFrequency(self::DAILY);
        }
        return $this->getFrequency() === self::DAILY;
    }


    /**
     * Get the frequency of the alarm as a human readable description.
     *
     * @return string
     */
    public function getFrequencyDescription()
    {
        $data = $this->attributes["Recurrence"];
        if ($data === "ONCE") {
            return "Once";
        }
        if ($data === "DAILY") {
            return "Daily";
        }
        if ($data === "WEEKDAYS") {
            return "Weekdays";
        }
        if ($data === "WEEKENDS") {
            return "Weekends";
        }

        $data = $this->getFrequency();
        $days = [
            self::MONDAY    =>  "Mon",
            self::TUESDAY   =>  "Tues",
            self::WEDNESDAY =>  "Wed",
            self::THURSDAY  =>  "Thurs",
            self::FRIDAY    =>  "Fri",
            self::SATURDAY  =>  "Sat",
            self::SUNDAY    =>  "Sun",
        ];
        $description = "";
        foreach ($days as $key => $val) {
            if ($data & $key) {
                if (strlen($description) > 0) {
                    $description .= ",";
                }
                $description .= $val;
            }
        }
        return $description;
    }


    /**
     * Get the volume of the alarm.
     *
     * @return int
     */
    public function getVolume()
    {
        return (int) $this->attributes["Volume"];
    }


    /**
     * Set the volume of the alarm.
     *
     * @param int $volume The volume of the alarm
     *
     * @return static
     */
    public function setVolume($volume)
    {
        $this->attributes["Volume"] = $volume;

        return $this->save();
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
        $mode = Helper::getMode($this->attributes["PlayMode"]);
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

        $mode = Helper::getMode($this->attributes["PlayMode"]);
        if ($mode[$type] === $value) {
            return $this;
        }

        $mode[$type] = $value;
        $this->attributes["PlayMode"] = Helper::setMode($mode);

        return $this->save();
    }


    /**
     * Check if repeat is active.
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
     * Check if shuffle is active.
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
     * Check if the alarm is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->attributes["Enabled"] ? true : false;
    }


    /**
     * Make the alarm active.
     *
     * @return static
     */
    public function activate()
    {
        $this->attributes["Enabled"] = true;

        return $this->save();
    }


    /**
     * Make the alarm inactive.
     *
     * @return static
     */
    public function deactivate()
    {
        $this->attributes["Enabled"] = false;

        return $this->save();
    }


    /**
     * Delete this alarm.
     *
     * @return void
     */
    public function delete()
    {
        $this->soap("AlarmClock", "DestroyAlarm");
        $this->id = null;
    }


    /**
     * Update the alarm with the current instance settings.
     *
     * @return static
     */
    protected function save()
    {
        $params = [
            "StartLocalTime"        =>  $this->attributes["StartTime"],
            "Duration"              =>  $this->attributes["Duration"],
            "Recurrence"            =>  $this->attributes["Recurrence"],
            "Enabled"               =>  $this->attributes["Enabled"] ? "1" : "0",
            "RoomUUID"              =>  $this->attributes["RoomUUID"],
            "ProgramURI"            =>  $this->attributes["ProgramURI"],
            "ProgramMetaData"       =>  $this->attributes["ProgramMetaData"],
            "PlayMode"              =>  $this->attributes["PlayMode"],
            "Volume"                =>  $this->attributes["Volume"],
            "IncludeLinkedZones"    =>  $this->attributes["IncludeLinkedZones"],
        ];

        $this->soap("AlarmClock", "UpdateAlarm", $params);

        return $this;
    }
}
