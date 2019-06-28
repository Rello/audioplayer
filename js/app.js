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

var Audios = function () {
    this.AudioPlayer = null;
    this.PlaylistContainer = $('#playlist-container');
    this.EmptyContainer = $('#empty-container');
    this.ActivePlaylist = $('#activePlaylist');
    this.albums = [];
    this.CategorySelectors = [];
    this.AjaxCallStatus = null;
    this.AlbumPlay = null;
    this.initialDocumentTitle = null;
};

Audios.prototype.init = function () {
    'use strict';

    this.initialDocumentTitle = $('title').html().trim();

    var locHash = decodeURI(location.hash).substr(1);
    if (locHash !== '') {
        var locHashTemp = locHash.split('-');

        $('#searchresults').addClass('hidden');
        window.location.href = '#';
        if (locHashTemp[0] !== 'volume' && locHashTemp[0] !== 'repeat' && locHashTemp[0] !== 'shuffle' && locHashTemp[0] !== 'prev' && locHashTemp[0] !== 'play' && locHashTemp[0] !== 'next') {
            this.CategorySelectors = locHashTemp;
            this.presetDisplay();
        }
    } else {
        // read saved values from user values
        this.getUserValue('category', this.displayCategory.bind(this));
    }

    this.initKeyListener();
    soundManager.audiosInstance = this;
    $('.toolTip').tooltip();
};

Audios.prototype.displayCategory = function () {
    'use strict';

    if (this.CategorySelectors === 'false') {
        this.showInitScreen();
    } else if (this.CategorySelectors[0] && this.CategorySelectors[0] !== 'Albums') {
        this.presetDisplay();
    } else {
        this.loadCategoryAlbums();
    }
};

Audios.prototype.presetDisplay = function () {
    'use strict';

    $('#category_selector').val(this.CategorySelectors[0]);
    this.loadCategory(this.loadLatestCategory.bind(this));
};

Audios.prototype.loadLatestCategory = function () {
    'use strict';

    if (this.CategorySelectors[1] && this.CategorySelectors[1] !== 'undefined') {
        $('#myCategory li[data-id="' + this.CategorySelectors[1] + '"]').addClass('active');
        var appNavigation = $('#app-navigation');
        appNavigation.scrollTop(appNavigation.scrollTop() + $('#myCategory li.active').first().position().top - 25);
        this.loadIndividualCategory(null, function () {                        // select the last played title
            if (this.CategorySelectors[2] && this.CategorySelectors[2] !== 'undefined') {
                var item = $('#individual-playlist li[data-trackid="' + this.CategorySelectors[2] + '"]');
                item.find('.icon').hide();
                item.find('.ioc').removeClass('ioc-volume-up').addClass('ioc-volume-off').show();
            }
        });
    }
};

Audios.prototype.initKeyListener = function () {
    'use strict';

    $(document).keyup(function (evt) {
        if (this.AudioPlayer !== null && $('#activePlaylist li').length > 0) {

            if (evt.target) {
                var nodeName = evt.target.nodeName.toUpperCase();
                //don't activate shortcuts when the user is in an input, textarea or select element
                if (nodeName === 'INPUT' || nodeName === 'TEXTAREA' || nodeName === 'SELECT') {
                    return;
                }
            }

            var currentVolume;
            var newVolume;
            if (evt.key === ' ') {//Space pause/play
                if ($('.sm2-bar-ui').hasClass('playing')) {
                    this.AudioPlayer.actions.stop();
                } else {
                    this.AudioPlayer.actions.play();
                }
            } else if (evt.key === 'ArrowRight') {// right
                this.AudioPlayer.actions.next();
            } else if (evt.key === 'ArrowLeft') {//left
                this.AudioPlayer.actions.prev();
            } else if (evt.key === 'ArrowUp') {//up sound up
                currentVolume = this.AudioPlayer.actions.getVolume();
                if (currentVolume > 0 && currentVolume <= 100) {
                    newVolume = currentVolume + 10;
                    if (newVolume >= 100) {
                        newVolume = 100;
                    }
                    this.AudioPlayer.actions.setVolume(newVolume);
                }
            } else if (evt.key === 'ArrowDown') {//down sound down
                //this.AudioPlayer.actions.setVolume(0);
                currentVolume = this.AudioPlayer.actions.getVolume();

                if (currentVolume > 0 && currentVolume <= 100) {
                    newVolume = currentVolume - 10;
                    if (newVolume <= 0) {
                        newVolume = 10;
                    }
                    this.AudioPlayer.actions.setVolume(newVolume);
                }
            }
        }
    }.bind(this));
};

