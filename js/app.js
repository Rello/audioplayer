/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2021 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

/* global OCA, OCP, OC, t, generateUrl, _, MediaMetadata, Sonos, playSonos, requestToken */
'use strict';

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {};
}

/**
 * @namespace OCA.Audioplayer.Core
 */
OCA.Audioplayer.Core = {

    initialDocumentTitle: null,
    CategorySelectors: [],
    AjaxCallStatus: null,
    canPlayMimeType: [],
    drag: null,

    init: function () {
        OCA.Audioplayer.Core.initialDocumentTitle = document.title;
        OCA.Audioplayer.UI.EmptyContainer = document.getElementById('empty-container');
        OCA.Audioplayer.UI.PlaylistContainer = $('#playlist-container'); //keep for bar-ui as it is still using jquery
        OCA.Audioplayer.UI.getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?t=';

        if (decodeURI(location.hash).length > 1) {
            OCA.Audioplayer.Core.processSearchResult();
        } else {
            // read saved values from user values
            OCA.Audioplayer.Backend.getUserValue('category', OCA.Audioplayer.Core.processCategoryFromPreset);
        }

        // evaluate if browser can play the mimetypes
        let mimeTypes = ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
        let mimeTypeAudio = document.createElement('audio');
        mimeTypes.forEach((element) => {
            if (mimeTypeAudio.canPlayType(element)) {
                OCA.Audioplayer.Core.canPlayMimeType.push(element);
            }
        });
        // add playlist mimetypes
        OCA.Audioplayer.Core.canPlayMimeType.push('audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml');
    },

    initKeyListener: function () {
        document.body.addEventListener('keydown', function (e) {
            if (e.target) {
                let nodeName = e.target['nodeName'].toUpperCase();
                //don't activate shortcuts when the user is in an input, textarea or select element
                if (nodeName === 'INPUT' || nodeName === 'TEXTAREA' || nodeName === 'SELECT') {
                    return;
                }
            }

            if (OCA.Audioplayer.Player) {
                let currentVolume;
                let newVolume;
                switch (e.key) {
                    case ' ':
                        if (document.getElementById('sm2-bar-ui').classList.contains('playing')) {
                            OCA.Audioplayer.Player.pause();
                        } else {
                            OCA.Audioplayer.Player.play();
                        }
                        e.preventDefault();
                        break;
                    case 'ArrowRight':
                        OCA.Audioplayer.Player.next();
                        break;
                    case 'ArrowLeft':
                        OCA.Audioplayer.Player.prev();
                        break;
                    case 'ArrowUp':
                        currentVolume = OCA.Audioplayer.Player.getVolume();
                        if (currentVolume < 1) {
                            newVolume = Math.min(currentVolume + 0.1, 1);
                            OCA.Audioplayer.Player.setVolume(newVolume);
                        }
                        e.preventDefault();
                        break;
                    case 'ArrowDown':
                        currentVolume = OCA.Audioplayer.Player.getVolume();
                        if (currentVolume > 0) {
                            newVolume = Math.max(currentVolume - 0.1, 0);
                            OCA.Audioplayer.Player.setVolume(newVolume);
                        }
                        e.preventDefault();
                        break;
                }
            }
        });
    },

    processSearchResult: function () {
        let locHash = decodeURI(location.hash).substring(1);
        let locHashTemp = locHash.split('-');

        document.getElementById('searchresults').classList.add('hidden');
        window.location.href = '#';
        OCA.Audioplayer.Core.CategorySelectors = locHashTemp;
        OCA.Audioplayer.Core.processCategoryFromPreset();
    },

    processCategoryFromPreset: function () {
        if (OCA.Audioplayer.Core.CategorySelectors[0] === 'Albums' || OCA.Audioplayer.Core.CategorySelectors[0] == null) {
            OCA.Audioplayer.Core.CategorySelectors[0] = 'Title';
            OCA.Audioplayer.Core.CategorySelectors[1] = '0';
        }
        document.getElementById('category_selector').value = OCA.Audioplayer.Core.CategorySelectors[0];
        OCA.Audioplayer.Category.load(OCA.Audioplayer.Core.selectCategoryItemFromPreset);
    },

    selectCategoryItemFromPreset: function () {
        if (OCA.Audioplayer.Core.CategorySelectors[1]) {
            let activeItem = document.querySelector('#myCategory li[data-id="' + OCA.Audioplayer.Core.CategorySelectors[1] + '"]');
            activeItem.classList.add('active');
            activeItem.scrollIntoView({behavior: 'smooth', block: 'center',});

            OCA.Audioplayer.Category.handleCategoryClicked(null, function () {                        // select the last played title
                if (OCA.Audioplayer.Core.CategorySelectors[2]) {
                    let item = $('#individual-playlist li[data-trackid="' + OCA.Audioplayer.Core.CategorySelectors[2] + '"]');
                    //item.find('.icon').hide();
                    //item.find('.ioc').removeClass('ioc-volume-up').addClass('ioc-volume-off').show();
                    document.querySelector('#individual-playlist li[data-trackid="' + OCA.Audioplayer.Core.CategorySelectors[2] + '"]').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                    if (OCA.Audioplayer.Core.CategorySelectors[3]) {
                        // if the title was previously played, the last position will be set
                        OCA.Audioplayer.Player.trackStartPosition = OCA.Audioplayer.Core.CategorySelectors[3];
                    }
                }
            });
        }
    },

    toggleFavorite: function (evt) {
        if (OCA.Audioplayer.Core.CategorySelectors[1][0] === 'S') {
            return;
        }
        let target = evt.target;
        let trackId = target.getAttribute('data-trackid');
        let isFavorite = OCA.Audioplayer.UI.toggleFavorite(target, trackId);
        OCA.Audioplayer.Backend.favoriteUpdate(trackId, isFavorite);
    }

};

/**
 * @namespace OCA.Audioplayer.Cover
 */
