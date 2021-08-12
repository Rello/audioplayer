/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2021 Marcel Scherello
 */
/** global: OC */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    OCA.Audioplayer.Dashboard.init();
})

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {};
}
OCA.Audioplayer.Player = {
    html5Audio: null,
    currentTrackIndex: 0,   // the index of the <source> list to be played
    currentPlaylist: 0,     // ID of the current playlist. Needed to recognize UI list changes
    currentTrackId: 0,      // current playing track id. Needed to recognize the current playing track in the playlist
    repeatMode: null,       // repeat mode null/single/list
    shuffleHistory: [],     // array to store the track ids which were already played. Avoid multi playback in shuffle
    shuffle: false,         // shuffle mode false/true
    trackStartPosition: 0,  // start position of a track when the player is reopened and the playback should continue
    lastSavedSecond: 0,     // last autosaved second

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
        document.getElementById('playerPlay').classList.remove('playing');
        //OCA.Audioplayer.Dashboard.indicateCurrentPlayingTrack();
    },

    /**
     * select the next track and play it
     * it is dependent on shuffle mode, repeat mode and possible end of playlist
     */
    next: function () {
        OCA.Audioplayer.Player.trackStartPosition = 0;
        OCA.Audioplayer.Player.lastSavedSecond = 0;
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

    /**
     * select the previous track and play it
     */
    prev: function () {
        OCA.Audioplayer.Player.trackStartPosition = 0;
        OCA.Audioplayer.Player.lastSavedSecond = 0;
        OCA.Audioplayer.Player.currentTrackIndex--;
        OCA.Audioplayer.Player.setTrack();
    },

    /**
     * set the track to the selected track index and check if it can be played at all
     * play/pause when the same track is selected or get a new one
     */
    setTrack: function () {
        var trackToPlay = this.html5Audio.children[this.currentTrackIndex];
        if (trackToPlay.dataset.canPlayMime === 'false') {
            this.next();
            return;
        }
        // new track to be played
        if (trackToPlay.src !== this.html5Audio.getAttribute('src')) {
            this.currentTrackId = trackToPlay.dataset.trackid;
            this.lastSavedSecond = 0;
            this.html5Audio.setAttribute('src', trackToPlay.src);
            this.html5Audio.load();
        } else if (!this.html5Audio.paused) {
            OCA.Audioplayer.Player.stop();
            return;
        }
        this.html5Audio.play();
        document.getElementById('playerPlay').classList.add('playing');
        OCA.Audioplayer.Player.indicateCurrentPlayingTrack();
    },

    indicateCurrentPlayingTrack: function () {
        //in every case, update the playbar and medaservices
        var coverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        var currentTrack = this.html5Audio.children[this.currentTrackIndex];

        if (currentTrack) {
            var addCss;
            var addDescr;
            var coverID = currentTrack.dataset.cover;
            if (coverID === 'null') {
                addCss = 'background-color: #D3D3D3;color: #333333;';
                addDescr = currentTrack.dataset.title[0];
                if ('mediaSession' in navigator) {
                    navigator.mediaSession.metadata = new MediaMetadata({
                        title: currentTrack.dataset.title,
                        artist: currentTrack.dataset.artist,
                        album: currentTrack.dataset.album,
                    });
                }
            } else {
                addCss = 'background-image:url(' + coverUrl + coverID + ');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
                addDescr = '';
                if ('mediaSession' in navigator) {
                    navigator.mediaSession.metadata = new MediaMetadata({
                        title: currentTrack.dataset.title,
                        artist: currentTrack.dataset.artist,
                        album: currentTrack.dataset.album,
                        artwork: [
                            {src: coverUrl + coverID, sizes: '192x192', type: 'image/png'},
                        ]
                    });
                }
            }
            document.getElementById('audioplayerCover').setAttribute('style', addCss);
            document.getElementById('audioplayerCover').innerText = addDescr;
        }
    },


}

/**
 * @namespace OCA.Audioplayer.Dashboard
 */
