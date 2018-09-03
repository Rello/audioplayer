<?php

namespace duncan3dc\Speaker\Providers;

use duncan3dc\Speaker\Exception;
use GuzzleHttp\Client;

/**
 * Convert a string of a text to spoken word audio.
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var Client $client A guzzle instance for http requests.
     */
    protected $client;

    /**
     * Get the guzzle client instance to use.
     *
     * @param Client $client
     *
     * @return static
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }


    /**
     * Get the guzzle client.
     *
     * @return Client
     */
    public function getClient()
    {
        if ($this->client === null) {
            $this->client = new Client;
        }

        return $this->client;
    }


    /**
     * Get the format of this audio.
     *
     * @return string
     */
    public function getFormat()
    {
        return "mp3";
    }


    /**
     * Get the current options.
     *
     * This is used in caching to determine if we have sent a request
     * with these options before and can use the previous result.
     *
     * @return array
     */
    public function getOptions()
    {
        return [];
    }


    /**
     * Send a http request.
     *
     * @param string $hostname The hostname to send the request to
     * @param string[] $params The parameters of the request
     *
     * @return string The response body
     */
    protected function sendRequest($hostname, array $params)
    {
        $url = $hostname . "?" . http_build_query($params);

        $response = $this->getClient()->get($url);

        if ($response->getStatusCode() != "200") {
            throw new Exception("Failed to call the external text-to-speech service");
        }

        return $response->getBody();
    }
}