Audios.prototype.loadCategoryAlbums = function () {
    'use strict';

    this.PlaylistContainer.show();
    this.EmptyContainer.hide();
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
        success: this.loadCategoryAlbumsResponse.bind(this)
    });
};

Audios.prototype.loadCategoryAlbumsResponse = function (jsondata) {
    'use strict';

    $('#loading').hide();
    if (jsondata.status === 'success') {
        $('.sm2-bar-ui').show();
        this.buildCoverRow(jsondata.data);
    } else {
        this.showInitScreen();
    }
};

Audios.prototype.buildCoverRow = function (aAlbums) {
    'use strict';

    var divAlbum = [];
    var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
    var divRow = $('<div />').addClass('coverrow');

    var boundLoadIndividualAlbums = this.loadIndividualAlbums.bind(this);
    for (var album of aAlbums) {
        var addCss;
        var addDescr;
        if (album.cid === '') {
            addCss = 'background-color: #D3D3D3;color: #333333;';
            addDescr = album.name.substring(0, 1);
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
    this.PlaylistContainer.append(divRow);
};

Audios.prototype.loadIndividualAlbums = function (evt) {
    'use strict';

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
        this.PlaylistContainer.data('playlist', 'Albums-' + AlbumId);

        activeAlbum.addClass('is-active');
        activeAlbum.find('.artist').css('visibility', 'hidden');
        this.buildSongContainer(eventTarget, directPlay);
    }
};

Audios.prototype.buildSongContainer = function (eventTarget, directPlay) {
    'use strict';

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
        addDescr = AlbumName.substring(0, 1);
    } else {
        addDescr = '';
        addCss = 'background-image:' + myCover + ';-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
    }
    var divSongContainerCover = $('<div/>').addClass('songcontainer-cover').attr({'style': addCss}).text(addDescr);
    var sidebarThumbnail = $('#sidebarThumbnail');

    if (this.PlaylistContainer.width() < 850) {
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
    this.PlaylistContainer.append(divSongContainer);

    if (this.AjaxCallStatus !== null) {
        this.AjaxCallStatus.abort();
    }

    this.AjaxCallStatus = $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
        data: {category: 'Album', categoryId: AlbumId},
        success: function (jsondata) {
            if (jsondata.status === 'success') {
                var songcounter = 0;
                $(jsondata.data).each(function (i, el) {
                    listAlbumWrapper.append(this.buildTrackRow(el));
                    songcounter++;
                }.bind(this));
                if (songcounter % 2 !== 0) {
                    var li = $('<li/>');
                    var spanNr = $('<span/>').addClass('number').text('\u00A0');
                    li.append(spanNr);
                    li.addClass('noPlaylist');
                    listAlbumWrapper.append(li); //add a blank row in case of uneven records=>avoid a Chrome bug to strangely split the records across columns
                }
                this.trackClickHandler();
                this.indicateCurrentPlayingTrack();
                if (directPlay === true) {
                    $('.albumwrapper').find('.title').first().trigger('click');

                }
            }
        }.bind(this)
    });

    var searchresult = decodeURI(location.hash).substr(1);
    if (searchresult !== '') {
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
};

