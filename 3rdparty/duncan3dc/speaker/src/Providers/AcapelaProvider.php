<?php

namespace duncan3dc\Speaker\Providers;

/**
 * Convert a string of a text to spoken word audio.
 */
class AcapelaProvider extends AbstractProvider
{
    /**
     * @var string $login Your acapela login.
     */
    protected $login = "";

    /**
     * @var string $application Your acapela application.
     */
    protected $application = "";

    /**
     * @var string $password Your acapela password.
     */
    protected $password = "";

    /**
     * @var string $voice The voice to use.
     */
    protected $voice = "rod";

    /**
     * @var int $speed The speech rate.
     */
    protected $speed = 180;


    /**
     * Create a new instance.
     *
     * @param string $voice The voice to use.
     */
    public function __construct($login, $application, $password, $voice = null, $speed = null)
    {
        $this->login = $login;
        $this->application = $application;
        $this->password = $password;

        if ($voice !== null) {
            $this->setVoice($voice);
        }

        if ($speed !== null) {
            $this->setSpeed($speed);
        }
    }


    /**
     * Set the voice to use.
     *
     * Visit http://www.acapela-vaas.com/ReleasedDocumentation/voices_list.php for available voices
     *
     * @param string $voice The voice to use (eg 'Graham')
     *
     * @return static
     */
    public function setVoice($voice)
    {
        $voice = trim($voice);
        if (strlen($voice) < 3) {
            throw new \InvalidArgumentException("Unexpected voice name ({$voice}), names should be at least 3 characters long");
        }

        $this->voice = strtolower($voice);

        return $this;
    }


    /**
     * Set the speech rate to use.
     *
     * @param int $speed The speech rate to use (between 60 and 360)
     *
     * @return static
     */
    public function setSpeed($speed)
    {
        $speed = (int) $speed;
        if ($speed < 60 || $speed > 360) {
            throw new \InvalidArgumentException("Invalid speed ({$speed}), must be a number between 60 and 360");
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
            "voice" =>  $this->voice,
            "speed" =>  $this->speed,
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
        if (strlen($text) > 300) {
            throw new \InvalidArgumentException("Only messages under 300 characters are supported");
        }

        return $this->sendRequest("http://vaas.acapela-group.com/Services/FileMaker.mp3", [
            "prot_vers" =>  2,
            "cl_login"  =>  $this->login,
            "cl_app"    =>  $this->application,
            "cl_pwd"    =>  $this->password,
            "req_voice" =>  "{$this->voice}22k",
            "req_spd"   =>  $this->speed,
            "req_text"  =>  $text,
        ]);
    }
}
