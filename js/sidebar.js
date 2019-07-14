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
    OCA.Audioplayer = {};
}
if (!OCA.Audioplayer.Sidebar) {
    /**
     * @namespace
     */
    OCA.Audioplayer.Sidebar = {};
}

/**
 * @namespace OCA.Audioplayer.Sidebar
 */
OCA.Audioplayer.Sidebar = {
    sidebar_tabs: {},

    showSidebar: function (evt) {

        var trackid = $(evt.target).closest('li').attr('data-trackid');
        var $appsidebar = $('#app-sidebar');

        if ($appsidebar.data('trackid') === trackid) {
            OCA.Audioplayer.Sidebar.hideSidebar();
        } else {
            var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
            var trackData = $('li[data-trackid=\'' + trackid + '\']');
            var cover = trackData.attr('data-cover');
            var sidebarThumbnail = $('#sidebarThumbnail');
            $('.thumbnailContainer').addClass('portrait large');

            if (cover !== '') {
                sidebarThumbnail.attr({
                    'style': 'background-image:url(' + getcoverUrl + cover + ')'
                });
            } else {
                sidebarThumbnail.attr({
                    'style': 'display: none;'
                });
            }

            $('#sidebarTitle').html(decodeURIComponent(trackData.attr('data-path')));
            $('#sidebarMime').html(trackData.attr('data-mimetype'));

            var starIcon = $('#sidebarFavorite').attr({'data-trackid': trackid});
            starIcon.off();
            starIcon.on('click',
                OCA.Audioplayer.audiosInstance.favoriteUpdate.bind(OCA.Audioplayer.audiosInstance)
            );

            if ($appsidebar.data('trackid') === '') {
                $('#sidebarClose').on('click', OCA.Audioplayer.Sidebar.hideSidebar);

                OCA.Audioplayer.Sidebar.constructTabs();
                $('#tabHeaderMetadata').addClass('selected');
                OC.Apps.showAppSidebar();
            }

            $appsidebar.data('trackid', trackid);
            document.getElementById('app-sidebar').dataset.trackid = trackid; //start moving to vanilla js
            $('.tabHeader.selected').trigger('click');
        }
    },

    registerSidebarTab: function (tab) {
        var id = tab.id;
        this.sidebar_tabs[id] = tab;
    },

    constructTabs: function () {
        var tab = {};

        $('.tabHeaders').empty();
        $('.tabsContainer').empty();

        OCA.Audioplayer.Sidebar.registerSidebarTab({
            id: 'tabHeaderAddons',
            class: 'addonsTabView',
            tabindex: '9',
            name: t('audioplayer', 'Add-ons'),
            action: OCA.Audioplayer.Sidebar.addonsTabView,
        });

        OCA.Audioplayer.Sidebar.registerSidebarTab({
            id: 'tabHeaderMetadata',
            class: 'metadataTabView',
            tabindex: '1',
            name: t('audioplayer', 'Metadata'),
            action: OCA.Audioplayer.Sidebar.metadataTabView,
        });

        OCA.Audioplayer.Sidebar.registerSidebarTab({
            id: 'tabHeaderPlaylists',
            class: 'playlistsTabView',
            tabindex: '2',
            name: t('audioplayer', 'Playlists'),
            action: OCA.Audioplayer.Sidebar.playlistsTabView,
        });

        var items = _.map(OCA.Audioplayer.Sidebar.sidebar_tabs, function (item) {
            return item;
        });
        items.sort(OCA.Audioplayer.Sidebar.sortByName);

        for (tab in items) {
            var li = $('<li/>').addClass('tabHeader')
                .attr({
                    'id': items[tab].id,
                    'tabindex': items[tab].tabindex
                });
            var atag = $('<a/>').text(items[tab].name);
            li.append(atag);
            $('.tabHeaders').append(li);

            var div = $('<div/>').addClass('tab ' + items[tab].class)
                .attr({
                    'id': items[tab].class
                });
            $('.tabsContainer').append(div);
            $('#' + items[tab].id).on('click', items[tab].action);
        }
    },

    hideSidebar: function () {
        $('#app-sidebar').data('trackid', '');
        OC.Apps.hideAppSidebar();
        $('.tabHeaders').empty();
        $('.tabsContainer').empty();
    },

    metadataTabView: function () {
        var trackid = $('#app-sidebar').data('trackid');

        OCA.Audioplayer.Sidebar.resetView();
        $('#tabHeaderMetadata').addClass('selected');
        $('#metadataTabView').removeClass('hidden').html('<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>');

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/getaudioinfo'),
            data: {trackid: trackid},
            success: function (jsondata) {
                var table;
                if (jsondata.status === 'success') {

                    table = $('<div>').css('display', 'table').addClass('table');
                    var tablerow;
                    var m;
                    var tablekey;
                    var tablevalue;

                    var audioinfo = jsondata.data;
                    for (m in audioinfo) {
                        tablerow = $('<div>').css('display', 'table-row');
                        tablekey = $('<div>').addClass('key').text(t('audioplayer', m));
                        tablevalue = $('<div>').addClass('value')
                            .text(audioinfo[m]);
                        tablerow.append(tablekey).append(tablevalue);

                        if (m === 'fav' && audioinfo[m] === 't') {
                            $('#sidebarFavorite').removeClass('icon-star')
                                .addClass('icon-starred')
                                .prop('title', t('files', 'Favorited'));
                            audioinfo[m] = '';
                        } else if (m === 'fav') {
                            $('#sidebarFavorite').removeClass('icon-starred')
                                .addClass('icon-star')
                                .prop('title', t('files', 'Favorite'));
                            audioinfo[m] = '';
                        }

                        if (audioinfo[m] !== '' && audioinfo[m] !== null) {
                            table.append(tablerow);
                        }
                    }
                } else {
                    table = '<div style="margin-left: 2em;" class="get-metadata"><p>' + t('audioplayer', 'No data') + '</p></div>';
                }

                $('#metadataTabView').html(table);
            }
        });
    },

    playlistsTabView: function () {
        var trackid = $('#app-sidebar').data('trackid');

        OCA.Audioplayer.Sidebar.resetView();
        $('#tabHeaderPlaylists').addClass('selected');
        $('#playlistsTabView').removeClass('hidden').html('<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>');

        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/getplaylists'),
            data: {trackid: trackid},
            success: function (jsondata) {
                var table;
                if (jsondata.status === 'success') {

                    table = $('<div>').css('display', 'table').addClass('table');
                    var tablerow;
                    var m;
                    var tablekey;
                    var tablevalue;

                    var audioinfo = jsondata.data;
                    for (m in audioinfo) {
                        var spanDelete = $('<a/>').attr({
                            'class': 'icon icon-delete toolTip',
                            'data-listid': audioinfo[m].playlist_id,
                            'data-trackid': trackid,
                            'title': t('audioplayer', 'Remove')
                        }).on('click', OCA.Audioplayer.Playlists.removeSongFromPlaylist.bind(OCA.Audioplayer.Playlists));

                        tablerow = $('<div>').css('display', 'table-row').attr({'data-id': audioinfo[m].playlist_id});
                        tablekey = $('<div>').addClass('key').append(spanDelete);

                        tablevalue = $('<div>').addClass('value')
                            .text(audioinfo[m].name);
                        tablerow.append(tablekey).append(tablevalue);
                        table.append(tablerow);
                    }
                } else {
                    table = '<div style="margin-left: 2em;" class="get-metadata"><p>' + t('audioplayer', 'No playlist entry') + '</p></div>';
                }

                $('#playlistsTabView').html(table);
            }
        });

    },

    addonsTabView: function () {
        OCA.Audioplayer.Sidebar.resetView();
        $('#tabHeaderAddons').addClass('selected');
        var html = '<div style="margin-left: 2em; background-position: initial;" class="icon-info">';
        html += '<p style="margin-left: 2em;">' + t('audioplayer', 'Available Audio Player Add-Ons:') + '</p>';
        html += '<p style="margin-left: 2em;"><br></p>';
        html += '<a href="https://github.com/rello/audioplayer_editor"  target="_blank" >';
        html += '<p style="margin-left: 2em;">- ' + t('audioplayer', 'ID3 editor') + '</p>';
        html += '</a>';
        html += '<a href="https://github.com/rello/audioplayer_sonos"  target="_blank" >';
        html += '<p style="margin-left: 2em;">- ' + t('audioplayer', 'SONOS playback') + '</p>';
        html += '</a></div>';
        $('#addonsTabView').removeClass('hidden').html(html);
    },

    resetView: function () {
        $('.tabHeader.selected').removeClass('selected');
        $('.tab').addClass('hidden');
    },

    sortByName: function (a, b) {
        var aName = a.tabindex;
        var bName = b.tabindex;
        return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    },
};
