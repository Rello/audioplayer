<?php
/**
 *
 * class to control a Sonos Multiroom System
 *
 *
 * Version:        2.1.3
 * Date:            20.11.2017
 * Auto:            Oliver Lewald
 * published in:    http://plugins.loxberry.de/
 *
 *
 *
 **/

# Available commands to interact with Sonos System:

# - ListAlarms()
# - UpdateAlarm($id, $startzeit, $duration, $welchetage, $an, $roomid, $programm, $programmeta, $playmode, $volume, $linkedzone)
# - GetAudioInputAttributes()
# - GetZoneAttributes()
# - GetZoneInfo()
# - SetLEDState($state)
# - GetLEDState()
# - SetInvisible($state)
# - GetInvisible()
# - SubscribeZPGroupManagement($callback)
# - AddMember($MemberID)
# - RemoveMember($MemberID)
# - RampToVolume($ramp_type, $volume)
# - SaveQueue($title,$id="")
# - GetCrossfadeMode()
# - SetCrossfadeMode($mode)
# - Stop()
# - Pause()
# - Play()
# - Next()
# - Previous()
# - Seek($arg1,$arg2="NONE")
# - Rewind()
# - SetGroupMute($mute)
# - SetGroupVolume($volume)
# - GetGroupVolume()
# - SnapshotGroupVolume()
# - SetRelativeGroupVolume($volume)
# - BecomeCoordinatorOfStandaloneGroup()
# - SetTreble($Treble)
# - SetBass($Bass)
# - SetLoudness($loud)
# - GetTreble()
# - GetBass()
# - GetLoudness()
# - Sleeptimer($timer)
# - SetVolume($volume)
# - GetVolume()
# - SetMute($mute)
# - GetMute()
# - SetPlayMode($mode)
# - GetTransportSettings()
# - GetCurrentTransportActions()
# - GetTransportInfo()
# - GetMediaInfo()
# - GetPositionInfo()
# - SetRadio($radio,$Name="default",$id="R:0/0/0",$parentID="R:0/0")
# - SetAVTransportURI($tspuri,$MetaData="")
# - ClearQueue()
# - AddToQueue($file)
# - RemoveFromQueue($track)
# - SetTrack($track)
# - GetCurrentPlaylist()
# - GetSonosPlaylists()
# - GetImportedPlaylists()
# - GetPlaylist($value)
# - Browse($value,$meta="BrowseDirectChildren",$filter="",$sindex="0",$rcount="1000",$sc="")
# - RadiotimeGetNowPlaying()
# - DelegateGroupCoordinationTo($RinconID, $Rejoin)
# - DelSonosPlaylist($id)
# - CreateStereoPair($ChannelMapSet)
# - SeperateStereoPair($ChannelMapSet)
// V2.1.1
# - GetVolumeMode($mode, $uuid)
# - SetVolumeMode($uuid)
// V3.4.0
# - AddFavoritesToQueue
# - SetBalance($direction, $volume)
# - ResetBasicEQ()


class PHPSonos
{
    private $address = "";

    # private $port = "1400";

    public function __construct($address)
    {
        $this->address = $address;
        #$this->port = $port;
    }


    /**
     * Returns a list of alarms from device
     *
     * @return Array
     *
     */

