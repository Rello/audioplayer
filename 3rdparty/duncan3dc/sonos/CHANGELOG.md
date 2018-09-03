Changelog
=========

## x.y.z - UNRELEASED

--------

## 1.9.11 - 2017-11-25

### Added

* [Support] Loosen the requirement on psr/log.

--------

## 1.9.10 - 2017-11-03

### Added

* [Network] Added support for the new ONE devices.

--------

## 1.9.9 - 2017-09-29

### Added

* [Network] Added support for the PLAYBASE devices.
* [Support] Added support for PHP 7.1

### Removed

* [Support] Dropped support for HHVM

--------

## 1.9.8 - 2017-02-22

### Fixed

* [Tracks] Added a GoogleUnlimited track to support Google unlimited tracks.

--------

## 1.9.7 - 2017-02-19

### Fixed

* [Controllers] Ensure PlayBar streaming continues after using interrupt().

--------

## 1.9.6 - 2017-02-12

### Fixed

* [Tracks] Allow text-to-speech messages longer than 100 characters.

--------

## 1.9.5 - 2017-01-19

### Added

* [Logging] Soap request and responses are now logged under the Debug level.
* [Support] Added support for PHP 7.1

--------

## 1.9.4 - 2016-12-31

### Fixed

* [Network] Added support for the new version of the PLAY:1.

--------

## 1.9.3 - 2016-10-04

### Fixed

* [Streams] Ensure the title is picked up when available.
* [Queues] Prevent inifite loop when the start position is invalid.

--------

## 1.9.2 - 2016-09-12

### Added

* [Controller] Allow the Network instance in use to be retrieved using getNetwork().

### Fixed

* [Tracks] Fix the caching of text-to-speech files.

--------

## 1.9.1 - 2016-03-13

### Added

* [Network] Add support for the ZP100 device.

--------

## 1.9.0 - 2016-03-12

### Added

* [Controller] Allow the Line-In to be controlled.

### Changed

* [Controller] The isStreaming() method now returns true when streaming from Line-In.

--------

## 1.8.0 - 2016-01-10

### Added

* [Network] Allow the network interface to be specified using Network::setNetworkInterface().

### Changed

* [Network] Correct the cache lookup to only use cache from the same network interface and multicast address.

--------

## 1.7.4 - 2015-12-03

### Fixed

* [Controllers] The getStateDetails() method can now handle Line-In streams and return a valid State instance.

--------

## 1.7.3 - 2015-11-19

### Fixed

* [Radio] Correct the constants used for retrieving favourites.
* [Alarms] Fix HHVM handling of days (array constants not valid).

--------

## 1.7.2 - 2015-10-18

### Fixed

* [Alarms] Correct the handling of days (Sunday is zero and the rest were off by one).

--------

## 1.7.1 - 2015-10-16

### Fixed

* [Playlists] Correct the adding of tracks that was broken in 1.5.0.

--------

## 1.7.0 - 2015-09-19

### Added

* [Tracks] Created a Google track to handle their specific metadata.
* [Tracks] Allow the Spotify region to be overridden.

### Fixed

* [Tracks] Prevent other services being incorrectly treated as Deezer tracks.

### Changed

* [Support] Drop support for PHP 5.5, as it nears end-of-life and constant expressions require 5.6

--------

## 1.6.1 - 2015-09-16

### Fixed

* [Playlist] Ensure the TrackFactory is available when working with playlists.

--------

## 1.6.0 - 2015-09-09

### Added

* [Tracks] Created Spotify/Deezer tracks to handle their specific metadata.

### Fixed

* [Tracks] The album art now only prepends a host if it is missing one

### Removed

* [Tracks] The QueueTrack has been merged with the Track class.

--------

## 1.5.1 - 2015-09-08

### Added

* [Network] Add support for the ZP80 ZonePlayer device.

--------

## 1.5.0 - 2015-08-29

### Changed

* [Tracks] Use league/flysystem to allow access to SMB shares from other machines.
* [Queues] Improve efficiency of adding tracks by adding up to 16 tracks at once.

--------

