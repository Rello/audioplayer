/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2019 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

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
    Player: null,
    canPlayMimeType: null,

    init: function () {
        OCA.Audioplayer.Core.initialDocumentTitle = document.title;
        OCA.Audioplayer.UI.EmptyContainer = document.getElementById('empty-container');
        OCA.Audioplayer.UI.PlaylistContainer = $('#playlist-container');
        OCA.Audioplayer.UI.ActivePlaylist = $('#activePlaylist');
        OCA.Audioplayer.UI.getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?file=';


        if (decodeURI(location.hash).length > 1) {
            OCA.Audioplayer.Core.processSearchResult();
        } else {
            // read saved values from user values
            OCA.Audioplayer.Backend.getUserValue('category', OCA.Audioplayer.Core.processCategoryFromPreset.bind());
        }

        OCA.Audioplayer.Core.canPlayMimeType = soundManager.html5;
        var stream_array = ['audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml'];
        for (var s = 0; s < stream_array.length; s++) {
            OCA.Audioplayer.Core.canPlayMimeType[stream_array[s]] = true;
        }

        $('.toolTip').tooltip();
    },

    initKeyListener: function () {
        document.body.addEventListener('keydown', function (e) {
            if (e.target) {
                var nodeName = e.target.nodeName.toUpperCase();
                //don't activate shortcuts when the user is in an input, textarea or select element
                if (nodeName === 'INPUT' || nodeName === 'TEXTAREA' || nodeName === 'SELECT') {
                    return;
                }
            }

            if (OCA.Audioplayer.Core.Player && document.querySelectorAll('#activePlaylist li').length > 0) {
                var currentVolume;
                var newVolume;
                switch (e.key) {
                    case ' ':
                        if (document.getElementById('sm2-bar-ui').classList.contains('playing')) {
                            OCA.Audioplayer.Core.Player.actions.pause();
                        } else {
                            OCA.Audioplayer.Core.Player.actions.resume();
                        }
                        e.preventDefault();
                        break;
                    case 'ArrowRight':
                        OCA.Audioplayer.Core.Player.actions.next();
                        break;
                    case 'ArrowLeft':
                        OCA.Audioplayer.Core.Player.actions.prev();
                        break;
                    case 'ArrowUp':
                        currentVolume = OCA.Audioplayer.Core.Player.actions.getVolume();
                        if (currentVolume < 100) {
                            newVolume = Math.min(currentVolume + 10, 100);
                            OCA.Audioplayer.Core.Player.actions.setVolume(newVolume);
                        }
                        e.preventDefault();
                        break;
                    case 'ArrowDown':
                        currentVolume = OCA.Audioplayer.Core.Player.actions.getVolume();
                        if (currentVolume > 0) {
                            newVolume = Math.max(currentVolume - 10, 0);
                            OCA.Audioplayer.Core.Player.actions.setVolume(newVolume);
                        }
                        e.preventDefault();
                        break;
                }
            }
        });
    },

    processSearchResult: function () {
        var locHash = decodeURI(location.hash).substring(1);
        var locHashTemp = locHash.split('-');

        document.getElementById('searchresults').classList.remove('hidden');
        window.location.href = '#';
        if (locHashTemp[0] !== 'volume' && locHashTemp[0] !== 'repeat' && locHashTemp[0] !== 'shuffle' && locHashTemp[0] !== 'prev' && locHashTemp[0] !== 'play' && locHashTemp[0] !== 'next') {
            OCA.Audioplayer.Core.CategorySelectors = locHashTemp;
            OCA.Audioplayer.Core.processCategoryFromPreset();
        }
    },

    processCategoryFromPreset: function () {
        if (OCA.Audioplayer.Core.CategorySelectors) { // handle exiting Albums selection from old AP version
            if (OCA.Audioplayer.Core.CategorySelectors[0] === 'Albums') {
                OCA.Audioplayer.Core.CategorySelectors[0] = 'Title';
                OCA.Audioplayer.Core.CategorySelectors[1] = '0';
            }
            document.getElementById('category_selector').value = OCA.Audioplayer.Core.CategorySelectors[0];
            OCA.Audioplayer.Category.load(OCA.Audioplayer.Core.selectCategoryItemFromPreset.bind(this));
        } else {
            OCA.Audioplayer.UI.showInitScreen();
        }
    },

    selectCategoryItemFromPreset: function () {
        if (OCA.Audioplayer.Core.CategorySelectors[1]) {
            document.querySelector('#myCategory li[data-id="' + OCA.Audioplayer.Core.CategorySelectors[1] + '"]').classList.add('active');
            var appNavigation = $('#app-navigation');
            appNavigation.scrollTop(appNavigation.scrollTop() + $('#myCategory li.active').first().position().top - 25);
            OCA.Audioplayer.Category.handleCategoryClicked(null, function () {                        // select the last played title
                if (OCA.Audioplayer.Core.CategorySelectors[2]) {
                    var item = $('#individual-playlist li[data-trackid="' + OCA.Audioplayer.Core.CategorySelectors[2] + '"]');
                    item.find('.icon').hide();
                    item.find('.ioc').removeClass('ioc-volume-up').addClass('ioc-volume-off').show();
                }
            }.bind(this));
        }
    },

    toggleFavorite: function (evt) {
        if (OCA.Audioplayer.Core.CategorySelectors[1][0] === 'S') {
            return;
        }
        var target = evt.target;
        var trackId = target.getAttribute('data-trackid');
        var isFavorite = OCA.Audioplayer.UI.toggleFavorite(target, trackId);
        OCA.Audioplayer.Backend.favoriteUpdate(trackId, isFavorite);
    }

};