OCA.Audioplayer.Cover = {

    load: function (category, categoryId) {
        document.getElementById('playlist-container').style.display = 'block';
        document.getElementById('empty-container').style.display = 'none';
        document.getElementById('loading').style.display = 'block';
        if (!categoryId) {
            document.querySelector('#myCategory .active').classList.remove('active');
            document.getElementById('newPlaylist').classList.add('ap_hidden');
        }
        document.getElementById('individual-playlist') ? document.getElementById('individual-playlist').remove() : false;
        document.getElementById('individual-playlist-info').style.display = 'none';
        document.getElementById('individual-playlist-header').style.display = 'none';
        document.querySelector('.coverrow') ? document.querySelector('.coverrow').remove() : false;
        document.querySelector('.songcontainer') ? document.querySelector('.songcontainer').remove() : false;

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitemcovers'),
            data: {category: category, categoryId: categoryId},
            success: function (jsondata) {
                document.getElementById('loading').style.display = 'none';
                if (jsondata.status === 'success') {
                    document.getElementById('sm2-bar-ui').style.display = 'block';
                    OCA.Audioplayer.Cover.buildCoverRow(jsondata.data);
                }
            }
        });
    },

    buildCoverRow: function (aAlbums) {
        let getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        let divRow = document.createElement('div');
        divRow.classList.add('coverrow');

        for (let album of aAlbums) {
            let addCss;
            let addDescr;
            if (!album['cid']) {
                addCss = 'background-color: #D3D3D3;color: #333333;';
                addDescr = album.name[0];
            } else {
                addDescr = '';
                addCss = 'background-image:url(' + getcoverUrl + album['cid'] + ');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
            }

            let divAlbum = document.createElement('div');
            divAlbum.classList.add('album');
            divAlbum.setAttribute('style', 'margin-left: 15px');
            divAlbum.dataset.album = album.id;
            divAlbum.dataset.name = album.name;
            divAlbum.addEventListener('click', OCA.Audioplayer.Cover.handleCoverClicked);

            let divPlayImage = document.createElement('div');
            divPlayImage.setAttribute('id', 'AlbumPlay');
            divPlayImage.addEventListener('click', OCA.Audioplayer.Cover.handleCoverClicked);

            let divAlbumCover = document.createElement('div');
            divAlbumCover.classList.add('albumcover');
            divAlbumCover.setAttribute('style', addCss);
            divAlbumCover.innerText = addDescr;

            let divAlbumDescr = document.createElement('div');
            divAlbumDescr.classList.add('albumdescr');
            divAlbumDescr.innerHTML = '<span class="albumname">' + album.name + '</span><span class="artist">' + album['art'] + '</span>';

            divAlbum.appendChild(divAlbumCover);
            divAlbum.appendChild(divAlbumDescr);
            divAlbum.appendChild(divPlayImage);
            divRow.appendChild(divAlbum);
        }
        document.getElementById('playlist-container').appendChild(divRow);
    },

    handleCoverClicked: function (evt) {
        evt.stopPropagation();
        evt.preventDefault();

        let eventTarget = evt.target;
        let AlbumId = eventTarget.parentNode.dataset.album;
        let activeAlbum = document.querySelector('.album[data-album="' + AlbumId + '"]');

        if (activeAlbum.classList.contains('is-active')) {
            $('.songcontainer').slideUp(200, function () {
                activeAlbum.getElementsByClassName('artist')[0].style.visibility = 'visible';
                activeAlbum.classList.remove('is-active');
            });
            return true;
        }

        document.getElementById('playlist-container').dataset.playlist = 'Albums-' + AlbumId;

        if (document.querySelector('.is-active')) {
            document.querySelector('.is-active').getElementsByClassName('artist')[0].style.visibility = 'visible';
            document.querySelector('.is-active').classList.remove('is-active');
        }

        activeAlbum.classList.add('is-active');
        activeAlbum.getElementsByClassName('artist')[0].style.visibility = 'hidden';
        OCA.Audioplayer.Cover.buildSongContainer(eventTarget);
    },

    buildSongContainer: function (eventTarget) {
        let albumDirectPlay = eventTarget.id === 'AlbumPlay';
        let activeAlbum = document.querySelector('.is-active');
        let AlbumId = activeAlbum.dataset.album;
        let AlbumName = activeAlbum.dataset.name;
        let iArrowLeft = 72;

        if (document.querySelector('.songcontainer')) {
            document.querySelector('.songcontainer').remove();
        }
        let divSongContainer = document.createElement('div');
        divSongContainer.classList.add('songcontainer');
        let diletrow = document.createElement('i');
        diletrow.classList.add('open-arrow');
        diletrow.style.left = (activeAlbum.offsetLeft + iArrowLeft) + 'px';
        let divSongContainerInner = document.createElement('div');
        divSongContainerInner.classList.add('songcontainer-inner');
        let listAlbumWrapper = document.createElement('ul');
        listAlbumWrapper.classList.add('albumwrapper');
        listAlbumWrapper.dataset.album = AlbumId;
        let h2SongHeader = document.createElement('h2');
        h2SongHeader.innerText = AlbumName;

        let myCover = window.getComputedStyle(document.querySelector('.album.is-active .albumcover'), null).getPropertyValue('background-image');
        let addCss, addDescr, divSongList;

        if (myCover === 'none') {
            addCss = 'background-color: #D3D3D3;color: #333333;';
            addDescr = AlbumName[0];
        } else {
            addDescr = '';
            addCss = 'background-image:' + myCover + ';-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
        }

        let divSongContainerCover = document.createElement('div');
        divSongContainerCover.classList.add('songcontainer-cover');
        divSongContainerCover.setAttribute('style', addCss);
        divSongContainerCover.innerText = addDescr;
        divSongList = document.createElement('div');
        divSongList.classList.add('songlist');
        divSongList.appendChild(listAlbumWrapper);

        if (document.getElementById('playlist-container').offsetWidth < 850) {
            divSongContainerCover.classList.add('cover-small');
            divSongList.classList.add('one-column');
        } else {
            divSongList.classList.add('two-column');
        }

        let br = document.createElement('br');
        br.style.clear = 'both';

        divSongContainerInner.appendChild(divSongContainerCover);
        divSongContainerInner.appendChild(h2SongHeader);
        divSongContainerInner.appendChild(document.createElement('br'));
        divSongContainerInner.appendChild(divSongList);
        divSongContainerInner.appendChild(br);
        divSongContainer.appendChild(diletrow);
        divSongContainer.appendChild(divSongContainerInner);
        document.getElementById('playlist-container').appendChild(divSongContainer);

        OCA.Audioplayer.Category.getTracks(null, 'Album', AlbumId, true, albumDirectPlay);

        // don´t show the playlist when the quick-play button is pressed
        if (albumDirectPlay !== true) {
            let iScroll = 20;
            let iSlideDown = 200;
            let iTop = 260;
            let containerTop;
            let appContentScroll;
            containerTop = activeAlbum.offsetTop + iTop;
            appContentScroll = activeAlbum.offsetTop + iScroll;

            $(divSongContainer).css({'top': containerTop}).slideDown(iSlideDown);
            window.scrollTo(0, appContentScroll);
        }

        return true;
    },
};

