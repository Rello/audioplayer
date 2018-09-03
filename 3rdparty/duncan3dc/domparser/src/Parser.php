<?php

namespace duncan3dc\DomParser;

/**
 * Shared methods for the parsers.
 */
trait Parser
{
    /**
     * @var array $errors An array of errors that occurred during parsing.
     */
    public $errors = [];

    /**
     * Get the content for parsing.
     *
     * Creates an internal dom instance.
     *
     * @param string Can either be a url with an xml/html response or string containing xml/html
     *
     * @return string The xml/html either passed in or downloaded from the url
     */
    protected function getData($param)
    {
        if (substr($param, 0, 4) == "http") {
            $data = $this->getDataFromUrl($param);
        } else {
            $data = $param;
        }

        $method = "load" . strtoupper($this->mode);

        libxml_use_internal_errors(true);
        $this->dom->$method($data);
        $this->errors = libxml_get_errors();
        libxml_clear_errors();

        return $data;
    }


    /**
     * Download the content from a URL.
     *
     * @param string The URL to download
     *
     * @return string The xml/html downloaded from the url
     */
    protected function getDataFromUrl($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL             =>  $url,
            CURLOPT_RETURNTRANSFER  =>  true,
            CURLOPT_CONNECTTIMEOUT  =>  0,
            CURLOPT_TIMEOUT         =>  0,
            CURLOPT_FOLLOWLOCATION  =>  true,
            CURLOPT_USERAGENT       =>  "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:40.0) Gecko/20100101 Firefox/40.0",
        ]);

        $result = curl_exec($curl);

        $error = curl_error($curl);

        curl_close($curl);

        if ($result === false) {
            throw new \Exception($error);
        }

        return $result;
    }
}