/**
 * @namespace OCA.Audioplayer.Cover
 */
OCA.Audioplayer.Cover = {

    load: function (category, categoryId) {
        OCA.Audioplayer.UI.PlaylistContainer.show();
        document.getElementById('empty-container').style.display = 'none';
        document.getElementById('loading').style.display = 'block';
        $('.toolTip').tooltip('hide');
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
        var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        var divRow = document.createElement('div');
        divRow.classList.add('coverrow');

        var boundLoadIndividualAlbums = OCA.Audioplayer.Cover.handleCoverClicked.bind();
        for (var album of aAlbums) {
            var addCss;
            var addDescr;
            if (!album.cid) {
                addCss = 'background-color: #D3D3D3;color: #333333;';
                addDescr = album.name[0];
            } else {
                addDescr = '';
                addCss = 'background-image:url(' + getcoverUrl + album.cid + ');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
            }

            var divAlbum = document.createElement('div');
            divAlbum.classList.add('album');
            divAlbum.setAttribute('style', 'margin-left: 15px');
            divAlbum.dataset.album = album.id;
            divAlbum.dataset.name = album.name;
            divAlbum.addEventListener('click', boundLoadIndividualAlbums);

            var divPlayImage = document.createElement('div');
            divPlayImage.setAttribute('id', 'AlbumPlay');
            divPlayImage.addEventListener('click', boundLoadIndividualAlbums);

            var divAlbumCover = document.createElement('div');
            divAlbumCover.classList.add('albumcover');
            divAlbumCover.setAttribute('style', addCss);
            divAlbumCover.innerText = addDescr;

            var divAlbumDescr = document.createElement('div');
            divAlbumDescr.classList.add('albumdescr');
            divAlbumDescr.innerHTML = '<span class="albumname">' + album.name + '</span><span class="artist">' + album.art + '</span>';

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

        var eventTarget = evt.target;
        var AlbumId = eventTarget.parentNode.dataset.album;
        var activeAlbum = document.querySelector('.album[data-album="' + AlbumId + '"]');

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
        var albumDirectPlay = eventTarget.id === 'AlbumPlay';
        var AlbumId = eventTarget.parentNode.dataset.album;
        var AlbumName = eventTarget.parentNode.dataset.name;
        var activeAlbum = document.querySelector('.is-active');
        var iArrowLeft = 72;

        if (document.querySelector('.songcontainer')) document.querySelector('.songcontainer').remove();
        var divSongContainer = document.createElement('div');
        divSongContainer.classList.add('songcontainer');
        var divArrow = document.createElement('i');
        divArrow.classList.add('open-arrow');
        divArrow.style.left = (activeAlbum.offsetLeft + iArrowLeft) + 'px';
        var divSongContainerInner = document.createElement('div');
        divSongContainerInner.classList.add('songcontainer-inner');
        var listAlbumWrapper = document.createElement('ul');
        listAlbumWrapper.classList.add('albumwrapper');
        listAlbumWrapper.dataset.album = AlbumId;
        var h2SongHeader = document.createElement('h2');
        h2SongHeader.innerText = AlbumName;

        var myCover = window.getComputedStyle(document.querySelector('.album.is-active .albumcover'), null).getPropertyValue("background-image");
        var addCss, addDescr, divSongList;

        if (myCover === 'none') {
            addCss = 'background-color: #D3D3D3;color: #333333;';
            addDescr = AlbumName[0];
        } else {
            addDescr = '';
            addCss = 'background-image:' + myCover + ';-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
        }

        var divSongContainerCover = document.createElement('div');
        divSongContainerCover.classList.add('songcontainer-cover');
        divSongContainerCover.setAttribute('style', addCss);
        divSongContainerCover.innerText = addDescr;
        var divSongList = document.createElement('div');
        divSongList.classList.add('songlist');

        // ToDo: why needed????
        var sidebarThumbnail = $('#sidebarThumbnail');

        if (OCA.Audioplayer.UI.PlaylistContainer.width() < 850) {
            divSongContainerCover.classList.add('cover-small');
            divSongList.classList.add('one-column');
            if (sidebarThumbnail.hasClass('full')) {
                sidebarThumbnail.addClass('larger').removeClass('full');
            }
        } else {
            divSongList.classList.add('two-column');
            if (sidebarThumbnail.hasClass('larger')) {
                sidebarThumbnail.addClass('full').removeClass('larger');
            }
        }

        var br = document.createElement('br');
        br.style.clear = 'both';

        divSongList.appendChild(listAlbumWrapper);
        divSongContainerInner.appendChild(divSongContainerCover);
        divSongContainerInner.appendChild(h2SongHeader);
        divSongContainerInner.appendChild(document.createElement('br'));
        divSongContainerInner.appendChild(divSongList);
        divSongContainerInner.appendChild(br);
        divSongContainer.appendChild(divArrow);
        divSongContainer.appendChild(divSongContainerInner);
        document.getElementById('playlist-container').appendChild(divSongContainer);

        OCA.Audioplayer.Category.getTracks(null, 'Album', AlbumId, true, albumDirectPlay);

        // ToDo: why needed????
        //var searchresult = decodeURI(location.hash).substring(1);
        //if (searchresult) {
        //    var locHashTemp = searchresult.split('-');
        //    var evt = {};
        //    evt.albumId = locHashTemp[1];
        //    window.location.href = '#';
        //}

        // don´t show the playlist when the quick-play button is pressed
        if (albumDirectPlay !== true) {
            var iScroll = 20;
            var iSlideDown = 200;
            var iTop = 210;
            var appContent;
            var containerTop;
            var appContentScroll;
            if ($('#content-wrapper').length === 1) { //check old structure of NC13 and oC
                appContent = $('#app-content');
                var scrollTopValue = appContent.scrollTop();
                containerTop = scrollTopValue + activeAlbum.offsetTop + iTop;
                appContentScroll = scrollTopValue + activeAlbum.offsetTop + iScroll;
            } else { //structure was changed with NC14
                containerTop = activeAlbum.offsetTop + iTop;
                appContentScroll = activeAlbum.offsetTop + iScroll;
            }

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
        var category = document.getElementById('category_selector').value;
        document.getElementById('addPlaylist').classList.add('hidden');
        document.getElementById('myCategory').innerHTML = '';
        $('.toolTip').tooltip('hide');

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
            data: {category: category},
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    var categoryRows = document.createDocumentFragment();

                    for (var categoryData of jsondata.data) {
                        var li = document.createElement('li');
                        li.dataset.id = categoryData.id;
                        li.dataset.name = categoryData.name;

                        if (category === 'Playlist' && categoryData.id.toString()[0] !== 'X' && categoryData.id.toString()[0] !== 'S' && categoryData.id !== '') {
                            OCA.Audioplayer.Playlists.buildCategoryRow(categoryData, li);
                        } else {
                            OCA.Audioplayer.Category.buildCategoryRow(categoryData, li);
                        }

                        var spanCounter = document.createElement('span');
                        spanCounter.classList.add('counter');
                        spanCounter.innerText = categoryData.cnt ? categoryData.cnt : '';
                        li.appendChild(spanCounter);
                        categoryRows.appendChild(li);
                    }

                    document.getElementById('myCategory').appendChild(categoryRows);
                    if (typeof callback === 'function') {
                        callback();
                    }
                    $('.toolTip').tooltip();
                } else {
                    OCA.Audioplayer.UI.showInitScreen();
                }
            }.bind()
        });
        if (category === 'Playlist') {
            document.getElementById('addPlaylist').classList.remove('hidden');
        }
        return true;
    },

    buildCategoryRow: function (categoryData, li) {
        var spanName = document.createElement('span');
        spanName.setAttribute('class', 'pl-name');
        spanName.setAttribute('title', categoryData.name);
        spanName.innerText = categoryData.name;
        if (categoryData.id !== '') spanName.addEventListener('click', OCA.Audioplayer.Category.handleCategoryClicked.bind());
        li.appendChild(spanName);
    },

    handleCategoryClicked: function (evt, callback) {
        var activeCategory = document.querySelector('#myCategory .active');
        if (evt) {
            if (activeCategory) activeCategory.classList.remove('active');
            evt.target.parentNode.classList.add('active');
            activeCategory = document.querySelector('#myCategory .active');
        }

        var category = document.getElementById('category_selector').value;
        var categoryItem = activeCategory.dataset.id;
        OCA.Audioplayer.Core.CategorySelectors[1] = categoryItem;
        OCA.Audioplayer.UI.PlaylistContainer.data('playlist', category + '-' + categoryItem);

        var classes = document.getElementById('view-toggle').classList;
        if (classes.contains('icon-toggle-pictures') && category !== 'Playlist') {
            OCA.Audioplayer.Cover.load(category, categoryItem);
        } else {
            OCA.Audioplayer.Category.buildListView(evt);
            OCA.Audioplayer.Category.getTracks(callback, category, categoryItem, false);
        }
    },

    buildListView: function (evt) {
        $('.toolTip').tooltip('hide');
        document.getElementById('playlist-container').style.display = 'block';
        document.getElementById('empty-container').style.display = 'none';
        document.getElementById('loading').style.display = 'block';
        if (document.querySelector('.coverrow')) document.querySelector('.coverrow').remove();
        if (document.querySelector('.songcontainer')) document.querySelector('.songcontainer').remove();
        if (document.getElementById('individual-playlist')) document.getElementById('individual-playlist').remove();
        document.getElementById('individual-playlist-info').style.display = 'block';
        document.getElementById('individual-playlist-header').style.display = 'block';

        OCA.Audioplayer.UI.PlaylistContainer.append('<ul id="individual-playlist" class="albumwrapper"></ul>');

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
                    var titleCounter = 0;
                    var itemRows = document.createDocumentFragment();
                    for (var itemData of jsondata.data) {
                        var tempItem = OCA.Audioplayer.UI.buildTrackRow(itemData, covers);
                        itemRows.appendChild(tempItem);
                        titleCounter++;
                    }

                    //required for Cover View
                    // add a blank row in case of uneven records=>avoid a Chrome bug to strangely split the records across columns
                    if (titleCounter % 2 !== 0) {
                        var li = document.createElement('li');
                        li.classList.add('noPlaylist');
                        var spanNr = document.createElement('span');
                        spanNr.classList.add('number');
                        spanNr.innerText = '\u00A0';
                        li.appendChild(spanNr);
                        itemRows.appendChild(li);
                    }

                    document.querySelector('.albumwrapper').appendChild(itemRows);
                    OCA.Audioplayer.UI.trackClickHandler(callback);

                    if (albumDirectPlay === true) {
                        document.querySelector('.albumwrapper').getElementsByClassName('title')[0].click()
                        return;
                    }
                    OCA.Audioplayer.UI.indicateCurrentPlayingTrack();

                    document.querySelector('.header-title').innerText = jsondata.header.col1;
                    document.querySelector('.header-artist').innerText = jsondata.header.col2;
                    document.querySelector('.header-album').innerText = jsondata.header.col3;
                    document.querySelector('.header-time').innerText = jsondata.header.col4;

                } else if (categoryItem[0] === 'X' || categoryItem[0] === 'S') {
                    OCA.Audioplayer.UI.showInitScreen('smart');
                } else {
                    OCA.Audioplayer.UI.showInitScreen('playlist');
                }
            }.bind()
        });
        var category_title = document.querySelector('#myCategory .active') ? document.querySelector('#myCategory .active').firstChild.title : false;
        if (category !== 'Title') {
            document.getElementById('individual-playlist-info').innerHTML = t('audioplayer', 'Selected ' + category) + ': ' + category_title;
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
        var canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;

        var li = document.createElement('li');
        li.classList.add('dragable');
        li.dataset.trackid = elem.id;
        li.dataset.title = elem.cl1;
        li.dataset.artist = elem.cl2;
        li.dataset.album = elem.cl3;
        li.dataset.cover = elem.cid;
        li.dataset.mimetype = elem.mim;
        li.dataset.path = elem.lin;

        var favAction = OCA.Audioplayer.UI.indicateFavorite(elem.fav, elem.id);

        var spanAction = document.createElement('span');
        spanAction.classList.add('actionsSong');
        var iAction = document.createElement('i');
        iAction.classList.add('ioc', 'ioc-volume-off');
        spanAction.appendChild(favAction);
        spanAction.appendChild(iAction);

        var streamUrl = document.createElement('a');
        streamUrl.setAttribute('type', elem.mim);
        if (elem.mim === 'audio/mpegurl' || elem.mim === 'audio/x-scpls' || elem.mim === 'application/xspf+xml') {
            streamUrl.setAttribute('href', elem.lin);
        } else {
            streamUrl.setAttribute('href', OCA.Audioplayer.UI.getAudiostreamUrl + elem.lin);
        }

        var spanInterpret = document.createElement('span');
        spanInterpret.classList.add('interpret');
        spanInterpret.innerText = elem.cl2;

        var spanAlbum = document.createElement('span');
        spanAlbum.classList.add('album-indi');
        spanAlbum.innerText = elem.cl3;

        var spanTime = document.createElement('span');
        spanTime.classList.add('time');
        spanTime.innerText = elem.len;

        var spanNr = document.createElement('span');
        spanNr.classList.add('number');
        spanNr.innerText = elem.cl3;

        var spanEdit = document.createElement('span');
        spanEdit.classList.add('edit-song', 'icon-more');
        spanEdit.setAttribute('title', t('audioplayer', 'Options'));
        spanEdit.addEventListener('click', OCA.Audioplayer.UI.handleOptionsClicked);

        var spanTitle = document.createElement('span');
        spanTitle.classList.add('title');

        if (canPlayMimeType[elem.mim]) {
            spanTitle.innerText = elem.cl1;
        } else {
            spanTitle.innerHTML = '<i>' + elem.cl1 + '</i>';
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

    handleOptionsClicked: function (event) {
        OCA.Audioplayer.Sidebar.showSidebar(event);
        event.stopPropagation();
    },

    indicateFavorite: function (fav, id) {
        var fav_action;
        if (fav === 't') {
            var fav_action = document.createElement('i');
            fav_action.classList.add('icon', 'icon-starred');
        } else {
            var fav_action = document.createElement('i');
            fav_action.classList.add('icon', 'icon-star');
        }
        fav_action.setAttribute('data-trackid', id);
        fav_action.addEventListener('click', OCA.Audioplayer.UI.handleStarClicked);
        return fav_action;
    },

    handleStarClicked: function (event) {
        OCA.Audioplayer.Core.toggleFavorite(event);
        event.stopPropagation();
    },

    handleViewToggleClicked: function (event) {
        var div = document.getElementById('view-toggle');
        var classes = div.classList;
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

    trackClickHandler: function (callback) {
        var albumWrapper = document.querySelector('.albumwrapper');
        var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        var category = document.getElementById('playlist-container').dataset.playlist.split('-');

        var playlist = albumWrapper.getElementsByTagName('li');

        if (!(category[0] === 'Playlist' && category[1].toString()[0] !== 'X' && category[1] !== '')) {
            for (var track of playlist) {
                $(track).draggable({
                    appendTo: 'body',
                    helper: OCA.Audioplayer.Playlists.dragElement,
                    cursor: 'move',
                    delay: 500,
                    start: function (event, ui) {
                        ui.helper.addClass('draggingSong');
                    }
                });
            }
        }
        albumWrapper.addEventListener('click', function (event) {
            OCA.Audioplayer.UI.onTitleClick(getcoverUrl, playlist, event.target);
        });
        // the callback is used for the the init function to get feedback when all title rows are ready
        if (typeof callback === 'function') {
            callback();
        }
    },

    onTitleClick: function (coverUrl, playlist, element) {
        var canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;
        var activeLi = $(element).closest('li');
        // if enabled, play sonos and skip the rest of the processing
        if ($('#audioplayer_sonos').val() === 'checked') {
            var liIndex = element.parents('li').index();
            OCA.Audioplayer.Sonos.playSonos(liIndex);
            OCA.Audioplayer.Backend.setStatistics();
            return;
        }
        if (!canPlayMimeType[activeLi.data('mimetype')]) {
            console.warn(`can't play ${activeLi.data('mimetype')}`);
            return false;
        }
        if (activeLi.hasClass('isActive')) {
            if (document.getElementById('sm2-bar-ui').classList.contains('playing')) {
                OCA.Audioplayer.Core.Player.actions.stop();
            } else {
                OCA.Audioplayer.Core.Player.actions.play();
            }
        } else {
            // the visible playlist has to be copied to the player queue
            // this disconnects the free navigation in AP while continuing to play a playlist
            if (OCA.Audioplayer.UI.PlaylistContainer.data('playlist') !== OCA.Audioplayer.UI.ActivePlaylist.data('playlist')) {
                var ClonePlaylist = $(playlist).clone();
                OCA.Audioplayer.UI.ActivePlaylist.html('');
                OCA.Audioplayer.UI.ActivePlaylist.append(ClonePlaylist);
                OCA.Audioplayer.UI.ActivePlaylist.find('span').remove();
                OCA.Audioplayer.UI.ActivePlaylist.find('.noPlaylist').remove();
                OCA.Audioplayer.UI.ActivePlaylist.data('playlist', OCA.Audioplayer.UI.PlaylistContainer.data('playlist'));
            }
            OCA.Audioplayer.UI.currentTrackUiChange(coverUrl, activeLi);
            if (OCA.Audioplayer.Core.Player.playlistController.data.selectedIndex === null) {
                OCA.Audioplayer.Core.Player.playlistController.data.selectedIndex = 0;
            }
            OCA.Audioplayer.Core.Player.actions.play(activeLi.index());
            OCA.Audioplayer.Backend.setStatistics();
        }
    },

    indicateCurrentPlayingTrack: function () {
        if (OCA.Audioplayer.UI.PlaylistContainer.data('playlist') === OCA.Audioplayer.UI.ActivePlaylist.data('playlist')) {
            var playingTrackId = document.querySelector('#activePlaylist li.selected').dataset.trackid;
            var playingListItem = document.querySelector('.albumwrapper li[data-trackid="' + playingTrackId + '"]');
            playingListItem.classList.add('isActive');
            var icon = playingListItem.querySelector('.ioc');
            icon.classList.remove('ioc-volume-off');
            icon.classList.add('ioc-volume-up');
            icon.style.display = 'block';
            playingListItem.querySelector('.icon').style.display = 'none';
        }
    },

    showInitScreen: function (mode) {
        document.getElementById('sm2-bar-ui').style.display = 'none';
        OCA.Audioplayer.UI.PlaylistContainer.hide();
        OCA.Audioplayer.UI.EmptyContainer.style.display = 'block';
        OCA.Audioplayer.UI.EmptyContainer.innerHTML = '';

        if (mode === 'smart') {
            OCA.Audioplayer.UI.EmptyContainer.innerHTML = '<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>';
        } else if (mode === 'playlist') {
            OCA.Audioplayer.UI.EmptyContainer.innerHTML = '<span class="no-songs-found">' + t('audioplayer', 'Add new tracks to playlist by drag and drop') + '</span>';
        } else {
            OCA.Audioplayer.UI.EmptyContainer.innerHTML = '<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>';
            OCA.Audioplayer.UI.EmptyContainer.append('<span class="no-songs-found"><i class="ioc ioc-refresh" title="' + t('audioplayer', 'Scan for new audio files') + '" id="scanAudiosFirst"></i> ' + t('audioplayer', 'Add new tracks to library') + '</span>');
            OCA.Audioplayer.UI.EmptyContainer.append('<a class="no-songs-found" href="https://github.com/rello/audioplayer/wiki" target="_blank">' + t('audioplayer', 'Help') + '</a>');
        }
    },

    currentTrackUiChange: function (coverUrl, activeLi) {
        var addCss;
        var addDescr;
        var coverID = activeLi.data('cover');
        if (!coverID) {
            addCss = 'background-color: #D3D3D3;color: #333333;';
            addDescr = activeLi.data('title')[0];
        } else {
            addCss = 'background-image:url(' + coverUrl + coverID + ');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
            addDescr = '';
        }
        document.querySelector('.sm2-playlist-cover').setAttribute('style', addCss);
        document.querySelector('.sm2-playlist-cover').innerText = addDescr;
        document.title = activeLi.data('title') + ' (' + activeLi.data('artist') + ' ) @ ' + OCA.Audioplayer.Core.initialDocumentTitle;
    },

    soundmanagerCallback: function (SMaction) {
        if (SMaction === 'setVolume') {
            OCA.Audioplayer.Backend.setUserValue('volume', Math.round(OCA.Audioplayer.Core.Player.actions.getVolume()));
        } else {
            OCA.Audioplayer.UI.currentTrackUiChange(
                OC.generateUrl('apps/audioplayer/getcover/'),
                $('#activePlaylist li.selected')
            );
            OCA.Audioplayer.Backend.setStatistics();
        }
    },

    sortPlaylist: function (evt) {
        var column = $(evt.target).attr('class').split('-')[1];
        var order = $(evt.target).data('order');
        var factor = 1;

        if (order === 'descending') {
            factor = -1;
            $(evt.target).data('order', 'ascending');
        } else {
            $(evt.target).data('order', 'descending');
        }

        var elems = $('#individual-playlist').children('li').get();
        if (elems.length === 0) {
            return;
        }

        var reg_check = $(elems).first().data(column).toString().match(/^\d{1,2}-\d{1,2}$/);
        elems.sort(function (a, b) {
            a = $(a).data(column).toString();
            b = $(b).data(column).toString();
            if (reg_check) {
                a = parseInt(a.split('-')[0]) * 100 + parseInt(a.split('-')[1]);
                b = parseInt(b.split('-')[0]) * 100 + parseInt(b.split('-')[1]);
            } else {
                a = a.toLowerCase();
                b = b.toLowerCase();
            }
            return ((a < b) ? -1 * factor : ((a > b) ? factor : 0));
        });
        $('#individual-playlist').append(elems);

        if (OCA.Audioplayer.UI.PlaylistContainer.data('playlist') === OCA.Audioplayer.UI.ActivePlaylist.data('playlist')) {
            elems = OCA.Audioplayer.UI.ActivePlaylist.children('li').get();
            elems.sort(function (a, b) {
                a = $(a).data(column).toString();
                b = $(b).data(column).toString();
                if (reg_check) {
                    a = parseInt(a.split('-')[0]) * 100 + parseInt(a.split('-')[1]);
                    b = parseInt(b.split('-')[0]) * 100 + parseInt(b.split('-')[1]);
                } else {
                    a = a.toLowerCase();
                    b = b.toLowerCase();
                }
                return ((a < b) ? -1 * factor : ((a > b) ? factor : 0));
            });
            OCA.Audioplayer.UI.ActivePlaylist.append(elems);
        }

        if (OCA.Audioplayer.Core.Player) {
            OCA.Audioplayer.Core.Player.playlistController.data.selectedIndex = $('#activePlaylist li.selected').index();
        }
    },

    resizePlaylist: function () {
        var songlist = $('.songcontainer .songlist');
        document.getElementById('sm2-bar-ui').style.width = OCA.Audioplayer.UI.PlaylistContainer.width() + 'px';
        if ($('.album.is-active').length !== 0) {
            OCA.Audioplayer.Cover.buildSongContainer($('.album.is-active'));
        }

        if (OCA.Audioplayer.UI.PlaylistContainer.width() < 850) {
            songlist.addClass('one-column');
            songlist.removeClass('two-column');
            $('.songcontainer .songcontainer-cover').addClass('cover-small');
        } else {
            songlist.removeClass('one-column');
            songlist.addClass('two-column');
            $('.songcontainer .songcontainer-cover').removeClass('cover-small');
        }
    },

    toggleFavorite: function (target, trackId) {
        if (target.tagName === 'SPAN') {
            var queryElem = 'i';
        } else {
            queryElem = 'span';
        }
        var other = document.querySelector(`${queryElem}[data-trackid="${trackId}"]`);

        var classes = target.classList;
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
    }
};

/**
 * @namespace OCA.Audioplayer.Backend
 */
OCA.Audioplayer.Backend = {
    favoriteUpdate: function (trackid, isFavorite) {
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setfavorite'),
            data: {
                'trackid': trackid,
                'isFavorite': isFavorite
            }
        });
    },

    getUserValue: function (user_type, callback) {
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getvalue'),
            data: {'type': user_type},
            success: function (jsondata) {
                if (jsondata.status === 'success' && user_type === 'category') {
                    OCA.Audioplayer.Core.CategorySelectors = jsondata.value.split('-');
                    callback(OCA.Audioplayer.Core.CategorySelectors);
                } else if (jsondata.status === 'false' && user_type === 'category') {
                    OCA.Audioplayer.Core.CategorySelectors = [];
                    callback(OCA.Audioplayer.Core.CategorySelectors);
                }
            }.bind()
        });
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
        var track_id = $('#activePlaylist li.selected').data('trackid');
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
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/checknewtracks'),
            success: function (data) {
                if (data === 'true') {
                    OC.Notification.showTemporary(t('audioplayer', 'New or updated audio files available'));
                }
            }
        });
    },

};