/**
 * @namespace OCA.Audioplayer.Category
 */
OCA.Audioplayer.Category = {

    load: function (callback) {
        let category = document.getElementById('category_selector').value;
        document.getElementById('addPlaylist').classList.add('hidden');
        document.getElementById('myCategory').innerHTML = '';

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
            data: {category: category},
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    let categoryRows = document.createDocumentFragment();

                    for (let categoryData of jsondata.data) {
                        let li = document.createElement('li');
                        li.dataset.id = categoryData.id;
                        li.dataset.name = categoryData.name;

                        if (category === 'Playlist' && categoryData.id.toString()[0] !== 'X' && categoryData.id.toString()[0] !== 'S' && categoryData.id !== '') {
                            OCA.Audioplayer.Playlists.buildCategoryRow(categoryData, li);
                        } else {
                            OCA.Audioplayer.Category.buildCategoryRow(categoryData, li);
                        }

                        let spanCounter = document.createElement('span');
                        spanCounter.classList.add('counter');
                        spanCounter.innerText = categoryData['cnt'] ? categoryData['cnt'] : '';
                        li.appendChild(spanCounter);
                        categoryRows.appendChild(li);
                    }

                    let categoryList = document.getElementById('myCategory');
                    categoryList.appendChild(categoryRows);
                    categoryList.addEventListener('click', OCA.Audioplayer.Category.handleCategoryClicked);
                    if (typeof callback === 'function') {
                        callback();
                    }
                } else {
                    OCA.Audioplayer.UI.showInitScreen();
                }
            }
        });
        if (category === 'Playlist') {
            document.getElementById('addPlaylist').classList.remove('hidden');
        }
        return true;
    },

    buildCategoryRow: function (categoryData, li) {
        let spanName = document.createElement('span');
        spanName.setAttribute('class', 'pl-name');
        spanName.setAttribute('title', categoryData.name);
        spanName.innerText = categoryData.name;
        li.appendChild(spanName);
    },

    handleCategoryClicked: function (evt, callback) {
        // do not react when playlist edit input window is active or when pressing sort button
        if (evt && (evt.target.nodeName === 'INPUT' || evt.target.nodeName === 'I')) {
            return;
        }

        let activeCategory = document.querySelector('#myCategory .active');
        if (evt) {
            if (activeCategory) {
                activeCategory.classList.remove('active');
            }
            let parentLi = evt.target.closest('li');
            parentLi.classList.add('active');
            activeCategory = parentLi;
        }

        let category = document.getElementById('category_selector').value;
        let categoryItem = activeCategory.dataset.id;
        OCA.Audioplayer.Core.CategorySelectors[1] = categoryItem;

        let classes = document.getElementById('view-toggle').classList;
        if (classes.contains('icon-toggle-pictures') && category !== 'Playlist') {
            OCA.Audioplayer.Cover.load(category, categoryItem);
        } else {
            OCA.Audioplayer.Category.buildListView(evt);
            OCA.Audioplayer.Category.getTracks(callback, category, categoryItem, false);
        }
    },

    buildListView: function () {
        document.getElementById('playlist-container').style.display = 'block';
        document.getElementById('empty-container').style.display = 'none';
        document.getElementById('loading').style.display = 'block';
        if (document.querySelector('.coverrow')) {
            document.querySelector('.coverrow').remove();
        }
        if (document.querySelector('.songcontainer')) {
            document.querySelector('.songcontainer').remove();
        }
        if (document.getElementById('individual-playlist')) {
            document.getElementById('individual-playlist').remove();
        }
        document.getElementById('individual-playlist-info').style.display = 'block';
        document.getElementById('individual-playlist-header').style.display = 'block';

        let ul = document.createElement('ul');
        ul.id = 'individual-playlist';
        ul.classList.add('albumwrapper');
        document.getElementById('playlist-container').appendChild(ul);

        document.querySelector('.header-title').dataset.order = '';
        document.querySelector('.header-artist').dataset.order = '';
        document.querySelector('.header-album').dataset.order = '';

        return true;
    },

    getTracks: function (callback, category, categoryItem, covers, albumDirectPlay) {


        if (OCA.Audioplayer.Core.AjaxCallStatus !== null) {
            OCA.Audioplayer.Core.AjaxCallStatus.abort();
        }

        OCA.Audioplayer.Core.AjaxCallStatus = $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/gettracks'),
            data: {category: category, categoryId: categoryItem},
            success: function (jsondata) {
                document.getElementById('loading').style.display = 'none';
                if (jsondata.status === 'success') {
                    document.getElementById('sm2-bar-ui').style.display = 'block';
                    let itemRows = document.createDocumentFragment();
                    for (let itemData of jsondata.data) {
                        let tempItem = OCA.Audioplayer.UI.buildTrackRow(itemData, covers);
                        itemRows.appendChild(tempItem);
                    }

                    document.getElementById('playlist-container').dataset.playlist = category + '-' + categoryItem;
                    document.querySelector('.albumwrapper').appendChild(itemRows);
                    OCA.Audioplayer.UI.addTitleClickEvents(callback);

                    if (albumDirectPlay === true) {
                        document.querySelector('.albumwrapper').getElementsByClassName('title')[0].click();
                        return;
                    }
                    OCA.Audioplayer.UI.indicateCurrentPlayingTrack();

                    document.querySelector('.header-title').innerText = jsondata['header']['col1'];
                    document.querySelector('.header-artist').innerText = jsondata['header']['col2'];
                    document.querySelector('.header-album').innerText = jsondata['header']['col3'];
                    document.querySelector('.header-time').innerText = jsondata['header']['col4'];

                } else if (categoryItem[0] === 'X' || categoryItem[0] === 'S') {
                    OCA.Audioplayer.UI.showInitScreen('smart');
                } else {
                    OCA.Audioplayer.UI.showInitScreen('playlist');
                }
            }
        });
        let category_title = document.querySelector('#myCategory .active') ? document.querySelector('#myCategory .active').firstChild['title'] : false;
        if (category !== 'Title') {
            document.getElementById('individual-playlist-info').innerHTML = t('audioplayer', 'Selected') + ' ' + category + ': ' + category_title;
        } else {
            document.getElementById('individual-playlist-info').innerHTML = t('audioplayer', 'Selected') + ': ' + category_title;
        }
    },

};

/**
 * @namespace OCA.Audioplayer.UI
 */
