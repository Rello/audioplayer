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
        document.getElementById('audioplayerTitle').innerHTML = '';
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
            document.getElementById('playerPlay').classList.replace('APplay-pause', 'icon-loading')
            this.lastSavedSecond = 0;
            this.html5Audio.setAttribute('src', trackToPlay.src);
            this.html5Audio.load();
        } else if (!this.html5Audio.paused) {
            OCA.Audioplayer.Player.stop();
            return;
        }

        let playPromise = this.html5Audio.play();
        if (playPromise !== undefined) {
            playPromise.then(_ => {
                document.getElementById('playerPlay').classList.replace('icon-loading', 'APplay-pause');
                document.getElementById('playerPlay').classList.add('playing');
                OCA.Audioplayer.Player.indicateCurrentPlayingTrack();
            })
                .catch(error => {
                    OCA.Audioplayer.Player.stop();
                    document.getElementById('playerPlay').classList.replace('icon-loading','icon-loading');
                    //document.getElementById('playerPlay').classList.replace('APplay-pause','play');
                });
        }

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
                addCss = 'background-image:url(' + coverUrl + coverID + ');height: 180px;';
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

            let currentCount = this.currentTrackIndex+1 + '/' + this.html5Audio.childElementCount + ': ';
            document.getElementById('audioplayerTitle').innerHTML = currentCount + currentTrack.dataset.title;
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
                el.innerHTML = OCA.Audioplayer.Dashboard.buildPlayer() +
                    OCA.Audioplayer.Dashboard.buildCategoryDropdown() +
                    OCA.Audioplayer.Dashboard.buildItemDropdown() +
                    OCA.Audioplayer.Dashboard.buildCurrentTitle() +
                    OCA.Audioplayer.Dashboard.buildItemCover();
                OCA.Audioplayer.Dashboard.initActions();
            });
        }
    },

    initActions: function () {
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

    buildPlayer: function () {
        return '<div id="" class="APplayerBar">'
            + '<div class="APplayerButton" title="' + t('audioplayer', 'Previous track') + '">'
            + '<div id="playerPrev" class="APbutton button APprevious"></div></div>'
            + '<div class="APplayerButton" title="' + t('audioplayer', 'Play/Pause') + '">'
            + '<div id="playerPlay" class="APbutton button APplay-pause"></div></div>'
            + '<div class="APplayerButton" title="' + t('audioplayer', 'Next track') + '">'
            + '<div id="playerNext" class="APbutton button APnext"></div></div><audio id="html5Audio" hidden=""></audio></div>';
    },

    buildCategoryDropdown: function () {
        return '<div class="APcategoryBar">\n' +
            '<select id="audiplayerCategory" style="width: 180px;">\n' +
            '<option value="" selected>' + t('audioplayer', 'Selection') + '</option>\n' +
            '<option value="Playlist">' + t('audioplayer', 'Playlists') + '</option>\n' +
            '<option value="Album">' + t('audioplayer', 'Albums') + '</option>\n' +
            '<option value="Album Artist">' + t('audioplayer', 'Album Artists') + '</option>\n' +
            '<option value="Artist">' + t('audioplayer', 'Artists') + '</option>\n' +
            '<option value="Folder">' + t('audioplayer', 'Folders') + '</option>\n' +
            '<option value="Genre">' + t('audioplayer', 'Genres') + '</option>\n' +
            '<option value="Title">' + t('audioplayer', 'Titles') + '</option>\n' +
            '<option value="Tags">' + t('audioplayer', 'Tags') + '</option>' +
            '<option value="Year">' + t('audioplayer', 'Years') + '</option>\n' +
            '</select>\n' +
            '</div>\n'
    },

    buildItemDropdown: function () {
        return '<div  class="APitemBar">\n' +
            '<select id="audioplayerItem" style="width: 180px;">\n' +
            '</select>\n' +
            '</div>\n'
    },

    buildItemCover: function () {
        return '<div class="APcoverBar">\n' +
            '<div id="audioplayerLoading" style="text-align:center; padding-top:100px" class="icon-loading" hidden></div>' +
            '<div id="audioplayerCover" class="cover"></div>' +
            '</div>\n'
    },

    buildCurrentTitle: function () {
        return '<div class="APtitleBar">\n' +
            '<div id="audioplayerTitle" style="width: 180px;">\n' +
            '</div>\n' +
            '</div>\n'
    },

    showElement: function (element) {
        if (document.getElementById(element)) {
            document.getElementById(element).hidden = false;
        }
    },

    hideElement: function (element) {
        if (document.getElementById(element)) {
            document.getElementById(element).hidden = true;
        }
    },

    loadCategory: function () {
        var category = document.getElementById('audiplayerCategory').value;
        OCA.Audioplayer.Dashboard.showElement('audioplayerLoading');

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
            data: {category: category},
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    let select = document.getElementById('audioplayerItem')
                    select.innerHTML = '<option value="" selected>' + t('audioplayer', 'Selection') + '</option>';

                    for (var categoryData of jsondata.data) {
                        var optionElement = document.createElement('option');
                        optionElement.value = categoryData.id;
                        optionElement.innerHTML = categoryData.name;
                        select.appendChild(optionElement);
                    }
                    OCA.Audioplayer.Dashboard.hideElement('audioplayerLoading');
                }
            }
        });
        return true;
    },

    getTracks: function (callback, covers, albumDirectPlay) {

        OCA.Audioplayer.Dashboard.showElement('audioplayerLoading');
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
                            jsondata.data = [];
                            break;
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
                    document.getElementById('audioplayerTitle').innerHTML = jsondata.data.length + ' ' + t('audioplayer', 'Titles');
                } else {
                    document.getElementById('audioplayerTitle').innerHTML = t('audioplayer', 'No data');
                }
                OCA.Audioplayer.Dashboard.hideElement('audioplayerLoading');
                document.getElementById('audioplayerCover').removeAttribute('style');
                document.getElementById('audioplayerCover').innerText = '';
                OCA.Audioplayer.Player.currentTrackIndex = 0;
            }
        });
    },
}