Audios.prototype.buildTrackRow = function (elem) {
    'use strict';

    var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?file=';
    /* global soundManager */
    // OK because ./js/soundmanager2.js is sourced before in html
    var can_play = soundManager.html5;

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

    if (can_play[elem.mim] === true) {
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
};

Audios.prototype.trackClickHandler = function (callback) {
    'use strict';

    var albumWrapper = $('.albumwrapper');
    var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
    var category = this.PlaylistContainer.data('playlist').split('-');

    var can_play = soundManager.html5;
    var stream_array = ['audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml'];
    for (var s = 0; s < stream_array.length; s++) {
        can_play[stream_array[s]] = true;
    }

    var playlist = albumWrapper.find('li');
    var boundProcessAlbum = this.processAlbum.bind(this, category, getcoverUrl, can_play, playlist);
    playlist.each(
        boundProcessAlbum
    );
    // the callback is used for the the init function to get feedback when all title rows are ready
    if (typeof callback === 'function') {
        callback();
    }
};

Audios.prototype.processAlbum = function (category, coverUrl, can_play, playlist, i, el) {
    'use strict';

    var element = $(el);

    if (!(category[0] === 'Playlist' && category[1].toString()[0] !== 'X' && category[1] !== '')) {
        element.draggable({
            appendTo: 'body',
            helper: this.dragElement,
            cursor: 'move',
            delay: 500,
            start: function (event, ui) {
                ui.helper.addClass('draggingSong');
            }
        });
    }

    element.find('.title').on('click',
        this.onTitleClick.bind(this, coverUrl, can_play, playlist, element)
    );
};


Audios.prototype.onTitleClick = function (coverUrl, can_play, playlist, element) {
    'use strict';

    var activeLi = element.closest('li');
    // if enabled, play sonos and skip the rest of the processing
    if ($('#audioplayer_sonos').val() === 'checked') {
        var liIndex = element.parents('li').index();
        this.PlaySonos(liIndex);
        this.setStatistics();
        return;
    }
    if (can_play[activeLi.data('mimetype')] !== true) {
        console.warn(`can't play ${activeLi.data('mimetype')}`);
        return false;
    }
    if (activeLi.hasClass('isActive')) {
        if ($('.sm2-bar-ui').hasClass('playing')) {
            this.AudioPlayer.actions.stop();
        } else {
            this.AudioPlayer.actions.play();
        }
    } else {
        // the visible playlist has to be copied to the player queue
        // this disconnects the free navigation in AP while continuing to play a playlist
        if (this.PlaylistContainer.data('playlist') !== this.ActivePlaylist.data('playlist')) {
            var ClonePlaylist = playlist.clone();
            this.ActivePlaylist.html('');
            this.ActivePlaylist.append(ClonePlaylist);
            this.ActivePlaylist.find('span').remove();
            this.ActivePlaylist.find('.noPlaylist').remove();
            this.ActivePlaylist.data('playlist', this.PlaylistContainer.data('playlist'));
        }
        this.currentTrackUiChange(coverUrl, activeLi);
        if (this.AudioPlayer.playlistController.data.selectedIndex === null) {
            this.AudioPlayer.playlistController.data.selectedIndex = 0;
        }
        this.AudioPlayer.actions.play(activeLi.index());
        this.setStatistics();
    }
};

Audios.prototype.indicateCurrentPlayingTrack = function () {
    'use strict';

    if (this.PlaylistContainer.data('playlist') === this.ActivePlaylist.data('playlist')) {
        var playingTrackId = $('#activePlaylist li.selected').data('trackid');
        var playingListItem = $('.albumwrapper li[data-trackid="' + playingTrackId + '"]');
        playingListItem.addClass('isActive');
        playingListItem.find('i.ioc').removeClass('ioc-volume-off').addClass('ioc-volume-up').show();
        playingListItem.find('i.icon').hide();
    }
};

Audios.prototype.loadCategory = function (callback) {
    $this = this;
    //var category = $this.CategorySelectors[0]; // use the variable and not the drop_down value???
    var category = $('#category_selector').val();
    var addPlaylist = $('#addPlaylist');
    addPlaylist.addClass('hidden');
    $('#myCategory').html('');
    $('.toolTip').tooltip('hide');
    $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/getcategory'),
        data: {category: category},
        success: function (jsondata) {
            if (jsondata.status === 'success') {
                $(jsondata.data).each(function (i, el) {
                    var li = $('<li/>').attr({'data-id': el.id, 'data-name': el.name});
                    var spanCounter = $('<span/>').attr('class', 'counter').text(el.counter);
                    var spanName;

                    if (category === 'Playlist' && el.id.toString()[0] !== 'X' && el.id !== '' && el.id.toString()[0] !== 'S') {
                        spanName = $('<span/>').attr({'class': 'pl-name-play'}).text(el.name).on('click', $this.loadIndividualCategory.bind($this));
                        var spanSort = $('<i/>').attr({
                            'class': 'ioc ioc-sort toolTip',
                            'data-sortid': el.id,
                            'title': t('audioplayer', 'Sort playlist')
                        }).on('click', $this.sortPlaylist.bind($this));
                        var spanEdit = $('<i/>').attr({
                            'class': 'icon icon-rename toolTip',
                            'data-name': el.name,
                            'data-editid': el.id,
                            'title': t('audioplayer', 'Rename playlist')
                        }).on('click', $this.renamePlaylist.bind($this));
                        var spanDelete = $('<i/>').attr({
                            'class': 'ioc ioc-delete toolTip',
                            'data-deleteid': el.id,
                            'title': t('audioplayer', 'Delete playlist')
                        }).on('click', $this.deletePlaylist.bind($this));
                        li.droppable({
                            activeClass: 'activeHover',
                            hoverClass: 'dropHover',
                            accept: 'li.dragable',
                            over: function () {
                            },
                            drop: function (event, ui) {
                                $this.addSongToPlaylist($(this).attr('data-id'), ui.draggable.attr('data-trackid'));
                            }
                        });
                        li.append(spanName);
                        li.append(spanEdit);
                        li.append(spanSort);
                        li.append(spanDelete);
                        li.append(spanCounter);
                    } else if (el.id === '') {
                        spanName = $('<span/>').text(el.name).css({'float': 'left', 'min-height': '10px'});
                        li.append(spanName);
                        li.append(spanCounter);
                    } else {
                        spanName = $('<span/>').attr({
                            'class': 'pl-name',
                            'title': el.name
                        }).text(el.name).on('click', $this.loadIndividualCategory.bind($this));
                        li.append(spanName);
                        li.append(spanCounter);
                    }
                    $('#myCategory').append(li);
                });
                if (typeof callback === 'function') {
                    callback();
                }
                $('.toolTip').tooltip();
            } else {
                $this.showInitScreen();
            }
        }
    });
    if (category === 'Playlist') {
        addPlaylist.removeClass('hidden');
    }
    return true;
};