    public function ListAlarms()
    {

        $header = 'POST /AlarmClock/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AlarmClock:1#ListAlarms"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?> <s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:AlarmClock xmlns:u="urn:schemas-upnp-org:service:AlarmClock:1"/>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->XMLsendPacket($content);
        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);
        $xmlr = new SimpleXMLElement($returnContent);
        $liste = array();
        for ($i = 0, $size = count($xmlr); $i < $size; $i++) {
            $attr = $xmlr->Alarm[$i]->attributes();
            $liste[$i]['ID'] = (string)$attr['ID'];
            $liste[$i]['StartTime'] = (string)$attr['StartTime'];
            $liste[$i]['Duration'] = (string)$attr['Duration'];
            $liste[$i]['Recurrence'] = (string)$attr['Recurrence'];
            $liste[$i]['Enabled'] = (string)$attr['Enabled'];
            $liste[$i]['RoomUUID'] = (string)$attr['RoomUUID'];
            $liste[$i]['ProgramURI'] = (string)$attr['ProgramURI'];
            $liste[$i]['ProgramMetaData'] = (string)$attr['ProgramMetaData'];
            $liste[$i]['PlayMode'] = (string)$attr['PlayMode'];
            $liste[$i]['Volume'] = (string)$attr['Volume'];
            $liste[$i]['IncludeLinkedZones'] = (string)$attr['IncludeLinkedZones'];

        }
        return $liste;
    }


    /**
     * Updates an existing alarm
     *
     * @param string $id Id of the Alarm
     * @param string $startzeit StartLocalTime
     * @param string $duration Duration
     * @param string $welchetage Recurrence
     * @param string $an Enabled? (true/false)
     * @param string $roomid Room UUID
     * @param string $programm ProgramUri
     * @param string $programmmeta ProgramMetadata
     * @param string $playmode PlayMode
     * @param string $volume Volume
     * @param string $linkedzone IncludeLinkedZones
     *
     * @return Void
     *
     */

    public function UpdateAlarm($id, $startzeit, $duration, $welchetage, $an, $roomid, $programm, $programmeta, $playmode, $volume, $linkedzone)
    {
        $payload = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<s:Body><u:UpdateAlarm xmlns:u="urn:schemas-upnp-org:service:AlarmClock:1">
<ID>' . $id . '</ID>
<StartLocalTime>' . $startzeit . '</StartLocalTime>
<Duration>' . $duration . '</Duration>
<Recurrence>' . $welchetage . '</Recurrence>
<Enabled>' . $an . '</Enabled>
<RoomUUID>' . $roomid . '</RoomUUID>
<ProgramURI>' . htmlspecialchars($programm) . '</ProgramURI>
<ProgramMetaData>' . htmlspecialchars($programmeta) . '</ProgramMetaData>
<PlayMode>' . $playmode . '</PlayMode>
<Volume>' . $volume . '</Volume>
<IncludeLinkedZones>' . $linkedzone . '</IncludeLinkedZones>
</u:updateAlarm></s:Body></s:Envelope>';


        $content = 'POST /AlarmClock/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . strlen($payload) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AlarmClock:1#UpdateAlarm"

' . $payload;

        $this->sendPacket($content);
    }



    /**
     * Get information of devices inputs
     *
     * @return Array
     *
     */

    public function GetAudioInputAttributes()
    {

        $header = 'POST /AudioIn/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AudioIn:1#GetAudioInputAttributes"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:GetAudioInputAttributes xmlns:u="urn:schemas-upnp-org:service:AudioIn:1"/>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->XMLsendPacket($content);


        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);



        $AudioInReturn = Array();

        $key = "CurrentName"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $AudioInReturn[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $AudioInReturn[$key] = "";
        }

        $key = "CurrentIcon"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $AudioInReturn[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $AudioInReturn[$key] = "";
        }


        return $AudioInReturn; //Assoziatives Array
    }


    /**
     * Reads Zone Attributes
     *
     * @return Array
     *
     **/

    public function GetZoneAttributes()
    {
        $header = 'POST /DeviceProperties/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#GetZoneAttributes"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:GetZoneAttributes xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"/>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->XMLsendPacket($content);


        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);


        $ZoneAttributes = Array();

        $key = "CurrentZoneName"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneAttributes[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneAttributes[$key] = "";
        }

        $key = "CurrentIcon"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneAttributes[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneAttributes[$key] = "";
        }


        return $ZoneAttributes; //Assoziatives Array
    }

    /**
     * Reads Zone Information
     *
     * Array
     * (
     *    [SerialNumber] => 00-zz-58-32-yy-xx:5
     *    [SoftwareVersion] => 15.4-442xx
     *    [DisplaySoftwareVersion] => 3.5.x
     *    [HardwareVersion] => 1.16.3.z-y
     *    [IPAddress] => yyy.168.z.xxx
     *    [MACAddress] => 00:zz:58:32:yy:xx
     *    [CopyrightInfo] => ? 2004-2007 Sonos, Inc. All Rights Reserved.
     *    [ExtraInfo] => OTP: 1.1.x(1-yy-3-0.x)
     *)
     * @return Array
     *
     */

    public function GetZoneInfo()
    {
        $header = 'POST /DeviceProperties/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#GetZoneInfo"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:GetZoneInfo xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"/>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->XMLsendPacket($content);


        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);


        $ZoneInfo = Array();

        $key = "SerialNumber"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }

        $key = "SoftwareVersion"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }

        $key = "SoftwareVersion"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }

        $key = "DisplaySoftwareVersion"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }

        $key = "HardwareVersion"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }

        $key = "IPAddress"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }


        $key = "MACAddress"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }


        $key = "CopyrightInfo"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = "";
        }


        $key = "ExtraInfo"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneInfo[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneInfo[$key] = ""; }


        return $ZoneInfo; //Assoziatives Array
    }

    /**
     * Sets the state of the white LED
     *
     * @param string $state true||false value or On / Off
     *
     * @return Boolean
     */

    public function SetLEDState($state) // added br
    {
        if ($state == "On") {
            $state = "On";
        } else {
            if ($state == "Off") {
                $state = "Off";
            } else {
                if ($state) {
                    $state = "On";
                } else {
                    $state = "Off";
                }
            }
        }

        $content = 'POST /DeviceProperties/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#SetLEDState"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetLEDState xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"><DesiredLEDState>' . $state . '</DesiredLEDState><u:SetLEDState></s:Body></s:Envelope>';

        return (bool)$this->sendPacket($content);
    }

    /**
     * Gets the state of the white LED
     *
     * @return Boolean
     *
     */

    public function GetLEDState() // added br
    {

        $content = 'POST /DeviceProperties/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#GetLEDState"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetLEDState xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"><InstanceID>0</InstanceID><u:GetLEDState></s:Body></s:Envelope>';

        if ($this->sendPacket($content) == "On") {
            return (true);
        } else return (false);
    }


    /**
     * Sets ZP to visible or unvisable
     *
     * @param string $state integer true||false value or string True/ False
     *
     * @return Boolean
     *
     */

    public function SetInvisible($state) // added br 110916
    {
        if ($state == "True") {
            $state = "True";
        } else {
            if ($state == "False") {
                $state = "False";
            } else {
                if ($state) {
                    $state = "True";
                } else {
                    $state = "False";
                }
            }
        }

        $content = 'POST /DeviceProperties/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#SetInvisible"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetInvisible xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"><DesiredInvisible>' . $state . '</DesiredInvisible><u:SetInvisible></s:Body></s:Envelope>';

        return (bool)$this->sendPacket($content);
    }

    /**
     * Gets ZP invisible information
     *
     * @return Boolean
     *
     */

    public function GetInvisible()
    {

        $content = 'POST /DeviceProperties/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#GetInvisible"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetInvisible xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"><InstanceID>0</InstanceID><u:GetInvisible></s:Body></s:Envelope>';

        if ($this->sendPacket($content) == "1") {
            return (true);
        } else return (false);
    }


    /**
     * Necessary to handle group management
     *
     * @return Boolean to IP
     *
     */

    function SubscribeZPGroupManagement($callback)
    { // added br
        $content = 'SUBSCRIBE /GroupManagement/Event HTTP/1.1
HOST: ' . $this->address . ':1400
CALLBACK: <' . $callback .'>
NT: upnp:event
TIMEOUT: Second-300
Content-Length: 0

';
        $this->sendPacket($content);
    }



    /**
     * Adds a Member to a existing ZoneGroup
     * @param string $MemberID LocalUUID/ Rincon of Player to add
     *
     * @return Array
     *
     */

    public function AddMember($MemberID) // added br
    {

        $header = 'POST /GroupManagement/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupManagement:1#AddMember"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body><u:AddMember xmlns:u="urn:schemas-upnp-org:service:GroupManagement:1"><MemberID>' . $MemberID . '</MemberID>
</u:AddMember></s:Body></s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;


        $returnContent = $this->XMLsendPacket($content);

        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);


        $ZoneAttributes = Array();

        $key = "CurrentTransportSettings"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneAttributes[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneAttributes[$key] = "";
        }

        $key = "GroupUUIDJoined"; // Lookfor
        if (isset($index[strtoupper($key)][0]) and isset($vals[$index[strtoupper($key)][0]]['value'])) {
            $ZoneAttributes[$key] = $vals[$index[strtoupper($key)][0]]['value'];
        } else {
            $ZoneAttributes[$key] = "";
        }


        return ($ZoneAttributes); //Assoziatives Array
        // set AVtransporturi ist notwendig
    }


    /**
     * Removes a Member from an existing ZoneGroup
     * @param string $MemberID LocalUUID/ Rincon of Player to remove
     *
     * @return Sring
     *
     */

    public function RemoveMember($MemberID) // added br

    {

        $header = 'POST /GroupManagement/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupManagement:1#RemoveMember"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body><u:RemoveMember xmlns:u="urn:schemas-upnp-org:service:GroupManagement:1"><MemberID>' . $MemberID . '</MemberID>
</u:RemoveMember></s:Body></s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;
        return $this->sendPacket($content);

    }


    /**
     * Ramps Volume to $volume using $ramp_type ; different algorithms are possible
     *
     *   Ramps Volume to $volume using the Method mentioned in $ramp_type as string:
     *   "SLEEP_TIMER_RAMP_TYPE" - mutes and ups Volume per default within 17 seconds to desiredVolume
     *   "ALARM_RAMP_TYPE" -Switches audio off and slowly goes to volume
     *   "AUTOPLAY_RAMP_TYPE" - very fast and smooth; Implemented from Sonos for the autoplay feature.
     *
     * @param string $volume DesiredVolume
     *
     * @return Void
     */

    public function RampToVolume($ramp_type, $volume) //added br // added soap parameters 20111021
    {


        $header = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
HOST: ' . $this->address . ':1400
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#RampToVolume"
';
        $xml = '<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body><u:RampToVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel><RampType>' . $ramp_type . '</RampType><DesiredVolume>' . $volume . '</DesiredVolume>
<ResetVolumeAfter>false</ResetVolumeAfter><ProgramURI></ProgramURI>
</u:RampToVolume></s:Body></s:Envelope>';
        $content = $header . 'Content-Length: ' . strlen($xml) . '

' . $xml;


        return (int)$this->sendPacket($content);

    }


    /**
     * TEST Function for MediaRenderAVT Callback and IPS Register Vars
     *
     * @param string $callback CallbackURL Well gat a HTTP Callback at this URl (SOAP)
     * @return Void
     */

    function SubscribeMRAVTransport($callback)
    { // added br
        $content = 'SUBSCRIBE /MediaRenderer/AVTransport/Event HTTP/1.1
HOST: ' . $this->address . ':1400
CALLBACK: <' . $callback . '>
NT: upnp:event
TIMEOUT: Second-300
Content-Length: 0

';
        $this->sendPacket($content);
    }

    /**
     * Save current queue off to sonos
     *
     * @param string $title Title of Playlist
     * @param string $id Playlists ID (optional)
     *
     * @return string
     *
     */
    public function SaveQueue($title, $id = "") // added br
    {

        $header = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#SaveQueue"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body>
<u:SaveQueue xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Title>' . $title . '</Title><ObjectID>' . $id . '</ObjectID></u:SaveQueue>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);

    }

    /**
     * Get info on actual crossfade mode
     *
     *
     * @return Boolean
     */

    public function GetCrossfadeMode() // added br
    {

        $header = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
HOST: ' . $this->address . ':1400
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#GetCrossfadeMode"
';
        $xml = '<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body><u:GetCrossfadeMode xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID>
</u:GetCrossfadeMode></s:Body></s:Envelope>';
        $content = $header . 'Content-Length: ' . strlen($xml) . '

' . $xml;

        return (bool)$this->sendPacket($content);
    }

    /**
     * Set crossfade to true or false
     *
     * @param string $mode Enable/ Disable = 1/0 (string) = true /false (boolean)
     *
     * @return Void
     */

    public function SetCrossfadeMode($mode) // added br
    {


        if ($mode) {
            $mode = "1";
        } else {
            $mode = "0";
        }
        $header = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
HOST: ' . $this->address . ':1400
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#SetCrossfadeMode"
';
        $xml = '<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body><u:SetCrossfadeMode xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CrossfadeMode>' . $mode . '</CrossfadeMode></u:SetCrossfadeMode></u:SetCrossfadeMode></s:Body></s:Envelope>';
        $content = $header . 'Content-Length: ' . strlen($xml) . '

' . $xml;

        $this->sendPacket($content);


    }
    /**
     * Stops playing
     *
     * @return Void
     */

    public function Stop()
    {
        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Stop"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Stop xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:Stop></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Pauses playing
     *
     * @return Void
     */

    public function Pause()
    {
        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 252
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Pause"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Pause xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:Pause></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * Play or continue playback
     *
     * @return Void
     */

    public function Play()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 266
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Play"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Play xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Speed>1</Speed></u:Play></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * NEXT
     *
     * @return Void
     */

    public function Next()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Next"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Next xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:Next></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * PREVIOUS
     *
     * @return Void
     */

    public function Previous()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 258
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Previous"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Previous xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:Previous></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * SEEK
     *
     * @param string $arg1 Unit ("TRACK_NR" || "REL_TIME" || "SECTION")
     * @param string $arg2 Target (if this Arg is not set Arg1 is considered to be "REL_TIME and the real arg1 value is set as arg2 value)
     *
     * @return String
     */

    public function Seek($arg1, $arg2 = "NONE")
    {
        if ($arg2 == "NONE") {
            $Unit = "REL_TIME";
            $position = $arg1;
        } else {
            $Unit = $arg1;
            $position = $arg2;
        }

        $header = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Seek"
CONTENT-TYPE: text/xml; charset="utf-8"
CONNECTION: close
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Unit>' . $Unit . '</Unit><Target>' . $position . '</Target></u:Seek></s:Envelope></s:Body></s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);



    }

    /**
     * REWIND
     *
     * @return String
     */

    public function Rewind()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 296
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Seek"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Unit>REL_TIME</Unit><Target>00:00:00</Target></u:Seek></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * Get mute mode of the current group
     *
     * @return boolean
     */

    public function GetGroupMute()
    {
        $content = 'POST /MediaRenderer/GroupRenderingControl/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupRenderingControl:1#GetGroupMute"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 276

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:GetGroupMute xmlns:u="urn:schemas-upnp-org:service:GroupRenderingControl:1"><InstanceID>0</InstanceID></u:GetGroupMute></s:Body></s:Envelope>';

        return (bool)$this->sendPacket($content);
    }

    /**
     * Get mute mode of the current group
     *
     * @return string
     */
    public function SetGroupMute($mute)
    {
        if ($mute) {
            $mute = "1";
        } else {
            $mute = "0";
        }

        $content = 'POST /MediaRenderer/GroupRenderingControl/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupRenderingControl:1#SetGroupMute"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 304

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SetGroupMute xmlns:u="urn:schemas-upnp-org:service:GroupRenderingControl:1"><InstanceID>0</InstanceID><DesiredMute>' . $mute . '</DesiredMute></u:SetGroupMute></s:Body></s:Envelope>';


        return (int)$this->sendPacket($content);
    }

    /**
     * Set Volume of the current group
     *
     * @return: none
     */

    public function SetGroupVolume($volume)
    {
        if ($volume < '10') {
            $length = '312';
        } else {
            $length = '313';
        }

        $content = 'POST /MediaRenderer/GroupRenderingControl/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupRenderingControl:1#SetGroupVolume"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: ' . $length . '

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SetGroupVolume xmlns:u="urn:schemas-upnp-org:service:GroupRenderingControl:1"><InstanceID>0</InstanceID><DesiredVolume>' . $volume . '</DesiredVolume></u:SetGroupVolume></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Get Volume of the current group
     *
     * @return: String
     */

    public function GetGroupVolume()
    {
        $content = 'POST /MediaRenderer/GroupRenderingControl/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupRenderingControl:1#GetGroupVolume"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 280

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:GetGroupVolume xmlns:u="urn:schemas-upnp-org:service:GroupRenderingControl:1"><InstanceID>0</InstanceID></u:GetGroupVolume></s:Body></s:Envelope>';

        return (int)$this->sendPacket($content);
    }


    /**
     * Get current Volume of all group members
     *
     * @return: String
     */

    public function SnapshotGroupVolume()
    {

        $content = 'POST /MediaRenderer/GroupRenderingControl/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupRenderingControl:1#SnapshotGroupVolume"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 290

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SnapshotGroupVolume xmlns:u="urn:schemas-upnp-org:service:GroupRenderingControl:1"><InstanceID>0</InstanceID></u:SnapshotGroupVolume></s:Body></s:Envelope>';

        # $this->sendPacket($content);
        return (int)$this->sendPacket($content);
    }


    /**
     * Set relative Volume of all group members in percentage to current volume
     *
     * @return: none
     */

    public function SetRelativeGroupVolume($volume)
    {

        if ($volume < '10') {
            $length = '322';
        } else {
            $length = '323';
        }

        $content = 'POST /MediaRenderer/GroupRenderingControl/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:GroupRenderingControl:1#SetRelativeGroupVolume"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: ' . $length . '

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SetRelativeGroupVolume xmlns:u="urn:schemas-upnp-org:service:GroupRenderingControl:1"><InstanceID>0</InstanceID><Adjustment>10</Adjustment></u:SetRelativeGroupVolume></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Remove Zone from Group to be his own Coordinator
     *
     * @return: none
     */
    public function BecomeCoordinatorOfStandaloneGroup()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#BecomeCoordinatorOfStandaloneGroup"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 310

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:BecomeCoordinatorOfStandaloneGroup xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:BecomeCoordinatorOfStandaloneGroup></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * Deletes playlist
     *
     * @return: none
     */

    public function DelSonosPlaylist($id)
    {
        $content = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 250
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#DestroyObject"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<s:Body><u:DestroyObject xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>' . $id . '</ObjectID></u:DestroyObject></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Delegates GroupCoordinator to another Zone
     *
     * @param string $RinconID , $Rejoin)
     *
     * @return: String
     */

    public function DelegateGroupCoordinationTo($RinconID, $Rejoin)
    {

# 0 = RejoinGroup --> false
# 1 = RejoinGroup --> true

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#DelegateGroupCoordinationTo"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 419

<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
   <s:Body>
      <u:DelegateGroupCoordinationTo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1">
         <InstanceID>0</InstanceID>
         <NewCoordinator>' . $RinconID . '</NewCoordinator>
         <RejoinGroup>' . $Rejoin . '</RejoinGroup>
      </u:DelegateGroupCoordinationTo>
   </s:Body>
</s:Envelope>';


        $this->sendPacket($content);
    }


    /**
     * Creates a Stereo Pair of two single zones
     *
     * @param string RinconID, LEFT and RinconID RIGHT
     *
     * @return: None
     */

    public function CreateStereoPair($ChannelMapSet)
    {
        $content = 'POST /DeviceProperties/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#CreateStereoPair"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 381

<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body>
<u:CreateStereoPair xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"><ChannelMapSet>' . $ChannelMapSet . '</ChannelMapSet></u:CreateStereoPair></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Seperates a Stereo Pair in two single zones
     *
     * @param string RinconID, LEFT and RinconID RIGHT
     *
     * @return: None
     */

    public function SeperateStereoPair($ChannelMapSet)
    {
        $content = 'POST /DeviceProperties/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:DeviceProperties:1#SeparateStereoPair"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 387

<?xml version="1.0" encoding="utf-8"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body>
<u:SeparateStereoPair xmlns:u="urn:schemas-upnp-org:service:DeviceProperties:1"><ChannelMapSet>' . $ChannelMapSet . '</ChannelMapSet></u:SeparateStereoPair></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }



# to be checked if necessary
    public function GetZoneGroupState()
    {
        return $this->processSoapCall("/ZoneGroupTopology/Control",
            "urn:schemas-upnp-org:service:ZoneGroupTopology:1",
            "GetZoneGroupState",
            array());
    }

    public function GetZoneGroupAttributes()
    {
        return $this->processSoapCall("/ZoneGroupTopology/Control",
            "urn:schemas-upnp-org:service:ZoneGroupTopology:1",
            "GetZoneGroupAttributes",
            array());
    }

    public function processSoapCall($path, $uri, $action, $parameter)
    {
        try {
            $client = new SoapClient(null, array("location" => "http://" . $this->address . ":1400" . $path,
                "uri" => $uri,
                "trace" => true));

            return $client->__soapCall($action, $parameter);
        } catch (Exception $e) {
            $faultstring = $e->faultstring;
            $faultcode = $e->faultcode;
            if (isset($e->detail->UPnPError->errorCode)) {
                $errorCode = $e->detail->UPnPError->errorCode;
                throw new Exception("Error during Soap Call: " . $faultstring . " " . $faultcode . " " . $errorCode . " (" . $this->resoveErrorCode($path, $errorCode) . ")");
            } else {
                throw new Exception("Error during Soap Call: " . $faultstring . " " . $faultcode);
            }
        }
    }

    public function resoveErrorCode($path, $errorCode)
    {
        $errorList = array("/MediaRenderer/AVTransport/Control" => array(
            "701" => "ERROR_AV_UPNP_AVT_INVALID_TRANSITION",
            "702" => "ERROR_AV_UPNP_AVT_NO_CONTENTS",
            "703" => "ERROR_AV_UPNP_AVT_READ_ERROR",
            "704" => "ERROR_AV_UPNP_AVT_UNSUPPORTED_PLAY_FORMAT",
            "705" => "ERROR_AV_UPNP_AVT_TRANSPORT_LOCKED",
            "706" => "ERROR_AV_UPNP_AVT_WRITE_ERROR",
            "707" => "ERROR_AV_UPNP_AVT_PROTECTED_MEDIA",
            "708" => "ERROR_AV_UPNP_AVT_UNSUPPORTED_REC_FORMAT",
            "709" => "ERROR_AV_UPNP_AVT_FULL_MEDIA",
            "710" => "ERROR_AV_UPNP_AVT_UNSUPPORTED_SEEK_MODE",
            "711" => "ERROR_AV_UPNP_AVT_ILLEGAL_SEEK_TARGET",
            "712" => "ERROR_AV_UPNP_AVT_UNSUPPORTED_PLAY_MODE",
            "713" => "ERROR_AV_UPNP_AVT_UNSUPPORTED_REC_QUALITY",
            "714" => "ERROR_AV_UPNP_AVT_ILLEGAL_MIME",
            "715" => "ERROR_AV_UPNP_AVT_CONTENT_BUSY",
            "716" => "ERROR_AV_UPNP_AVT_RESOURCE_NOT_FOUND",
            "717" => "ERROR_AV_UPNP_AVT_UNSUPPORTED_PLAY_SPEED",
            "718" => "ERROR_AV_UPNP_AVT_INVALID_INSTANCE_ID"
        ),
            "/MediaRenderer/RenderingControl/Control" => array(
                "701" => "ERROR_AV_UPNP_RC_INVALID_PRESET_NAME",
                "702" => "ERROR_AV_UPNP_RC_INVALID_INSTANCE_ID"
            ),
            "/MediaServer/ContentDirectory/Control" => array(
                "701" => "ERROR_AV_UPNP_CD_NO_SUCH_OBJECT",
                "702" => "ERROR_AV_UPNP_CD_INVALID_CURRENTTAGVALUE",
                "703" => "ERROR_AV_UPNP_CD_INVALID_NEWTAGVALUE",
                "704" => "ERROR_AV_UPNP_CD_REQUIRED_TAG_DELETE",
                "705" => "ERROR_AV_UPNP_CD_READONLY_TAG_UPDATE",
                "706" => "ERROR_AV_UPNP_CD_PARAMETER_NUM_MISMATCH",
                "708" => "ERROR_AV_UPNP_CD_BAD_SEARCH_CRITERIA",
                "709" => "ERROR_AV_UPNP_CD_BAD_SORT_CRITERIA",
                "710" => "ERROR_AV_UPNP_CD_NO_SUCH_CONTAINER",
                "711" => "ERROR_AV_UPNP_CD_RESTRICTED_OBJECT",
                "712" => "ERROR_AV_UPNP_CD_BAD_METADATA",
                "713" => "ERROR_AV_UPNP_CD_RESTRICTED_PARENT_OBJECT",
                "714" => "ERROR_AV_UPNP_CD_NO_SUCH_SOURCE_RESOURCE",
                "715" => "ERROR_AV_UPNP_CD_SOURCE_RESOURCE_ACCESS_DENIED",
                "716" => "ERROR_AV_UPNP_CD_TRANSFER_BUSY",
                "717" => "ERROR_AV_UPNP_CD_NO_SUCH_FILE_TRANSFER",
                "718" => "ERROR_AV_UPNP_CD_NO_SUCH_DESTINATION_RESOURCE",
                "719" => "ERROR_AV_UPNP_CD_DESTINATION_RESOURCE_ACCESS_DENIED",
                "720" => "ERROR_AV_UPNP_CD_REQUEST_FAILED"
            ));

        if (isset($errorList[$path][$errorCode])) {
            return $errorList[$path][$errorCode];
        } else {
            return "UNKNOWN";
        }
    }


    /**
     * Sets Treble for Zone
     *
     * @param string Treble
     *
     * @return: None
     */

    public function SetTreble($Treble)
    {
        $laenge = 296 + (strlen($Treble));
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . $laenge . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetTreble"

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SetTreble xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><DesiredTreble>' . $Treble . '</DesiredTreble></u:SetTreble></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Sets Bass for Zone
     *
     * @param string Treble
     *
     * @return: None
     */

    public function SetBass($Bass)
    {
        $laenge = 288 + (strlen($Bass));
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . $laenge . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetBass"

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SetBass xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><DesiredBass>' . $Bass . '</DesiredBass></u:SetBass></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Sets Loudness for Zone
     *
     * @param string (0 or 1)
     *
     * @return: None
     */


    public function SetLoudness($loud)
    {
        if ($loud) {
            $loud = "1";
        } else {
            $loud = "0";
        }

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 330
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetLoudness"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetLoudness xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel><DesiredLoudness>' . $loud . '</DesiredLoudness></u:SetLoudness></s:Body></s:Envelope>';
        $this->sendPacket($content);
    }


    /**
     * Gets Loudness for Zone
     *
     * @param: none
     *
     * @return: boolean
     */

    public function GetLoudness()
    {
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 293
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#GetLoudness"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetLoudness xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetLoudness></s:Body></s:Envelope>';

        return (bool)$this->sendPacket($content);
    }


    /**
     * Gets Treble for Zone
     *
     * @param: none
     *
     * @return: string
     */

    public function GetTreble()
    {
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 290
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#GetTreble"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetTreble xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetTreble></s:Body></s:Envelope>';

        return (int)$this->sendPacket($content);
    }


    /**
     * Gets Bass for Zone
     *
     * @param: None
     *
     * @return: String
     */

    public function GetBass()
    {
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 279
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#GetBass"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetBass xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetBass></s:Body></s:Envelope>';

        return (int)$this->sendPacket($content);
    }

    /**
     * Sleeptimer in Minutes (0-59)
     *
     * @params string
     *
     * @return: None
     */

    public function Sleeptimer($timer)
    {
        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#ConfigureSleepTimer"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400
Content-Length: 335

<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:ConfigureSleepTimer xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><NewSleepTimerDuration>' . $timer . '</NewSleepTimerDuration></u:ConfigureSleepTimer></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Sets volume for a player
     *
     * @param string $volume Volume in percent
     *
     * @return String
     */

    public function SetVolume($volume)
    {

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 32' . strlen($volume) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetVolume"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel><DesiredVolume>' . $volume . '</DesiredVolume></u:SetVolume></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Sets balance volume for player
     *
     * @param string $volume (Volume in percent), Left or right speaker
     *
     * @return String
     */

    public function SetBalance($direction, $volume)
    {

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (317 + strlen($volume)) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetVolume"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>' . strtoupper($direction) . '</Channel><DesiredVolume>' . $volume . '</DesiredVolume></u:SetVolume></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Sets balance, bass and treble to standard
     *
     * @param string
     *
     * @return String
     */
    public function ResetBasicEQ()
    {

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
USER-AGENT: Linux UPnP/1.0 Sonos/42.2-52113 (WDCR:Microsoft Windows NT 6.1.7601 Service Pack 1)
CONTENT-LENGTH: 271
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#ResetBasicEQ"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:ResetBasicEQ xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID></u:ResetBasicEQ></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Gets current volume information from player
     *
     * @return String
     */

    public function GetVolume()
    {

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 290
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#GetVolume"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetVolume></s:Body></s:Envelope>';

        return (int)$this->sendPacket($content);
    }


    /**
     * Sets mute/unmute for a player
     *
     * @param string $mute Mute unmute as (boolean)true/false or (string)1/0
     *
     * @return String
     */

    public function SetMute($mute)
    {

        if ($mute) {
            $mute = "1";
        } else {
            $mute = "0";
        }

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 314
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetMute"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetMute xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel><DesiredMute>' . $mute . '</DesiredMute></u:SetMute></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * Gets mute/unmute status for a player
     *
     * @return string
     */

    public function GetMute()
    {

        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 286
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#GetMute"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetMute xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetMute></s:Body></s:Envelope>';

        return (bool)$this->sendPacket($content);
    }


    /**
     * Sets Playmode for a renderer (could affect more than one zone!)
     *
     * NORMAL = SHUFFLE and REPEAT -->FALSE
     * REPEAT_ALL = REPEAT --> TRUE, Shuffle --> FALSE
     * SHUFFLE_NOREPEAT = SHUFFLE -->TRUE / REPEAT = FALSE
     * SHUFFLE = SHUFFLE and REPEAT -->TRUE </PRE>
     *
     * @param string $mode "NORMAL" || "REPEAT_ALL" || "SHUFFLE_NOREPEAT" || "SHUFFLE"
     *
     * @return String
     */

    public function SetPlayMode($mode)
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (291 + strlen($mode)) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#SetPlayMode"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetPlayMode xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><NewPlayMode>' . $mode . '</NewPlayMode></u:SetPlayMode></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Set for Sonos CONNECT the Volume to fixed or variable
     *
     * @params '0' = variable, '1' = fixed
     * @return string
     */

    public function SetVolumeMode($mode, $uuid)
    {
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
ACCEPT-ENCODING: gzip
HOST: ' . $this->address . ':1400
USER-AGENT: Linux UPnP/1.0 Sonos/38.9-46251 (WDCR:Microsoft Windows NT 6.1.7601 Service Pack 1)
CONTENT-LENGTH: 305
CONTENT-TYPE: text/xml; charset="utf-8"
X-SONOS-TARGET-UDN: uuid:' . $uuid . '
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#SetOutputFixed"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetOutputFixed xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><DesiredFixed>' . $mode . '</DesiredFixed></u:SetOutputFixed></s:Body></s:Envelope>';

        return $this->sendPacket($content);
    }


    /**
     * Get for Sonos CONNECT the Volume mode
     *
     * @params
     * @return '0' = variable, '1' = fixed
     */

    public function GetVolumeMode($uuid)
    {
        $content = 'POST /MediaRenderer/RenderingControl/Control HTTP/1.1
CONNECTION: close
ACCEPT-ENCODING: gzip
HOST: ' . $this->address . ':1400
USER-AGENT: Linux UPnP/1.0 Sonos/38.9-46251 (WDCR:Microsoft Windows NT 6.1.7601 Service Pack 1)
CONTENT-LENGTH: 275
CONTENT-TYPE: text/xml; charset="utf-8"
X-SONOS-TARGET-UDN: uuid:' . $uuid . '
SOAPACTION: "urn:schemas-upnp-org:service:RenderingControl:1#GetOutputFixed"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetOutputFixed xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID></u:GetOutputFixed></s:Body></s:Envelope>';

        return (bool)$this->sendPacket($content);
    }


    /**
     * Gets transport settings for a renderer
     *
     *
     * NORMAL = SHUFFLE and REPEAT and REPEAT ON -->FALSE
     * REPEAT_ALL = REPEAT --> TRUE, SHUFFLE --> FALSE, REPEAT ONE --> FALSE
     * SHUFFLE_NOREPEAT = SHUFFLE -->TRUE, REPEAT = FALSE, REPEAT ONE --> FALSE
     * SHUFFLE = SHUFFLE and REPEAT -->TRUE, REPEAT ONE --> FALSE </PRE>
     * SHUFFLE_REPEAT_ONE = SHUFFLE --> TRUE, REPEAT --> FALSE, REPEAT ONE --> TRUE
     * REPEAT_ONE = SHUFFLE --> FALSE, REPEAT --> FALSE, REPEAT ONE --> TRUE
     *
     * @return Array
     */
    public function GetTransportSettings()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 282
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#GetTransportSettings"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetTransportSettings xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetTransportSettings></s:Body></s:Envelope>';

        $returnContent = $this->sendPacket($content);

//   echo "\n===" . $this->address. "====\n" . $returnContent . "\n===\n";

        if (strstr($returnContent, "NORMAL") !== false) {
            return Array(
                "repeat" => false,
                "repeat one" => false,
                "shuffle" => false
            );
        } elseif (strstr($returnContent, "REPEAT_ALL") !== false) {
            return Array(
                "repeat" => true,
                "repeat one" => false,
                "shuffle" => false
            );

        } elseif (strstr($returnContent, "SHUFFLE_NOREPEAT") !== false) {
            return Array(
                "repeat" => false,
                "repeat one" => false,
                "shuffle" => true
            );

        } elseif (strstr($returnContent, "SHUFFLE_REPEAT_ONE") !== false) {
            return Array(
                "repeat" => false,
                "repeat one" => true,
                "shuffle" => true
            );

        } elseif (strstr($returnContent, "SHUFFLE") !== false) {
            return Array(
                "repeat" => true,
                "repeat one" => false,
                "shuffle" => true
            );

        } elseif (strstr($returnContent, "REPEAT_ONE") !== false) {
            return Array(
                "repeat" => false,
                "repeat one" => true,
                "shuffle" => false
            );

        }
    }

    /**
     * Gets transport settings for a renderer
     *
     * @return String
     */

    public function GetCurrentTransportActions()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 282
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#GetCurrentTransportActions"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetCurrentTransportActions xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetCurrentTransportActions></s:Body></s:Envelope>';

        $returnContent = $this->sendPacket($content);

//   echo "\n===" . $this->address. "====\n" . $returnContent . "\n===\n";

        $ret = preg_replace("#(.*)<Actions>(.*?)\</Actions>(.*)#is", '$2', $returnContent);
        return $ret;

    }


    /**
     * Gets transport settings for a renderer
     *
     * @return Array
     */

    public function GetTransportInfo()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 274
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#GetTransportInfo"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetTransportInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetTransportInfo></s:Body></s:Envelope>';

        $returnContent = $this->sendPacket($content);

        if (strstr($returnContent, "PLAYING") !== false) {
            return 1;
        } elseif (strstr($returnContent, "PAUSED_PLAYBACK") !== false) {
            return 2;
        } elseif (strstr($returnContent, "STOPPED") !== false) {
            return 3;
        }

    }

    /**
     * Gets Media Info
     *
     * Array    (
     * [CurrentURI] => http://192.168.0.2:10243/WMPNSSv4/1458092455/0_ezg1ODYxQzMwLTEyNzgtNDc0Ri05MkQ0LTQxNzE1MDQ0MjMyMX0uMC40.mp3
     * [CurrentURIMetaData] => <DIDL-Lite xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/" xmlns="urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/">    <item id="{85861C30-1278-474F-92D4-417150442321}.0.4" restricted="0" parentID="4">        <dc:title>Car Crazy Cutie</dc:title>        <dc:creator>Beach Boys</dc:creator>        <res size="2753092" duration="0:02:50.000" bitrate="16000" protocolInfo="http-get:*:audio/mpeg:DLNA.ORG_OP=01;DLNA.ORG_FLAGS=01500000000000000000000000000000" sampleFrequency="44100" bitsPerSample="16" nrAudioChannels="2" microsoft:codec="{00000055-0000-0010-8000-00AA00389B71}" xmlns:microsoft="urn:schemas-microsoft-com:WMPNSS-1-0/">http://192.168.0.2:10243/WMPNSSv4/1458092455/0_ezg1ODYxQzMwLTEyNzgtNDc0Ri05MkQ0LTQxNzE1MDQ0MjMyMX0uMC40.mp3</res>        <res duration="0:02:50.000" bitrate="16000" protocolInfo="http-get:*:audio/mpeg:DLNA.ORG_PN=MP3;DLNA.ORG_OP=10;DLNA.ORG_CI=1;DLNA.ORG_FLAGS=01500000000000000000000000000000" sampleFrequency="44100" nrAudioChannels="1" microso
     * [title] => Car Crazy Cutie                         )
     *  </code>
     *
     * @return Array
     */

    public function GetMediaInfo()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 266
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#GetMediaInfo"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetMediaInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetMediaInfo></s:Body></s:Envelope>';

        $returnContent = $this->XMLsendPacket($content);
        #print_r($returnContent);

        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);

        // print_r($index);
        $mediaInfo = Array();



        if (isset($vals[$index["CURRENTURI"][0]]["value"])) {
            $mediaInfo["CurrentURI"] = $vals[$index["CURRENTURI"][0]]["value"];
        } else {
            $mediaInfo["CurrentURI"] = "";
        }

        if (isset($vals[$index["CURRENTURIMETADATA"][0]]["value"])) {
            $mediaInfo["CurrentURIMetaData"] = $vals[$index["CURRENTURIMETADATA"][0]]["value"];

            // print_r($index);
            // print_r($vals);


            $xmlParser = xml_parser_create("UTF-8");
            xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
            xml_parse_into_struct($xmlParser, $mediaInfo["CurrentURIMetaData"], $vals, $index);
            xml_parser_free($xmlParser);

            // print_r($index);
            // print_r($vals);

            if (isset($index["DC:TITLE"]) and isset($vals[$index["DC:TITLE"][0]]["value"])) {
                $mediaInfo["title"] = $vals[$index["DC:TITLE"][0]]["value"];
            } else {
                $mediaInfo["title"] = "";
            }

        } else {
            $mediaInfo["CurrentURIMetaData"] = "";
        }
        return $mediaInfo;
    }

    /**
     * Gets position info
     *
     * Example:
     * Array
     *  (
     *    [position] => 0:00:59
     *    [RelTime] => 0:00:59
     *    [duration] => 0:01:51
     *    [TrackDuration] => 0:01:51
     *    [URI] => http://zzz.yyy.0.x:10243/WMPNSSv4/1458092455/0_ezRENTU5NjFDLUE3MDctNDIwRC04NTc4LUFDODgxQTVFMzMxQX0uMC40.mp3
     *    [TrackURI] => http://192.168.0.x:10243/WMPNSSv4/1458092455/0_ezRENTU5NjFDLUE3MDctNDIwRC04NTc4LUFDODgxQTVFMzMxQX0uMC40.mp3
     *    [artist] => Beach Bxxx....
     *    [title] => Cher... What?
     *    [album] => Little Deuce...
     *    [albumArtURI] => http://zzz.168.y.xxx:1400/getaa?u=http://zzz.168.y.xxx:10243/WMPNSSv4/1458092455/0_ezRENTU5NjFDLUE3MDctNDIwRC04NTc4LUFDODgxQTVFMzMxQX0uMC40.mp3&v=279
     *    [albumArtist] => Beach xxx....
     *    [albumTrackNumber] => 5
     *    [streamContent] =>
     *    [trackURI] =>
     *    [Track] => 1
     *  )
     *
     * @return Array
     */

    public function GetPositionInfo()
    {
        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 272
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#GetPositionInfo"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetPositionInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetPositionInfo></s:Body></s:Envelope>';

        $returnContent = $this->sendPacket($content);
        $returnContentMeta = $returnContent;

        $position = substr($returnContent, stripos($returnContent, "NOT_IMPLEMENTED") - 7, 7);

        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);

        $positionInfo = Array();

        $positionInfo["position"] = $position;
        $positionInfo["RelTime"] = $position;

        if (isset($index["RES"]) and isset($vals[$index["RES"][0]]["attributes"]["DURATION"])) {
            $positionInfo["duration"] = $vals[$index["RES"][0]]["attributes"]["DURATION"];
            $positionInfo["TrackDuration"] = $vals[$index["RES"][0]]["attributes"]["DURATION"];
        } else {
            $positionInfo["duration"] = "";
            $positionInfo["TrackDuration"] = "";
        }
#print_r($index);
        if (isset($index["RES"]) and isset($vals[$index["RES"][0]]["value"])) {
            $positionInfo["URI"] = $vals[$index["RES"][0]]["value"];
            $positionInfo["TrackURI"] = $vals[$index["RES"][0]]["value"];
        } else {
            $positionInfo["URI"] = "";
            $positionInfo["TrackURI"] = "";
        }

        if (isset($index["DC:CREATOR"]) and isset($vals[$index["DC:CREATOR"][0]]["value"])) {
            $positionInfo["artist"] = $vals[$index["DC:CREATOR"][0]]["value"];
        } else {
            $positionInfo["artist"] = "";
        }

        if (isset($index["DC:TITLE"]) and isset($vals[$index["DC:TITLE"][0]]["value"])) {
            $positionInfo["title"] = $vals[$index["DC:TITLE"][0]]["value"];
        } else {
            $positionInfo["title"] = "";
        }

        if (isset($index["UPNP:ALBUM"]) and isset($vals[$index["UPNP:ALBUM"][0]]["value"])) {
            $positionInfo["album"] = $vals[$index["UPNP:ALBUM"][0]]["value"];
        } else {
            $positionInfo["album"] = "";
        }

        if (isset($index["UPNP:ALBUMARTURI"]) and isset($vals[$index["UPNP:ALBUMARTURI"][0]]["value"])) {
            $positionInfo["albumArtURI"] = "http://" . $this->address . ":1400" . $vals[$index["UPNP:ALBUMARTURI"][0]]["value"];
        } else {

            /*
                           // Ask Radiotime (added br as a test)
                           $mi=$this->GetMediaInfo();

                           $station=preg_replace("#(.*)x-sonosapi-stream:(.*?)\?sid(.*)#is",'$2',$mi['CurrentURI']);
                        //   echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!".$station."########\n";
                           if (($station!="")and $station[0]=="s"){
                              $content = @file_get_contents("http://opml.radiotime.com/Describe.ashx?c=nowplaying&id=".$station."&partnerId=Sonos&serial=00-0E-58-25-41-12:4");
                           //   echo "----". $content;
                              $albumArtURI=preg_replace("#(.*)<LOGO>(.*?)\</LOGO>(.*)#is",'$2',$content);
                           //   echo $albumArtURI;
                              $positionInfo["albumArtURI"] = $albumArtURI;
                           } else{

            */
            $positionInfo["albumArtURI"] = "";
            /*               }
            */
        }

        if (isset($index["R:ALBUMARTIST"]) and isset($vals[$index["R:ALBUMARTIST"][0]]["value"])) {
            $positionInfo["albumArtist"] = $vals[$index["R:ALBUMARTIST"][0]]["value"];
        } else {
            $positionInfo["albumArtist"] = "";
        }

        if (isset($index["UPNP:ORIGINALTRACKNUMBER"]) and isset($vals[$index["UPNP:ORIGINALTRACKNUMBER"][0]]["value"])) {
            $positionInfo["albumTrackNumber"] = $vals[$index["UPNP:ORIGINALTRACKNUMBER"][0]]["value"];
        } else {
            $positionInfo["albumTrackNumber"] = "";
        }

        if (isset($index["R:STREAMCONTENT"]) and isset($vals[$index["R:STREAMCONTENT"][0]]["value"])) {
            $positionInfo["streamContent"] = $vals[$index["R:STREAMCONTENT"][0]]["value"];


        } else {
            $positionInfo["streamContent"] = "";
        }
        // added br if this contains "rincon" we are slave to a coordinator mentioned in this field (otherwise path to the file is provided)!
        // implemented via second XMLsendpacket to not break michaels current code

        if (isset($index["RES"][0]) and isset($vals[($index["RES"][0])]["value"])) {
            $positionInfo["trackURI"] = $vals[($index["RES"][0])]["value"];

        } else {
            $returnContent = $this->XMLsendPacket($content);

            $xmlParser = xml_parser_create("UTF-8");
            xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
            xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
            xml_parser_free($xmlParser);
        }

        if (isset($index["TRACKURI"][0]) and isset($vals[($index["TRACKURI"][0])]["value"])) {
            $positionInfo["trackURI"] = $vals[($index["TRACKURI"][0])]["value"];
            $positionInfo["TrackURI"] = $vals[($index["TRACKURI"][0])]["value"];
        } else {
            $positionInfo["trackURI"] = "";
        }

        // Track Number in Playlist
        $returnContent = $this->XMLsendPacket($content);

        $xmlParser = xml_parser_create("UTF-8");
        xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
        xml_parse_into_struct($xmlParser, $returnContent, $vals, $index);
        xml_parser_free($xmlParser);

        if (isset($index["TRACK"][0]) and isset($vals[($index["TRACK"][0])]["value"])) {
            $positionInfo["Track"] = $vals[($index["TRACK"][0])]["value"];

        } else {
            $positionInfo["Track"] = "";
        }
        #$positionInfo["TrackMetaData"] = htmlentities(substr($returnContentMeta, strpos($returnContentMeta, "DIDL-Lite") - 4, strripos($returnContentMeta, "DIDL-Lite") + 5));
        $positionInfo["TrackMetaData"] = substr($returnContentMeta, strpos($returnContentMeta, "DIDL-Lite") - 4, strripos($returnContentMeta, "DIDL-Lite") + 5);

        return $positionInfo;
    }


    /**
     * Play Radio station
     *
     * @param string $radio radio url
     * @param string $Name Name of station (optional)
     * @param string $id ID of Station (optional, default R:0/0/0)
     * @param string $parentID parentID (optional, default R:0/0)
     * @return array
     */

    #public function SetRadio($radio, $name, $id="R:0/0/0", $parentID="R:0/0")
    public function SetRadio($radio, $name, $id = "R:0/0/43", $parentID = "R:0/0")
    {
        $MetaData = "&lt;DIDL-Lite xmlns:dc=&quot;http://purl.org/dc/elements/1.1/&quot; xmlns:upnp=&quot;urn:schemas-upnp-org:metadata-1-0/upnp/&quot; xmlns:r=&quot;urn:schemas-rinconnetworks-com:metadata-1-0/&quot; xmlns=&quot;urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/&quot;&gt;&lt;item id=&quot;" . $id . "&quot; parentID=&quot;" . $parentID . "&quot; restricted=&quot;true&quot;&gt;&lt;dc:title&gt;" . $name . "&lt;/dc:title&gt;&lt;upnp:class&gt;object.item.audioItem.audioBroadcast&lt;/upnp:class&gt;&lt;desc id=&quot;cdudn&quot; nameSpace=&quot;urn:schemas-rinconnetworks-com:metadata-1-0/&quot;&gt;SA_RINCON65031_&lt;/desc&gt;&lt;/item&gt;&lt;/DIDL-Lite&gt;";

        $this->SetAVTransportURI($radio, $MetaData);

    }


    /**
     * Sets Av Transport URI
     *
     * @param string $tspuri Transport URI
     * @param string $MetaData (optional for MetaData)
     *
     * @return String
     */

    public function SetAVTransportURI($tspuri, $MetaData = "")
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (342 + strlen(htmlspecialchars($tspuri)) + strlen($MetaData)) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#SetAVTransportURI"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetAVTransportURI xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CurrentURI>' . htmlspecialchars($tspuri) . '</CurrentURI><CurrentURIMetaData>' . $MetaData . '.</CurrentURIMetaData></u:SetAVTransportURI></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Sets Queue
     *
     * @param string $queue transport URI or Queue
     * @param string $MetaData (optional for MetaData)
     *
     * @return Void
     */

    public function SetQueue($queue, $MetaData = "")
    {
        $this->SetAVTransportURI($queue, $MetaData);

    }

    /**
     * Clears devices Queue
     *
     * @return String
     */

    public function ClearQueue()
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: 290
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#RemoveAllTracksFromQueue"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:RemoveAllTracksFromQueue xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:RemoveAllTracksFromQueue></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Adds URI to Queue (not the Playlist!!)
     *
     * @param string $file Uri or Filename
     *
     * @return String
     */

    public function AddToQueue($file)
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (438 + strlen(htmlspecialchars($file))) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#AddURIToQueue"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:AddURIToQueue xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><EnqueuedURI>' . htmlspecialchars($file) . '</EnqueuedURI><EnqueuedURIMetaData></EnqueuedURIMetaData><DesiredFirstTrackNumberEnqueued>0</DesiredFirstTrackNumberEnqueued><EnqueueAsNext>1</EnqueueAsNext></u:AddURIToQueue></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }


    /**
     * Adds URI to Queue (not the Playlist!!)
     *
     * @param string $file Uri or Filename
     *
     * @return String
     */

    public function AddFavoritesToQueue($file, $meta)
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (438 + strlen(htmlspecialchars($file)) + strlen(htmlspecialchars($meta))) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#AddURIToQueue"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<s:Body><u:AddURIToQueue xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID>
<EnqueuedURI>' . htmlspecialchars($file) . '</EnqueuedURI>
<EnqueuedURIMetaData>' . htmlspecialchars($meta) . '</EnqueuedURIMetaData>
<DesiredFirstTrackNumberEnqueued>0</DesiredFirstTrackNumberEnqueued><EnqueueAsNext>1</EnqueueAsNext></u:AddURIToQueue></s:Body></s:Envelope>';
#var_dump(htmlspecialchars($meta));

        $this->sendPacket($content);
    }


    /**
     * Removes track from queue (not the Playlist!!)
     *
     * @param string $track Tracknumber/id to remove from current sonos queue (!)
     *
     * @return string
     */

    public function RemoveFromQueue($track)
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (307 + strlen($track)) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#RemoveTrackFromQueue"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:RemoveTrackFromQueue xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><ObjectID>Q:0/' . $track . '</ObjectID></u:RemoveTrackFromQueue></s:Body></s:Envelope>';

        $this->sendPacket($content);
    }

    /**
     * Jumps directly to the track
     *
     * @param string $track Number/ID of the track to play in queue
     *
     * @return string
     */

    public function SetTrack($track)
    {

        $content = 'POST /MediaRenderer/AVTransport/Control HTTP/1.1
CONNECTION: close
HOST: ' . $this->address . ':1400
CONTENT-LENGTH: ' . (288 + strlen($track)) . '
CONTENT-TYPE: text/xml; charset="utf-8"
SOAPACTION: "urn:schemas-upnp-org:service:AVTransport:1#Seek"

<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Unit>TRACK_NR</Unit><Target>' . $track . '</Target></u:Seek></s:Body></s:Envelope>';
        $this->sendPacket($content);
    }


    /**
     * Returns an array with the songs of the actual sonos queue
     *
     * @return String
     */

    public function GetCurrentPlaylist()
    {
        $header = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#Browse"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>Q:0</ObjectID><BrowseFlag>BrowseDirectChildren</BrowseFlag><Filter></Filter><StartingIndex>0</StartingIndex><RequestedCount>1000</RequestedCount><SortCriteria></SortCriteria></u:Browse>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);

        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xml = new SimpleXMLElement($returnContent);
        $liste = array();
        for ($i = 0, $size = count($xml); $i < $size; $i++) {
            $aktrow = $xml->item[$i];
            $albumart = $aktrow->xpath("upnp:albumArtURI");
            $title = $aktrow->xpath("dc:title");
            $artist = $aktrow->xpath("dc:creator");
            $album = $aktrow->xpath("upnp:album");
            $liste[$i]['listid'] = $i + 1;
            if (isset($albumart[0])) {
                $liste[$i]['albumArtURI'] = "http://" . $this->address . ":1400" . (string)$albumart[0];
            } else {
                $liste[$i]['albumArtURI'] = "";
            }
            $liste[$i]['title'] = (string)$title[0];
            if (isset($artist[0])) {
                $liste[$i]['artist'] = (string)$artist[0];
            } else {
                $liste[$i]['artist'] = "";
            }
            if (isset($album[0])) {
                $liste[$i]['album'] = (string)$album[0];
            } else {
                $liste[$i]['album'] = "";
            }
        }
        return $liste;
    }


    /**
     * Returns an array with all sonos playlists
     *
     * @return Array
     */

    public function GetSonosPlaylists()
    {
        $header = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#Browse"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>SQ:</ObjectID><BrowseFlag>BrowseDirectChildren</BrowseFlag><Filter></Filter><StartingIndex>0</StartingIndex><RequestedCount>100</RequestedCount><SortCriteria></SortCriteria></u:Browse>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);
        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xml = new SimpleXMLElement($returnContent);
        $liste = array();
        for ($i = 0, $size = count($xml); $i < $size; $i++) {
            $attr = $xml->container[$i]->attributes();
            $liste[$i]['id'] = (string)$attr['id'];
            $title = $xml->container[$i];
            $title = $title->xpath('dc:title');
            $liste[$i]['title'] = (string)$title[0];
            $liste[$i]['typ'] = "Sonos";
            $liste[$i]['file'] = urlencode((string)$xml->container[$i]->res);

        }


        return $liste;
    }


    /**
     * Returns an array with all imported PL
     *
     * @return Array
     */

    public function GetImportedPlaylists()
    {
        $header = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#Browse"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>A:PLAYLISTS</ObjectID><BrowseFlag>BrowseDirectChildren</BrowseFlag><Filter></Filter><StartingIndex>0</StartingIndex><RequestedCount>100</RequestedCount><SortCriteria></SortCriteria></u:Browse>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);
        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xml = new SimpleXMLElement($returnContent);
        $liste = array();
        for ($i = 0, $size = count($xml); $i < $size; $i++) {
            $attr = $xml->container[$i]->attributes();
            $liste[$i]['id'] = (string)$attr['id'];
            $title = $xml->container[$i];
            $title = $title->xpath('dc:title');
            // br substring use cuts my playlist names at the 4th char

            $liste[$i]['title'] = (string)$title[0];
            $liste[$i]['title'] = preg_replace("/^(.+)\.m3u$/i", "\\1", $liste[$i]['title']);
            $liste[$i]['typ'] = "Import";
            $liste[$i]['file'] = (string)$xml->container[$i]->res;
        }


        return $liste;
    }


    /**
     * Returns an array with all songs of a specific Playlist
     *
     * @param string $value Id of the playlist
     *
     * @return Array
     */

    public function GetPlaylist($value)
    {
        $header = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#Browse"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>' . $value . '</ObjectID><BrowseFlag>BrowseDirectChildren</BrowseFlag><Filter></Filter><StartingIndex>0</StartingIndex><RequestedCount>1000</RequestedCount><SortCriteria></SortCriteria></u:Browse>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);
        $xmlParser = xml_parser_create();
        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xml = new SimpleXMLElement($returnContent);
        $liste = array();
        for ($i = 0, $size = count($xml); $i < $size; $i++) {
            $aktrow = $xml->item[$i];
            $albumart = $aktrow->xpath("upnp:albumArtURI");
            $title = $aktrow->xpath("dc:title");
            $artist = $aktrow->xpath("dc:creator");
            $album = $aktrow->xpath("upnp:album");
            $liste[$i]['listid'] = $i + 1;
            if (isset($albumart[0])) {
                $liste[$i]['albumArtURI'] = "http://" . $this->address . ":1400" . (string)$albumart[0];
            } else {
                $liste[$i]['albumArtURI'] = "";
            }
            $liste[$i]['title'] = (string)$title[0];
            if (isset($interpret[0])) {
                $liste[$i]['artist'] = (string)$artist[0];
            } else {
                $liste[$i]['artist'] = "";
            }
            if (isset($album[0])) {
                $liste[$i]['album'] = (string)$album[0];
            } else {
                $liste[$i]['album'] = "";
            }
        }
        return $liste;
    }


    /**
     * Universal function to browse ContentDirectory
     *
     * @param string $value ObjectID
     * @param string $meta BrowseFlag
     * @param string $filter Filter
     * @param string $sindex SearchIndex
     * @param string $rcount ResultCount
     * @param string $sc SortCriteria
     *
     * @return Array
     */

    public function Browse($value, $meta = "BrowseDirectChildren", $filter = "", $sindex = "0", $rcount = "1000", $sc = "")
    {

        switch ($meta) {
            case 'BrowseDirectChildren':
            case 'c':
            case 'child':
                $meta = "BrowseDirectChildren";
                break;
            case 'BrowseMetadata':
            case 'm':
            case 'meta':
                $meta = "BrowseMetadata";
                break;
            default:
                return false;
        }
        $header = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#Browse"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>' . htmlspecialchars($value) . '</ObjectID><BrowseFlag>' . $meta . '</BrowseFlag><Filter>' . $filter . '</Filter><StartingIndex>' . $sindex . '</StartingIndex><RequestedCount>' . $rcount . '</RequestedCount><SortCriteria>' . $sc . '</SortCriteria></u:Browse>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);
        $xmlParser = xml_parser_create();
        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xml = new SimpleXMLElement($returnContent);
        $liste = array();
        for ($i = 0, $size = count($xml); $i < $size; $i++) {
            //Wenn Container vorhanden, dann ist es ein Browse Element
            //Wenn Item vorhanden, dann ist es ein Song.
            if (isset($xml->container[$i])) {
                $aktrow = $xml->container[$i];
                $attr = $xml->container[$i]->attributes();
                $liste[$i]['typ'] = "container";
            } else if (isset($xml->item[$i])) {
                //Item vorhanden also nur noch Musik
                $aktrow = $xml->item[$i];
                $attr = $xml->item[$i]->attributes();
                $liste[$i]['typ'] = "item";
            } else {
                //Fehler aufgetreten
                return;
            }
            $id = $attr['id'];
            $parentid = $attr['parentID'];
            $albumart = $aktrow->xpath("upnp:albumArtURI");
            $titel = $aktrow->xpath("dc:title");
            $interpret = $aktrow->xpath("dc:creator");
            $album = $aktrow->xpath("upnp:album");

            if (isset($aktrow->res)) {
                $res = (string)$aktrow->res;
                $liste[$i]['res'] = urlencode($res);

            } else {
                $liste[$i]['res'] = "leer";
            }
            $resattr = $aktrow->res->attributes();
            # if(isset($resattr['duration'])){
            #   $liste[$i]['duration']=(string)$resattr['duration'];
            #}else{
            #   $liste[$i]['duration']="leer";
            #}
            if (isset($albumart[0])) {
                $liste[$i]['albumArtURI'] = "http://" . $this->address . ":1400" . (string)$albumart[0];
            } else {
                $liste[$i]['albumArtURI'] = "leer";
            }
            $liste[$i]['title'] = (string)$titel[0];
            if (isset($interpret[0])) {
                $liste[$i]['artist'] = (string)$interpret[0];
            } else {
                $liste[$i]['artist'] = "leer";
            }
            if (isset($id) && !empty($id)) {
                $liste[$i]['id'] = urlencode((string)$id);
            } else {
                $liste[$i]['id'] = "leer";
            }
            if (isset($parentid) && !empty($parentid)) {
                $liste[$i]['parentid'] = urlencode((string)$parentid);
            } else {
                $liste[$i]['parentid'] = "leer";
            }
            if (isset($album[0])) {
                $liste[$i]['album'] = (string)$album[0];
            } else {
                $liste[$i]['album'] = "leer";
            }


        }
        return $liste;
    }


    /**
     * Universal function to browse ContentDirectory
     *
     * @param string $value ObjectID
     * @param string $meta BrowseFlag
     * @param string $filter Filter
     * @param string $sindex SearchIndex
     * @param string $rcount ResultCount
     * @param string $sc SortCriteria
     *
     * @return Array
     */

    public function GetSonosFavorites($value, $meta = "BrowseDirectChildren", $filter = "", $sindex = "0", $rcount = "1000", $sc = "")
    {

        switch ($meta) {
            case 'BrowseDirectChildren':
            case 'c':
            case 'child':
                $meta = "BrowseDirectChildren";
                break;
            case 'BrowseMetadata':
            case 'm':
            case 'meta':
                $meta = "BrowseMetadata";
                break;
            default:
                return false;
        }
        $header = 'POST /MediaServer/ContentDirectory/Control HTTP/1.1
SOAPACTION: "urn:schemas-upnp-org:service:ContentDirectory:1#Browse"
CONTENT-TYPE: text/xml; charset="utf-8"
HOST: ' . $this->address . ':1400';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:Browse xmlns:u="urn:schemas-upnp-org:service:ContentDirectory:1"><ObjectID>' . htmlspecialchars($value) . '</ObjectID><BrowseFlag>' . $meta . '</BrowseFlag><Filter>' . $filter . '</Filter><StartingIndex>' . $sindex . '</StartingIndex><RequestedCount>' . $rcount . '</RequestedCount><SortCriteria>' . $sc . '</SortCriteria></u:Browse>
</s:Body>
</s:Envelope>';
        $content = $header . '
Content-Length: ' . strlen($xml) . '

' . $xml;

        $returnContent = $this->sendPacket($content);


        $xmlParser = xml_parser_create();
        $returnContent = substr($returnContent, stripos($returnContent, '&lt;'));
        $returnContent = substr($returnContent, 0, strrpos($returnContent, '&gt;') + 4);
        $returnContent = str_replace(array("&lt;", "&gt;", "&quot;", "&amp;", "%3a", "%2f", "%25"), array("<", ">", "\"", "&", ":", "/", "%"), $returnContent);

        $xml = new SimpleXMLElement($returnContent);

        $liste = array();
        for ($i = 0, $size = count($xml); $i < $size; $i++) {

            //Wenn Container vorhanden, dann ist es ein Browse Element
            //Wenn Item vorhanden, dann ist es ein Song.
            if (isset($xml->container[$i])) {
                $aktrow = $xml->container[$i];
                $attr = $xml->container[$i]->attributes();
                $liste[$i]['typ'] = "container";
            } else if (isset($xml->item[$i])) {
                //Item vorhanden also nur noch Musik
                $aktrow = $xml->item[$i];
                $attr = $xml->item[$i]->attributes();
                $liste[$i]['typ'] = "item";
            } else {
                //Fehler aufgetreten
                return;
            }
            print_r($returnContent);


            $id = $attr['id'];
            $parentid = $attr['parentID'];
            $albumart = $aktrow->xpath("upnp:albumArtURI");
            $titel = $aktrow->xpath("dc:title");
            $interpret[0] = $aktrow->xpath("r:description");
            $album = $aktrow->xpath("upnp:album");
            if (isset($aktrow->res)) {
                $res = (string)$aktrow->res;
                #$liste[$i]['res'] = urlencode($res);
                $liste[$i]['res'] = ($res);

            } else {
                $liste[$i]['res'] = "leer";
            }
            $resattr = $aktrow->res->attributes();
            if (isset($resattr['duration'])) {
                $liste[$i]['duration'] = (string)$resattr['duration'];
            } else {
                $liste[$i]['duration'] = "leer";
            }
            if (isset($resattr['protocolInfo'])) {
                $liste[$i]['TypeOfAudio'] = (string)$resattr['protocolInfo'];
            } else {
                $liste[$i]['TypeOfAudio'] = "leer";
            }
            if (isset($resattr['//DIDL-Lite'])) {
                $liste[$i]['DIDL-Lite'] = (string)$resattr['//DIDL-Lite'];
            } else {
                $liste[$i]['DIDL-Lite'] = "leer";
            }
            if (isset($albumart[0])) {
                #$liste[$i]['albumArtURI']="http://" . $this->address . ":1400".(string)$albumart[0];
                $liste[$i]['albumArtURI'] = (string)$albumart[0];
            } else {
                $liste[$i]['albumArtURI'] = "leer";
            }
            $liste[$i]['title'] = (string)$titel[0];
            if (isset($interpret[0])) {
                $liste[$i]['artist'] = (string)$interpret[0][0];
            } else {
                $liste[$i]['artist'] = "leer";
            }
            if (isset($id) && !empty($id)) {
                #$liste[$i]['id']=urlencode((string)$id);
                $liste[$i]['id'] = (string)$id;
            } else {
                $liste[$i]['id'] = "leer";
            }
            if (isset($parentid) && !empty($parentid)) {
                #$liste[$i]['parentid']=urlencode((string)$parentid);
                $liste[$i]['parentid'] = (string)$parentid;
            } else {
                $liste[$i]['parentid'] = "leer";
            }
            if (isset($album[0])) {
                $liste[$i]['album'] = (string)$album[0];
            } else {
                $liste[$i]['album'] = "leer";
            }


        }
        return $liste;
    }


    /**
     * Get Now Playing information from Radiotime via opml
     *
     * @return Array
     */

    public function RadiotimeGetNowPlaying() // added br
    {
        $list["version"] = "";
        $list["status"] = "";
        $list["logo"] = "";

        // Serial for Tunein is our MAC - prevents block / throttling (maybe we should shift this off)
        $zoneinfo = $this->GetZoneInfo($this->address);
        $serial = $zoneinfo['MACAddress'];


        // Get mi
        $mi = $this->GetMediaInfo();
        // Filter out station id
        $station = preg_replace("#(.*)x-sonosapi-stream:(.*?)\?sid(.*)#is", '$2', $mi['CurrentURI']);


        // Only Ask Radiotime / Tunein for valid stationids (!!)
        if (($station != "") and $station[0] == "s") {
            // Ask with PHPSonos PartnerID and serial (mac)
            $content = @file_get_contents("http://opml.radiotime.com/Describe.ashx?c=nowplaying&id=" . $station . "&partnerId=IAeIhU42&serial=" . $serial);
            // DEBUG DEEP ONLY
            // echo "----". $content;
            $list["version"] = preg_replace('#(.*)version="(.*?)\">(.*)#is', '$2', $content);
            $list["status"] = preg_replace('#(.*)<status>(.*?)\</status>(.*)#is', '$2', $content);


            $list["outline"] = preg_replace('#(.*)<body>(.*)<outline type="text" text="(.*?)\" guide_id="(.*)\" key#is', '$2', $content);


            $list["logo"] = preg_replace('#(.*)<LOGO>(.*?)\</LOGO>(.*)#is', '$2', $content);
            // TAG_DEBUG_DEEP for Intune-Throttling (or blocking!)
            // echo "\n!!!!!!!!!!!!!!!!!INTUNE REQUEST EXECUTED!!!!!!!!!!!!!!\n";

        }
        return $list;
    }




    /***************************************************************************
     * Helper / sendPacket
     ***************************************************************************/

    /**
     * XMLsendPacket
     *
     * - <b>NOTE:</b> This function does send of a soap query and DOES NOT filter a xml answer
     * - <b>Returns:</b> Answer as XML
     *
     * @return Array
     */
    private function XMLsendPacket($content)
    {
        $fp = fsockopen($this->address, 1400 /* Port */, $errno, $errstr, 10);
        if (!$fp)
            throw new Exception("Error opening socket: " . $errstr . " (" . $errno . ")");

        fputs($fp, $content);
        $ret = "";
        $buffer = "";
        while (!feof($fp)) {
            $ret .= fgets($fp, 128);
        }

        fclose($fp);

        if (strpos($ret, "200 OK") === false)
            throw new Exception("Error sending command: " . $ret);

        $array = preg_split("/\r\n/", $ret);

        $result = "";
        if (strpos($ret, "TRANSFER-ENCODING: chunked") === false) {
            $result = $array[count($array) - 1];
        } else {
            $chunksStarted = false;
            $content = false;
            foreach ($array as $key => $value) {
                if ($value == "") {
                    $chunksStarted = true;
                    continue;
                }
                if ($chunksStarted === false)
                    continue;
                if ($content === false) {
                    if ($value === 0)
                        break;
                    $content = true;
                    continue;
                }
                $result = $result . $value;
                $content = false;
            }
        }

        return $result;
    }

    /**
     * sendPacket - communicate with the device
     *
     * - <b>NOTE:</b> This function does send of a soap query and may filter xml answers
     * - <b>Returns:</b> Answer
     *
     * @return Array
     */

    private function sendPacket($content)
    {
        $fp = fsockopen($this->address, 1400 /* Port */, $errno, $errstr, 10);
        if (!$fp)
            throw new Exception("Error opening socket: " . $errstr . " (" . $errno . ")");

        fputs($fp, $content);
        $ret = "";
        while (!feof($fp)) {
            $ret .= fgetss($fp, 128);
        }
        fclose($fp);

        if (strpos($ret, "200 OK") === false)
            throw new Exception("Error sending command: " . $ret);

        $array = preg_split("/\r\n/", $ret);

        $result = "";
        if (strpos($ret, "TRANSFER-ENCODING: chunked") === false) {
            $result = $array[count($array) - 1];
        } else {
            $chunksStarted = false;
            $content = false;
            foreach ($array as $key => $value) {
                if ($value == "") {
                    $chunksStarted = true;
                    continue;
                }
                if ($chunksStarted === false)
                    continue;
                if ($content === false) {
                    if ($value === 0)
                        break;
                    $content = true;
                    continue;
                }
                $result = $result . $value;
                $content = false;
            }
        }

        return $result;
    }
}

?>