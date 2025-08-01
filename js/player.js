/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
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
    html5Audio: document.getElementById('html5Audio'), // the <audio> element
    preloadAudio: document.getElementById('audioPreload'), // hidden audio element for preloading
    currentTrackIndex: 0,   // the index of the <source> list to be played
    currentPlaylist: 0,     // ID of the current playlist. Needed to recognize UI list changes
    currentTrackId: 0,      // current playing track id. Needed to recognize the current playing track in the playlist
    repeatMode: null,       // repeat mode null/single/list
    trackStartPosition: 0,  // start position of a track when the player is reopened and the playback should continue
    lastSavedSecond: 0,     // last autosaved second
    playbackSpeeds: [1, 2, 3, 0.5],
    currentSpeedIndex: 0,
    waveformData: null,     // cached waveform data for the current track
    _audioCtx: null,        // AudioContext instance used for waveform decoding

    /**
     * set the track to the selected track index and check if it can be played at all
     * play/pause when the same track is selected or get a new one
     */
    setTrack: function () {
        let trackToPlay = this.html5Audio.children[this.currentTrackIndex];
        if (trackToPlay.dataset.canPlayMime === 'false') {
            this.next();
            return;
        }
        // new track to be played
        if (trackToPlay.src !== this.html5Audio.getAttribute('src')) {
            document.getElementById('playerPlay').classList.replace('play-pause', 'icon-loading')
            this.currentTrackId = trackToPlay.dataset.trackid;
            OCA.Audioplayer.Core.CategorySelectors[2] = trackToPlay.dataset.trackid;
            this.lastSavedSecond = 0;
            this.html5Audio.setAttribute('src', trackToPlay.src);
            this.html5Audio.load();
            this.drawWaveform(trackToPlay.src);
            this.html5Audio.playbackRate =
                this.playbackSpeeds[this.currentSpeedIndex];
        } else if (!OCA.Audioplayer.Player.isPaused()) {
            OCA.Audioplayer.Player.stop();
            return;
        }
        let playPromise = this.html5Audio.play();
        if (playPromise !== undefined) {
            let _this = this;
            playPromise.then(function() {
                document.getElementById('playerPlay').classList.replace('icon-loading', 'play-pause');
                document.getElementById('sm2-bar-ui').classList.add('playing');
                OCA.Audioplayer.UI.indicateCurrentPlayingTrack();
                _this.preloadNext();
            }).catch(function(error) {
                document.getElementById('playerPlay').classList.replace('icon-loading','play-pause');
                OCP.Toast.error(t('audioplayer', 'Playback error'));
            });
        } else {
            this.preloadNext();
        }
    },

    /**
     * set track and play it
     */
    play: function () {
        OCA.Audioplayer.Player.setTrack();
    },

    /**
     * stop the playback and update the UI with the paused track
     */
    stop: function () {
        this.html5Audio.pause();
        document.getElementById('playerPlay').classList.replace('icon-loading','play-pause');
        document.getElementById('playerPlay').classList.replace('play','play-pause');
        document.getElementById('sm2-bar-ui').classList.remove('playing');
        OCA.Audioplayer.UI.indicateCurrentPlayingTrack();
    },

    /**
     * pause => stop the playback
     */
    pause: function () {
        this.stop();
    },

    /**
     * select the next track and play it
     * it is dependent on repeat mode and possible end of playlist
     */
    next: function () {
        OCA.Audioplayer.Player.trackStartPosition = 0;
        OCA.Audioplayer.Player.lastSavedSecond = 0;
        let numberOfTracks = OCA.Audioplayer.Player.html5Audio.childElementCount - 1; // index stats counting at 0
        if (OCA.Audioplayer.Player.currentTrackIndex === numberOfTracks) {
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

    /**
     * select the previous track and play it
     */
    prev: function () {
        OCA.Audioplayer.Player.trackStartPosition = 0;
        OCA.Audioplayer.Player.lastSavedSecond = 0;
        if (OCA.Audioplayer.Player.currentTrackIndex === 0) {
            if (OCA.Audioplayer.Player.repeatMode === 'list') {
                OCA.Audioplayer.Player.currentTrackIndex = OCA.Audioplayer.Player.html5Audio.childElementCount - 1;
                OCA.Audioplayer.Player.setTrack();
            } else {
                OCA.Audioplayer.Player.stop();
            }
        } else {
            OCA.Audioplayer.Player.currentTrackIndex--;
            OCA.Audioplayer.Player.setTrack();
        }
    },

    /**
     * preload the next track for gapless playback
     */
    preloadNext: function () {
        const nextIndex = OCA.Audioplayer.Player.currentTrackIndex + 1;
        const preload = OCA.Audioplayer.Player.preloadAudio;
        if (nextIndex < OCA.Audioplayer.Player.html5Audio.childElementCount) {
            const nextTrack = OCA.Audioplayer.Player.html5Audio.children[nextIndex];
            if (nextTrack.dataset.canPlayMime !== 'false') {
                preload.pause();
                preload.src = nextTrack.src;
                preload.load();
            }
        } else {
            preload.removeAttribute('src');
        }
    },

    /**
     * toggle the repeat mode off->single->list->off
     */
    setRepeat: function (overwrite) {
        let repeatIcon = document.getElementById('playerRepeat');

        if (overwrite === 'single') {
            OCA.Audioplayer.Player.repeatMode = null;
        } else if (overwrite === 'list') {
            OCA.Audioplayer.Player.repeatMode = 'single';
        }

        if (OCA.Audioplayer.Player.repeatMode === null) {
            OCA.Audioplayer.Player.html5Audio.loop = true;
            OCA.Audioplayer.Player.repeatMode = 'single';
            repeatIcon.classList.remove('repeat');
            repeatIcon.classList.add('repeat-single');
            repeatIcon.style.opacity = '1';
            OCA.Audioplayer.Backend.setUserValue('repeat', 'single');
        } else if (OCA.Audioplayer.Player.repeatMode === 'single') {
            OCA.Audioplayer.Player.html5Audio.loop = false;
            OCA.Audioplayer.Player.repeatMode = 'list';
            repeatIcon.classList.add('repeat');
            repeatIcon.classList.remove('repeat-single');
            repeatIcon.style.opacity = '1';
            OCA.Audioplayer.Backend.setUserValue('repeat', 'list');
        } else {
            OCA.Audioplayer.Player.repeatMode = null;
            repeatIcon.style.removeProperty('opacity');
            OCA.Audioplayer.Backend.setUserValue('repeat', 'none');
        }
    },

    /**
     * toggle the shuffle mode true->false->true
     */
    shuffleTitles: function () {
        let playlist = document.getElementById('individual-playlist');

        let classes = document.getElementById('view-toggle').classList;
        if (classes.contains('icon-toggle-pictures')) {
            return;
        }

            let children = [].slice.call(playlist.childNodes);
        [].sort.call(children, function () {
            return 0.5 - Math.random();
        });
        children.forEach(function (child) {
            playlist.appendChild(child);
        });

        let playlistItems = document.querySelectorAll('.albumwrapper li');
        OCA.Audioplayer.Player.addTracksToSourceList(playlistItems);
    },

    /**
     * set the playback volume
     */
    setVolume: function () {
        OCA.Audioplayer.Player.html5Audio.volume = document.getElementById('playerVolume').value;
        OCA.Audioplayer.Backend.setUserValue('volume', document.getElementById('playerVolume').value   );
    },

    /**
     * get the playback volume
     */
    getVolume: function () {
        return OCA.Audioplayer.Player.html5Audio.volume;
    },

    /**
     * set playback speed and update display
     */
    setSpeed: function (speed) {
        OCA.Audioplayer.Player.html5Audio.playbackRate = speed;
        document.getElementById('playerSpeed').textContent = speed + 'x';
    },

    /**
     * toggle between predefined playback speeds
     */
    toggleSpeed: function () {
        OCA.Audioplayer.Player.currentSpeedIndex = (OCA.Audioplayer.Player.currentSpeedIndex + 1) % OCA.Audioplayer.Player.playbackSpeeds.length;
        let speed = OCA.Audioplayer.Player.playbackSpeeds[OCA.Audioplayer.Player.currentSpeedIndex];
        OCA.Audioplayer.Player.setSpeed(speed);
        OCA.Audioplayer.Backend.setUserValue('speed', speed.toString());
    },

    /**
     * check, if the audio element is currently paused
     */
    isPaused: function () {
        return this.html5Audio.paused;
    },

    /**
     * take the playlist from the frontend and add then as source-elements to the audio tag
     * @param playlistItems
     */
    addTracksToSourceList: function (playlistItems) {
        OCA.Audioplayer.Player.html5Audio.innerHTML = '';
        for (let i = 0; i < playlistItems.length; ++i) {
            let audioSource = document.createElement('source');
            audioSource.src = playlistItems[i].firstChild.href;
            audioSource.dataset.trackid = playlistItems[i].dataset.trackid;
            audioSource.dataset.canPlayMime = playlistItems[i].dataset.canPlayMime;
            audioSource.dataset.title = playlistItems[i].dataset.title;
            audioSource.dataset.artist = playlistItems[i].dataset.artist;
            audioSource.dataset.album = playlistItems[i].dataset.album;
            audioSource.dataset.cover = playlistItems[i].dataset.cover;
            OCA.Audioplayer.Player.html5Audio.appendChild(audioSource);
        }
    },

    /**
     * decode the audio source and draw its waveform to the progress canvas
     * @param src
     */
    drawWaveform: async function (src) {
        const canvas = document.getElementById('progressBar');
        if (!canvas.getContext) {
            return;
        }
        if (!this._audioCtx) {
            this._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        try {
            const response = await fetch(src);
            const arrayBuffer = await response.arrayBuffer();
            const audioBuffer = await this._audioCtx.decodeAudioData(arrayBuffer);
            const rawData = audioBuffer.getChannelData(0);
            const samples = canvas.width;
            const blockSize = Math.floor(rawData.length / samples);
            const filteredData = [];
            let maxVal = 0;
            for (let i = 0; i < samples; i++) {
                let sum = 0;
                for (let j = 0; j < blockSize; j++) {
                    sum += Math.abs(rawData[(i * blockSize) + j]);
                }
                let value = sum / blockSize;
                filteredData.push(value);
                if (value > maxVal) {
                    maxVal = value;
                }
            }
            if (maxVal > 0) {
                for (let i = 0; i < filteredData.length; i++) {
                    filteredData[i] = filteredData[i] / maxVal;
                }
            }
            this.waveformData = filteredData;
        } catch (e) {
            this.waveformData = null;
        }
    },

    /**
     * Set the progress bar to the current playtime
     */
    initProgressBar: function () {
        let player = OCA.Audioplayer.Player.html5Audio;
        let canvas = document.getElementById('progressBar');
        if (player.currentTime !== 0) {
            document.getElementById('startTime').textContent =
                OCA.Audioplayer.Player.formatSecondsToTime(player.currentTime);
            document.getElementById('endTime').textContent =
                OCA.Audioplayer.Player.formatSecondsToTime(player.duration);
        } else {
            // document.getElementById('startTime').innerHTML = t('audioplayer', 'loading');
            // document.getElementById('endTime').innerHTML = '';
        }

        let elapsedTime = Math.round(player.currentTime);
        if (canvas.getContext) {
            let ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
            let progressValue = (elapsedTime / player.duration);
            if (OCA.Audioplayer.Player.waveformData) {
                const samples = OCA.Audioplayer.Player.waveformData.length;
                const progressSamples = Math.floor(progressValue * samples);
                ctx.fillStyle = 'rgb(180,180,180)';
                for (let i = 0; i < samples; i++) {
                    const val = OCA.Audioplayer.Player.waveformData[i];
                    const height = val * canvas.clientHeight;
                    ctx.fillRect(i, (canvas.clientHeight - height) / 2, 1, height);
                }
                ctx.fillStyle = 'rgb(0,130,201)';
                for (let i = 0; i < progressSamples; i++) {
                    const val = OCA.Audioplayer.Player.waveformData[i];
                    const height = val * canvas.clientHeight;
                    ctx.fillRect(i, (canvas.clientHeight - height) / 2, 1, height);
                }
            } else {
                ctx.fillStyle = 'rgb(0,130,201)';
                let fWidth = progressValue * canvas.clientWidth;
                if (fWidth > 0) {
                    ctx.fillRect(0, 0, fWidth, canvas.clientHeight);
                }
            }
        }

        // save position every 10 seconds
        let positionCalc = Math.round(player.currentTime) / 10;
        if (Math.round(positionCalc) === positionCalc && positionCalc !== 0 && OCA.Audioplayer.Player.lastSavedSecond !== positionCalc) {
            OCA.Audioplayer.Player.lastSavedSecond = Math.round(positionCalc);
            OCA.Audioplayer.Backend.setUserValue('category',
                OCA.Audioplayer.Core.CategorySelectors[0]
                + '-' + OCA.Audioplayer.Core.CategorySelectors[1]
                + '-' + OCA.Audioplayer.Core.CategorySelectors[2]
                + '-' + Math.round(player.currentTime)
            );
        }

    },

    /**
     * set the tracktime when the progressbar is moved
     * @param evt
     */
    seek: function (evt) {
        let progressbar = document.getElementById('progressBar');
        let player = OCA.Audioplayer.Player.html5Audio;
        player.currentTime = player.duration * (evt.offsetX / progressbar.clientWidth);
    },

    /**
     * calculate a time in the format of 00:00 for the progress
     * @param value
     * @return string
     */
    formatSecondsToTime: function (value) {
        if (value <= 0 || isNaN(value)) {
            return '0:00';
        }
        value = Math.floor(value);
        let hours = Math.floor(value / 3600),
            minutes = Math.floor(value / 60 % 60),
            seconds = (value % 60);
        return (hours !== 0 ? String(hours) + ':' : '') + (hours !== 0 ? String(minutes).padStart(2, '0') : String(minutes)) + ':' + String(seconds).padStart(2, '0');
    },

    /**
     * get the currently playing track and provide its data (dataset) to e.g. playbar or sidebar
     * @return Element
     */
    getCurrentPlayingTrackInfo: function () {
        return this.html5Audio.children[this.currentTrackIndex];
    },
};

document.addEventListener('DOMContentLoaded', function () {
    OCA.Audioplayer.Player.html5Audio.addEventListener('ended', OCA.Audioplayer.Player.next, true);
    OCA.Audioplayer.Player.html5Audio.addEventListener('timeupdate', OCA.Audioplayer.Player.initProgressBar, true);
    OCA.Audioplayer.Player.html5Audio.addEventListener('canplay', function () {
        if (parseInt(OCA.Audioplayer.Player.trackStartPosition) !== 0 && OCA.Audioplayer.Player.html5Audio.currentTime !== parseInt(OCA.Audioplayer.Player.trackStartPosition)) {
            OCA.Audioplayer.Player.html5Audio.pause();
            OCA.Audioplayer.Player.html5Audio.currentTime = parseInt(OCA.Audioplayer.Player.trackStartPosition);
            OCA.Audioplayer.Player.html5Audio.play();
            OCA.Audioplayer.Player.trackStartPosition = 0; // reset the time to avoid that is being set again and again when seeking
        }
        document.getElementById('startTime').style.visibility = 'visible';
        document.getElementById('endTime').style.visibility = 'visible';
    });

    document.getElementById('progressBar').addEventListener('click', OCA.Audioplayer.Player.seek, true);
    document.getElementById('playerPrev').addEventListener('click', OCA.Audioplayer.Player.prev);
    document.getElementById('playerNext').addEventListener('click', OCA.Audioplayer.Player.next);
    document.getElementById('playerPlay').addEventListener('click', OCA.Audioplayer.Player.play);
    document.getElementById('playerRepeat').addEventListener('click', OCA.Audioplayer.Player.setRepeat);
    document.getElementById('playerShuffle').addEventListener('click', OCA.Audioplayer.Player.shuffleTitles);
    document.getElementById('playerVolume').addEventListener('input', OCA.Audioplayer.Player.setVolume);
    document.getElementById('playerVolume').value = document.getElementById('audioplayer_volume').value;

    document.getElementById('playerSpeed').addEventListener('click', OCA.Audioplayer.Player.toggleSpeed);
    let speedValue = parseFloat(document.getElementById('audioplayer_speed').value);
    let index = OCA.Audioplayer.Player.playbackSpeeds.indexOf(speedValue);
    if (index !== -1) {
        OCA.Audioplayer.Player.currentSpeedIndex = index;
    }
    OCA.Audioplayer.Player.setSpeed(OCA.Audioplayer.Player.playbackSpeeds[OCA.Audioplayer.Player.currentSpeedIndex]);

    let repeat = document.getElementById('audioplayer_repeat').value;
    if (repeat !== 'none') {
        OCA.Audioplayer.Player.setRepeat(repeat);
    }
    OCA.Audioplayer.Player.setVolume();
});