Audios.prototype.loadIndividualCategory = function (evt, callback) {
    'use strict';

    this.PlaylistContainer.show();
    this.EmptyContainer.hide();
    $('#loading').show();
    $('.toolTip').tooltip('hide');
    $('#alben').removeClass('active');
    var individual_playlist = $('#individual-playlist');
    individual_playlist.remove();
    $('#individual-playlist-info').show();
    $('#individual-playlist-header').show();
    $('.coverrow').remove();
    $('.songcontainer').remove();

    this.PlaylistContainer.append('<ul id="individual-playlist" class="albumwrapper"></ul>');

    var category = $('#category_selector').val();

    if (typeof evt !== 'undefined' && evt !== null) {
        $('#myCategory li').removeClass('active').removeClass('active');
        var eventTarget = $(evt.target);
        eventTarget.parent('li').addClass('active').addClass('active');
    }

    var categoryActive = $('#myCategory li.active');
    var PlaylistId = categoryActive.data('id');
    this.CategorySelectors[1] = PlaylistId;
    this.PlaylistContainer.data('playlist', category + '-' + PlaylistId);


    if (individual_playlist.data('ui-sortable')) { $('#individual-playlist').sortable('destroy'); }
    $('.header-title').data('order', '');
    $('.header-artist').data('order', '');
    $('.header-album').data('order', '');

    if (this.AjaxCallStatus !== null) {
        this.AjaxCallStatus.abort();
    }

    this.AjaxCallStatus = $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
        data: {category: category, categoryId: PlaylistId},
        success: this.onGetCategoryItemsResponse.bind(this, callback, category, categoryActive, PlaylistId)
    });
};

