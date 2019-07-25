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
        OCA.Audioplayer.Core.initialDocumentTitle = $('title').html().trim();

        if (decodeURI(location.hash).length > 1) {
            OCA.Audioplayer.Core.processSearchResult();
        } else {
            // read saved values from user values
            OCA.Audioplayer.Backend.getUserValue('category', OCA.Audioplayer.Core.processCategoryFromPreset.bind(this));
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
            if (OCA.Audioplayer.Core.Player !== null && $('#activePlaylist li').length > 0) {

                if (e.target) {
                    var nodeName = e.target.nodeName.toUpperCase();
                    //don't activate shortcuts when the user is in an input, textarea or select element
                    if (nodeName === 'INPUT' || nodeName === 'TEXTAREA' || nodeName === 'SELECT') {
                        return;
                    }
                }

                var currentVolume;
                var newVolume;
                if (e.key === 'Space') {//Space pause/play
                    if ($('.sm2-bar-ui').hasClass('playing')) {
                        OCA.Audioplayer.Core.Player.actions.stop();
                    } else {
                        OCA.Audioplayer.Core.Player.actions.play();
                    }
                } else if (e.key === 39) {// right
                    OCA.Audioplayer.Core.Player.actions.next();
                } else if (e.key === 'ArrowLeft') {//left
                    OCA.Audioplayer.Core.Player.actions.prev();
                } else if (e.key === 'ArrowUp') {//up sound up
                    currentVolume = OCA.Audioplayer.Core.Player.actions.getVolume();
                    if (currentVolume < 100) {
                        newVolume = currentVolume + 10;
                        if (newVolume >= 100) newVolume = 100;
                        OCA.Audioplayer.Core.Player.actions.setVolume(newVolume);
                    }
                } else if (e.key === 'ArrowDown') {//down sound down
                    currentVolume = OCA.Audioplayer.Core.Player.actions.getVolume();
                    if (currentVolume > 0) {
                        newVolume = currentVolume - 10;
                        if (newVolume <= 0) newVolume = 0;
                        OCA.Audioplayer.Core.Player.actions.setVolume(newVolume);
                    }
                }
            }
        })
    },

    processSearchResult: function () {
        var locHash = decodeURI(location.hash).substring(1);
        var locHashTemp = locHash.split('-');

        $('#searchresults').addClass('hidden');
        window.location.href = '#';
        if (locHashTemp[0] !== 'volume' && locHashTemp[0] !== 'repeat' && locHashTemp[0] !== 'shuffle' && locHashTemp[0] !== 'prev' && locHashTemp[0] !== 'play' && locHashTemp[0] !== 'next') {
            OCA.Audioplayer.Core.CategorySelectors = locHashTemp;
            OCA.Audioplayer.Core.processCategoryFromPreset();
        }
    },

    processCategoryFromPreset: function () {
        if (OCA.Audioplayer.Core.CategorySelectors === 'false') {
            OCA.Audioplayer.UI.showInitScreen();
        } else if (OCA.Audioplayer.Core.CategorySelectors[0] && OCA.Audioplayer.Core.CategorySelectors[0] !== 'Albums') {
            $('#category_selector').val(OCA.Audioplayer.Core.CategorySelectors[0]);
            OCA.Audioplayer.Category.load(OCA.Audioplayer.Core.selectCategoryItemFromPreset.bind(this));
        } else {
            OCA.Audioplayer.Albums.load();
        }
    },

    selectCategoryItemFromPreset: function () {
        if (OCA.Audioplayer.Core.CategorySelectors[1]) {
            $('#myCategory li[data-id="' + OCA.Audioplayer.Core.CategorySelectors[1] + '"]').addClass('active');
            var appNavigation = $('#app-navigation');
            appNavigation.scrollTop(appNavigation.scrollTop() + $('#myCategory li.active').first().position().top - 25);
            OCA.Audioplayer.Category.loadItems(null, function () {                        // select the last played title
                if (OCA.Audioplayer.Core.CategorySelectors[2]) {
                    var item = $('#individual-playlist li[data-trackid="' + OCA.Audioplayer.Core.CategorySelectors[2] + '"]');
                    item.find('.icon').hide();
                    item.find('.ioc').removeClass('ioc-volume-up').addClass('ioc-volume-off').show();
                }
            }.bind(this));
        }
    },

}

/**
 * @namespace OCA.Audioplayer.Albums
 */
