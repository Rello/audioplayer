Changelog
=========

## x.y.z - UNRELEASED

--------

## 0.7.3 - 2017-04-07

### Fixed

* [Providers] Don't use a forward slash in the client name.

--------

## 0.7.2 - 2017-03-01

### Fixed

* [Providers] Use symfony/process within the Picotts provider for shell commands.

--------

## 0.7.1 - 2017-02-05

### Fixed

* [Providers] Fixed a bug with setLanguage() for the Picotts provider.

--------

## 0.7.0 - 2016-09-12

### Changed

* [TextToSpeech] Made generateFilename() public.
* [Providers] Picotts provider.
* [Support] Drop support for php5.5

--------

## 0.6.0 - 2015-08-29

### Changed

* [Google] Added the new "client" parameter
* [Providers] Acapela provider.

--------

## 0.5.1 - 2015-08-22

### Changed

* [Dependencies] Added PHPUnit to the dev dependencies.

--------

## 0.5.0 - 2015-06-13

### Changed

* [Dependencies] Use Guzzle 6.
* [Support] Drop support for php5.4

--------

## 0.2.0 - 2015-05-20

### Added

* [TextToSpeech] getFile() method to cache webservice calls.
* [TextToSpeech] save() method to store the audio on the local filesystem.

--------

## 0.1.0 - 2015-05-19

### Added

* [Providers] Google provider.
* [Providers] Voxygen provider.
* [Providers] Voice RSS provider.
