<?php

namespace duncan3dc\Speaker\Providers;

/**
 * Convert a string of a text to spoken word audio.
 */
class GoogleProvider extends AbstractProvider
{
    /**
     * @var string $language The language to use.
     */
    protected $language = "en";

    /**
     * Create a new instance.
     *
     * @param string $language The language to use.
     */
    public function __construct($language = null)
    {
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
        if (strlen($language) !== 2) {
            throw new \InvalidArgumentException("Unexpected language code ({$language}), codes should be 2 characters");
        }

        $this->language = $language;

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
        if (strlen($text) > 100) {
            throw new \InvalidArgumentException("Only messages under 100 characters are supported");
        }

        return $this->sendRequest("http://translate.google.com/translate_tts", [
            "q"         =>  $text,
            "tl"        =>  $this->language,
            "client"    =>  "duncan3dc-speaker",
        ]);
    }
}
