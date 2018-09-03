<?php

namespace duncan3dc\DomParser;

trait Element
{
    public function __toString()
    {
        return $this->nodeValue;
    }


    public function __get($key)
    {
        $value = $this->dom->$key;

        switch ($key) {

            case "parentNode":
                return $this->newElement($value);

            case "childNodes":
                $elements = [];
                if ($value !== null) {
                    foreach ($value as $element) {
                        $elements[] = $this->newElement($element);
                    }
                }
                return $elements;

            case "nodeValue":
                return trim($value);
        }

        return $value;
    }


    public function nodeValue($value)
    {
        $this->dom->nodeValue = "";

        $node = $this->dom->ownerDocument->createTextNode($value);

        $this->dom->appendChild($node);

        return $this;
    }


    public function hasAttribute($name)
    {
        return $this->dom->hasAttribute($name);
    }


    public function getAttribute($name)
    {
        return $this->dom->getAttribute($name);
    }


    public function setAttribute($name, $value)
    {
        $this->dom->setAttribute($name, $value);

        return $this;
    }


    public function getAttributes()
    {
        $attributes = [];
        foreach ($this->dom->attributes as $attr) {
            $attributes[$attr->name] = $attr->value;
        }
        return $attributes;
    }


    public function removeChild(Base $element)
    {
        $this->dom->removeChild($element->dom);

        return $this;
    }
}