OCA.Audioplayer.UI = {

    buildTrackRow: function (elem, covers) {
        let canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;

        let li = document.createElement('li');
        li.draggable = 'true';
        li.addEventListener("dragstart", OCA.Audioplayer.Playlists.dragstart_handler);
        li.addEventListener("dragend", OCA.Audioplayer.Playlists.dragend_handler);

        li.dataset.trackid = elem.id;
        li.dataset.title = elem['cl1'];
        li.dataset.artist = elem['cl2'];
        li.dataset.album = elem['cl3'];
        li.dataset.cover = elem['cid'];
        li.dataset.mimetype = elem['mim'];
        li.dataset.path = elem['lin'];

        let favAction = OCA.Audioplayer.UI.indicateFavorite(elem['fav'], elem.id);

        let spanAction = document.createElement('span');
        spanAction.classList.add('actionsSong');
        let iAction = document.createElement('i');
        iAction.classList.add('ioc', 'ioc-volume-off');
        spanAction.appendChild(favAction);
        spanAction.appendChild(iAction);

        let streamUrl = document.createElement('a');
        streamUrl.hidden = true;
        streamUrl.setAttribute('type', elem['mim']);
        if (elem['mim'] === 'audio/mpegurl' || elem['mim'] === 'audio/x-scpls' || elem['mim'] === 'application/xspf+xml') {
            streamUrl.setAttribute('href', elem['lin']);
        } else {
            streamUrl.setAttribute('href', OCA.Audioplayer.UI.getAudiostreamUrl + elem.id);
        }

        let spanInterpret = document.createElement('span');
        spanInterpret.classList.add('interpret');
        spanInterpret.innerText = elem['cl2'];

        let spanAlbum = document.createElement('span');
        spanAlbum.classList.add('album-indi');
        spanAlbum.innerText = elem['cl3'];

        let spanTime = document.createElement('span');
        spanTime.classList.add('time');
        spanTime.innerText = elem['len'];

        let spanNr = document.createElement('span');
        spanNr.classList.add('number');
        spanNr.innerText = elem['cl3'];

        let spanEdit = document.createElement('span');
        spanEdit.classList.add('edit-song', 'icon-more');
        spanEdit.setAttribute('title', t('audioplayer', 'Options'));
        spanEdit.addEventListener('click', OCA.Audioplayer.UI.handleOptionsClicked);

        let spanTitle = document.createElement('span');
        spanTitle.classList.add('title');

        if (canPlayMimeType.includes(elem['mim'])) {
            spanTitle.innerText = elem['cl1'];
        } else {
            spanTitle.innerHTML = '<i>' + elem['cl1'] + '</i>';
            li.dataset.canPlayMime = 'false';
        }

        if (covers) {
            li.appendChild(streamUrl);
            li.appendChild(spanAction);
            li.appendChild(spanNr);
            li.appendChild(spanTitle);
            li.appendChild(spanEdit);
        } else {
            li.appendChild(streamUrl);
            li.appendChild(spanAction);
            li.appendChild(spanTitle);
            li.appendChild(spanInterpret);
            li.appendChild(spanAlbum);
            li.appendChild(spanTime);
            li.appendChild(spanEdit);
        }

        return li;
    },

    addTitleClickEvents: function (callback) {
        let albumWrapper = document.querySelector('.albumwrapper');
        let getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        let category = document.getElementById('playlist-container').dataset.playlist.split('-');

        let playlist = albumWrapper.getElementsByTagName('li');

        if ((category[0] === 'Playlist' && category[1].toString()[0] !== 'X' && category[1] !== '')) {
            for (let track of playlist) {
                track.addEventListener("dragover", OCA.Audioplayer.Playlists.dragover_row_handler);
            }
        }

        albumWrapper.addEventListener('click', function (event) {
            OCA.Audioplayer.UI.handleTitleClicked(getcoverUrl, playlist, event.target);
        });
        // the callback is used for the the init function to get feedback when all title rows are ready
        if (typeof callback === 'function') {
            callback();
        }
    },

    indicateCurrentPlayingTrack: function () {
        if (document.getElementById('playlist-container').dataset.playlist === OCA.Audioplayer.Player.currentPlaylist) {

            if (document.getElementsByClassName('isActive').length === 1) {
                document.getElementsByClassName('isActive')[0].classList.remove('isActive');
            }

            // reset all playing icons
            let iocIcon = document.querySelectorAll('.albumwrapper li i.ioc');
            for (let i = 0; i < iocIcon.length; ++i) {
            }
            let iconIcon = document.querySelectorAll('.albumwrapper li i.icon');
            for (let j = 0; j < iconIcon.length; ++j) {
            }

            document.getElementById('nowPlayingText').innerHTML = iocIcon[OCA.Audioplayer.Player.currentTrackIndex].parentElement.parentElement.dataset.title;
            document.querySelectorAll('.albumwrapper li')[OCA.Audioplayer.Player.currentTrackIndex].classList.add('isActive');
            document.querySelectorAll('.albumwrapper li')[OCA.Audioplayer.Player.currentTrackIndex].scrollIntoView(
                {
                    behavior: 'smooth',
                    block: 'center',
                });
        }

        //in every case, update the playbar and medaservices
        let coverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        let currentTrack = OCA.Audioplayer.Player.getCurrentPlayingTrackInfo();
        if (currentTrack) {

            let addCss;
            let addDescr;
            let coverID = currentTrack.dataset.cover;
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
            document.querySelector('.sm2-playlist-cover').setAttribute('style', addCss);
            document.querySelector('.sm2-playlist-cover').innerText = addDescr;
            document.title = currentTrack.dataset.title + ' (' + currentTrack.dataset.artist + ') @ ' + OCA.Audioplayer.Core.initialDocumentTitle;
        }

        // update sidebar information
        if (document.getElementById('app-sidebar').dataset.trackid !== '') {
            OCA.Audioplayer.Sidebar.showSidebar(undefined, OCA.Audioplayer.Player.currentTrackId);
        }
    },

    handleOptionsClicked: function (event) {
        OCA.Audioplayer.Sidebar.showSidebar(event);
        event.stopPropagation();
    },

    handleStarClicked: function (event) {
        OCA.Audioplayer.Core.toggleFavorite(event);
        event.stopPropagation();
    },

    handleViewToggleClicked: function () {
        let div = document.getElementById('view-toggle');
        let classes = div.classList;
        if (classes.contains('icon-toggle-filelist')) {
            classes.remove('icon-toggle-filelist');
            classes.add('icon-toggle-pictures');
            div.innerText = t('audioplayer', 'Album Covers');
            OCA.Audioplayer.Backend.setUserValue('view', 'pictures');
        } else {
            classes.remove('icon-toggle-pictures');
            classes.add('icon-toggle-filelist');
            div.innerText = t('audioplayer', 'List View');
            OCA.Audioplayer.Backend.setUserValue('view', 'filelist');
        }
        if (document.querySelector('#myCategory .active')) {
            OCA.Audioplayer.Category.handleCategoryClicked();
        }
    },

    handleTitleClicked: function (coverUrl, playlist, element) {
        let canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;
        let activeLi = element.parentNode;
        // if enabled, play sonos and skip the rest of the processing
        if (document.getElementById('audioplayer_sonos').value === 'checked') {
            OCA.Audioplayer.Sonos.playSonos(element);
            OCA.Audioplayer.Backend.setStatistics();
            return;
        }
        if (!canPlayMimeType.includes(activeLi.dataset.mimetype)) {
            console.warn(`can't play ${activeLi.dataset.mimetype}`);
            return false;
        }
        if (activeLi.classList.contains('isActive')) {
            OCA.Audioplayer.Player.play();
        } else {
            if (document.getElementById('playlist-container').dataset.playlist !== OCA.Audioplayer.Player.currentPlaylist) {
                let playlistItems = document.querySelectorAll('.albumwrapper li');
                OCA.Audioplayer.Player.addTracksToSourceList(playlistItems);
                OCA.Audioplayer.Player.currentPlaylist = document.getElementById('playlist-container').dataset.playlist;
            }
            let k = 0, e = activeLi;
            while (e = e.previousSibling) {
                ++k;
            }
            // when a new title is played, the old playtime will be reset
            if (parseInt(OCA.Audioplayer.Core.CategorySelectors[2]) !== parseInt(activeLi.dataset.trackid)) {
                OCA.Audioplayer.Player.trackStartPosition = 0;
            }
            OCA.Audioplayer.Player.currentTrackIndex = k;
            OCA.Audioplayer.Player.play();
            OCA.Audioplayer.Backend.setStatistics();
        }
    },

    showInitScreen: function (mode) {
        document.getElementById('sm2-bar-ui').style.display = 'none';
        document.getElementById('playlist-container').style.display = 'none';
        OCA.Audioplayer.UI.EmptyContainer.style.display = 'block';
        OCA.Audioplayer.UI.EmptyContainer.innerHTML = '';

        if (mode === 'smart') {
            OCA.Audioplayer.UI.EmptyContainer.innerHTML = '<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>';
        } else if (mode === 'playlist') {
            OCA.Audioplayer.UI.EmptyContainer.innerHTML = '<span class="no-songs-found">' + t('audioplayer', 'Add new tracks to playlist by drag and drop') + '</span>';
        } else {
            let html = '<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>';
            html += '<span class="no-songs-found"><i class="ioc ioc-refresh" title="' + t('audioplayer', 'Scan for new audio files') + '" id="scanAudiosFirst"></i> ' + t('audioplayer', 'Add new tracks to library') + '</span>';
            html += '<a class="no-songs-found" href="https://github.com/rello/audioplayer/wiki" target="_blank">' + t('audioplayer', 'Help') + '</a>';
            OCA.Audioplayer.UI.EmptyContainer.innerHTML = html;
        }
    },

    compareTracks: function (a, b, reg_check, column) {
        a = $(a).data(column).toString();
        b = $(b).data(column).toString();
        if (reg_check) {
            a = parseInt(a.split('-')[0]) * 100 + parseInt(a.split('-')[1]);
            b = parseInt(b.split('-')[0]) * 100 + parseInt(b.split('-')[1]);
        } else {
            a = a.toLowerCase();
            b = b.toLowerCase();
        }
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    },

    sortPlaylist: function (evt) {
        let evtTarget = evt.target;
        let column = evtTarget.getAttribute('class').split('-')[1];
        let order = evtTarget.getAttribute('data-order');
        let factor = 1;

        if (order === 'descending') {
            factor = -1;
            evtTarget.setAttribute('data-order', 'ascending');
        } else {
            evtTarget.setAttribute('data-order', 'descending');
        }

        let elems = $('#individual-playlist').children('li').get();
        if (elems.length === 0) {
            return;
        }

        let reg_check = $(elems).first().data(column).toString().match(/^\d{1,2}-\d{1,2}$/);
        elems.sort(function (a, b) {
            return OCA.Audioplayer.UI.compareTracks(a, b, reg_check, column) * factor;
        });
        $('#individual-playlist').append(elems.slice(0));

        if (document.getElementById('playlist-container').dataset.playlist === OCA.Audioplayer.Player.currentPlaylist) {
            let playlistItems = document.querySelectorAll('.albumwrapper li');
            OCA.Audioplayer.Player.addTracksToSourceList(playlistItems);

            // search the playlist for the track that is currently selected by the audio element
            // the first occurance is the audio element itself. the second [1] is the source element
            let e = document.querySelectorAll('[src="' + OCA.Audioplayer.Player.html5Audio.src + '"]')[1];
            if (e) {
                let k = 0;
                while (e = e.previousSibling) {
                    ++k;
                }
                OCA.Audioplayer.Player.currentTrackIndex = k;
            }
        }
    },

    resizePlaylist: function () {
        document.getElementById('sm2-bar-ui').style.width = document.getElementById('playlist-container').offsetWidth + 'px';
        document.getElementById('progressBar').width = document.getElementById('progressContainer').offsetWidth;
        if (document.querySelector('.is-active')) {
            if (document.getElementById('playlist-container').offsetWidth < 850) {
                document.querySelector('.songcontainer-cover').classList.add('cover-small');
                document.querySelector('.songlist').classList.add('one-column');
                document.querySelector('.songlist').classList.remove('two-column');
            } else {
                document.querySelector('.songcontainer-cover').classList.remove('cover-small');
                document.querySelector('.songlist').classList.remove('one-column');
                document.querySelector('.songlist').classList.add('two-column');
            }
        }
    },

    indicateFavorite: function (fav, id) {
        let fav_action;
        if (fav === 't') {
            fav_action = document.createElement('i');
            fav_action.classList.add('icon', 'icon-starred');
        } else {
            fav_action = document.createElement('i');
            fav_action.classList.add('icon', 'icon-star');
        }
        fav_action.setAttribute('data-trackid', id);
        fav_action.addEventListener('click', OCA.Audioplayer.UI.handleStarClicked);
        return fav_action;
    },

    toggleFavorite: function (target, trackId) {
        let queryElem;
        if (target.tagName === 'SPAN') {
            queryElem = 'i';
        } else {
            queryElem = 'span';
        }
        let other = document.querySelector(`${queryElem}[data-trackid="${trackId}"]`);

        let classes = target.classList;
        if (classes.contains('icon-starred')) {
            classes.replace('icon-starred', 'icon-star');
            if (other) {
                other.classList.replace('icon-starred', 'icon-star');
            }
            return true;
        } else {
            classes.replace('icon-star', 'icon-starred');
            if (other) {
                other.classList.replace('icon-star', 'icon-starred');
            }
            return false;
        }
    },

    whatsNewSuccess: function (data, statusText, xhr) {
        if (xhr.status !== 200) {
            return;
        }

        let item, menuItem, text, icon;

        const div = document.createElement('div');
        div.classList.add('popovermenu', 'open', 'whatsNewPopover', 'menu-left');

        const list = document.createElement('ul');

        // header
        item = document.createElement('li');
        menuItem = document.createElement('span');
        menuItem.className = 'menuitem';

        text = document.createElement('span');
        text.innerText = t('core', 'New in') + ' ' + data['product'];
        text.className = 'caption';
        menuItem.appendChild(text);

        icon = document.createElement('span');
        icon.className = 'icon-close';
        icon.onclick = function () {
            OCA.Audioplayer.Backend.whatsnewDismiss(data['version']);
        };
        menuItem.appendChild(icon);

        item.appendChild(menuItem);
        list.appendChild(item);

        // Highlights
        for (let i in data['whatsNew']['regular']) {
            const whatsNewTextItem = data['whatsNew']['regular'][i];
            item = document.createElement('li');

            menuItem = document.createElement('span');
            menuItem.className = 'menuitem';

            icon = document.createElement('span');
            icon.className = 'icon-checkmark';
            menuItem.appendChild(icon);

            text = document.createElement('p');
            text.innerHTML = _.escape(whatsNewTextItem);
            menuItem.appendChild(text);

            item.appendChild(menuItem);
            list.appendChild(item);
        }

        // Changelog URL
        if (!_.isUndefined(data['changelogURL'])) {
            item = document.createElement('li');

            menuItem = document.createElement('a');
            menuItem.href = data['changelogURL'];
            menuItem.rel = 'noreferrer noopener';
            menuItem.target = '_blank';

            icon = document.createElement('span');
            icon.className = 'icon-link';
            menuItem.appendChild(icon);

            text = document.createElement('span');
            text.innerText = t('core', 'View changelog');
            menuItem.appendChild(text);

            item.appendChild(menuItem);
            list.appendChild(item);
        }

        div.appendChild(list);
        document.body.appendChild(div);
    },

    handleSettingsButton: function () {
        document.getElementById('app-settings').classList.toggle('open');
    },

};

