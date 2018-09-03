<?php

namespace duncan3dc\Speaker\Providers;

use duncan3dc\Speaker\Exception;
use duncan3dc\Speaker\Providers\AbstractProvider;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Convert a string of a text to a spoken word wav.
 */
class PicottsProvider extends AbstractProvider
{
    /**
     * @var string $pico The picotts program.
     */
    protected $pico;

    /**
     * @var string $language The language to use.
     */
    protected $language = "en-US";


    /**
     * Create a new instance.
     *
     * @param string $language The language to use
     */
    public function __construct($language = null)
    {
        $pico = trim(exec("which pico2wave"));
        if (!file_exists($pico)) {
            throw new Exception("Unable to find picotts program, please install pico2wave before trying again");
        }

        $this->pico = $pico;

        if ($language !== null) {
            $this->setLanguage($language);
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
        $language = trim($language);

        if (strlen($language) === 2) {
            $language = "{$language}-{$language}";
        }

        if (!preg_match("/^[a-z]{2}-[a-z]{2}$/i", $language)) {
            throw new \InvalidArgumentException("Unexpected language code ({$language}), codes should be 2 characters, a hyphen, and a further 2 characters");
        }

        list($main, $sub) = explode("-", $language);
        $this->language = strtolower($main) . "-" . strtoupper($sub);

        return $this;
    }


    /**
     * Get the format of this audio.
     *
     * @return string
     */
    public function getFormat()
    {
        return "wav";
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
        ];
    }


    /**
     * Convert the specified text to audio.
     *
     * @param string $text The text to convert
     *
     * @return string The audio data
     */
    public function textToSpeech($text, ProcessBuilder $process = null)
    {
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "speaker_picotts.wav";

        if ($process === null) {
            $process = new ProcessBuilder;
        }

        $process
            ->setPrefix($this->pico)
            ->add("--wave={$filename}")
            ->add("--lang={$this->language}")
            ->add($text)
            ->getProcess()
            ->run();

        if (!file_exists($filename)) {
            throw new Exception("TextToSpeech unable to create file: {$filename}");
        }

        $result = file_get_contents($filename);
        unlink($filename);

        return $result;
    }
}