Audios.prototype.onGetCategoryItemsResponse = function (callback, category, categoryActive, playlistId, jsondata) {
    'use strict';
    var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream') + '?file=';
    var albumcount = '';
    var can_play = soundManager.html5;
    var stream_array = ['audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml'];
    for (var s = 0; s < stream_array.length; s++) {
        can_play[stream_array[s]] = true;
    }
    var category_title = categoryActive.find('span').first().text();
    $('#loading').hide();
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
                    .on('click', this.favoriteUpdate.bind(this));
            } else {
                fav_action = $('<i/>').addClass('icon icon-star')
                    .attr({'data-trackid': el.id})
                    .on('click', this.favoriteUpdate.bind(this));
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

            if (can_play[el.mim] === true || stream_type === true) {
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

        this.trackClickHandler(callback);
        this.indicateCurrentPlayingTrack();

        $('.header-title').text(jsondata.header.col1);
        $('.header-artist').text(jsondata.header.col2);
        $('.header-album').text(jsondata.header.col3);
        $('.header-time').text(jsondata.header.col4);

        if (jsondata.albums >> 1) {
            albumcount = ' (' + jsondata.albums + ' ' + t('audioplayer', 'Albums') + ')';
        } else {
            albumcount = '';
        }

    } else if (playlistId.toString()[0] === 'X') {
        this.showInitScreen('smart');
    } else {
        this.showInitScreen('playlist');
    }

    if (category !== 'Title') {
        $('#individual-playlist-info').html(t('audioplayer', 'Selected ' + category) + ': ' + category_title + albumcount);
    } else {
        $('#individual-playlist-info').html(t('audioplayer', 'Selected') + ': ' + category_title + albumcount);
    }
};

Audios.prototype.showInitScreen = function (mode) {
    'use strict';
    $('.sm2-bar-ui').hide();
    this.PlaylistContainer.hide();
    this.EmptyContainer.show();
    this.EmptyContainer.html('');

    if (mode === 'smart') {
        this.EmptyContainer.html('<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>');
    } else if (mode === 'playlist') {
        this.EmptyContainer.html('<span class="no-songs-found">' + t('audioplayer', 'Add new tracks to playlist by drag and drop') + '</span>');
    } else {
        this.EmptyContainer.html('<span class="no-songs-found">' + t('audioplayer', 'Welcome to') + ' ' + t('audioplayer', 'Audio Player') + '</span>');
        this.EmptyContainer.append('<span class="no-songs-found-pl"><i class="ioc ioc-refresh" title="' + t('audioplayer', 'Scan for new audio files') + '" id="scanAudiosFirst"></i> ' + t('audioplayer', 'Add new tracks to library') + '</span>');
        this.EmptyContainer.append('<a class="no-songs-found-pl" href="https://github.com/rello/audioplayer/wiki" target="_blank">' + t('audioplayer', 'Help') + '</a>');
    }
};

Audios.prototype.dragElement = function () {
    'use strict';
    return $(this).clone().text($(this).find('.title').attr('data-title'));
};

Audios.prototype.favoriteUpdate = function (evt) {
    'use strict';
    var trackid = $(evt.target).attr('data-trackid');
    var isFavorite = false;

    if (this.CategorySelectors[1].toString().substring(0, 1) === 'S') { return; }

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
};

Audios.prototype.addSongToPlaylist = function (plId, songId) {
    'use strict';
    var sort = parseInt($('#myPlayList li[data-id="' + plId + '"]').find('.counter').text());
    return $.getJSON(OC.generateUrl('apps/audioplayer/addtracktoplaylist'), {
        playlistid: plId,
        songid: songId,
        sorting: (sort + 1)
    }).then(function () {
        $('.toolTip').tooltip('hide');
        this.CategorySelectors[0] = 'Playlist';
        this.loadCategory();
    }.bind(this));
};