/**
 * @namespace OCA.Audioplayer.Backend
 */
OCA.Audioplayer.Backend = {
    favoriteUpdate: function (trackid, isFavorite) {
        let params = 'trackid=' + trackid + '&isFavorite=' + isFavorite;

        let xhr = new XMLHttpRequest();
        xhr.open('GET', OC.generateUrl('apps/audioplayer/setfavorite' + '?' + params, true));
        xhr.setRequestHeader('requesttoken', OC.requestToken);
        xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        xhr.send();
    },

    getUserValue: function (user_type, callback) {
        let params = 'type=' + user_type;
        let xhr = new XMLHttpRequest();
        xhr.open('GET', OC.generateUrl('apps/audioplayer/getvalue' + '?' + params, true));
        xhr.setRequestHeader('requesttoken', OC.requestToken);
        xhr.setRequestHeader('OCS-APIREQUEST', 'true');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                let jsondata = JSON.parse(xhr.response);
                if (jsondata['status'] === 'success' && user_type === 'category') {
                    OCA.Audioplayer.Core.CategorySelectors = jsondata['value'].split('-');
                    callback(OCA.Audioplayer.Core.CategorySelectors);
                } else if (jsondata['status'] === 'false' && user_type === 'category') {
                    OCA.Audioplayer.Core.CategorySelectors = [];
                    callback(OCA.Audioplayer.Core.CategorySelectors);
                }
            }
        };
        xhr.send();
    },

    setUserValue: function (user_type, user_value) {
        if (user_type) {
            if (user_type === 'category') {
                OCA.Audioplayer.Core.CategorySelectors = user_value.split('-');
            }
            $.ajax({
                type: 'GET',
                url: OC.generateUrl('apps/audioplayer/setvalue'),
                data: {
                    'type': user_type,
                    'value': user_value
                },
                success: function () {
                }
            });
        }
    },

    setStatistics: function () {
        let track_id = OCA.Audioplayer.Player.currentTrackId;
        if (track_id) {
            $.ajax({
                type: 'GET',
                url: OC.generateUrl('apps/audioplayer/setstatistics'),
                data: {'track_id': track_id},
                success: function () {
                }
            });
            OCA.Audioplayer.Backend.setUserValue('category', OCA.Audioplayer.Core.CategorySelectors[0] + '-' + OCA.Audioplayer.Core.CategorySelectors[1] + '-' + track_id);
        }

    },

    checkNewTracks: function () {
        let xhr = new XMLHttpRequest();
        xhr.open('POST', OC.generateUrl('apps/audioplayer/checknewtracks'));
        xhr.setRequestHeader('requesttoken', OC.requestToken);
        xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.response === 'true') {
                    OCP.Toast.info(t('audioplayer', 'New or updated audio files available'));
                }
            }
        };
        xhr.send();
    },

    whatsnew: function (options) {
        options = options || {};
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/whatsnew'),
            data: {'format': 'json'},
            success: options.success || function (data, statusText, xhr) {
                OCA.Audioplayer.UI.whatsNewSuccess(data, statusText, xhr);
            },
        });
    },

    whatsnewDismiss: function (version) {
        //let data = {version: encodeURIComponent(version)};
        //let xhr = new XMLHttpRequest();
        //xhr.open('POST', OC.generateUrl('apps/audioplayer/whatsnew'));
        //xhr.setRequestHeader('requesttoken', OC.requestToken);
        //xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        //xhr.send(JSON.stringify(data));
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/whatsnew'),
            data: {version: encodeURIComponent(version)}
        })

        let elem = document.querySelector('.whatsNewPopover');
        elem.parentNode.removeChild(elem);
    }
};

