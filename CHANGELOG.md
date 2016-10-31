# Changelog

## 1.3
2016-11-xx
- fix: handling of temporary scanner files [#68](https://github.com/rello/audioplayer/issues/68)
- fix: simpler analysis of incorrect files in scanner [#57](https://github.com/rello/audioplayer/issues/57)
- enhancement: occ support [#72](https://github.com/rello/audioplayer/issues/72)
- enhancement: cleanup of classes; move from \OC\Files\View to \OCP\Files\IRootFolder [#72](https://github.com/rello/audioplayer/issues/72)

## 1.2.2
2016-09-18
- fix: icon issues with custom apps directory [#65](https://github.com/rello/audioplayer/issues/65)

## 1.2.1
2016-09-15
- fix: share player only working for mp3 files [#54](https://github.com/rello/audioplayer/issues/54)
- fix: input box for new playlists does not hide [#61](https://github.com/rello/audioplayer/issues/61)
- enhancement: new clean design with less crazy colors [#59](https://github.com/rello/audioplayer/issues/59)

## 1.2.0
2016-09-09
- fix: wrong spinning wheel location in css
- fix: cover art crop box more flexible
- fix: album transparency [#44](https://github.com/rello/audioplayer/issues/44)
+ enhancement: rework of sidebar with integrated playlists in dynamic navigation [#53](https://github.com/rello/audioplayer/pull/53)
- enhancement: performance improvement when loading categories [#53](https://github.com/rello/audioplayer/pull/53)
- app:check-code compatibility [#46](https://github.com/rello/audioplayer/issues/46)
- app will be signed as of this release

## 1.1.0
2016-08-24
- new navigator: dynamic lists for artists, genres, years (more to come)
- redesign of backend table structures
- proper handling of artists, album artists, genres
- RU localization
- special scanner setting for cyrillic characters (see personal settings)
- navigator views remembered after app restart
- better visible playing indicators in files app
- fix: ID3 editor dropdowns

## 1.0.3
2016-08-08
- fix: genre not always shown [#35](https://github.com/rello/audioplayer/issues/35)
- fix: album not rearranged on navigation show/hide [#36](https://github.com/rello/audioplayer/issues/36)
- fix: display issues on small (phone) screens [#36](https://github.com/rello/audioplayer/issues/36)
- fix: cover art on sharing screen [#37](https://github.com/rello/audioplayer/issues/37)
- fix: various display improvements

## 1.0.2
2016-08-04
- fix: One Click Play [#22](https://github.com/rello/audioplayer/issues/22)
- fix: special characters in filenames [#26](https://github.com/rello/audioplayer/issues/26)
- fix: library reset does not remove playlists [#30](https://github.com/rello/audioplayer/issues/30)
- enhancement: fix soundbar when scrolling [#25](https://github.com/rello/audioplayer/issues/25)
- enhancement: add file option dropdown entry
- enhancement: use reduced soundmanager-js for One Click Play

## 1.0.1
2016-07-23
- fix: spinning wheel when file was deleted [#19](https://github.com/rello/audioplayer/issues/19)
- fix: red progress bar with nextcloud [#18](https://github.com/rello/audioplayer/issues/18)
- fix: issue with filesearch (missing ID3 Tags) [#14](https://github.com/rello/audioplayer/issues/14)
- fix: wrong album artist shown when different track-artists available [#13](https://github.com/rello/audioplayer/issues/13)
- enhancement: Taiwanese localization (thanks to [sbr9150](https://github.com/sbr9150))

## 1.0.0
2016-07-18
- rebranding from "Audios" to "Audio Player"
- update of 3rd party libraries
- fix: scanner stabilization (continuation after errors)
- fix: mobile usability (tooltip issue on touch)
- fix: search provider returning correct result set
- fix: search is not case sensitive anymore
- enhancement: fullscreen mode as default (hide app-navigation)
- enhancement: playlist section conditional display
- enhancement: genre view (besides albums and custom playlists)
