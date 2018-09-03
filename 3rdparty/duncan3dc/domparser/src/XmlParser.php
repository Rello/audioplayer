<?php

namespace duncan3dc\DomParser;

/**
 * Parse xml.
 */
class XmlParser extends XmlBase
{
    use Parser;

    /**
     * @var string The xml string we are parsing.
     */
    public $xml;


    /**
     * Create a new parser.
     *
     * @param string Can either be a url with an xml response or string containing xml
     */
    public function __construct($param)
    {
        parent::__construct(new \DomDocument);

        $this->dom->preserveWhiteSpace = false;

        $this->xml = $this->getData($param);
    }
}
