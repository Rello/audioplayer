<?php

namespace duncan3dc\DomParser;

abstract class Base
{
    public $dom;
    public $mode;

    abstract protected function newElement($element);

    public function __construct($dom, $mode)
    {
        $this->dom = $dom;
        $this->mode = $mode;
    }


    public function getTags($tagName)
    {
        $elements = [];

        $list = $this->dom->getElementsByTagName($tagName);
        foreach ($list as $element) {
            $elements[] = $this->newElement($element);
        }

        return $elements;
    }


    public function getElementsByTagName($tagName)
    {
        return $this->getTags($tagName);
    }


    public function getTag($tagName, $key = 0)
    {
        $elements = $this->dom->getElementsByTagName($tagName);

        if (!$element = $elements->item($key)) {
            return false;
        }

        return $this->newElement($element);
    }


    public function getElementByTagName($tagName, $key = 0)
    {
        return $this->getTag($tagName, $key);
    }


    public function output()
    {
        if (!$doc = $this->dom->ownerDocument) {
            $doc = $this->dom;
        }

        $doc->formatOutput = true;

        $method = "save" . strtoupper($this->mode);

        return $doc->$method($this->dom);
    }


    public function xpath($path)
    {
        $xpath = new \DomXPath($this->dom);

        $list = $xpath->query($path);

        $return = [];
        foreach ($list as $node) {
            $return[] = $this->newElement($node);
        }

        return $return;
    }
}
