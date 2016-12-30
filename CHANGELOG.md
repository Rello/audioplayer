# Changelog
All notable changes to this project will be documented in this file.

## Unreleased
### Fixed
- correct arrays and remove obsolete functions [#110](https://github.com/rello/audioplayer/issues/110) (thanks @mc-comanescu)

### Removed
- support for ownCloud 8
- support for PHP <5.6

## 1.4.0 - 2016-12-15
### Added
- uk translation from @[BODYA7979](https://github.com/BODYA7979) [#94](https://github.com/rello/audioplayer/issues/94)
- folders as additional filter category [#98](https://github.com/rello/audioplayer/issues/98)
- search for cover in album folder [#24](https://github.com/rello/audioplayer/issues/24)
- Opus support [#92](https://github.com/rello/audioplayer/issues/92)
- ID3 tags on sharing screen [#102](https://github.com/rello/audioplayer/issues/102)

### Changed
- correct album sort order to case-insensitive

### Fixed
- catch special characters in album [#87](https://github.com/rello/audioplayer/issues/87)
- occ catch unknown user [#93](https://github.com/rello/audioplayer/issues/93)
- first search result row is partially hidden under the top menu [#74](https://github.com/rello/audioplayer/issues/74)

## 1.3.1 - 2016-11-17
### Fixed
- One Click Play for wav not working
- wrong SQL statement for PostgreSQL [#90](https://github.com/rello/audioplayer/issues/90)

## 1.3.0 - 2016-11-15
### Added
- command-line support for library scan and reset [#72](https://github.com/rello/audioplayer/issues/72)
- select a dedicated folder for scanning in personal settings [#79](https://github.com/rello/audioplayer/issues/79)
- exclude folders from scanning via .noaudio file [#79](https://github.com/rello/audioplayer/issues/79)
- significantly reduce database reads during scanning [#79](https://github.com/rello/audioplayer/issues/79)
- cleanup of classes; move from \OC\Files\View to \OCP\Files\IRootFolder [#72](https://github.com/rello/audioplayer/issues/72)

### Changed
- neutral cover for unknown album [#16](https://github.com/rello/audioplayer/issues/16)

### Fixed
- handling of temporary scanner files [#68](https://github.com/rello/audioplayer/issues/68)
- simpler analysis of incorrect files in scanner [#57](https://github.com/rello/audioplayer/issues/57)
- album sorted correctly by artist and album [#80](https://github.com/rello/audioplayer/issues/80)
- error message from ID3 editor shown in front-end [#77](https://github.com/rello/audioplayer/issues/77)

## 1.2.2 - 2016-09-18
### Fixed
- icon issues with custom apps directory [#65](https://github.com/rello/audioplayer/issues/65)

## 1.2.1 - 2016-09-15
### Added
- new clean design with less crazy colors [#59](https://github.com/rello/audioplayer/issues/59)

### Fixed
- share player only working for mp3 files [#54](https://github.com/rello/audioplayer/issues/54)
- input box for new playlists does not hide [#61](https://github.com/rello/audioplayer/issues/61)

## 1.2.0 - 2016-09-09
### Added
- rework of sidebar with integrated playlists in dynamic navigation [#53](https://github.com/rello/audioplayer/pull/53)
- performance improvement when loading categories [#53](https://github.com/rello/audioplayer/pull/53)
- app:check-code compatibility [#46](https://github.com/rello/audioplayer/issues/46)
- app will be signed as of this release

### Changed
- cover art crop box more flexible

### Fixed
- wrong spinning wheel location in css
- album transparency [#44](https://github.com/rello/audioplayer/issues/44)

## 1.1.0 - 2016-08-24
### Added
- new navigator: dynamic lists for artists, genres, years (more to come)
- special scanner setting for cyrillic characters (see personal settings)
- navigator views remembered after app restart
- ru translation

### Changed
- redesign of backend table structures
- proper handling of artists, album artists, genres
- better visible playing indicators in files app

### Fixed
- ID3 editor dropdowns

## 1.0.3 - 2016-08-08
### Changed
- various display improvements

### Fixed
- genre not always shown [#35](https://github.com/rello/audioplayer/issues/35)
- album not rearranged on navigation show/hide [#36](https://github.com/rello/audioplayer/issues/36)
- display issues on small (phone) screens [#36](https://github.com/rello/audioplayer/issues/36)
- cover art on sharing screen [#37](https://github.com/rello/audioplayer/issues/37)

## 1.0.2 - 2016-08-04
### Added
- add file option dropdown entry

### Changed
- fix soundbar when scrolling [#25](https://github.com/rello/audioplayer/issues/25)
- use reduced soundmanager-js for One Click Play

### Fixed
- One Click Play [#22](https://github.com/rello/audioplayer/issues/22)
- special characters in filenames [#26](https://github.com/rello/audioplayer/issues/26)
- library reset does not remove playlists [#30](https://github.com/rello/audioplayer/issues/30)

## 1.0.1 - 2016-07-23
### Added
- zh-TW translation from @[sbr9150](https://github.com/sbr9150)

### Fixed
- spinning wheel when file was deleted [#19](https://github.com/rello/audioplayer/issues/19)
- red progress bar with nextcloud [#18](https://github.com/rello/audioplayer/issues/18)
- issue with filesearch (missing ID3 Tags) [#14](https://github.com/rello/audioplayer/issues/14)
- wrong album artist shown when different track-artists available [#13](https://github.com/rello/audioplayer/issues/13)

## 1.0.0 - 2016-07-18
### Added
- fullscreen mode as default (hide app-navigation)
- playlist section conditional display
- genre view (besides albums and custom playlists)

### Changed
- rebranding from "Audios" to "Audio Player"
- update of 3rd party libraries

### Fixed
- scanner stabilization (continuation after errors)
- mobile usability (tooltip issue on touch)
- search provider returning correct result set
- search is not case sensitive anymore
