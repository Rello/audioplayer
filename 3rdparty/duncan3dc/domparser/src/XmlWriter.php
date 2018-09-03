<?php

namespace duncan3dc\DomParser;

/**
 * Generate xml from arrays.
 */
class XmlWriter
{
    /**
     * @var DomDocument $dom The internal dom instance.
     */
    protected $dom;


    /**
     * Create an internal dom instance from the passed array structure.
     *
     * @param array $structure The array representation of the xml structure
     * @param string $encoding The encoding to declare in the <?xml tag
     */
    public function __construct(array $structure, $encoding = null)
    {
        if ($encoding === null) {
            $encoding = "utf-8";
        }
        $this->dom = new \DomDocument("1.0", $encoding);

        foreach ($structure as $key => $val) {
            $this->addElement($key, $val, $this->dom);
        }
    }


    /**
     * Get the internal dom instance.
     *
     * @return DomDocument
     */
    public function getDomDocument()
    {
        return $this->dom;
    }


    /**
     * Convert the internal dom instance to a string.
     *
     * @param boolean $format Whether to pretty format the string or not
     *
     * @return string
     */
    public function toString($format = null)
    {
        if ($format) {
            $this->dom->formatOutput = true;
        }
        return $this->dom->saveXML();
    }


    /**
     * Append an element recursively to a dom instance.
     *
     * @param string $name The name of the element to append
     * @param mixed $params The value to set the new element to, or the elements to append to it
     * @param mixed $parent The dom instance to append the element to
     *
     * @return DomElement
     */
    public function addElement($name, $params, $parent)
    {
        $name = preg_replace("/_[0-9]+$/", "", $name);

        $element = $this->dom->createElement($name);

        if (!is_array($params)) {
            $this->setElementValue($element, $params);
        } else {
            foreach ($params as $key => $val) {
                if ($key == "_attributes") {
                    foreach ($val as $k => $v) {
                        $element->setAttribute($k, $v);
                    }
                } elseif ($key == "_value") {
                    $this->setElementValue($element, $val);
                } else {
                    $this->addElement($key, $val, $element);
                }
            }
        }

        $parent->appendChild($element);

        return $element;
    }


    /**
     * Append an element on to the xml document with a simple value.
     *
     * @param mixed $element The dom instance to append the element to
     * @param mixed $value The value to set the new element to
     *
     * @return void
     */
    public function setElementValue($element, $value)
    {
        $node = $this->dom->createTextNode($value);
        $element->appendChild($node);
    }


    /**
     * Convert the passed array structure to an xml string.
     *
     * @param array $structure The array representation of the xml structure
     * @param string $encoding The encoding to declare in the <?xml tag
     *
     * @return string
     */
    public static function createXml($structure, $encoding = null)
    {
        $writer = new static($structure, $encoding);
        return trim($writer->toString());
    }


    /**
     * Convert the passed array structure to an xml string using pretty formatting.
     *
     * @param array $structure The array representation of the xml structure
     *
     * @return string
     */
    public static function formatXml(array $structure)
    {
        $writer = new static($structure);
        return trim($writer->toString(true));
    }
}