OCA.Audioplayer.Albums = {

    load: function () {
        OCA.Audioplayer.UI.PlaylistContainer.show();
        OCA.Audioplayer.UI.EmptyContainer.hide();
        $('#loading').show();
        $('.toolTip').tooltip('hide');
        $('#alben').addClass('active');
        $('#individual-playlist').remove();
        $('#individual-playlist-info').hide();
        $('#individual-playlist-header').hide();
        $('.coverrow').remove();
        $('.songcontainer').remove();

        $('#myCategory li').removeClass('active');
        $('#newPlaylist').addClass('ap_hidden');

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategory'),
            data: {category: 'Album'},
            success: function (jsondata) {
                $('#loading').hide();
                if (jsondata.status === 'success') {
                    $('.sm2-bar-ui').show();
                    OCA.Audioplayer.Albums.buildAlbumCoverRow(jsondata.data);
                } else {
                    OCA.Audioplayer.UI.showInitScreen();
                }
            }
        });
    },

    buildAlbumCoverRow: function (aAlbums) {
        var divAlbum = [];
        var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        var divRow = $('<div />').addClass('coverrow');

        var boundLoadIndividualAlbums = OCA.Audioplayer.Albums.loadItems.bind(this);
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

            divAlbum = $('<div/>').addClass('album').css('margin-left', '15px').attr({
                'data-album': album.id,
                'data-name': album.name     //required for songcontainer title
            }).on('click', boundLoadIndividualAlbums);

            var divPlayHref = $('<a/>');
            var divPlayImage = $('<div/>').attr({
                'id': 'AlbumPlay'
            }).on('click', boundLoadIndividualAlbums);

            divPlayHref.append(divPlayImage);

            var divAlbumCover = $('<div/>').addClass('albumcover').attr({'style': addCss}).text(addDescr);
            var divAlbumDescr = $('<div/>').addClass('albumdescr').html('<span class="albumname">' + album.name + '</span><span class="artist">' + album.art + '</span>');

            divAlbum.append(divAlbumCover);
            divAlbum.append(divAlbumDescr);
            divAlbum.append(divPlayImage);
            divRow.append(divAlbum);
        }
        OCA.Audioplayer.UI.PlaylistContainer.append(divRow);
    },

    loadItems: function (evt) {
        evt.stopPropagation();
        evt.preventDefault();

        var directPlay = typeof $(evt.target).attr('id') !== 'undefined';
        var eventTarget = $(evt.target).parent();
        var AlbumId = eventTarget.attr('data-album');
        var activeAlbum = $('.album[data-album="' + AlbumId + '"]');

        if (activeAlbum.hasClass('is-active')) {
            $('.songcontainer').slideUp(200, function () {
                $('.album').removeClass('is-active').find('.artist').css('visibility', 'visible');
            });
        } else {
            $('.album').removeClass('is-active').find('.artist').css('visibility', 'visible');
            OCA.Audioplayer.UI.PlaylistContainer.data('playlist', 'Albums-' + AlbumId);

            activeAlbum.addClass('is-active');
            activeAlbum.find('.artist').css('visibility', 'hidden');
            OCA.Audioplayer.Albums.buildSongContainer(eventTarget, directPlay);
        }
    },

    buildSongContainer: function (eventTarget, directPlay) {
        var AlbumId = eventTarget.attr('data-album');
        var AlbumName = eventTarget.attr('data-name');
        var activeAlbum = $('.album[data-album="' + AlbumId + '"]');
        var iArrowLeft = 72;

        $('.songcontainer').remove();
        var divSongContainer = $('<div/>').addClass('songcontainer');
        var divArrow = $('<i/>').addClass('open-arrow').css('left', activeAlbum.position().left + iArrowLeft);
        var divSongContainerInner = $('<div/>').addClass('songcontainer-inner');
        var listAlbumWrapper = $('<ul/>').addClass('albumwrapper').attr('data-album', AlbumId);
        var divSongList;
        var h2SongHeader = $('<h2/>').text(AlbumName);
        var addCss;
        var addDescr;
        var myCover = $('.album.is-active .albumcover').css('background-image');

        if (myCover === 'none') {
            addCss = 'background-color: #D3D3D3;color: #333333;';
            addDescr = AlbumName[0];
        } else {
            addDescr = '';
            addCss = 'background-image:' + myCover + ';-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
        }
        var divSongContainerCover = $('<div/>').addClass('songcontainer-cover').attr({'style': addCss}).text(addDescr);
        var sidebarThumbnail = $('#sidebarThumbnail');

        if (OCA.Audioplayer.UI.PlaylistContainer.width() < 850) {
            divSongContainerCover.addClass('cover-small');
            divSongList = $('<div/>').addClass('songlist one-column');
            if (sidebarThumbnail.hasClass('full')) {
                sidebarThumbnail.addClass('larger').removeClass('full');
            }
        } else {
            divSongList = $('<div/>').addClass('songlist two-column');
            if (sidebarThumbnail.hasClass('larger')) {
                sidebarThumbnail.addClass('full').removeClass('larger');
            }
        }

        var br = $('<br />').css('clear', 'both');

        divSongList.append(listAlbumWrapper);
        divSongContainerInner.append(divSongContainerCover);
        divSongContainerInner.append(h2SongHeader);
        divSongContainerInner.append('<br/>');
        divSongContainerInner.append(divSongList);
        divSongContainerInner.append(br);
        divSongContainer.append(divArrow);
        divSongContainer.append(divSongContainerInner);
        OCA.Audioplayer.UI.PlaylistContainer.append(divSongContainer);

        if (OCA.Audioplayer.Core.AjaxCallStatus !== null) {
            OCA.Audioplayer.Core.AjaxCallStatus.abort();
        }

        OCA.Audioplayer.Core.AjaxCallStatus = $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
            data: {category: 'Album', categoryId: AlbumId},
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    var songcounter = 0;
                    $(jsondata.data).each(function (i, el) {
                        listAlbumWrapper.append(OCA.Audioplayer.UI.buildTrackRow(el));
                        songcounter++;
                    }.bind(this));
                    if (songcounter % 2 !== 0) {
                        var li = $('<li/>');
                        var spanNr = $('<span/>').addClass('number').text('\u00A0');
                        li.append(spanNr);
                        li.addClass('noPlaylist');
                        listAlbumWrapper.append(li); //add a blank row in case of uneven records=>avoid a Chrome bug to strangely split the records across columns
                    }
                    OCA.Audioplayer.UI.trackClickHandler();
                    OCA.Audioplayer.UI.indicateCurrentPlayingTrack();
                    if (directPlay) {
                        $('.albumwrapper').find('.title').first().trigger('click');

                    }
                }
            }.bind(this)
        });

        var searchresult = decodeURI(location.hash).substring(1);
        if (searchresult) {
            var locHashTemp = searchresult.split('-');
            var evt = {};
            evt.albumId = locHashTemp[1];
            window.location.href = '#';
        }

        if (directPlay !== true) {

            var iScroll = 120;
            var iSlideDown = 200;
            var iTop = 80;
            var appContent;
            var containerTop;
            var appContentScroll;
            if ($('#content-wrapper').length === 1) { //check old structure of NC13 and oC
                appContent = $('#app-content');
                var scrollTopValue = appContent.scrollTop();
                containerTop = scrollTopValue + activeAlbum.offset().top + iTop;
                appContentScroll = scrollTopValue + activeAlbum.offset().top - iScroll;
            } else { //structure was changed with NC14
                appContent = $(document);
                containerTop = activeAlbum.offset().top + iTop;
                appContentScroll = activeAlbum.offset().top - iScroll;
            }

            divSongContainer.css({'top': containerTop}).slideDown(iSlideDown);
            appContent.scrollTop(appContentScroll);
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
            url: OC.generateUrl('apps/audioplayer/getcategory'),
            data: {category: category},
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    var categoryRows = document.createDocumentFragment();

                    for (var categoryData of jsondata.data) {
                        var li = document.createElement('li');
                        li.dataset.id = categoryData.id;
                        li.dataset.name = categoryData.name;

                        var spanCounter = document.createElement('span');
                        spanCounter.classList.add('counter');
                        spanCounter.innerText = categoryData.counter ? categoryData.counter : '';

                        if (category === 'Playlist' && categoryData.id.toString()[0] !== 'X' && categoryData.id.toString()[0] !== 'S' && categoryData.id !== '') {
                            OCA.Audioplayer.Playlists.buildPlaylistCategoryRow(categoryData, li);
                        } else {
                            OCA.Audioplayer.Category.buildCategoryRow(categoryData, li);
                        }

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
        if (categoryData.id !== '') spanName.addEventListener('click', OCA.Audioplayer.Category.loadItems.bind());
        li.appendChild(spanName);
    },

    loadItems: function (evt, callback) {
        $('.toolTip').tooltip('hide');
        OCA.Audioplayer.UI.PlaylistContainer.show();
        OCA.Audioplayer.UI.EmptyContainer.hide();
        document.getElementById('loading').style.display = 'block';
        document.getElementById('alben').classList.remove('active');
        document.querySelector('.coverrow') ? document.querySelector('.coverrow').remove() : false;
        document.getElementById('individual-playlist') ? document.getElementById('individual-playlist').remove() : false;
        document.getElementById('individual-playlist-info').style.display = 'block';
        document.getElementById('individual-playlist-header').style.display = 'block';

        OCA.Audioplayer.UI.PlaylistContainer.append('<ul id="individual-playlist" class="albumwrapper"></ul>');

        var category = document.getElementById('category_selector').value;

        if (evt) {
            document.querySelector('#myCategory .active') ? document.querySelector('#myCategory .active').classList.remove('active') : false;
            evt.target.parentNode.classList.add('active');
        }

        var PlaylistId = document.querySelector('#myCategory .active').dataset.id;
        OCA.Audioplayer.Core.CategorySelectors[1] = PlaylistId;
        OCA.Audioplayer.UI.PlaylistContainer.data('playlist', category + '-' + PlaylistId);

        $('.header-title').data('order', '');
        $('.header-artist').data('order', '');
        $('.header-album').data('order', '');

        if (OCA.Audioplayer.Core.AjaxCallStatus !== null) {
            OCA.Audioplayer.Core.AjaxCallStatus.abort();
        }

        OCA.Audioplayer.Core.AjaxCallStatus = $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
            data: {category: category, categoryId: PlaylistId},
            success: OCA.Audioplayer.Category.onGetCategoryItemsResponse.bind(this, callback, category, PlaylistId)
        });
    },

    onGetCategoryItemsResponse: function (callback, category, playlistId, jsondata) {
        var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?file=';
        var canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;
        var category_title = document.querySelector('#myCategory .active').firstChild.title;

        document.getElementById('loading').style.display = 'none';
        if (jsondata.status === 'success') {
            $('.sm2-bar-ui').show();
            $(jsondata.data).each(function (i, el) {

                var li = $('<li/>').attr({
                    'data-trackid': el.id,
                    'data-mimetype': el.mim,
                    'mimetype': el.mim,
                    'data-title': el.cl1,
                    'data-artist': el.cl2,
                    'data-album': el.cl3,
                    'data-cover': el.cid,
                    'data-path': el.lin,
                    'class': 'dragable'
                });
                var fav_action;

                if (el.fav === 't') {
                    fav_action = $('<i/>').addClass('icon icon-starred')
                        .css({'opacity': 0.3})
                        .attr({'data-trackid': el.id})
                        .on('click', OCA.Audioplayer.Backend.favoriteUpdate.bind(this));
                } else {
                    fav_action = $('<i/>').addClass('icon icon-star')
                        .attr({'data-trackid': el.id})
                        .on('click', OCA.Audioplayer.Backend.favoriteUpdate.bind(this));
                }

                var stream_type;
                var streamUrl;
                var spanAction;
                var spanEdit;
                var spanTitle;

                if (el.mim === 'audio/mpegurl' || el.mim === 'audio/x-scpls' || el.mim === 'application/xspf+xml') {
                    stream_type = true;
                    streamUrl = $('<a/>').attr({'href': el.lin, 'type': el.mim});
                    spanAction = $('<span/>')
                        .addClass('actionsSong')
                        .append(fav_action)
                        .append($('<i/>').addClass('ioc ioc-volume-off'));
                } else {
                    stream_type = false;
                    streamUrl = $('<a/>').attr({'href': getAudiostreamUrl + el.lin, 'type': el.mim});
                    spanAction = $('<span/>').addClass('actionsSong')
                        .append(fav_action)
                        .append($('<i/>').addClass('ioc ioc-volume-off'));
                }
                var spanInterpret = $('<span>').attr({'class': 'interpret'});
                var spanAlbum = $('<span>').attr({'class': 'album-indi'});
                var spanTime = $('<span/>').addClass('time').text(el.len);

                if (canPlayMimeType[el.mim] || stream_type) {
                    spanTitle = $('<span/>').addClass('title').text(el.cl1);
                    spanInterpret = spanInterpret.text(el.cl2);
                    spanAlbum = spanAlbum.text(el.cl3);
                    spanEdit = $('<span/>').addClass('edit-song icon-more').attr({'title': t('audioplayer', 'Options')}).on('click', OCA.Audioplayer.Sidebar.showSidebar.bind(this));
                } else {
                    spanTitle = $('<span/>').addClass('title').html('<i>' + el.cl1 + '</i>');
                    spanInterpret = spanInterpret.html('<i>' + el.cl2 + '</i>');
                    spanAlbum = spanAlbum.html('<i>' + el.cl3 + '</i>');
                    spanEdit = $('<span/>').addClass('edit-song ioc-close').attr({'title': t('audioplayer', 'MIME type not supported by browser')}).css({
                        'opacity': 1,
                        'text-align': 'center'
                    }).on('click', OCA.Audioplayer.Sidebar.showSidebar.bind(this));
                }


                li.append(streamUrl);
                li.append(spanAction);
                li.append(spanTitle);
                li.append(spanInterpret);
                li.append(spanAlbum);
                li.append(spanTime);
                li.append(spanEdit);

                $('#individual-playlist').append(li);
            }.bind(this)); // end each loop

            OCA.Audioplayer.UI.trackClickHandler(callback);
            OCA.Audioplayer.UI.indicateCurrentPlayingTrack();

            $('.header-title').text(jsondata.header.col1);
            $('.header-artist').text(jsondata.header.col2);
            $('.header-album').text(jsondata.header.col3);
            $('.header-time').text(jsondata.header.col4);

        } else if (playlistId[0] === 'X') {
            OCA.Audioplayer.UI.showInitScreen('smart');
        } else {
            OCA.Audioplayer.UI.showInitScreen('playlist');
        }

        if (category !== 'Title') {
            $('#individual-playlist-info').html(t('audioplayer', 'Selected ' + category) + ': ' + category_title);
        } else {
            $('#individual-playlist-info').html(t('audioplayer', 'Selected') + ': ' + category_title);
        }
    },

};