/**
 * @namespace OCA.Audioplayer.Playlists
 */
OCA.Audioplayer.Playlists = {
    addSongToPlaylist: function (plId, songId) {
        let sort = parseInt($('#myPlayList li[data-id="' + plId + '"]').find('.counter').text());
        return $.post(OC.generateUrl('apps/audioplayer/addtracktoplaylist'), {
            playlistid: plId,
            songid: songId,
            sorting: (sort + 1)
        }).then(function () {
            OCA.Audioplayer.Core.CategorySelectors[0] = 'Playlist';
            OCA.Audioplayer.Category.load();
        });
    },

    newPlaylist: function (playlistName) {
        $.post(OC.generateUrl('apps/audioplayer/addplaylist'), {
            playlist: playlistName
        }, function (jsondata) {
            if (jsondata.status === 'success') {
                OCA.Audioplayer.Category.load();
            }
            if (jsondata.status === 'error') {
                OCP.Toast.error(t('audioplayer', 'No playlist selected!'));
            }
        });
    },

    renamePlaylist: function (evt) {
        let eventTarget = $(evt.target);
        let playlistId = eventTarget.data('editid');
        let playlistName = eventTarget.data('name');
        let originalItem = $('#myCategory li[data-id="' + playlistId + '"]');
        let myClone = $('#pl-clone').clone();
        let boundGenerateRenameRequest = OCA.Audioplayer.Playlists.generateRenameRequest;

        originalItem.after(myClone);
        originalItem.hide();
        myClone.attr('data-id', playlistId).show();
        myClone.addClass('active');
        myClone.find('input[name="playlist"]').val(playlistName).trigger('focus');

        myClone.on('keydown', function (evt) {
            if (evt.key === 'Enter') {
                if (myClone.find('input[name="playlist"]').val() !== '') {
                    boundGenerateRenameRequest(playlistId, myClone);
                } else {
                    myClone.remove();
                    $('#myCategory li[data-id="' + playlistId + '"]').show();
                }
            }
        });

        myClone.find('button.icon-checkmark').on('click', function () {
            if (myClone.find('input[name="playlist"]').val() !== '') {
                boundGenerateRenameRequest(playlistId, myClone);
            }
        });
        myClone.find('button.icon-close').on('click', function () {
            myClone.remove();
            $('#myCategory li[data-id="' + playlistId + '"]').show();
        });
    },

    generateRenameRequest: function (playlistId, playlistClone) {
        let saveForm = $('.plclone[data-id="' + playlistId + '"]');
        let playlistName = saveForm.find('input[name="playlist"]').val();

        $.post(OC.generateUrl('apps/audioplayer/updateplaylist'), {
            plId: playlistId,
            newname: playlistName
        }, function (jsondata) {
            if (jsondata.status === 'success') {
                OCA.Audioplayer.Category.load();
                playlistClone.remove();
            }
            if (jsondata.status === 'error') {
                alert('could not update playlist');
            }
        });
    },

    sortPlaylist: function (evt) {
        let eventTarget = $(evt.target);
        if ($('#myCategory li').hasClass('active')) {
            let plId = eventTarget.attr('data-sortid');
            if (eventTarget.hasClass('sortActive')) {

                let idsInOrder = [];
                let tracks = document.getElementById("individual-playlist").querySelectorAll('li');
                tracks.forEach((item, index) => {
                    idsInOrder.push(item.dataset.trackid);
                });

                if (idsInOrder.length !== 0) {
                    $.post(OC.generateUrl('apps/audioplayer/sortplaylist'), {
                        playlistid: plId,
                        songids: idsInOrder.join(';')
                    }, function (jsondata) {
                        if (jsondata.status === 'success') {
                            OCP.Toast.info(jsondata['msg']);
                            document.getElementById('myCategory').getElementsByClassName('active')[0].click();
                        }
                    });
                }
                eventTarget.removeClass('sortActive');
            } else {
                OCP.Toast.info(t('audioplayer', 'Sort modus active'));
                eventTarget.addClass('sortActive');
                if (document.getElementById('sm2-bar-ui').classList.contains('playing')) {
                    OCA.Audioplayer.Player.pause();
                    $('#individual-playlist li').removeClass('isActive');
                    $('#individual-playlist li i.ioc').hide();
                } else {
                    $('#individual-playlist li').removeClass('isActive');
                    $('#individual-playlist li i.ioc').hide();
                }

            }
        }
    },

    deletePlaylist: function (evt) {
        let plId = $(evt.target).attr('data-deleteid');

        OC.dialogs.confirm(
            t('audioplayer', 'Are you sure?'),
            t('audioplayer', 'Delete playlist'),
            function (e) {
                if (e) {
                    $.post(OC.generateUrl('apps/audioplayer/removeplaylist'), {
                        playlistid: plId
                    }, function (jsondata) {
                        if (jsondata.status === 'success') {
                            OCA.Audioplayer.Category.load();
                            OCP.Toast.success(t('audioplayer', 'Playlist successfully deleted!'));
                        }
                    });
                }
            },
            true
        );
        return false;
    },

    buildCategoryRow: function (el, li) {
        let spanName = document.createElement('span');
        spanName.setAttribute('class', 'pl-name-play');
        spanName.setAttribute('title', el.name);
        spanName.innerText = el.name;

        let iSort = document.createElement('i');
        iSort.classList.add('ioc', 'ioc-sort');
        iSort.setAttribute('title', t('audioplayer', 'Sort playlist'));
        iSort.dataset.sortid = el.id;
        iSort.addEventListener('click', OCA.Audioplayer.Playlists.sortPlaylist);

        let iEdit = document.createElement('i');
        iEdit.classList.add('icon', 'icon-rename');
        iEdit.setAttribute('title', t('audioplayer', 'Rename playlist'));
        iEdit.dataset.name = el.name;
        iEdit.dataset.editid = el.id;
        iEdit.addEventListener('click', OCA.Audioplayer.Playlists.renamePlaylist);

        let iDelete = document.createElement('i');
        iDelete.classList.add('ioc', 'ioc-delete');
        iDelete.setAttribute('title', t('audioplayer', 'Delete playlist'));
        iDelete.dataset.deleteid = el.id;
        iDelete.addEventListener('click', OCA.Audioplayer.Playlists.deletePlaylist);

        li.addEventListener("drop", OCA.Audioplayer.Playlists.drop_handler);
        li.addEventListener("dragover", OCA.Audioplayer.Playlists.dragover_handler);
        li.addEventListener("dragleave", OCA.Audioplayer.Playlists.dragleave_handler);

        li.appendChild(spanName);
        li.appendChild(iEdit);
        li.appendChild(iSort);
        li.appendChild(iDelete);
    },

    removeSongFromPlaylist: function (evt) {
        let trackid = $(evt.target).attr('data-trackid');
        let playlistId = $(evt.target).attr('data-listid');

        $.post(OC.generateUrl('apps/audioplayer/removetrackfromplaylist'), {
            'playlistid': playlistId,
            'trackid': trackid
        }, function (jsondata) {
            if (jsondata) {
                let currentCount = $('#myCategory li[data-id="' + playlistId + '"]').find('.counter');
                currentCount.text(currentCount.text() - 1);
                $('#playlistsTabView div[data-id="' + playlistId + '"]').remove();
            }
        });
    },

    dragstart_handler: function (ev) {
        ev.dataTransfer.setData("id", ev.target.dataset.trackid);
        ev.effectAllowed = "copyMove";
        OCA.Audioplayer.Core.drag = ev.target;
    },

    dragend_handler: function (ev) {
        ev.dataTransfer.clearData();
    },

    drop_handler: function (ev) {
        ev.preventDefault();
        OCA.Audioplayer.Playlists.addSongToPlaylist(this.dataset.id, ev.dataTransfer.getData("id"));
        ev.currentTarget.style.background = "";
    },

    dragover_handler: function (ev) {
        ev.currentTarget.style.background = "#FCEFA1";
        ev.preventDefault();
    },

    dragleave_handler: function (ev) {
        ev.currentTarget.style.background = "";
        ev.preventDefault();
    },

    dragover_row_handler: function (ev) {
        if (OCA.Audioplayer.Playlists.isBefore(OCA.Audioplayer.Core.drag, ev.target.parentNode))
            ev.target.parentNode.parentNode.insertBefore(OCA.Audioplayer.Core.drag, ev.target.parentNode);
        else
            ev.target.parentNode.parentNode.insertBefore(OCA.Audioplayer.Core.drag, ev.target.parentNode.nextSibling);
    },

    isBefore: function (el1, el2) {
        if (el2.parentNode === el1.parentNode)
            for (var cur = el1.previousSibling; cur && cur.nodeType !== 9; cur = cur.previousSibling)
                if (cur === el2)
                    return true;
        return false;
    },

    initPlaylistActions: function () {
        document.getElementById('addPlaylist').addEventListener('click', function () {
            document.getElementById('newPlaylistTxt').value = '';
            document.getElementById('newPlaylist').classList.remove('ap_hidden');
        });

        document.getElementById('newPlaylistBtn_cancel').addEventListener('click', function () {
            document.getElementById('newPlaylistTxt').value = '';
            document.getElementById('newPlaylist').classList.add('ap_hidden');
        });

        document.getElementById('newPlaylistBtn_ok').addEventListener('click', function () {
            let newPlaylistTxt = document.getElementById('newPlaylistTxt');
            if (newPlaylistTxt.value !== '') {
                OCA.Audioplayer.Playlists.newPlaylist(newPlaylistTxt.value);
                newPlaylistTxt.value = '';
                newPlaylistTxt.focus();
                document.getElementById('newPlaylist').classList.add('ap_hidden');
            }
        });

        document.getElementById('newPlaylistTxt').addEventListener('keydown', function (event) {
            let newPlaylistTxt = document.getElementById('newPlaylistTxt');
            if (event.key === 'Enter' && newPlaylistTxt.value !== '') {
                OCA.Audioplayer.Playlists.newPlaylist(newPlaylistTxt.value);
                newPlaylistTxt.value = '';
                newPlaylistTxt.focus();
                document.getElementById('newPlaylist').classList.add('ap_hidden');
            }
        });
    },
};