/**
 * @namespace OCA.Audioplayer.Playlists
 */
OCA.Audioplayer.Playlists = {
    addSongToPlaylist: function (plId, songId) {
        var sort = parseInt($('#myPlayList li[data-id="' + plId + '"]').find('.counter').text());
        return $.post(OC.generateUrl('apps/audioplayer/addtracktoplaylist'), {
            playlistid: plId,
            songid: songId,
            sorting: (sort + 1)
        }).then(function () {
            $('.toolTip').tooltip('hide');
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
                $('#notification').text(t('audioplayer', 'No playlist selected!')).slideDown();
                window.setTimeout(function () {
                    $('#notification').slideUp();
                }, 3000);
            }
        });
    },

    renamePlaylist: function (evt) {
        var eventTarget = $(evt.target);
        var playlistId = eventTarget.data('editid');
        var playlistName = eventTarget.data('name');
        var originalItem = $('#myCategory li[data-id="' + playlistId + '"]');
        var myClone = $('#pl-clone').clone();
        var boundGenerateRenameRequest = OCA.Audioplayer.Playlists.generateRenameRequest.bind(OCA.Audioplayer.Playlists);

        originalItem.after(myClone);
        originalItem.hide();
        myClone.attr('data-pl', playlistId).show();
        myClone.find('input[name="playlist"]').val(playlistName).trigger('focus');

        myClone.on('keydown', function (evt) {
            if (evt.keyCode === 13) {
                if (myClone.find('input[name="playlist"]').val() !== '') {
                    boundGenerateRenameRequest(playlistId, myClone);
                } else {
                    myClone.remove();
                    $('#myCategory li[data-id="' + playlistId + '"]').show();
                }
            }
        });

        myClone.on('keyup', function (evt) {
            if (evt.keyCode === 27) {
                myClone.remove();
                $('#myCategory li[data-id="' + playlistId + '"]').show();
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
        var saveForm = $('.plclone[data-pl="' + playlistId + '"]');
        var playlistName = saveForm.find('input[name="playlist"]').val();

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
        var eventTarget = $(evt.target);
        var notification = $('#notification');
        if ($('#myCategory li').hasClass('active')) {
            var plId = eventTarget.attr('data-sortid');
            if (eventTarget.hasClass('sortActive')) {

                var idsInOrder = $('#individual-playlist').sortable('toArray', {attribute: 'data-trackid'});
                $.post(OC.generateUrl('apps/audioplayer/sortplaylist'), {
                    playlistid: plId,
                    songids: idsInOrder.join(';')
                }, function (jsondata) {
                    if (jsondata.status === 'success') {
                        eventTarget.removeClass('sortActive');
                        $('#individual-playlist').sortable('destroy');
                        notification.text(jsondata.msg);
                        notification.slideDown();
                        window.setTimeout(function () {
                            $('#notification').slideUp();
                        }, 3000);
                    }
                });

            } else {

                notification.text(t('audioplayer', 'Sort modus active'));
                notification.slideDown();
                window.setTimeout(function () {
                    $('#notification').slideUp();
                }, 3000);

                $('#individual-playlist').sortable({
                    items: 'li',
                    axis: 'y',
                    placeholder: 'ui-state-highlight',
                    helper: 'clone',
                    stop: function () {
                    }
                });

                eventTarget.addClass('sortActive');
                if (document.getElementById('sm2-bar-ui').classList.contains('playing')) {
                    OCA.Audioplayer.Core.Player.actions.pause();
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
        var plId = $(evt.target).attr('data-deleteid');

        OC.dialogs.message(
            t('audioplayer', 'Are you sure?'),
            t('audioplayer', 'Delete playlist'),
            null,
            OCdialogs.YES_NO_BUTTONS,
            function (e) {
                if (e) {
                    $.post(OC.generateUrl('apps/audioplayer/removeplaylist'), {
                        playlistid: plId
                    }, function (jsondata) {
                        if (jsondata.status === 'success') {
                            OCA.Audioplayer.Category.load();
                            $('#notification').text(t('audioplayer', 'Playlist successfully deleted!')).slideDown();
                            window.setTimeout(function () {
                                $('#notification').slideUp();
                            }, 3000);
                        }
                    });
                }
            },
            true
        );
        return false;
    },


    buildCategoryRow: function (el, li) {
        li = $(li); // temporary workaround to apply the .droppable to a vanilla JS element
        var spanName = $('<span/>').attr({'class': 'pl-name-play'}).text(el.name).on('click', OCA.Audioplayer.Category.handleCategoryClicked.bind(this));
        var spanSort = $('<i/>').attr({
            'class': 'ioc ioc-sort toolTip',
            'data-sortid': el.id,
            'title': t('audioplayer', 'Sort playlist')
        }).on('click', OCA.Audioplayer.Playlists.sortPlaylist.bind());
        var spanEdit = $('<i/>').attr({
            'class': 'icon icon-rename toolTip',
            'data-name': el.name,
            'data-editid': el.id,
            'title': t('audioplayer', 'Rename playlist')
        }).on('click', OCA.Audioplayer.Playlists.renamePlaylist.bind());
        var spanDelete = $('<i/>').attr({
            'class': 'ioc ioc-delete toolTip',
            'data-deleteid': el.id,
            'title': t('audioplayer', 'Delete playlist')
        }).on('click', OCA.Audioplayer.Playlists.deletePlaylist.bind());
        li.droppable({
            activeClass: 'activeHover',
            hoverClass: 'dropHover',
            accept: 'li.dragable',
            over: function () {
            },
            drop: function (event, ui) {
                OCA.Audioplayer.Playlists.addSongToPlaylist($(this).attr('data-id'), ui.draggable.attr('data-trackid'));
            }
        });
        li.append(spanName);
        li.append(spanEdit);
        li.append(spanSort);
        li.append(spanDelete);
    },

    removeSongFromPlaylist: function (evt) {

        var trackid = $(evt.target).attr('data-trackid');
        var playlistId = $(evt.target).attr('data-listid');

        $.post(OC.generateUrl('apps/audioplayer/removetrackfromplaylist'), {
            'playlistid': playlistId,
            'trackid': trackid
        }, function (jsondata) {
            if (jsondata) {
                var currentCount = $('#myCategory li[data-id="' + playlistId + '"]').find('.counter');
                currentCount.text(currentCount.text() - 1);
                $('#playlistsTabView div[data-id="' + playlistId + '"]').remove();
            }
        });
    },

    dragElement: function () {
        return $(this).clone().text($(this).find('.title').attr('data-title'));
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
            var newPlaylistTxt = document.getElementById('newPlaylistTxt');
            if (newPlaylistTxt.value !== '') {
                OCA.Audioplayer.Playlists.newPlaylist(newPlaylistTxt.value);
                newPlaylistTxt.value = '';
                newPlaylistTxt.focus();
                document.getElementById('newPlaylist').classList.add('ap_hidden');
            }
        });

        document.getElementById('newPlaylistTxt').addEventListener('keydown', function (event) {
            var newPlaylistTxt = document.getElementById('newPlaylistTxt');
            if (event.key === 'Enter' && newPlaylistTxt.value !== '') {
                OCA.Audioplayer.Playlists.newPlaylist(newPlaylistTxt.value);
                newPlaylistTxt.value = '';
                newPlaylistTxt.focus();
                document.getElementById('newPlaylist').classList.add('ap_hidden');
            }
        });
    },

};

var resizeTimeout = null;
document.addEventListener('DOMContentLoaded', function () {

    OCA.Audioplayer.Core.init();
    OCA.Audioplayer.Core.initKeyListener();
    OCA.Audioplayer.Backend.checkNewTracks();
    OCA.Audioplayer.Playlists.initPlaylistActions();
    if (document.getElementById('audioplayer_sonos').value !== 'checked') {
        OCA.Audioplayer.Core.Player = new SM2BarPlayer(document.getElementById('sm2-bar-ui'));
        OCA.Audioplayer.Core.Player.actions.setVolume(document.getElementById('audioplayer_volume').value);
    }

    var notify = document.getElementById('audioplayer_notification').value;
    if (notify !== '') {
        OC.Notification.showHtml(
            notify,
            {
                type: 'error',
                isHTML: true
            }
        );
    }

    OCA.Audioplayer.UI.resizePlaylist = _.debounce(OCA.Audioplayer.UI.resizePlaylist.bind(OCA.Audioplayer.UI), 250);
    $('#app-content').on('appresized', OCA.Audioplayer.UI.resizePlaylist);

    document.getElementById('view-toggle').addEventListener('click', OCA.Audioplayer.UI.handleViewToggleClicked);

    document.getElementById('app-navigation-toggle_alternative').addEventListener('click', function () {
        document.getElementById('newPlaylist').classList.add('ap_hidden');
        if (document.getElementById('app-navigation').classList.contains('hidden')) {
            document.getElementById('app-navigation').classList.remove('hidden');
            OCA.Audioplayer.Backend.setUserValue('navigation', 'true');
        } else {
            document.getElementById('app-navigation').classList.add('hidden');
            OCA.Audioplayer.Backend.setUserValue('navigation', 'false');
        }
        OCA.Audioplayer.UI.resizePlaylist.call(OCA.Audioplayer.UI);
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

    var boundSortPlaylist = OCA.Audioplayer.UI.sortPlaylist.bind();
    document.querySelector('.header-title').addEventListener('click', boundSortPlaylist);
    document.querySelector('.header-artist').addEventListener('click', boundSortPlaylist);
    document.querySelector('.header-album').addEventListener('click', boundSortPlaylist);

    window.setTimeout(function () {
        document.getElementById('sm2-bar-ui').style.width = OCA.Audioplayer.UI.PlaylistContainer.width() + 'px'
    }, 1000);

    var resizeTimeout;
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
});