<?php

namespace duncan3dc\DomParser;

class HtmlBase extends Base
{
    public function __construct($dom)
    {
        parent::__construct($dom, "html");
    }


    protected function newElement($element)
    {
        return new HtmlElement($element);
    }


    public function getElementById($id)
    {
        if (!$element = $this->dom->getElementById($id)) {
            return false;
        }

        return $this->newElement($element);
    }


    public function getElementsByClassName($className, $limit = 0)
    {
        if (!is_array($className)) {
            $className = [$className];
        }

        $return = [];

        $elements = $this->dom->getElementsByTagName("*");
        foreach ($elements as $node) {
            if (!$node->hasAttributes()) {
                continue;
            }

            if (!$check = $node->attributes->getNamedItem("class")) {
                continue;
            }

            $classes = explode(" ", $check->nodeValue);
            $found = true;
            foreach ($className as $val) {
                if (!in_array($val, $classes)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                $return[] = $this->newElement($node);
                if ($limit) {
                    if (!--$limit) {
                        break;
                    }
                }
            }
        }

        return $return;
    }


    public function getElementByClassName($className, $key = 0)
    {
        $elements = $this->getElementsByClassName($className, $key + 1);

        return isset($elements[$key]) ? $elements[$key] : null;
    }


    public function parseForm()
    {
        $url = $this->generateUrlFromFormElements();

        parse_str($url, $data);

        return $data;
    }


    private function generateUrlFromFormElements()
    {
        if (!is_array($this->childNodes)) {
            return "";
        }

        $url = "";

        foreach ($this->childNodes as $node) {

            # Recurse
            if (!in_array($node->nodeName, ["input", "select", "textarea"], true)) {
                $url .= $node->generateUrlFromFormElements();
                continue;
            }

            # Get the element's name, ignore if it doesn't have one
            $name = $node->getAttribute("name");
            if (!$name) {
                continue;
            }

            # Custom handling for the currently selected option, or default the first one
            if ($node->nodeName === "select") {
                $options = $node->getTags("option");

                if (count($options) < 1) {
                    continue;
                }

                $found = false;
                foreach ($options as $tag) {
                    if ($tag->hasAttribute("selected")) {
                        $found = true;
                        $value = $tag->getAttribute("value");
                        break;
                    }
                }
                if (!$found) {
                    $value = $options[0]->getAttribute("value");
                }

            # Text area's value is just within the opening and closing tags
            } elseif ($node->nodeName === "textarea") {
                $value = $node->nodeValue;

            # For all other elements assume their value attribute contains the relevant value
            } else {
                $value = $node->getAttribute("value");
            }

            # Don't send any checkboxes/radio elements that aren't checked
            if (in_array($node->getAttribute("type"), ["checkbox", "radio"], true)) {
                if (!$node->hasAttribute("checked")) {
                    continue;
                }
            }

            $url .= "&{$name}={$value}";
        }

        return $url;
    }
}
