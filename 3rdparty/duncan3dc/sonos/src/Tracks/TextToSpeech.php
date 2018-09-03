<?php

namespace duncan3dc\Sonos\Tracks;

use duncan3dc\Sonos\Directory;
use duncan3dc\Sonos\Helper;
use duncan3dc\Speaker\Providers\GoogleProvider;
use duncan3dc\Speaker\Providers\ProviderInterface;
use duncan3dc\Speaker\TextToSpeech as TextToSpeechHandler;

/**
 * Convert a string of a text to spoken word audio.
 */
class TextToSpeech implements UriInterface
{
    /**
     * @var Directory $directory The directory to store the audio file in.
     */
    protected $directory;

    /**
     * @var string $text The text to convert.
     */
    protected $text;

    /**
     * @var Provider $provider The text to speech provider.
     */
    protected $provider;

    /**
     * Create a TextToSpeech object.
     *
     * @param string $text The text to convert
     * @param Directory $directory The directory to store the audio file in
     */
    public function __construct($text, Directory $directory, ProviderInterface $provider = null)
    {
        $this->directory = $directory;
        $this->text = $text;

        if ($provider !== null) {
            $this->setProvider($provider);
        }
    }


    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;

        return $this;
    }


    public function getProvider()
    {
        if ($this->provider === null) {
            $this->provider = new GoogleProvider;
        }

        return $this->provider;
    }


    /**
     * Set the language to use in the google text-to-speech call.
     *
     * @param string $language The language to use (eg 'en')
     *
     * @return static
     */
    public function setLanguage($language)
    {
        $this->getProvider()->setLanguage($language);

        return $this;
    }


    /**
     * Get the URI for this message.
     *
     * If it doesn't already exist on the filesystem then the text-to-speech handler will be called.
     *
     * @return string
     */
    public function getUri()
    {
        $provider = $this->getProvider();
        $tts = new TextToSpeechHandler($this->text, $provider);

        $filename = $tts->generateFilename();

        if (!$this->directory->has($filename)) {
            $data = $tts->getAudioData();
            $this->directory->write($filename, $data);
        }

        return "x-file-cifs://" . $this->directory->getSharePath() . "/{$filename}";
    }


    /**
     * Get the metadata xml for this message.
     *
     * @return string
     */
    public function getMetaData()
    {
        return Helper::createMetaDataXml("-1", "-1", [
            "res"               =>  $this->getUri(),
            "upnp:albumArtURI"  =>  "",
            "dc:title"          =>  $this->text,
            "upnp:class"        =>  "object.item.audioItem.musicTrack",
            "dc:creator"        =>  "Google",
            "upnp:album"        =>  "Text To Speech",
        ]);
    }
}