Audios.prototype.newPlaylist = function (plName) {
    'use strict';
    $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/addplaylist'),
        data: {'playlist': plName},
        success: function (jsondata) {
            if (jsondata.status === 'success') {
                this.loadCategory();
            }
            if (jsondata.status === 'error') {
                $('#notification').text(t('audioplayer', 'No playlist selected!')).slideDown();
                window.setTimeout(function () {
                    $('#notification').slideUp();
                }, 3000);
            }
        }.bind(this)
    });
};

Audios.prototype.renamePlaylist = function (evt) {
    'use strict';
    var eventTarget = $(evt.target);
    if ($('.plclone').length === 1) {
        var plId = eventTarget.data('editid');
        var plistName = eventTarget.data('name');
        var myClone = $('#pl-clone').clone();


        $('#myCategory li[data-id="' + plId + '"]').after(myClone);
        myClone.attr('data-pl', plId).show();
        $('#myCategory li[data-id="' + plId + '"]').hide();

        var boundGenerateRenameRequest = this.generateRenameRequest.bind(this);
        myClone.find('input[name="playlist"]')
            .bind('keydown', function (event) {
                if (event.which === 13) {
                    if (myClone.find('input[name="playlist"]').val() !== '') {
                        boundGenerateRenameRequest(plId, myClone);
                    } else {
                        myClone.remove();
                        $('#myCategory li[data-id="' + plId + '"]').show();
                    }
                }
            })
            .val(plistName).trigger('focus');


        myClone.on('keyup', function (evt) {
            if (evt.keyCode === 27) {
                myClone.remove();
                $('#myCategory li[data-id="' + plId + '"]').show();
            }
        });
        myClone.find('button.icon-checkmark').on('click', function () {
            if (myClone.find('input[name="playlist"]').val() !== '') {
                boundGenerateRenameRequest(plId, myClone);
            }
        });
        myClone.find('button.icon-close').on('click', function () {
            this.loadCategory();
            myClone.remove();
        });
    }
};

Audios.prototype.generateRenameRequest = function (playlistId, playlistClone) {
    'use strict';

    var saveForm = $('.plclone[data-pl="' + playlistId + '"]');
    var plname = saveForm.find('input[name="playlist"]').val();

    $.getJSON(OC.generateUrl('apps/audioplayer/updateplaylist'), {
        plId: playlistId,
        newname: plname
    }, function (jsondata) {
        if (jsondata.status === 'success') {
            this.loadCategory();
            playlistClone.remove();
        }
        if (jsondata.status === 'error') {
            alert('could not update playlist');
        }
    }.bind(this));
};

Audios.prototype.sortPlaylist = function (evt) {
    'use strict';
    var eventTarget = $(evt.target);
    var notification = $('#notification');
    if ($('#myCategory li').hasClass('active')) {
        var plId = eventTarget.attr('data-sortid');
        if (eventTarget.hasClass('sortActive')) {

            var idsInOrder = $('#individual-playlist').sortable('toArray', {attribute: 'data-trackid'});
            $.getJSON(OC.generateUrl('apps/audioplayer/sortplaylist'), {
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
            }.bind(this));

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
                this.AudioPlayer.actions.pause();
                $('#individual-playlist li').removeClass('isActive');
                $('#individual-playlist li i.ioc').hide();
            } else {
                $('#individual-playlist li').removeClass('isActive');
                $('#individual-playlist li i.ioc').hide();
            }

        }
    }
};