## 1.4.2 - 2015-08-16

### Changed

* [Network] Improve the topology caching as these change fairly frequently.

--------

## 1.4.0 - 2015-06-15

### Added

* [Streams] Allow the name/title of a stream to be retrieved.

### Fixed

* [Spotify] Enable metadata (artist, album, etc) to display correctly in some cases.

### Changed

* [Network] Cache the device descriptions and topology (these rarely change so the performance improvement is preferable).
* [Support] Drop support for PHP 5.4, as it nears end-of-life and Guzzle 6 requires 5.5

--------

## 1.3.1 - 2015-05-30

### Added

* [Network] Add methods for getting radio station/show information.

--------

## 1.3.0 - 2015-05-29

### Added

* [Tracks] Created a Radio class.

### Changed

* [Tracks] Use duncan3dc/speaker for text-to-speech handling

### Fixed

* [Tracks] Correct the handling of queueid to avoid metadata loss.
* [Controllers] Only seek if we have some tracks in the queue.

--------

## 1.2.0 - 2015-04-29

### Added

* [Network] Added support for the PLAYBAR and CONNECT devices (treated as the same as PLAY:1, PLAY:3, etc).
* [Tracks] Created a Directory class to handle SMB music library shares.
* [Tracks] Created a TextToSpeech class.
* [Controllers] Added a method to interrupt playback with a single track.
* [Controllers] Created selectTrack() and seek() methods.
* [Controllers] Allowed state to be exported and restored.
* [Controllers] Added methods to check if a controller is streaming or using a queue.
* [Speakers] Added speaker LED functionality to turn on and off, and check status.
* [Speakers] Added equaliser functionality (treble, bass, loudness).

### Fixed
* [Queues] Detect and throw an understandable error when an empty queue is attempted to be played.

--------

## 1.1.0 - 2015-02-27

### Added

* [Alarms] Allow the room/speaker of an alarm to be get and set.
* [Logging] Allow a PSR-3 compatible logger to be passed for logging support.

### Fixed
* [Network] Ignore any non Sonos devices from the discovery.
* [Network] Ignore any Sonos devices that are not speakers (bridges, etc).

--------

## 1.0.6 - 2015-01-30

### Fixed

* [Network] Return null from getControllerByRoom() if there are no speakers found for that room.

--------

## 1.0.5 - 2015-01-18

### Added

* [Dependencies] Bumped the doctrine/cache requirement to ~1.4.0

--------

## 1.0.4 - 2015-01-17

### Added

* [Playlists] Created a moveTrack() method to re-order playlist tracks.
* [Playlists] Created a hasPlaylist() method on the Network class to check if a playlist exists.

--------

## 1.0.3 - 2015-01-06

### Fixed

* [Network] Clear the internal cache of how speakers are grouped when one is removed/added.

--------

## 1.0.2 - 2015-01-05

### Fixed

* [Network] If no devices are found on the network the result is no longer cached.

--------

## 1.0.1 - 2014-12-30

### Added

* [Alarms] Allow alarm information to be read, and managed using the Alarm class
* [Controllers] Added support for Crossfade.

### Changed

* [Network] The Network class is no longer static, it should be instantiated before calling its methods.
* [Network] The cache handling is now provided by doctrine/cache
* [Controllers] The getStateDetails() method now returns an instance of the State class.
* [Playlists] Creating playlists is now done using the createPlaylist() method on the Network class.
* [Queues/Playlists] Adding individual tracks is now doing using addTrack(), and addTracks() only supports arrays.
* [Queues/Playlists] The getTracks() method now returns an array of QueueTrack instances.

--------

## 0.8.8 - 2014-12-03

### Added

* [Docs] Created a changelog!

### Changed

* [Exceptions] Methods that throw exceptions related to parameters now throw InvalidArgumentException
```
Controller::__construct()
Controller::setState()
Network::getSpeakerByRoom()
Network::getSpeakersByRoom()
Network::getControllerByRoom()
Network::getPlaylistByName()
```

### Fixed

* [Controllers] The getStateDetails() method can now handle empty queues and return a valid array.

--------
