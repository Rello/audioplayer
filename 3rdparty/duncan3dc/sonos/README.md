sonos
=====

A PHP library for interacting with [Sonos](http://www.sonos.com/) speakers.  

Full documentation is available at http://duncan3dc.github.io/sonos/  
PHPDoc API documentation is also available at [http://duncan3dc.github.io/sonos/api/](http://duncan3dc.github.io/sonos/api/namespaces/duncan3dc.Sonos.html)  

[![Latest Version](https://img.shields.io/packagist/v/duncan3dc/sonos.svg)](https://packagist.org/packages/duncan3dc/sonos)
[![Build Status](https://travis-ci.org/duncan3dc/sonos.svg?branch=master)](https://travis-ci.org/duncan3dc/sonos)
[![Coverage Status](https://coveralls.io/repos/github/duncan3dc/sonos/badge.svg)](https://coveralls.io/github/duncan3dc/sonos)


Quick Examples
--------------

Start all groups playing music
```php
$sonos = new \duncan3dc\Sonos\Network;
$controllers = $sonos->getControllers();
foreach ($controllers as $controller) {
    echo $controller->name . " (" . $controller->room . ")\n";
    echo "\tState: " . $controller->getState() . "\n";
    $controller->play();
}
```

Add all the tracks from one playlist to another
```php
$sonos = new \duncan3dc\Sonos\Network;
$protest = $sonos->getPlaylistByName("protest the hero");
$progmetal = $sonos->getPlaylistByName("progmetal");

foreach ($protest->getTracks() as $track) {
    $progmetal->addTracks($track["uri"]);
}
```

_Read more at http://duncan3dc.github.io/sonos/_  


Changelog
---------
A [Changelog](CHANGELOG.md) has been available since version 0.8.8


Where to get help
-----------------
Found a bug? Got a question? Just not sure how something works?  
Please [create an issue](//github.com/duncan3dc/sonos/issues) and I'll do my best to help out.  
Alternatively you can catch me on [Twitter](https://twitter.com/duncan3dc)
