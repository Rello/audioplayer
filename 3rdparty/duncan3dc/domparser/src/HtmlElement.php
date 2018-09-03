<?php

namespace duncan3dc\DomParser;

/**
 * Represents an html element.
 */
class HtmlElement extends HtmlBase
{
    use Element;

    /**
     * Check if this element has the specified class applied to it.
     *
     * @param string $className The name of the class to check for (case-sensitive)
     *
     * @return bool
     */
    public function hasClass($className)
    {
        if (!$class = $this->dom->getAttribute("class")) {
            return false;
        }

        $classes = explode(" ", $class);

        return in_array($className, $classes, true);
    }
}