Audios.prototype.deletePlaylist = function (evt) {
    'use strict';

    var plId = $(evt.target).attr('data-deleteid');

    OC.dialogs.message(
        t('audioplayer', 'Are you sure?'),
        t('audioplayer', 'Delete playlist'),
        null,
        OCdialogs.YES_NO_BUTTONS,
        function (e) {
            if (e === true) {
                $.ajax({
                    type: 'GET',
                    url: OC.generateUrl('apps/audioplayer/removeplaylist'),
                    data: {'playlistid': plId},
                    success: function (jsondata) {
                        if (jsondata.status === 'success') {
                            this.loadCategory();
                            $('#notification').text(t('audioplayer', 'Playlist successfully deleted!')).slideDown();
                            window.setTimeout(function () {
                                $('#notification').slideUp();
                            }, 3000);
                        }
                    }.bind(this)
                });
            }
        }.bind(this),
        true
    );
    return false;

};

Audios.prototype.getUserValue = function (user_type, callback) {
    'use strict';

    $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/getvalue'),
        data: {'type': user_type},
        success: function (jsondata) {
            if (jsondata.status === 'success' && user_type === 'category') {
                this.CategorySelectors = jsondata.value.split('-');
                callback(this.CategorySelectors);
            } else if (jsondata.status === 'false' && user_type === 'category'){
                this.CategorySelectors = 'false';
                callback(this.CategorySelectors);
            }
        }
    });
};

Audios.prototype.setUserValue = function (user_type, user_value) {
    'use strict';

    if (user_type) {
        if (user_type === 'category') { this.CategorySelectors = user_value.split('-'); }
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
};

Audios.prototype.setStatistics = function () {
    'use strict';

    var track_id = $('#activePlaylist li.selected').data('trackid');
    if (track_id) {
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setstatistics'),
            data: {'track_id': track_id},
            success: function () {
            }
        });
        this.setUserValue('category', this.CategorySelectors[0] + '-' + this.CategorySelectors[1] + '-' + track_id);
    }

};

Audios.prototype.sortPlaylist = function (evt) {
    'use strict';

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

    if (this.PlaylistContainer.data('playlist') === this.ActivePlaylist.data('playlist')) {
        elems = this.ActivePlaylist.children('li').get();
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
        this.ActivePlaylist.append(elems);
    }

    if (this.AudioPlayer) {
        this.AudioPlayer.playlistController.data.selectedIndex = $('#activePlaylist li.selected').index();
    }
};

Audios.prototype.currentTrackUiChange = function (coverUrl, activeLi) {
    'use strict';

    var addCss;
    var addDescr;
    var coverID = activeLi.data('cover');
    if (coverID === '') {
        addCss = 'background-color: #D3D3D3;color: #333333;';
        addDescr = activeLi.data('title').substring(0, 1);
    } else {
        addCss = 'background-image:url(' + coverUrl + coverID + ');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
        addDescr = '';
    }
    $('.sm2-playlist-cover').attr({'style': addCss}).text(addDescr);
    document.title = activeLi.data('title') + ' (' + activeLi.data('artist') + ' ) @ ' + this.initialDocumentTitle;
};

Audios.prototype.soundmanagerCallback = function (SMaction) {
    'use strict';

    if (SMaction === 'setVolume') {
        this.setUserValue('volume', Math.round(this.AudioPlayer.actions.getVolume()));
    } else {
        this.currentTrackUiChange(
            OC.generateUrl('apps/audioplayer/getcover/'),
            $('#activePlaylist li.selected')
        );
        this.setStatistics();
    }
};

Audios.prototype.checkNewTracks = function () {
    'use strict';

    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/checknewtracks'),
        success: function (data) {
            if (data === 'true') {
                OC.Notification.showTemporary(t('audioplayer', 'New or updated audio files available'));
            }
        }
    });
};

Audios.prototype.resizePlaylist = function () {
    'use strict';

    var songlist = $('.songcontainer .songlist');
    $('.sm2-bar-ui').width(this.PlaylistContainer.width());
    if ($('.album.is-active').length !== 0) {
        this.buildSongContainer($('.album.is-active'));
    }

    if (this.PlaylistContainer.width() < 850) {
        songlist.addClass('one-column');
        songlist.removeClass('two-column');
        $('.songcontainer .songcontainer-cover').addClass('cover-small');
    } else {
        songlist.removeClass('one-column');
        songlist.addClass('two-column');
        $('.songcontainer .songcontainer-cover').removeClass('cover-small');
    }
};

