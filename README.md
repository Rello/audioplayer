#![](https://github.com/z000ao8q/screenshots/blob/master/Audioplayer_Icon_30.png) Audio Player App
Albums and Playlists for mp3 & m4a files within a nice, animated cover-art navigation.<br>
Alternatively playing audio files from the file-browser or a shared link.<br>
A pure player. No backends or other overhead...

![](https://github.com/z000ao8q/screenshots/blob/master/audioplayer_main.png)<br>
Playlists & Genres:
![](https://github.com/z000ao8q/screenshots/blob/master/audioplayer_lists.png)<br>
Share-Player
![](https://github.com/z000ao8q/screenshots/blob/master/audioplayer_share.png)

##Features
- Album view inspired by http://thomaspark.co/project/expandingalbums/ 
- Genre view
- Playlist view
- Scanning & Resetting Library & Playlist editing in UI
- Editing ID3 Tags (incl. picture)
- mobil view support
- One-Click-Play from files browser
- Play shared audiofiles directly

###Filetypes
- mp3, m4a, ogg, wav

##Changelog
1.0.2 (in progress)
- fix: one-click-play #22
- enhancement: add file-option-dropdown-entry
- enhancement: use reduced soundmanager-js for one-click-play

1.0.1
- note: please reindex files after upgrade
- fix: Spinning wheel when file was deleted #19
- fix: red progress bar with nextcloud #18
- fix: issue with filesearch (missing ID3 Tags) #14 
- fix: wrong album artist shown when different track-artists available #13 
- enhancement: Taiwanese localization (thanks to sbr9150)
 
1.0.0
- rebranding from "Audios" to "Audio Player"
- update of 3rd party libraries
- fix: scanner stabilization (continuation after errors)
- fix: mobile usability (tooltip issue on touch)
- fix: search provider returning correct result set
- fix: search is not case sensitive anymore
- enhancement: fullscreen mode as default (hide app-navigation)
- enhancement: playlist-section conditional display
- enhancement: genre view (besides albums & custom playlists)

##Installation
Use the App-Store or download the zip file and copy it into your apps directory
https://github.com/z000ao8q/audioplayer/releases/download/v1.0.0/audioplayer-1.0.0.zip

##Maintainer
Marcel Scherello<br>
(Initial Developer Sebastian Döll)

##Note
This is the rework of the former "MP3 Player" app which is not maintained anymore. <br>
Thanks to Sebastian Döll for the awesome initial work
