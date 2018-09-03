domparser
=========

Wrappers for the PHP DOMDocument class to provide extra functionality for html/xml parsing

[![Build Status](https://travis-ci.org/duncan3dc/domparser.svg?branch=master)](https://travis-ci.org/duncan3dc/domparser)
[![Latest Stable Version](https://poser.pugx.org/duncan3dc/domparser/version.svg)](https://packagist.org/packages/duncan3dc/domparser)



Constructor Arguments
---------------------
There are 2 constructors available, one for the HtmlParser class and one for the XmlParser class, they both work in the same way.
* Only 1 parameter is available, which should be passed as a string, and contain either a url, or the content to parse (xml/html)
* If the string begins with the text http then it will be treated as a url (this will work for https too).
* Warnings are captured during the loading of the content using libxml_use_internal_errors() and libxml_get_errors(), these are available from the errors property after the class has been initiated


Public Properties
-----------------
* html/xml (string) - Depending on which class was used one of these properties will be present and contain the content used for parsing
* errors (array) - If any errors were encountered during parsing they will be in this array (see [Constructor Arguments])
* dom (DOMDocument) - This is the internal instance of the DOMDocument class used
* mode (string) - Indicates whether the parser is operating in html or xml mode


Public Methods
--------------
* getTags(string $tagName): array - Similar to DOMDocument::getElementsByTagName() except a standard array is returned.  
Alias: getElementsByTagName()
* getTag(string $tagName): Element - Similar to getTags() but will return the first element matched, instead of an array of elements.  
Alias: getElementByTagName()
* getElementsByClassName(mixed $className): array - Matches elements that have a class attribute that matches the string parameter.  
If you want to find elements with multiple classes, pass the $className as an array of classes, and only elements that have all classes will be returned
* getElementByClassName(mixed $className): Element - Similar to getElementsByClassName() except this will return the first element matched
* output(): string - Returns the xml/html repesented by the receiver, formatted using DOMDocument::formatOutput
* xpath(string $path): array - Returns an array of elements matching the $path


Dom Elements
------------
All of the methods that return elements return them as instances of the Element class, this acts similarly to the standard DOMElement class, except it has all of the above methods available, and the nodeValue property has leading and trailing whitespace removed using trim()


Examples
--------

The parser classes use a namespace of duncan3dc\DomParser
```php
use duncan3dc\DomParser\HtmlParser;
use duncan3dc\DomParser\XmlParser;
```

-------------------

```php
$parser = new HtmlParser("http://example.com");

echo "Page Title: " . $parser->getTag("title")->nodeValue . "\n";

$contentType = false;
$meta = $parser->getTags("meta");
foreach($meta as $element) {
	if($element->getAttribute("http-equiv") == "Content-type") {
		$contentType = $element->getAttribute("content");
	}
}
echo "Content Type: " . (($contentType) ?: "NOT FOUND") . "\n";
```
