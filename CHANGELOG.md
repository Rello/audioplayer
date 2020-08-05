# Changelog
All notable changes to the Audio Player project will be documented in this file.

## 2.11.2 - 2020-07-30
### Fixed
- Fix dark theme volume background color [#491](https://github.com/rello/audioplayer/pull/491) @[r4sas](https://github.com/r4sas)

## 2.11.1 - 2020-07-30
### Fixed
- Track name/artist is not correctly recognized in EXTINF [#485](https://github.com/rello/audioplayer/issues/485)
- Volume controll working again
- theming issues [#486](https://github.com/rello/audioplayer/issues/486)
- Array offset error when deleting files [#484](https://github.com/rello/audioplayer/issues/484)
- Fix player ui timer and change progressbar height [#483](https://github.com/rello/audioplayer/pull/483) @[r4sas](https://github.com/r4sas)
- Rounded border for album picture in bar [#482](https://github.com/rello/audioplayer/pull/482) @[r4sas](https://github.com/r4sas)
- Update volume css, autosave js [#481](https://github.com/rello/audioplayer/issues/481) @[r4sas](https://github.com/r4sas)
- add volume slider CSS for FF and Edge [#489](https://github.com/rello/audioplayer/pull/489) @[r4sas](https://github.com/r4sas)

### Added
- Download track via path in sidebar [#453](https://github.com/rello/audioplayer/issues/453)

## 2.11.0 - 2020-07-19
### Added
- WhatsNew popup [#480](https://github.com/rello/audioplayer/issues/480)
- Hardware media keys & Chrome/Android player [#479](https://github.com/rello/audioplayer/issues/479)
- Aif Aiff support [#475](https://github.com/rello/audioplayer/issues/475)
- Absolute path and '../' in .m3u playlist files [#457](https://github.com/rello/audioplayer/issues/457)
- Remember playback position between sessions [#288](https://github.com/rello/audioplayer/issues/288)
- Repeat single track (or playlist) [#172](https://github.com/rello/audioplayer/issues/172)
- Shuffle: play tracks only once [#361](https://github.com/rello/audioplayer/issues/361)

### Changed
- Migration from database.xml to /Migration
- Drop soundmanager2 and bar-ui for HTML5 audio [#481](https://github.com/rello/audioplayer/issues/481)

## 2.10.1 - 2020-06-15
### Fixed
- Search result hidden behind playlist [#472](https://github.com/rello/audioplayer/issues/472)

## 2.10.0 - 2020-04-13
### Added
- add random-tracks smart playlist [#442](https://github.com/rello/audioplayer/issues/442)
- NC 19

### Fixed
- Playlist ends outside of visible part of page [#461](https://github.com/rello/audioplayer/issues/461)

## 2.9.0 - 2020-01-15
### Changed
- update getID3 to 1.9.18-201911300717
- PHP 7.4 compatibility [#449](https://github.com/rello/audioplayer/issues/449)

### Added
- NC18

### Fixed
- SQL group by exception [#450](https://github.com/rello/audioplayer/issues/450)

## 2.8.4 - 2019-09-3
### Fixed
- Play icon in Cover View not starting the correct track [#438](https://github.com/rello/audioplayer/issues/438)
- Sidebar not hiding correctly
- Cover issue in playbar
- Resizing playlist on sidebar close

## 2.8.3 - 2019-09-1
### Fixed
- typo in JS

## 2.8.2 - 2019-09-1
### Fixed
- PostgreSQL compatibility issue [#437](https://github.com/rello/audioplayer/issues/437)
- Sidebar not updating

## 2.8.1 - 2019-08-31
### Fixed
- missing js file during release

## 2.8.0 - 2019-08-31

### Added
- Cover view selectable for all categories [#165](https://github.com/rello/audioplayer/issues/165)
- APIs & events to enable Audio Player add-ons [#408](https://github.com/rello/audioplayer/issues/408)
- NC17 compatibility

### Changed
- Significant UI performance improvements (>70%)
- Reduction of metadata transfer size (>45%) [#433](https://github.com/rello/audioplayer/issues/433)
- Scanner performance improvements (>20%) [#419](https://github.com/rello/audioplayer/pull/419) @[mmatous](https://github.com/mmatous)
- Add-on: SONOS playback as separate app [#411](https://github.com/rello/audioplayer/issues/411)
- Add-on: ID3 editor as separate app [#436](https://github.com/rello/audioplayer/issues/436)
- Add-on: Dashboard widget as separate app [#431](https://github.com/rello/audioplayer/issues/431)
- Codestyle consistency [#403](https://github.com/rello/audioplayer/pull/403) [#405](https://github.com/rello/audioplayer/issues/405) @[mmatous](https://github.com/mmatous)
- JS introduction of namespaces
- Use non-minified Soundmanager [#417](https://github.com/rello/audioplayer/pull/417) @[mmatous](https://github.com/mmatous)  

### Fixed
- Fix hotkey clashing [#416](https://github.com/rello/audioplayer/pull/416) @[mmatous](https://github.com/mmatous) 
- Remove hardcoded protocol [#421](https://github.com/rello/audioplayer/pull/421) @[mmatous](https://github.com/mmatous) 

## 2.7.2 - 2019-06-23
### Fixed
- SONOS playback issue

## 2.7.1 - 2019-06-17
### Fixed
- performance: caching for album cover arts
- performance: reduce json data amount by optimizing content
- better mobile css compatibility
- Fix favourite star in sidebar [#400](https://github.com/rello/audioplayer/pull/400) @[mmatous](https://github.com/mmatous)

### Changed
- Show song info in title [#393](https://github.com/rello/audioplayer/pull/393) @[mmatous](https://github.com/mmatous)

## 2.7.0 - 2019-04-21
### Added
- NC 16 support
- es translation [#390](https://github.com/rello/audioplayer/pull/390) @[MaxGitHubAccount](https://github.com/MaxGitHubAccount)

### Changed
- appstore redesign
- Additional AlbumArts files [#387](https://github.com/Rello/audioplayer/pull/387) @[tidoni](https://github.com/tidoni)

### Fixed
- JS error in favoriteUpdate() [#389](https://github.com/rello/audioplayer/issues/389)
- iconv error for cyrillic symbols [#386](https://github.com/rello/audioplayer/issues/386)
- incorrect occ -vv output for streams
- Albums below the selected one are relocated [#377](https://github.com/rello/audioplayer/issues/377)
- scanner stuck due to corrupt artwork file [#362](https://github.com/rello/audioplayer/issues/362)

## 2.6.1 - 2019-03-10
### Fixed
- Artist search not working correctly [#380](https://github.com/rello/audioplayer/issues/380)
- Adding track to playlist [#381](https://github.com/rello/audioplayer/issues/381) @[r4sas](https://github.com/r4sas)

### Added
- Audio Player icon in search results [#380](https://github.com/rello/audioplayer/issues/380)
- fr translation [#383](https://github.com/rello/audioplayer/issues/383) @[ewidance](https://github.com/ewidance)

## 2.6.0 - 2019-01-31
### Added
- local playlists (in M3U files) [#325](https://github.com/rello/audioplayer/issues/325)

## 2.5.2 - 2019-01-22
### Fixed
- player not loaded on shared folder [#371](https://github.com/rello/audioplayer/issues/371) @[Ark74](https://github.com/Ark74)
- copyright 2019
- dark design and theming compatibility [#367](https://github.com/rello/audioplayer/issues/367)
- replace deprecated insertIfNotExist()
- Album Artist category wrong title counters

## 2.5.1 - 2018-12-23
### Added
- favicons
- php7.3

### Changed
- update owncloud version [#370](https://github.com/rello/audioplayer/pull/370) @[ho4ho](https://github.com/ho4ho)

## 2.5.0 - 2018-12-15
### Added
- dark design and theming compatibility [#367](https://github.com/rello/audioplayer/issues/367)

### Changed
- ru translation [#364](https://github.com/rello/audioplayer/pull/364) @[r4sas](https://github.com/r4sas)
- SONOS: enable/disable the plugin globally as admin [#363](https://github.com/rello/audioplayer/issues/363)
- harden ContentSecurityPolicy
- update getID3 to version 1.9.16-201812050141
- switch scanner progress (UI) from cache to table storage [#362](https://github.com/rello/audioplayer/issues/362)
- switch from --debug to verbosity levels in occ [#352](https://github.com/rello/audioplayer/issues/352)
- widget migration to Dashboard 6.0.0 [#366](https://github.com/rello/audioplayer/pull/366) @[daita](https://github.com/daita)
- update PHPSonos to V2.1.3
- switch from css to scss stylesheets using global variables

### Fixed
- show "title" tag for mouse hover in category list [#354](https://github.com/rello/audioplayer/issues/354)
- Closing tracklist on album jumps to top [#351](https://github.com/rello/audioplayer/issues/351)
- SONOS socket backend error handling [#348](https://github.com/rello/audioplayer/issues/348)

## 2.4.1 - 2018-09-29
### Fixed
- Play button not displayed due to png format [#345](https://github.com/rello/audioplayer/issues/345)

### Changed
- zh_CN translation [#346](https://github.com/rello/audioplayer/pull/346) @[PYCG](https://github.com/PYCG)
- tr translation [#344](https://github.com/rello/audioplayer/pull/344) @[mzeyrek](https://github.com/mzeyrek)

## 2.4.0 - 2018-09-23
### Added
- SONOS player integration [#331](https://github.com/rello/audioplayer/issues/331)
- Audioplayer widget for Dashboard App (beta) [#328](https://github.com/rello/audioplayer/issues/328)
- Play button on album cover [#319](https://github.com/rello/audioplayer/issues/319)

### Changed
- Settings moved to user/personal settings menu
- personal settings difference between ownCloud & Nextcloud [#344](https://github.com/rello/audioplayer/issues/344)
- zh_CN translation [#342](https://github.com/rello/audioplayer/pull/342) @[limingqi](https://github.com/limingqi)
- cs translation [#336](https://github.com/rello/audioplayer/pull/336) @[447937](https://github.com/447937)
- pl translation [#335](https://github.com/rello/audioplayer/pull/335) @[andypl78](https://github.com/andypl78)
- ru translation [#309](https://github.com/rello/audioplayer/pull/309) @[r4sas](https://github.com/r4sas)
- upgrade getID3 to version 1.9.15-201809221240 [#340](https://github.com/rello/audioplayer/issues/340)

### Fixed
- Postgres issue in categories [#330](https://github.com/rello/audioplayer/pull/330) @[jpumc](https://github.com/jpumc)
- NC14: Album not scrolled into viewarea [#337](https://github.com/rello/audioplayer/issues/337)
- special characters in folder names (one click play) [#291](https://github.com/rello/audioplayer/issues/291)

## 2.3.2 - 2018-08-19
### Fixed
- NC14 compatibility of navigation [#324](https://github.com/rello/audioplayer/pull/324) @[juliushaertl](https://github.com/juliushaertl)

## 2.3.1 - 2018-07-03
### Added
- cs translation [#306](https://github.com/rello/audioplayer/pull/306) @[447937](https://github.com/447937)

### Changed
- zh_CN translation [#304](https://github.com/rello/audioplayer/pull/304) @[limingqi](https://github.com/limingqi)
- ru translation [#309](https://github.com/rello/audioplayer/pull/309) @[r4sas](https://github.com/r4sas)

### Fixed
- unshared files aren't recognized
- `local` external storage folders aren't displayed properly in Folder category
- playback will not continue on albums without covers [#305](https://github.com/rello/audioplayer/issues/305)
- display issue with Firefox in album list display

## 2.3.0 - 2018-04-28
### Added
- sidebar integration
- support for PHP 7.2 [#277](https://github.com/rello/audioplayer/issues/277)
- path and file name to sidebar [#283](https://github.com/rello/audioplayer/issues/283)
- album artists to category selection [#286](https://github.com/rello/audioplayer/issues/286) and sharing screen
- detect changed audio metadata of indexed files [#284](https://github.com/rello/audioplayer/issues/284)
- output duplicate files in scanner [#273](https://github.com/rello/audioplayer/issues/273)
- `ISRC` and `COPYRIGHT` to metadata [#293](https://github.com/rello/audioplayer/issues/293)
- tr translation [#289](https://github.com/rello/audioplayer/pull/289) @[mzeyrek](https://github.com/mzeyrek)

### Changed
- rework of `.js` backend
- albums cover display performance
- 360° player/SoundManager 2 replaced by `AUDIO` tag on sharing screen [#280](https://github.com/rello/audioplayer/issues/280)
- separate multiple albums with the same name **!rescan required!** [#271](https://github.com/rello/audioplayer/issues/271) [#283](https://github.com/rello/audioplayer/issues/283)
- pl translation [#294](https://github.com/rello/audioplayer/issue/294) @[andypl78](https://github.com/andypl78)
- uk translation [#295](https://github.com/rello/audioplayer/issue/295) @[BODYA7979](https://github.com/BODYA7979)
- zh_TW translation [#297](https://github.com/rello/audioplayer/issues/297) @[sbr9150](https://github.com/sbr9150)

### Fixed
- caching for cover arts enabled
- double player on sharing screen [#280](https://github.com/rello/audioplayer/issues/280)
- highlight current track in album view [#282](https://github.com/rello/audioplayer/issues/282)

### Removed
- ID3 tag editor ([separate app](https://github.com/rello/audioplayer_editor) in development)  [#290](https://github.com/rello/audioplayer/issues/290)
- support for Nextcloud 11

## 2.2.5 - 2018-02-02
### Fixed
- no standard playlist creation possible [#270](https://github.com/rello/audioplayer/issues/270)

## 2.2.4 - 2018-01-20
### Fixed
- separate archives for Nextcloud and ownCloud code signing [#268](https://github.com/rello/audioplayer/issues/268)

## 2.2.3 - 2018-01-16
### Changed
- adjust PLS playlist parser [#265](https://github.com/rello/audioplayer/issues/265)

### Deprecated
- `OCP\PERMISSION_UPDATE` replaced by `OCP\Constants::PERMISSION_UPDATE` [#266](https://github.com/rello/audioplayer/issues/266)

### Fixed
- Audio Player CSS is breaking Overflow Menu in Files app [#264](https://github.com/rello/audioplayer/issues/264)

## 2.2.2 - 2017-12-26
### Added
- `Folder.jpg` as cover art filename [#256](https://github.com/rello/audioplayer/issues/256)
- store playback volume into user settings [#260](https://github.com/rello/audioplayer/issues/260)

### Deprecated
- `OCP\Util::writeLog` replaced by `OCP\ILogger` [#257](https://github.com/rello/audioplayer/issues/257)

### Fixed
- offset in seekable progress bar when sidebar is open [#263](https://github.com/rello/audioplayer/issues/263) (fixes [#111](https://github.com/rello/audioplayer/issues/111) [#128](https://github.com/rello/audioplayer/issues/128)) @[juliushaertl](https://github.com/juliushaertl)
- Nextcloud 13 compatibility of settings panel [#258](https://github.com/rello/audioplayer/issues/258)

## 2.2.1 - 2017-11-19
### Fixed
- only one change in a playlist possible

## 2.2.0 - 2017-11-17
### Added
- stream URLs [#27](https://github.com/rello/audioplayer/issues/27) [#233](https://github.com/rello/audioplayer/issues/233)
- Scrutinizer Continuous Inspection checks
- notification for required rescan [#246](https://github.com/rello/audioplayer/issues/246)
- tooltips for soundbar icons in desktop browsers [#252](https://github.com/rello/audioplayer/issues/252)

### Changed
- sidebar rebuilt [#233](https://github.com/rello/audioplayer/issues/233)
- user settings moved to sidebar [#233](https://github.com/rello/audioplayer/issues/233)
- scan and reset moved to settings [#233](https://github.com/rello/audioplayer/issues/233)
- database index optimizations
- statistics table renamed to meet guideline (resets current statistics)
- spellings and translations reworked [#243](https://github.com/rello/audioplayer/issues/243)
- soundbar icons [#253](https://github.com/rello/audioplayer/issues/253)

### Fixed
- `YEAR` field in metadata editor enlarged [#221](https://github.com/rello/audioplayer/issues/221)
- `count()` in `for()` loop [#235](https://github.com/rello/audioplayer/issues/235)
- One Click Play did not start on first click
- moved `.dialog` to `.ocdialog` for better server integration [#247](https://github.com/rello/audioplayer/issues/247)
- wrong icons in soundbar [#252](https://github.com/rello/audioplayer/issues/252)

## 2.1.0 - 2017-08-29
### Added
- count albums of selected artist [#205](https://github.com/rello/audioplayer/issues/205)
- FileHooks: library cleanup after deleting files
- support for Nextcloud 13

### Changed
- number of tracks in Smart Playlists [#208](https://github.com/rello/audioplayer/issues/208)
- crop cover to middle square
- cleanup of js functions

### Deprecated
- [ownCloud App Store](https://apps.owncloud.com/) (`ocsid`)

### Removed
- support for ownCloud 9.0 [#222](https://github.com/rello/audioplayer/issues/222)
- support for Nextcloud 10

### Fixed
- scanner truncates long multiple title properly [#203](https://github.com/rello/audioplayer/issues/203) @[nhirokinet](https://github.com/nhirokinet)
- issue with files app [#210](https://github.com/rello/audioplayer/issues/210) (thanks @[artemanufrij](https://github.com/artemanufrij))
- catch undeclared variable [#212](https://github.com/rello/audioplayer/issues/212)
- raw cover data removed from metadata [#214](https://github.com/rello/audioplayer/issues/214)
- playlist cleanup after deleting files [#216](https://github.com/rello/audioplayer/issues/216)
- `folder_id` removed from duplicate check [#217](https://github.com/rello/audioplayer/issues/217)
- catch soundbar buttons triggering category selector [#225](https://github.com/rello/audioplayer/issues/225)
- forcing reset of `selectedIndex` [#226](https://github.com/rello/audioplayer/issues/226)
- PostgreSQL issue in favorites

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
- `DISC`, `COMPOSER`, and `SUBTITLE` to metadata [#184](https://github.com/rello/audioplayer/issues/184) (thanks @[Faldon](https://github.com/Faldon))
- notify user when new/unscanned tracks are available [#188](https://github.com/rello/audioplayer/issues/188)
- UserHooks: library cleanup after deleting users
- more metadata to Share Player

### Changed
- zh_TW translation [#173](https://github.com/rello/audioplayer/issues/173) @[sbr9150](https://github.com/sbr9150)
- translation sources reworked
- CSS changes for navigation menu [#179](https://github.com/rello/audioplayer/issues/179) @[artemanufrij](https://github.com/artemanufrij)
- compress `.js` files [#191](https://github.com/rello/audioplayer/issues/191)

### Deprecated
- `OCP\IDb` replaced by `OCP\IDbConnection` [#183](https://github.com/rello/audioplayer/issues/183)
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
- missing tags for WAV files [#166](https://github.com/rello/audioplayer/issues/166)
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
- padding in Share Player
- search integration improvements [#155](https://github.com/rello/audioplayer/issues/155)

### Fixed
- handling of multiple album artists of an album [#13](https://github.com/rello/audioplayer/issues/13)
- album cover in soundbar [#133](https://github.com/rello/audioplayer/issues/133)
- scanner adjustments [#137](https://github.com/rello/audioplayer/issues/137) [#140](https://github.com/rello/audioplayer/issues/140) [#145](https://github.com/rello/audioplayer/issues/145)
- not playing from shared subfolders [#139](https://github.com/rello/audioplayer/issues/139)
- mobile browser support [#141](https://github.com/rello/audioplayer/issues/141)
- scan progress dialog reworked [#153](https://github.com/rello/audioplayer/issues/153)
- category views cleanup after deleting files [#154](https://github.com/rello/audioplayer/issues/154)
- search integration [#155](https://github.com/rello/audioplayer/issues/155)
- playlist selection lost after edit or sort [#162](https://github.com/rello/audioplayer/issues/162)

## 1.4.1 - 2017-01-26
### Added
- pl translation [#105](https://github.com/rello/audioplayer/issues/105) @[andypl78](https://github.com/andypl78)
- support for PHP 7.1

### Changed
- getID3 to 1.9.13-201612181356 [#119](https://github.com/rello/audioplayer/issues/119)
- search order of cover art [#126](https://github.com/rello/audioplayer/issues/126)
- padding/&#8203;margin in Share Player
- natural sorting for category lists
- de + de_DE translations

### Removed
- support for ownCloud 8
- support for PHP &#60;5.6
- album year from search criteria [#116](https://github.com/rello/audioplayer/issues/116)

### Fixed
- Chrome CSS issue with only one track in album [#104](https://github.com/rello/audioplayer/issues/104)
- arrays corrected and obsolete functions removed [#110](https://github.com/rello/audioplayer/issues/110) [#123](https://github.com/rello/audioplayer/issues/123) (thanks @[mc-comanescu](https://github.com/mc-comanescu) and @[rseabra](https://github.com/rseabra))
- correct `ORDER BY` syntax for PostgreSQL database [#112](https://github.com/rello/audioplayer/issues/112) (thanks @[Turgon37](https://github.com/turgon37))
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
- One Click Play of WAV does not work
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
- Share Player works only with MP3 files [#54](https://github.com/rello/audioplayer/issues/54)
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