document.addEventListener('DOMContentLoaded', function () {
    OCA.Audioplayer.Core.init();
    OCA.Audioplayer.Core.initKeyListener();
    OCA.Audioplayer.Backend.checkNewTracks();
    OCA.Audioplayer.Playlists.initPlaylistActions();
    OCA.Audioplayer.Backend.whatsnew();

    OCA.Audioplayer.UI.resizePlaylist = _.debounce(OCA.Audioplayer.UI.resizePlaylist, 250);
    document.getElementById('app-content').addEventListener('appresized', OCA.Audioplayer.UI.resizePlaylist);
    document.getElementById('view-toggle').addEventListener('click', OCA.Audioplayer.UI.handleViewToggleClicked);
    document.getElementById('appSettingsButton').addEventListener('click', OCA.Audioplayer.UI.handleSettingsButton);

    document.getElementById('app-navigation-toggle_alternative').addEventListener('click', function () {
        document.getElementById('newPlaylist').classList.add('ap_hidden');
        if (document.getElementById('app-navigation').classList.contains('hidden')) {
            document.getElementById('app-navigation').classList.remove('hidden');
            OCA.Audioplayer.Backend.setUserValue('navigation', 'true');
        } else {
            document.getElementById('app-navigation').classList.add('hidden');
            OCA.Audioplayer.Backend.setUserValue('navigation', 'false');
        }
        OCA.Audioplayer.UI.resizePlaylist();
    });

    document.getElementById('category_selector').addEventListener('change', function () {
        document.getElementById('newPlaylist').classList.add('ap_hidden');
        OCA.Audioplayer.Core.CategorySelectors[0] = document.getElementById('category_selector').value;
        OCA.Audioplayer.Core.CategorySelectors[1] = '';
        document.getElementById('myCategory').innerHTML = '';
        if (OCA.Audioplayer.Core.CategorySelectors[0] !== '') {
            OCA.Audioplayer.Category.load();
        }
    });

    document.querySelector('.header-title').addEventListener('click', OCA.Audioplayer.UI.sortPlaylist);
    document.querySelector('.header-artist').addEventListener('click', OCA.Audioplayer.UI.sortPlaylist);
    document.querySelector('.header-album').addEventListener('click', OCA.Audioplayer.UI.sortPlaylist);

    window.setTimeout(function () {
        document.getElementById('sm2-bar-ui').style.width = document.getElementById('playlist-container').offsetWidth + 'px';
        document.getElementById('progressBar').width = document.getElementById('progressContainer').offsetWidth;
    }, 1000);

    let resizeTimeout;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            OCA.Audioplayer.UI.resizePlaylist();
        }, 500);
    });

    window.onhashchange = function () {
        if (decodeURI(location.hash).substring(1)) {
            OCA.Audioplayer.Core.processSearchResult();
        }
    };

    // mediaSession currently use for Chrome already to support hardware keys
    if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('play', function () {
            OCA.Audioplayer.Player.play();
        });
        navigator.mediaSession.setActionHandler('pause', function () {
            OCA.Audioplayer.Player.pause();
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
});