OCA.Audioplayer.Dashboard = {
    AjaxCallStatus: null,
    canPlayMimeType: [],

    init: function () {
        if (typeof OCA.Dashboard === 'object') {
            OCA.Dashboard.register('audioplayer', (el) => {
                //el.innerHTML = '<ul id="ulAudioplayer"></ul>';
                el.innerHTML = OCA.Audioplayer.Dashboard.getPlayer() +
                    OCA.Audioplayer.Dashboard.getCategoryDropdown() +
                    OCA.Audioplayer.Dashboard.getItemDropdown() +
                    OCA.Audioplayer.Dashboard.getItemCover();
                OCA.Audioplayer.Dashboard.initListeners();
            });
        }
    },

    initListeners: function () {
        document.getElementById('audiplayerCategory').addEventListener('change', OCA.Audioplayer.Dashboard.loadCategory);
        document.getElementById('audioplayerItem').addEventListener('change', OCA.Audioplayer.Dashboard.getTracks);
        document.getElementById('playerPrev').addEventListener('click', OCA.Audioplayer.Player.prev);
        document.getElementById('playerNext').addEventListener('click', OCA.Audioplayer.Player.next);
        document.getElementById('playerPlay').addEventListener('click', OCA.Audioplayer.Player.play);
        OCA.Audioplayer.Player.html5Audio = document.getElementById('html5Audio');
        OCA.Audioplayer.Player.html5Audio.addEventListener('ended', OCA.Audioplayer.Player.next, true);

        // mediaSession currently use for Chrome already to support hardware keys
        if ('mediaSession' in navigator) {
            navigator.mediaSession.setActionHandler('play', function () {
                OCA.Audioplayer.Player.play();
            });
            navigator.mediaSession.setActionHandler('pause', function () {
                OCA.Audioplayer.Player.stop();
            });
            navigator.mediaSession.setActionHandler('stop', function () {
                OCA.Audioplayer.Player.stop();
            });
            navigator.mediaSession.setActionHandler('previoustrack', function () {
                OCA.Audioplayer.Player.prev();
            });
            navigator.mediaSession.setActionHandler('nexttrack', function () {
                OCA.Audioplayer.Player.next();
            });
        }

        // evaluate if browser can play the mimetypes
        let mimeTypes = ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
        let mimeTypeAudio = document.createElement('audio');
        mimeTypes.forEach((element) => {
            if (mimeTypeAudio.canPlayType(element)) {
                OCA.Audioplayer.Dashboard.canPlayMimeType.push(element);
            }
        });
        // add playlist mimetypes
        OCA.Audioplayer.Dashboard.canPlayMimeType.push('audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml');
    },

    getPlayer: function () {
        return '<div id="" class="playerBar">'
            + '<div class="playerButton" title="' + t('analytics', 'Previous track') + '">'
            + '<div id="playerPrev" class="button previous"></div></div>'
            + '<div class="playerButton" title="' + t('analytics', 'Play/Pause') + '">'
            + '<div id="playerPlay" class="button play-pause"></div></div>'
            + '<div class="playerButton" title="' + t('analytics', 'Next track') + '">'
            + '<div id="playerNext" class="button next"></div></div><audio id="html5Audio" hidden=""></audio></div>';
    },

    getCategoryDropdown: function () {
        return '<div class="categoryBar">\n' +
            '<select id="audiplayerCategory" style="width: 180px;">\n' +
            '<option value="" selected>' + t('analytics', 'Selection') + '</option>\n' +
            '<option value="Playlist">' + t('analytics', 'Playlists') + '</option>\n' +
            '<option value="Artist">' + t('analytics', 'Artists') + '</option>\n' +
            '<option value="Album Artist">' + t('analytics', 'Album Artists') + '</option>\n' +
            '<option value="Album">' + t('analytics', 'Albums') + '</option>\n' +
            '<option value="Title">' + t('analytics', 'Titles') + '</option>\n' +
            '<option value="Genre">' + t('analytics', 'Genres') + '</option>\n' +
            '<option value="Year">' + t('analytics', 'Years') + '</option>\n' +
            '<option value="Folder">' + t('analytics', 'Folders') + '</option>\n' +
            '</select>\n' +
            '</div>\n'
    },

    getItemDropdown: function () {
        return '<div  class="itemBar">\n' +
            '<select id="audioplayerItem" style="width: 180px;">\n' +
            '</select>\n' +
            '</div>\n'
    },

    getItemCover: function () {
        return '<div class="coverBar">\n' +
            '<div id="audioplayerCover" class="cover"></div>' +
            '</div>\n'
    },

    loadCategory: function () {
        var category = document.getElementById('audiplayerCategory').value;

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
            data: {category: category},
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    let select = document.getElementById('audioplayerItem')
                    select.innerHTML = '';

                    for (var categoryData of jsondata.data) {
                        var optionElement = document.createElement('option');
                        optionElement.value = categoryData.id;
                        optionElement.innerHTML = categoryData.name;
                        select.appendChild(optionElement);
                    }

                }
            }
        });
        return true;
    },

    getTracks: function (callback, covers, albumDirectPlay) {

        if (OCA.Audioplayer.Dashboard.AjaxCallStatus !== null) {
            OCA.Audioplayer.Dashboard.AjaxCallStatus.abort();
        }

        let category = document.getElementById('audiplayerCategory').value;
        let categoryItem = document.getElementById('audioplayerItem').value;
        let player = document.getElementById('html5Audio');
        let canPlayMimeType = OCA.Audioplayer.Dashboard.canPlayMimeType;

        OCA.Audioplayer.Dashboard.AjaxCallStatus = $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/gettracks'),
            data: {category: category, categoryId: categoryItem},
            success: function (jsondata) {
                //document.getElementById('loading').style.display = 'none';
                if (jsondata.status === 'success') {

                    player.innerHTML = '';
                    for (let itemData of jsondata.data) {

                        let streamUrl;
                        if (itemData['mim'] === 'audio/mpegurl' || itemData['mim'] === 'audio/x-scpls' || itemData['mim'] === 'application/xspf+xml') {
                            streamUrl = itemData['lin'];
                        } else {
                            streamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?t=' + itemData['id'];
                        }

                        let canPlayMime
                        if (canPlayMimeType.includes(itemData['mim'])) {
                            canPlayMime = true;
                        } else {
                            canPlayMime = 'false';
                        }

                        let audioSource = document.createElement('source');
                        audioSource.src = streamUrl;
                        audioSource.dataset.trackid = itemData['id'];
                        audioSource.dataset.title = itemData['cl1'];
                        audioSource.dataset.artist = itemData['cl2'];
                        audioSource.dataset.album = itemData['cl3'];
                        audioSource.dataset.cover = itemData['cid'];
                        audioSource.dataset.canPlayMime = canPlayMime;
                        player.appendChild(audioSource);
                    }
                }
            }
        });
    },

    handleNavigationClicked: function (evt) {
        let reportId = evt.target.closest('a').parentElement.id.replace('analyticsWidgetItem', '');
        if (document.querySelector('#navigationDatasets [data-id="' + reportId + '"]') !== null) {
            document.querySelector('#navigationDatasets [data-id="' + reportId + '"]').click();
        }
    },
}