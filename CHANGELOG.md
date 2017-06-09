# Changelog
All notable changes to the Audio Player project will be documented in this file.

## 2.0.2 - 2017-06-09
### Added
- 360° player MIME type hint [#201](https://github.com/rello/audioplayer/issues/201)

### Changed
- default playlist sorting [#174](https://github.com/rello/audioplayer/issues/174)
- pl translation [#197](https://github.com/rello/audioplayer/pull/197) @[andypl78](https://github.com/andypl78)
- sorting weight for navigation in apps selection menu

### Fixed
- continuous playback of tracks without album cover [#199](https://github.com/rello/audioplayer/issues/199)
- `VERSION` tag ignored in VorbisComment [#200](https://github.com/rello/audioplayer/issues/200)
- progress bar of 360° player does not work [#201](https://github.com/rello/audioplayer/issues/201)

## 2.0.1 - 2017-05-27
### Added
- zh_CN translation [#193](https://github.com/rello/audioplayer/pull/193) @[TheOne1006](https://github.com/TheOne1006)

### Changed
- zh_TW translation [#173](https://github.com/rello/audioplayer/issues/173) @[sbr9150](https://github.com/sbr9150)
- uk translation [#195](https://github.com/rello/audioplayer/pull/195) @[BODYA7979](https://github.com/BODYA7979)

### Fixed
- `DISCNUMBER` tag ignored in VorbisComment [#196](https://github.com/rello/audioplayer/issues/196)

## 2.0.0 - 2017-05-24
### Added
- FLAC support [#45](https://github.com/rello/audioplayer/issues/45)
- favorites integration [#176](https://github.com/rello/audioplayer/issues/176) in Smart Playlists [#164](https://github.com/rello/audioplayer/issues/164)
- second stage [#177](https://github.com/rello/audioplayer/issues/177) of Smart Playlists [#164](https://github.com/rello/audioplayer/issues/164)
- Dynamic Playlists [#186](https://github.com/rello/audioplayer/issues/186)
- dragging also from selected lists into playlists [#168](https://github.com/rello/audioplayer/issues/168)
- support for ownCloud 10.0 and Nextcloud 12 [#183](https://github.com/rello/audioplayer/issues/183)
- PSR-4 autoloader compatibility [#184](https://github.com/rello/audioplayer/issues/184)
- disc, composer, and subtitle to metadata [#184](https://github.com/rello/audioplayer/issues/184) (thanks @[Faldon](https://github.com/Faldon))
- notify user when new/unscanned tracks are available [#188](https://github.com/rello/audioplayer/issues/188)
- UserHooks: library cleanup when user is removed
- more metadata to Share Player

### Changed
- zh_TW translation [#173](https://github.com/rello/audioplayer/issues/173) @[sbr9150](https://github.com/sbr9150)
- translation sources reworked
- CSS changes for navigation menu [#179](https://github.com/rello/audioplayer/issues/179) @[artemanufrij](https://github.com/artemanufrij)
- compress `.js` files [#191](https://github.com/rello/audioplayer/issues/191)

### Deprecated
- `OCP\IDB` replaced by `OCP\IDbConnection` [#183](https://github.com/rello/audioplayer/issues/183)
- `tipsy()` replaced by `tooltips()` [#189](https://github.com/rello/audioplayer/issues/189)

### Removed
- support for Nextcloud 9

### Fixed
- missing strings added to language files
- sorting albums by disc and track [#88](https://github.com/rello/audioplayer/issues/88) [#174](https://github.com/rello/audioplayer/issues/174) (thanks @[Faldon](https://github.com/Faldon))
- limit year to 4 digits
- undefined variable during empty category [#187](https://github.com/rello/audioplayer/issues/187) 

### Security
- avoid XSS in metadata

## 1.5.1 - 2017-04-08
### Fixed
- missing tags for wav files [#166](https://github.com/rello/audioplayer/issues/166)
- playing of shared files [#171](https://github.com/rello/audioplayer/issues/171)

## 1.5.0 - 2017-03-31
### Added
- sorting of lists [#122](https://github.com/rello/audioplayer/issues/122)
- albums to selection [#132](https://github.com/rello/audioplayer/issues/132)
- highlighting and focusing to selection
- separate scrolling for navigation and content [#144](https://github.com/rello/audioplayer/issues/144)
- first stage [#160](https://github.com/rello/audioplayer/issues/160) of Smart Playlists [#164](https://github.com/rello/audioplayer/issues/164)

### Changed
- front-end performance improvements [#130](https://github.com/rello/audioplayer/issues/130) [#149](https://github.com/rello/audioplayer/issues/149)
- scanner performance improvements [#151](https://github.com/rello/audioplayer/issues/151)
- selection order and naming
- cover art for "Unknown/&#8203;Various Artists"
- padding in share player
- search integration improvements [#155](https://github.com/rello/audioplayer/issues/155)

### Fixed
- handling of multiple album artists of an album [#13](https://github.com/rello/audioplayer/issues/13)
- album cover in soundbar [#133](https://github.com/rello/audioplayer/issues/133)
- scanner adjustments [#137](https://github.com/rello/audioplayer/issues/137) [#140](https://github.com/rello/audioplayer/issues/140) [#145](https://github.com/rello/audioplayer/issues/145)
- not playing from shared subfolders [#139](https://github.com/rello/audioplayer/issues/139)
- mobile browser support [#141](https://github.com/rello/audioplayer/issues/141)
- scan progress dialog reworked [#153](https://github.com/rello/audioplayer/issues/153)
- cleaning up deleted audio files [#154](https://github.com/rello/audioplayer/issues/154)
- search integration [#155](https://github.com/rello/audioplayer/issues/155)
- playlist selection lost after edit or sort [#162](https://github.com/rello/audioplayer/issues/162)

## 1.4.1 - 2017-01-26
### Added
- pl translation [#105](https://github.com/rello/audioplayer/issues/105) @[andypl78](https://github.com/andypl78)
- support for PHP 7.1

### Changed
- getID3 to 1.9.13-201612181356 [#119](https://github.com/rello/audioplayer/issues/119)
- search order of cover art [#126](https://github.com/rello/audioplayer/issues/126)
- padding/&#8203;margin in share player
- natural sorting for category lists
- de + de_DE translations

### Removed
- support for ownCloud 8
- support for PHP &#60;5.6
- album year from search criteria [#116](https://github.com/rello/audioplayer/issues/116)

### Fixed
- Chrome CSS issue with only one track in album [#104](https://github.com/rello/audioplayer/issues/104)
- correct arrays and remove obsolete functions [#110](https://github.com/rello/audioplayer/issues/110) [#123](https://github.com/rello/audioplayer/issues/123) (thanks @[mc-comanescu](https://github.com/mc-comanescu) and @[rseabra](https://github.com/rseabra))
- correct ORDER BY syntax for PostgreSQL database [#112](https://github.com/rello/audioplayer/issues/112) (thanks @[Turgon37](https://github.com/turgon37))
- album cover not written to database [#113](https://github.com/rello/audioplayer/issues/113)
- catch Unicode characters in artist name [#118](https://github.com/rello/audioplayer/issues/118)
- welcome screen not shown at first start [#121](https://github.com/rello/audioplayer/issues/121) 

## 1.4.0 - 2016-12-15
### Added
- uk translation [#94](https://github.com/rello/audioplayer/issues/94) @[BODYA7979](https://github.com/BODYA7979)
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
- exclude folders from scanning via `.noaudio` file [#79](https://github.com/rello/audioplayer/issues/79)
- significantly reduce database reads during scanning [#79](https://github.com/rello/audioplayer/issues/79)
- cleanup of classes; move from `OC\Files\View` to `OCP\Files\IRootFolder` [#72](https://github.com/rello/audioplayer/issues/72)

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
- wrong spinning wheel location in CSS
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
- file option dropdown entry

### Changed
- use reduced soundmanager-js for One Click Play

### Fixed
- One Click Play [#22](https://github.com/rello/audioplayer/issues/22)
- soundbar when scrolling [#25](https://github.com/rello/audioplayer/issues/25)
- special characters in filenames [#26](https://github.com/rello/audioplayer/issues/26)
- library reset does not remove playlists [#30](https://github.com/rello/audioplayer/issues/30)

## 1.0.1 - 2016-07-23
### Added
- zh_TW translation @[sbr9150](https://github.com/sbr9150)

### Fixed
- spinning wheel when file was deleted [#19](https://github.com/rello/audioplayer/issues/19)
- red progress bar with nextcloud [#18](https://github.com/rello/audioplayer/issues/18)
- issue with filesearch (missing ID3 Tags) [#14](https://github.com/rello/audioplayer/issues/14)
- wrong album artist shown when different track-artists available [#13](https://github.com/rello/audioplayer/issues/13)

## 1.0.0 - 2016-07-18
### Added
- fullscreen mode as default (hide app navigation)
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
