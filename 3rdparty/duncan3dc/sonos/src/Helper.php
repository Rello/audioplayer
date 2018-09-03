<?php

namespace duncan3dc\Sonos;

use duncan3dc\DomParser\XmlWriter;

/**
 * Provides helper functions for the classes.
 */
class Helper
{
    const TRACK_HASH = "00032020";
    const ALBUM_HASH = "0004206c";

    /**
     * Create a mode array from the mode text value.
     *
     * @param string $mode
     *
     * @return array Mode data containing the following boolean elements (shuffle, repeat)
     */
    public static function getMode($mode)
    {
        $options = [
            "shuffle"   =>  false,
            "repeat"    =>  false,
        ];

        if (in_array($mode, ["REPEAT_ALL", "SHUFFLE"])) {
            $options["repeat"] = true;
        }
        if (in_array($mode, ["SHUFFLE_NOREPEAT", "SHUFFLE"])) {
            $options["shuffle"] = true;
        }

        return $options;
    }


    /**
     * Create a mode string from a mode array.
     *
     * @param array $options An array with 2 boolean elements (shuffle and repeat)
     *
     * @return string
     */
    public static function setMode(array $options)
    {
        if ($options["shuffle"]) {
            if (!$options["repeat"]) {
                return "SHUFFLE_NOREPEAT";
            }
            return "SHUFFLE";
        }

        if ($options["repeat"]) {
            return "REPEAT_ALL";
        }

        return "NORMAL";
    }


    /**
     * Create the xml metadata required by Sonos.
     *
     * @param string $id The ID of the track
     * @param string $parent The ID of the parent
     * @param array $extra An xml array of extra attributes for this item
     *
     * @return string
     */
    public static function createMetaDataXml($id, $parent = "-1", array $extra = [], $service = null)
    {
        if ($service !== null) {
            $extra["desc"] = [
                "_attributes"   =>  [
                    "id"        =>  "cdudn",
                    "nameSpace" =>  "urn:schemas-rinconnetworks-com:metadata-1-0/",
                ],
                "_value"        =>  "SA_RINCON{$service}_X_#Svc{$service}-0-Token",
            ];
        }

        $xml = XmlWriter::createXml([
            "DIDL-Lite" =>  [
                "_attributes"   =>  [
                    "xmlns:dc"      =>  "http://purl.org/dc/elements/1.1/",
                    "xmlns:upnp"    =>  "urn:schemas-upnp-org:metadata-1-0/upnp/",
                    "xmlns:r"       =>  "urn:schemas-rinconnetworks-com:metadata-1-0/",
                    "xmlns"         =>  "urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/",
                ],
                "item"  =>  array_merge([
                    "_attributes"   =>  [
                        "id"            =>  $id,
                        "parentID"      =>  $parent,
                        "restricted"    =>  "true",
                    ],
                ], $extra),
            ],
        ]);

        # Get rid of the xml header as only the DIDL-Lite element is required
        $metadata = explode("\n", $xml)[1];

        return $metadata;
    }
}
