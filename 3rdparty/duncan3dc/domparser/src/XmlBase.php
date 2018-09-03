<?php

namespace duncan3dc\DomParser;

class XmlBase extends Base
{
    public function __construct($dom)
    {
        parent::__construct($dom, "xml");
    }


    protected function newElement($element)
    {
        return new XmlElement($element);
    }


    public function getTagsNS($ns, $tagName)
    {
        $elements = [];

        $list = $this->dom->getElementsByTagNameNS($ns, $tagName);
        foreach ($list as $element) {
            $elements[] = $this->newElement($element);
        }

        return $elements;
    }


    public function getElementsByTagNameNS($ns, $tagName)
    {
        return $this->getTagsNS($ns, $tagName);
    }


    public function getTagNS($ns, $tagName, $key = 0)
    {
        $elements = $this->dom->getElementsByTagNameNS($ns, $tagName);

        if (!$element = $elements->item($key)) {
            return false;
        }

        return $this->newElement($element);
    }


    public function getElementByTagNameNS($ns, $tagName, $key = 0)
    {
        return $this->getTagNS($ns, $tagName, $key);
    }
}
