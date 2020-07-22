/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2020 Marcel Scherello
 */

'use strict';

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {};
}

/**
 * @namespace OCA.Audioplayer.Player
 */
OCA.Audioplayer.Player = {
    html5Audio: document.getElementById('html5Audio'),
    currentTrackIndex: 0,
    currentPlaylist: 0,
    currentTrackId: 0,
    repeatMode: null,
    shuffleHistory: [],
    shuffle: false,
    trackStartPosition: 0,

    setTrack: function () {
        var trackToPlay = this.html5Audio.children[this.currentTrackIndex];
        if (trackToPlay.dataset.canPlayMime === 'false') {
            this.next();
            return;
        }
        // new track to be played
        if (trackToPlay.src !== this.html5Audio.getAttribute('src')) {
            this.currentTrackId = trackToPlay.dataset.trackid;
            OCA.Audioplayer.Core.CategorySelectors[2] = trackToPlay.dataset.trackid;
            this.html5Audio.setAttribute("src", trackToPlay.src);
            this.html5Audio.load();
        } else if (!OCA.Audioplayer.Player.isPaused()) {
            OCA.Audioplayer.Player.stop();
            return;
        }
        this.html5Audio.play();
        document.getElementById('sm2-bar-ui').classList.add('playing');
        OCA.Audioplayer.UI.indicateCurrentPlayingTrack();
    },

    play: function () {
            OCA.Audioplayer.Player.setTrack();
    },

    stop: function () {
        this.html5Audio.pause();
        document.getElementById('sm2-bar-ui').classList.remove('playing');
        OCA.Audioplayer.UI.indicateCurrentPlayingTrack();
    },

    pause: function () {
        this.stop();
    },

    next: function () {
        var numberOfTracks = OCA.Audioplayer.Player.html5Audio.childElementCount - 1; // index stats counting at 0
        if (OCA.Audioplayer.Player.shuffle === true) {
            // shuffle => get random track index
            var minimum = 0;
            var maximum = numberOfTracks;
            var randomIndex = 0;
            var foundPlayedTrack = false;

            if (OCA.Audioplayer.Player.shuffleHistory.length === OCA.Audioplayer.Player.html5Audio.childElementCount) {
                OCA.Audioplayer.Player.stop();
                OCA.Audioplayer.Player.shuffleHistory = [];
                return;
            }

            do {
                randomIndex = Math.floor(Math.random() * (maximum - minimum + 1)) + minimum;
                foundPlayedTrack = OCA.Audioplayer.Player.shuffleHistory.includes(randomIndex);
            } while (foundPlayedTrack === true);

            OCA.Audioplayer.Player.currentTrackIndex = randomIndex;
            OCA.Audioplayer.Player.shuffleHistory.push(randomIndex);
            OCA.Audioplayer.Player.setTrack();
        } else if (OCA.Audioplayer.Player.currentTrackIndex === numberOfTracks) {
            // if end is reached, either stop or restart the list
            if (OCA.Audioplayer.Player.repeatMode === 'list') {
                OCA.Audioplayer.Player.currentTrackIndex = 0;
                OCA.Audioplayer.Player.setTrack();
            } else {
                OCA.Audioplayer.Player.stop();
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

    setRepeat: function () {
        var repeatIcon = document.getElementById('playerRepeat');
        if (OCA.Audioplayer.Player.repeatMode === null) {
            OCA.Audioplayer.Player.html5Audio.loop = true;
            OCA.Audioplayer.Player.repeatMode = 'single';
            repeatIcon.classList.remove('repeat');
            repeatIcon.classList.add('repeat-single');
            repeatIcon.style.opacity = 1;
        } else if (OCA.Audioplayer.Player.repeatMode === 'single') {
            OCA.Audioplayer.Player.html5Audio.loop = false;
            OCA.Audioplayer.Player.repeatMode = 'list';
            repeatIcon.classList.add('repeat');
            repeatIcon.classList.remove('repeat-single');
        } else {
            OCA.Audioplayer.Player.repeatMode = null;
            repeatIcon.style.removeProperty('opacity');
        }
    },

    setShuffle: function () {
        if (OCA.Audioplayer.Player.shuffle === false) {
            OCA.Audioplayer.Player.shuffle = true;
            document.getElementById('playerShuffle').style.opacity = 1;
        } else {
            OCA.Audioplayer.Player.shuffle = false;
            document.getElementById('playerShuffle').style.removeProperty('opacity');
        }
    },

    isPaused: function () {
        return this.html5Audio.paused;
    },

    addTracksToSourceList: function (playlistItems) {
        OCA.Audioplayer.Player.html5Audio.innerHTML = '';
        for (var i = 0; i < playlistItems.length; ++i) {
            var audioSource = document.createElement('source');
            audioSource.src = playlistItems[i].firstChild.href;
            audioSource.dataset.trackid = playlistItems[i].dataset.trackid;
            audioSource.dataset.canPlayMime = playlistItems[i].dataset.canPlayMime;
            OCA.Audioplayer.Player.html5Audio.appendChild(audioSource);
        }
    },

    initProgressBar: function () {
        var player = OCA.Audioplayer.Player.html5Audio;
        var canvas = document.getElementById('progressBar');
        document.getElementById('startTime').innerHTML = OCA.Audioplayer.Player.formatSecondsToTime(player.currentTime) + '&nbsp;/&nbsp;';
        document.getElementById('endTime').innerHTML = OCA.Audioplayer.Player.formatSecondsToTime(player.duration) + '&nbsp;&nbsp;';

        var elapsedTime = Math.round(player.currentTime);
        if (canvas.getContext) {
            var ctx = canvas.getContext("2d");
            ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
            ctx.fillStyle = "rgb(0,130,201)";
            var progressValue = (elapsedTime / player.duration);
            var fWidth = progressValue * canvas.clientWidth;
            if (fWidth > 0) {
                ctx.fillRect(0, 0, fWidth, canvas.clientHeight);
            }
        }

        // save position every 10 seconds
        var positionCalc = Math.round(player.currentTime) / 10;
        if (Math.round(positionCalc) === positionCalc) {
            OCA.Audioplayer.Backend.setUserValue('category',
                OCA.Audioplayer.Core.CategorySelectors[0]
                + '-' + OCA.Audioplayer.Core.CategorySelectors[1]
                + '-' + OCA.Audioplayer.Core.CategorySelectors[2]
                + '-' + Math.round(player.currentTime)
            );
        }

    },

    seek: function (evt) {
        var progressbar = document.getElementById('progressBar');
        var player = OCA.Audioplayer.Player.html5Audio;
        player.currentTime = player.duration * (evt.offsetX / progressbar.clientWidth);
    },

    formatSecondsToTime: function (seconds) {
        if (seconds <= 0) return "00:00";
        var hours = Math.floor(seconds / 3600),
            minutes = Math.floor(seconds / 60 % 60),
            seconds = (seconds % 60),
            time = (hours !== 0 ? String(hours).padStart(2, '0')+ ":" : "") + String(minutes).padStart(2, '0') + ":" + String(seconds).padStart(2, '0');
        return time;
    },
}

document.addEventListener('DOMContentLoaded', function () {

    OCA.Audioplayer.Player.html5Audio.addEventListener('ended', OCA.Audioplayer.Player.next,true);
    OCA.Audioplayer.Player.html5Audio.addEventListener('timeupdate', OCA.Audioplayer.Player.initProgressBar, true);
    OCA.Audioplayer.Player.html5Audio.addEventListener('canplay', function() {
        if (OCA.Audioplayer.Player.html5Audio.currentTime !== parseInt(OCA.Audioplayer.Player.trackStartPosition) && parseInt(OCA.Audioplayer.Player.trackStartPosition) !== 0) {
            OCA.Audioplayer.Player.html5Audio.pause();
            OCA.Audioplayer.Player.html5Audio.currentTime = parseInt(OCA.Audioplayer.Player.trackStartPosition);
            OCA.Audioplayer.Player.html5Audio.play();
        }
    });

    document.getElementById('progressBar').addEventListener("click", OCA.Audioplayer.Player.seek, true);

    document.getElementById('playerPrev').addEventListener('click', OCA.Audioplayer.Player.prev);
    document.getElementById('playerNext').addEventListener('click', OCA.Audioplayer.Player.next);
    document.getElementById('playerPlay').addEventListener('click', OCA.Audioplayer.Player.play);
    document.getElementById('playerRepeat').addEventListener('click', OCA.Audioplayer.Player.setRepeat);
    document.getElementById('playerShuffle').addEventListener('click', OCA.Audioplayer.Player.setShuffle);
});