/**
 * @namespace OCA.Audioplayer.UI
 */
OCA.Audioplayer.UI = {

    EmptyContainer: $('#empty-container'),
    PlaylistContainer: $('#playlist-container'),
    ActivePlaylist: $('#activePlaylist'),

    buildTrackRow: function (elem) {
        var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?file=';
        var canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;

        var li = $('<li/>').attr({
            'data-trackid': elem.id,
            'data-title': elem.cl1,
            'data-artist': elem.cl2,
            'data-cover': elem.cid,
            'data-mimetype': elem.mim,
            'data-path': elem.lin,
            'class': 'dragable'
        });

        var spanAction = $('<span/>').addClass('actionsSong').html('<i class="ioc ioc-volume-off"></i>&nbsp;');
        var spanNr = $('<span/>').addClass('number').text(elem.cl3);
        var streamUrl = $('<a/>').attr({'href': getAudiostreamUrl + elem.lin, 'type': elem.mim});
        var spanEdit = $('<span/>').addClass('edit-song icon-more').attr({'title': t('audioplayer', 'Options')}).on('click', OCA.Audioplayer.Sidebar.showSidebar.bind(this));
        var spanTitle;

        if (canPlayMimeType[elem.mim]) {
            spanTitle = $('<span/>').addClass('title').text(elem.cl1);
        } else {
            spanTitle = $('<span/>').addClass('title').html('<i>' + elem.cl1 + '</i>');
        }

        li.append(streamUrl);
        li.append(spanAction);
        li.append(spanNr);
        li.append(spanTitle);
        li.append(spanEdit);

        return li;
    },

    trackClickHandler: function (callback) {
        var albumWrapper = $('.albumwrapper');
        var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        var category = OCA.Audioplayer.UI.PlaylistContainer.data('playlist').split('-');

        var canPlayMimeType = OCA.Audioplayer.Core.canPlayMimeType;
        var playlist = albumWrapper.find('li');

        playlist.each(function (index, elm) {
            var element = $(elm);

            if (!(category[0] === 'Playlist' && category[1].toString()[0] !== 'X' && category[1] !== '')) {
                element.draggable({
                    appendTo: 'body',
                    helper: OCA.Audioplayer.Playlists.dragElement,
                    cursor: 'move',
                    delay: 500,
                    start: function (event, ui) {
                        ui.helper.addClass('draggingSong');
                    }
                });
            }

            element.find('.title').on('click',
                OCA.Audioplayer.UI.onTitleClick.bind(OCA.Audioplayer.UI, getcoverUrl, canPlayMimeType, playlist, element)
            );
        });
        // the callback is used for the the init function to get feedback when all title rows are ready
        if (typeof callback === 'function') {
            callback();
        }
    },

    onTitleClick: function (coverUrl, canPlayMimeType, playlist, element) {
        var activeLi = element.closest('li');
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
            if ($('.sm2-bar-ui').hasClass('playing')) {
                OCA.Audioplayer.Core.Player.actions.stop();
            } else {
                OCA.Audioplayer.Core.Player.actions.play();
            }
        } else {
            // the visible playlist has to be copied to the player queue
            // this disconnects the free navigation in AP while continuing to play a playlist
            if (OCA.Audioplayer.UI.PlaylistContainer.data('playlist') !== OCA.Audioplayer.UI.ActivePlaylist.data('playlist')) {
                var ClonePlaylist = playlist.clone();
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
            var playingTrackId = $('#activePlaylist li.selected').data('trackid');
            var playingListItem = $('.albumwrapper li[data-trackid="' + playingTrackId + '"]');
            playingListItem.addClass('isActive');
            playingListItem.find('i.ioc').removeClass('ioc-volume-off').addClass('ioc-volume-up').show();
            playingListItem.find('i.icon').hide();
        }
    },

    showInitScreen: function (mode) {
        $('.sm2-bar-ui').hide();
        OCA.Audioplayer.UI.PlaylistContainer.hide();
        OCA.Audioplayer.UI.EmptyContainer.show();
        OCA.Audioplayer.UI.EmptyContainer.html('');

        if (mode === 'smart') {
            OCA.Audioplayer.UI.EmptyContainer.html('<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>');
        } else if (mode === 'playlist') {
            OCA.Audioplayer.UI.EmptyContainer.html('<span class="no-songs-found">' + t('audioplayer', 'Add new tracks to playlist by drag and drop') + '</span>');
        } else {
            OCA.Audioplayer.UI.EmptyContainer.html('<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>');
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
        $('.sm2-playlist-cover').attr({'style': addCss}).text(addDescr);
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
        $('.sm2-bar-ui').width(OCA.Audioplayer.UI.PlaylistContainer.width());
        if ($('.album.is-active').length !== 0) {
            OCA.Audioplayer.Albums.buildSongContainer($('.album.is-active'));
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

};

/**
 * @namespace OCA.Audioplayer.Backend
 */
OCA.Audioplayer.Backend = {
    favoriteUpdate: function (evt) {
        var trackid = $(evt.target).attr('data-trackid');
        var isFavorite = false;

        if (OCA.Audioplayer.Core.CategorySelectors[1][0] === 'S') {
            return;
        }

        if ($(evt.target).hasClass('icon icon-starred')) {
            isFavorite = true;
            $(evt.target).removeClass('icon icon-starred');
            $(evt.target).addClass('icon icon-star').removeAttr('style');
        } else {
            isFavorite = false;
            $(evt.target).removeClass('icon icon-star');
            $(evt.target).addClass('icon icon-starred').css('opacity', 1);
        }

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setfavorite'),
            data: {
                'trackid': trackid,
                'isFavorite': isFavorite
            }
        });
        return false;
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
                    OCA.Audioplayer.Core.CategorySelectors = 'false';
                    callback(OCA.Audioplayer.Core.CategorySelectors);
                }
            }.bind(this)
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
        $.post(OC.generateUrl('apps/audioplayer/addPlaylist'), {
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
                if ($('.sm2-bar-ui').hasClass('playing')) {
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

    buildPlaylistCategoryRow: function (el, li) {
        li = $(li); // temporary workaround to apply the .droppable to a vanilla JS element
        var spanName = $('<span/>').attr({'class': 'pl-name-play'}).text(el.name).on('click', OCA.Audioplayer.Category.loadItems.bind(this));
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

};

var resizeTimeout = null;
document.addEventListener('DOMContentLoaded', function () {

    OCA.Audioplayer.Core.init();
    OCA.Audioplayer.Core.initKeyListener();
    OCA.Audioplayer.Backend.checkNewTracks();
    if ($('#audioplayer_sonos').val() !== 'checked') {
        OCA.Audioplayer.Core.Player = new SM2BarPlayer($('.sm2-bar-ui')[0]);
        OCA.Audioplayer.Core.Player.actions.setVolume($('#audioplayer_volume').val());
    }

    var notify = $('#audioplayer_notification').val();
    if (notify !== '') {
        OC.Notification.showHtml(
            notify,
            {
                type: 'error',
                isHTML: true
            }
        );
    }

    $('.sm2-bar-ui').width(OCA.Audioplayer.UI.PlaylistContainer.width());

    OCA.Audioplayer.UI.resizePlaylist = _.debounce(OCA.Audioplayer.UI.resizePlaylist.bind(OCA.Audioplayer.UI), 250);
    $('#app-content').on('appresized', OCA.Audioplayer.UI.resizePlaylist);

    $('#addPlaylist').on('click', function () {
        $('#newPlaylistTxt').val('');
        $('#newPlaylist').removeClass('ap_hidden');
    });

    $('#newPlaylistBtn_cancel').on('click', function () {
        $('#newPlaylistTxt').val('');
        $('#newPlaylist').addClass('ap_hidden');
    });

    $('#newPlaylistBtn_ok').on('click', function () {
        var newPlaylistTxt = $('#newPlaylistTxt');
        if (newPlaylistTxt.val() !== '') {
            OCA.Audioplayer.Playlists.newPlaylist(newPlaylistTxt.val());
            newPlaylistTxt.val('');
            newPlaylistTxt.trigger('focus');
            $('#newPlaylist').addClass('ap_hidden');
        }
    });

    $('#newPlaylistTxt').bind('keydown', function (event) {
        var newPlaylistTxt = $('#newPlaylistTxt');
        if (event.which === 13 && newPlaylistTxt.val() !== '') {
            OCA.Audioplayer.Playlists.newPlaylist(newPlaylistTxt.val());
            newPlaylistTxt.val('');
            newPlaylistTxt.trigger('focus');
            $('#newPlaylist').addClass('ap_hidden');
        }
    });


    $('#alben').addClass('active').on('click', function () {
        OCA.Audioplayer.Albums.load();
        OCA.Audioplayer.Backend.setUserValue('category', 'Albums');
    });


    $('#toggle_alternative').prepend('<div id="app-navigation-toggle_alternative" class="icon-menu" style="float: left; box-sizing: border-box;"></div>');

    $('#app-navigation-toggle_alternative').on('click', function () {
        $('#newPlaylist').addClass('ap_hidden');
        if ($('#app-navigation').hasClass('hidden')) {
            $('#app-navigation').removeClass('hidden');
            OCA.Audioplayer.Backend.setUserValue('navigation', 'true');
        } else {
            $('#app-navigation').addClass('hidden');
            OCA.Audioplayer.Backend.setUserValue('navigation', 'false');
        }
        OCA.Audioplayer.UI.resizePlaylist.call(OCA.Audioplayer.UI);
    });

    $('#category_selector').change(function () {
        $('#newPlaylist').addClass('ap_hidden');
        OCA.Audioplayer.Core.CategorySelectors[0] = $('#category_selector').val();
        OCA.Audioplayer.Core.CategorySelectors[1] = '';
        $('#myCategory').html('');
        if (OCA.Audioplayer.Core.CategorySelectors[0] !== '') {
            OCA.Audioplayer.Category.load();
        }
    });

    var boundSortPlaylist = OCA.Audioplayer.UI.sortPlaylist.bind(this);
    $('.header-title').on('click', boundSortPlaylist).css('cursor', 'pointer');
    $('.header-artist').on('click', boundSortPlaylist).css('cursor', 'pointer');
    $('.header-album').on('click', boundSortPlaylist).css('cursor', 'pointer');

    window.setTimeout(function () {
        $('.sm2-bar-ui').width(OCA.Audioplayer.UI.PlaylistContainer.width());
    }, 1000);

    $(window).on('resize', _.debounce(function () {
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }
        resizeTimeout = setTimeout(function () {
            OCA.Audioplayer.UI.resizePlaylist();
        }, 500);
    }));

    window.onhashchange = function () {
        if (decodeURI(location.hash).substring(1)) {
            OCA.Audioplayer.Core.processSearchResult();
        }
    };
});
