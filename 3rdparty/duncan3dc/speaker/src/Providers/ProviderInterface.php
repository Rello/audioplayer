<?php

namespace duncan3dc\Speaker\Providers;

/**
 * Convert a string of a text to spoken word audio.
 */
interface ProviderInterface
{
    /**
     * Get the format of this audio.
     *
     * @return string (mp3/wav)
     */
    public function getFormat();

    /**
     * Get the current options.
     *
     * This is used in caching to determine if we have sent a request
     * with these options before and can use the previous result.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Convert the specified text to audio.
     *
     * @param string $text The text to convert
     *
     * @return string The audio data
     */
    public function textToSpeech($text);
}
