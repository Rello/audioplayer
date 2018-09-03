<?php

namespace duncan3dc\Speaker\Providers;

use duncan3dc\Speaker\Exception;

/**
 * Convert a string of a text to spoken word audio.
 */
class VoiceRssProvider extends AbstractProvider
{
    /**
     * @var string $language The language to use.
     */
    protected $language = "en-gb";

    /**
     * @var int $speed The speech rate.
     */
    protected $speed = 0;

    /**
     * Create a new instance.
     *
     * @param string $api Your Voice RSS API key.
     * @param string $language The language to use.
     * @param string $speed The speech rate to use.
     */
    public function __construct($apikey, $language = null, $speed = null)
    {
        $this->apikey = $apikey;

        if ($language !== null) {
            $this->setLanguage($language);
        }

        if ($speed !== null) {
            $this->setSpeed($speed);
        }
    }


    /**
     * Set the language to use.
     *
     * @param string $language The language to use (eg 'en')
     *
     * @return static
     */
    public function setLanguage($language)
    {
        $language = strtolower(trim($language));

        if (strlen($language) === 2) {
            $language = "{$language}-{$language}";
        }

        if (!preg_match("/^[a-z]{2}-[a-z]{2}$/", $language)) {
            throw new \InvalidArgumentException("Unexpected language code ({$language}), codes should be 2 characters, a hyphen, and a further 2 characters");
        }

        $this->language = $language;

        return $this;
    }


    /**
     * Set the speech rate to use.
     *
     * @param int $speed The speech rate to use (between -10 and 10)
     *
     * @return static
     */
    public function setSpeed($speed)
    {
        $speed = (int) $speed;
        if ($speed < -10 || $speed > 10) {
            throw new \InvalidArgumentException("Invalid speed ({$speed}), must be a number between -10 and 10");
        }

        $this->speed = $speed;

        return $this;
    }


    /**
     * Get the current options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            "language"  =>  $this->language,
            "speed"     =>  $this->speed,
        ];
    }


    /**
     * Convert the specified text to audio.
     *
     * @param string $text The text to convert
     *
     * @return string The audio data
     */
    public function textToSpeech($text)
    {
        $result = $this->sendRequest("https://api.voicerss.org/", [
            "key"   =>  $this->apikey,
            "src"   =>  $text,
            "hl"    =>  $this->language,
            "r"     =>  $this->speed,
            "c"     =>  "MP3",
            "f"     =>  "16khz_16bit_stereo",
        ]);

        if (substr($result, 0, 6) === "ERROR:") {
            throw new Exception("TextToSpeech {$result}");
        }

        return $result;
    }
}
