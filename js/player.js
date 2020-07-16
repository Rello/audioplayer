/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

'use strict';

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {
    };
}

/**
 * @namespace OCA.Audioplayer.Player
 */
OCA.Audioplayer.Player = {
    currentTrackIndex: 0,
    currentPlaylist: 0,
    currentTrackId: 0,
    repeatMode: null,
    trackStartPosition: 0,
    html5Audio: document.getElementById('html5Audio'),

    setTrack: function() {
        var songURL = OCA.Audioplayer.Player.html5Audio.children[OCA.Audioplayer.Player.currentTrackIndex].src;
        // new track to be played
        if (songURL !== OCA.Audioplayer.Player.html5Audio.getAttribute('src')) {
            OCA.Audioplayer.Player.html5Audio.setAttribute("src", songURL);
            OCA.Audioplayer.Player.currentTrackId = OCA.Audioplayer.Player.html5Audio.children[OCA.Audioplayer.Player.currentTrackIndex].dataset.trackid;
            OCA.Audioplayer.Player.html5Audio.load();
            OCA.Audioplayer.Player.html5Audio.currentTime = this.trackStartPosition;

            if (document.getElementById('playlist-container').dataset.playlist === OCA.Audioplayer.Player.currentPlaylist) {
                if (document.getElementsByClassName('isActive').length ===1) {
                    var currentActive = document.getElementsByClassName('isActive')[0]
                    // does not work yet, when a song is preselected bot not isActive
                    //currentActive.querySelector('i.ioc').style.display = 'none';
                    //currentActive.querySelector('i.icon').style.display = 'block';
                    document.getElementsByClassName('isActive')[0].classList.remove('isActive');
                }

                var iocIcon = document.querySelectorAll('.albumwrapper li i.ioc')
                for (var i = 0; i < iocIcon.length; ++i) {
                    iocIcon[i].style.display = 'none';
                }
                var iconIcon = document.querySelectorAll('.albumwrapper li i.icon')
                for (var i = 0; i < iconIcon.length; ++i) {
                    iconIcon[i].style.display = 'block';
                }

                iocIcon[OCA.Audioplayer.Player.currentTrackIndex].classList.remove('ioc-volume-off');
                iocIcon[OCA.Audioplayer.Player.currentTrackIndex].classList.add('ioc-volume-up');
                iocIcon[OCA.Audioplayer.Player.currentTrackIndex].style.display = 'block';
                iconIcon[OCA.Audioplayer.Player.currentTrackIndex].style.display = 'none';

                document.querySelectorAll('.albumwrapper li')[OCA.Audioplayer.Player.currentTrackIndex].classList.add('isActive');
            }
        } else {
            var iocIcon = document.querySelectorAll('.albumwrapper li i.ioc')
            iocIcon[OCA.Audioplayer.Player.currentTrackIndex].classList.remove('ioc-volume-off');
            iocIcon[OCA.Audioplayer.Player.currentTrackIndex].classList.add('ioc-volume-up');
        }
        OCA.Audioplayer.Player.playBack();

    },

    /**
     * Controls playback of the audio element.
     *
     **/
    playBack: function() {
            OCA.Audioplayer.Player.html5Audio.play();
            document.getElementById('sm2-bar-ui').classList.add('playing');
    },

    play: function () {
        if (OCA.Audioplayer.Player.isPaused()) {
            OCA.Audioplayer.Player.setTrack();
        } else {
            OCA.Audioplayer.Player.stop();
        }
    },

    stop: function () {
        OCA.Audioplayer.Player.html5Audio.pause();
        if (document.getElementById('playlist-container').dataset.playlist === OCA.Audioplayer.Player.currentPlaylist) {
            var iocIcon = document.querySelectorAll('.albumwrapper li i.ioc')
            iocIcon[OCA.Audioplayer.Player.currentTrackIndex].classList.remove('ioc-volume-up');
            iocIcon[OCA.Audioplayer.Player.currentTrackIndex].classList.add('ioc-volume-off');
            iocIcon[OCA.Audioplayer.Player.currentTrackIndex].style.display = 'block';
        }
        document.getElementById('sm2-bar-ui').classList.remove('playing');
    },

    pause: function () {
        this.stop();
    },

    next: function () {
        if (OCA.Audioplayer.Player.currentTrackIndex === OCA.Audioplayer.Player.html5Audio.childElementCount - 1) {
            if (OCA.Audioplayer.Player.loopMode === 'list') {
                OCA.Audioplayer.Player.currentTrackIndex = 0;
                OCA.Audioplayer.Player.setTrack();
            } else {
                this.stop();
            }
        } else {
            OCA.Audioplayer.Player.currentTrackIndex++;
            OCA.Audioplayer.Player.setTrack();
        }
    },

    prev: function () {
        OCA.Audioplayer.Player.currentTrackIndex--;
        OCA.Audioplayer.Player.setTrack();
    },

    repeat: function () {
        if (OCA.Audioplayer.Player.repeatMode === null) {
            OCA.Audioplayer.Player.html5Audio.loop = true;
            OCA.Audioplayer.Player.repeatMode = 'single';
            document.getElementById('playerRepeat').classList.remove('repeat');
            document.getElementById('playerRepeat').classList.add('repeat-single');
            document.getElementById('playerRepeat').style.opacity = 1;
        } else if (OCA.Audioplayer.Player.repeatMode === 'single'){
            OCA.Audioplayer.Player.html5Audio.loop = false;
            OCA.Audioplayer.Player.repeatMode = 'list';
            document.getElementById('playerRepeat').classList.add('repeat');
            document.getElementById('playerRepeat').classList.remove('repeat-single');
        } else {
            OCA.Audioplayer.Player.repeatMode = null;
            document.getElementById('playerRepeat').style.removeProperty('opacity');
        }
    },

    isPaused: function() {
      return this.html5Audio.paused;
    },

    addTracksToSourceList: function (playlistItems) {
        OCA.Audioplayer.Player.html5Audio.innerHTML = '';
        for (var i = 0; i < playlistItems.length; ++i) {
            var audioSource = document.createElement('source');
            audioSource.src = playlistItems[i].firstChild.href;
            audioSource.dataset.trackid = playlistItems[i].dataset.trackid;
            OCA.Audioplayer.Player.html5Audio.appendChild(audioSource);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {

    OCA.Audioplayer.Player.html5Audio.onended = function() {
        OCA.Audioplayer.Player.next();
    };

    document.getElementById('playerPrev').addEventListener('click', OCA.Audioplayer.Player.prev);
    document.getElementById('playerNext').addEventListener('click', OCA.Audioplayer.Player.next);
    document.getElementById('playerPlay').addEventListener('click', OCA.Audioplayer.Player.play);
    document.getElementById('playerRepeat').addEventListener('click', OCA.Audioplayer.Player.repeat);

    playerPrev
});
