<?php

namespace duncan3dc\Speaker\Providers;

/**
 * Convert a string of a text to spoken word audio.
 */
class VoxygenProvider extends AbstractProvider
{
    /**
     * @var string $voice The voice to use.
     */
    protected $voice = "Bronwen";

    /**
     * Create a new instance.
     *
     * @param string $voice The voice to use.
     */
    public function __construct($voice = null)
    {
        if ($voice !== null) {
            $this->setVoice($voice);
        }
    }


    /**
     * Set the voice to use.
     *
     * Visit http://voxygen.fr/voices.json for available voices
     *
     * @param string $voice The voice to use (eg 'Elizabeth')
     *
     * @return static
     */
    public function setVoice($voice)
    {
        $voice = trim($voice);
        if (strlen($voice) < 2) {
            throw new \InvalidArgumentException("Unexpected voice name ({$voice}), names should be at least 2 characters long");
        }

        $this->voice = $voice;

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
            "voice" =>  $this->voice,
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
        return $this->sendRequest("http://www.voxygen.fr/sites/all/modules/voxygen_voices/assets/proxy/index.php", [
            "method"    =>  "redirect",
            "voice"     =>  $this->voice,
            "text"      =>  $text,
        ]);
    }
}