var resizeTimeout = null;
$(document).ready(function () {
    'use strict;';

    var myAudios = new Audios();
    myAudios.init();
    myAudios.checkNewTracks();
    if ($('#audioplayer_sonos').val() !== 'checked') {
        /* global SM2BarPlayer */
        // OK, because ./js/soundmanager2.js is sourced before in html
        myAudios.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
        myAudios.AudioPlayer.actions.setVolume($('#audioplayer_volume').val());
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

    $('.sm2-bar-ui').width(myAudios.PlaylistContainer.width());

    myAudios.resizePlaylist = _.debounce(myAudios.resizePlaylist.bind(myAudios), 250);
    $('#app-content').on('appresized', myAudios.resizePlaylist);

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
            myAudios.newPlaylist(newPlaylistTxt.val());
            newPlaylistTxt.val('');
            newPlaylistTxt.trigger('focus');
            $('#newPlaylist').addClass('ap_hidden');
        }
    });

    $('#newPlaylistTxt').bind('keydown', function (event) {
        var newPlaylistTxt = $('#newPlaylistTxt');
        if (event.which === 13 && newPlaylistTxt.val() !== '') {
            myAudios.newPlaylist(newPlaylistTxt.val());
            newPlaylistTxt.val('');
            newPlaylistTxt.trigger('focus');
            $('#newPlaylist').addClass('ap_hidden');
        }
    });


    $('#alben').addClass('active').on('click', function () {
        myAudios.loadCategoryAlbums();
        myAudios.setUserValue('category', 'Albums');
    });


    $('#toggle_alternative').prepend('<div id="app-navigation-toggle_alternative" class="icon-menu" style="float: left; box-sizing: border-box;"></div>');

    $('#app-navigation-toggle_alternative').on('click', function () {
        $('#newPlaylist').addClass('ap_hidden');
        if ($('#app-navigation').hasClass('hidden')) {
            $('#app-navigation').removeClass('hidden');
            myAudios.setUserValue('navigation', 'true');
        } else {
            $('#app-navigation').addClass('hidden');
            myAudios.setUserValue('navigation', 'false');
        }
        myAudios.resizePlaylist.call(myAudios);
    });

    $('#category_selector').change(function () {
        $('#newPlaylist').addClass('ap_hidden');
        myAudios.CategorySelectors[0] = $('#category_selector').val();
        myAudios.CategorySelectors[1] = '';
        $('#myCategory').html('');
        if (myAudios.CategorySelectors[0] !== '') {
            myAudios.loadCategory();
        }
    });

    var boundSortPlaylist = myAudios.sortPlaylist.bind(myAudios);
    $('.header-title').on('click', boundSortPlaylist).css('cursor', 'pointer');
    $('.header-artist').on('click', boundSortPlaylist).css('cursor', 'pointer');
    $('.header-album').on('click', boundSortPlaylist).css('cursor', 'pointer');

    window.setTimeout(function () {
        $('.sm2-bar-ui').width(myAudios.PlaylistContainer.width());
    }, 1000);

    $(window).on('resize', _.debounce(function () {
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }
        resizeTimeout = setTimeout(function () {
            myAudios.resizePlaylist();
        }, 500);
    }));

    window.onhashchange = function () {
        var locHash = decodeURI(location.hash).substr(1);
        if (locHash !== '') {
            var locHashTemp = locHash.split('-');

            $('#searchresults').addClass('hidden');
            window.location.href = '#';
            if (locHashTemp[0] !== 'volume' && locHashTemp[0] !== 'repeat' && locHashTemp[0] !== 'shuffle' && locHashTemp[0] !== 'prev' && locHashTemp[0] !== 'play' && locHashTemp[0] !== 'next') {
                myAudios.CategorySelectors = locHashTemp;
                myAudios.presetDisplay();
            }
        }
    };
});
