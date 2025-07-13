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

    showSidebar: function (evt, trkid) {
        if (typeof trkid !== 'undefined') {
            var trackid = trkid;
        } else {
            var targetPlaylistItem = evt.target.closest('li');
            var trackid = targetPlaylistItem.getAttribute('data-trackid');
        }

        var appsidebar = document.getElementById('app-sidebar');

        if (appsidebar.dataset.trackid === trackid) {
            OCA.Audioplayer.Sidebar.hideSidebar();
        } else {
            var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
            var trackData = document.querySelector('li[data-trackid="' + trackid + '"]');
            var cover = trackData ? trackData.getAttribute('data-cover') : '';
            var sidebarThumbnail = document.getElementById('sidebarThumbnail');
            document.querySelectorAll('.thumbnailContainer').forEach(function (el) {
                el.classList.add('portrait', 'large');
            });

            if (cover !== '') {
                sidebarThumbnail.setAttribute('style', 'background-image:url(' + getcoverUrl + cover + ')');
            } else {
                sidebarThumbnail.setAttribute('style', 'display: none;');
            }

            document.getElementById('sidebarTitle').innerHTML = decodeURIComponent(trackData.getAttribute('data-title'));
            document.getElementById('sidebarMime').innerHTML = trackData.getAttribute('data-mimetype');

            var starIcon = document.getElementById('sidebarFavorite');
            starIcon.dataset.trackid = trackid;
            starIcon.removeEventListener('click', OCA.Audioplayer.Core.toggleFavorite);
            starIcon.addEventListener('click', OCA.Audioplayer.Core.toggleFavorite);

            if (appsidebar.dataset.trackid === '') {
                document.getElementById('sidebarClose').addEventListener('click', OCA.Audioplayer.Sidebar.hideSidebar);

                OCA.Audioplayer.Sidebar.constructTabs();
                document.getElementById('tabHeaderMetadata').classList.add('selected');
                appsidebar.classList.remove('disappear');
            }

            appsidebar.dataset.trackid = trackid;
            var selectedHeader = document.querySelector('.tabHeader.selected');
            if (selectedHeader) {
                selectedHeader.click();
            }
            OCA.Audioplayer.UI.resizePlaylist();
        }
    },

    registerSidebarTab: function (tab) {
        var id = tab.id;
        this.sidebar_tabs[id] = tab;
    },

    constructTabs: function () {
        var tab = {};

        document.querySelector('.tabHeaders').innerHTML = '';
        document.querySelector('.tabsContainer').innerHTML = '';

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
            var li = document.createElement('li');
            li.classList.add('tabHeader');
            li.setAttribute('id', items[tab].id);
            li.setAttribute('tabindex', items[tab].tabindex);
            var atag = document.createElement('a');
            atag.textContent = items[tab].name;
            atag.title = items[tab].name;
            li.appendChild(atag);
            document.querySelector('.tabHeaders').appendChild(li);

            var div = document.createElement('div');
            div.className = 'tab ' + items[tab].class;
            div.setAttribute('id', items[tab].class);
            document.querySelector('.tabsContainer').appendChild(div);
            document.getElementById(items[tab].id).addEventListener('click', items[tab].action);
        }
    },

    hideSidebar: function () {
        document.getElementById('app-sidebar').dataset.trackid = '';
        document.getElementById('app-sidebar').classList.add('disappear');
        document.querySelector('.tabHeaders').innerHTML = '';
        document.querySelector('.tabsContainer').innerHTML = '';
        OCA.Audioplayer.UI.resizePlaylist();
    },

    metadataTabView: function () {
        var trackid = document.getElementById('app-sidebar').dataset.trackid;

        OCA.Audioplayer.Sidebar.resetView();
        document.getElementById('tabHeaderMetadata').classList.add('selected');
        var metadataTabView = document.getElementById('metadataTabView');
        metadataTabView.classList.remove('hidden');
        metadataTabView.innerHTML = '<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>';

        fetch(
            OC.generateUrl('apps/audioplayer/getaudioinfo') + '?trackid=' + encodeURIComponent(trackid),
            {method: 'GET', headers: OCA.Audioplayer.headers()}
        ).then(function (response) {
            return response.json();
        }).then(function (jsondata) {
            var table;
            if (jsondata.status === 'success') {
                table = document.createElement('div');
                table.style.display = 'table';
                table.classList.add('table');

                var audioinfo = jsondata.data;
                for (var m in audioinfo) {
                    var tablerow = document.createElement('div');
                    tablerow.style.display = 'table-row';
                    var tablekey = document.createElement('div');
                    tablekey.classList.add('key');
                    tablekey.textContent = t('audioplayer', m);
                    var tablevalue = document.createElement('div');
                    tablevalue.classList.add('value');
                    tablevalue.textContent = audioinfo[m];
                    if (m === 'Path') {
                        tablevalue.textContent = '';
                        var tablevalueDownload = document.createElement('a');
                        tablevalueDownload.setAttribute('href', OC.linkToRemote('webdav' + audioinfo[m]));
                        tablevalueDownload.textContent = audioinfo[m];
                        tablevalue.appendChild(tablevalueDownload);
                    }
                    tablerow.appendChild(tablekey);
                    tablerow.appendChild(tablevalue);

                    if (m === 'fav' && audioinfo[m] === 't') {
                        var fav = document.getElementById('sidebarFavorite');
                        fav.classList.remove('icon-star');
                        fav.classList.add('icon-starred');
                        fav.title = t('files', 'Favorited');
                        audioinfo[m] = '';
                    } else if (m === 'fav') {
                        var fav2 = document.getElementById('sidebarFavorite');
                        fav2.classList.remove('icon-starred');
                        fav2.classList.add('icon-star');
                        fav2.title = t('files', 'Favorite');
                        audioinfo[m] = '';
                    }

                    if (audioinfo[m] !== '' && audioinfo[m] !== null) {
                        table.appendChild(tablerow);
                    }
                }
            } else {
                table = document.createElement('div');
                table.setAttribute('style', 'margin-left: 2em;');
                table.classList.add('get-metadata');
                table.innerHTML = '<p>' + t('audioplayer', 'No data') + '</p>';
            }

            var metadataTabView = document.getElementById('metadataTabView');
            metadataTabView.innerHTML = '';
            metadataTabView.appendChild(table);
        });
    },

    playlistsTabView: function () {
        var trackid = document.getElementById('app-sidebar').dataset.trackid;

        OCA.Audioplayer.Sidebar.resetView();
        document.getElementById('tabHeaderPlaylists').classList.add('selected');
        var playlistsTabView = document.getElementById('playlistsTabView');
        playlistsTabView.classList.remove('hidden');
        playlistsTabView.innerHTML = '<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>';

        fetch(
            OC.generateUrl('apps/audioplayer/getplaylists'),
            {
                method: 'POST',
                headers: OCA.Audioplayer.headers(),
                body: JSON.stringify({trackid: trackid})
            }
        ).then(function (response) {
            return response.json();
        }).then(function (jsondata) {
            var table;
            if (jsondata.status === 'success') {
                table = document.createElement('div');
                table.style.display = 'table';
                table.classList.add('table');
                var audioinfo = jsondata.data;
                for (var m in audioinfo) {
                    var spanDelete = document.createElement('a');
                    spanDelete.setAttribute('class', 'icon icon-delete toolTip');
                    spanDelete.dataset.listid = audioinfo[m].playlist_id;
                    spanDelete.dataset.trackid = trackid;
                    spanDelete.title = t('audioplayer', 'Remove');
                    spanDelete.addEventListener('click', OCA.Audioplayer.Playlists.removeSongFromPlaylist);

                    var tablerow = document.createElement('div');
                    tablerow.style.display = 'table-row';
                    tablerow.dataset.id = audioinfo[m].playlist_id;
                    var tablekey = document.createElement('div');
                    tablekey.classList.add('key');
                    tablekey.appendChild(spanDelete);

                    var tablevalue = document.createElement('div');
                    tablevalue.classList.add('value');
                    tablevalue.textContent = audioinfo[m].name;
                    tablerow.appendChild(tablekey);
                    tablerow.appendChild(tablevalue);
                    table.appendChild(tablerow);
                }
            } else {
                table = document.createElement('div');
                table.setAttribute('style', 'margin-left: 2em;');
                table.classList.add('get-metadata');
                table.innerHTML = '<p>' + t('audioplayer', 'No playlist entry') + '</p>';
            }

            var playlistsTabView = document.getElementById('playlistsTabView');
            playlistsTabView.innerHTML = '';
            playlistsTabView.appendChild(table);

        });
    },

    addonsTabView: function () {
        OCA.Audioplayer.Sidebar.resetView();
        document.getElementById('tabHeaderAddons').classList.add('selected');
        var html = '<div style="margin-left: 2em; background-position: initial;" class="icon-info">';
        html += '<p style="margin-left: 2em;">' + t('audioplayer', 'Available Audio Player Add-Ons:') + '</p>';
        html += '<p style="margin-left: 2em;"><br></p>';
        html += '<a href="https://github.com/rello/audioplayer_sonos"  target="_blank" >';
        html += '<p style="margin-left: 2em;">- ' + t('audioplayer', 'SONOS playback') + '</p>';
        html += '</a></div>';
        var addonsTabView = document.getElementById('addonsTabView');
        addonsTabView.classList.remove('hidden');
        addonsTabView.innerHTML = html;
    }
    ,

    resetView: function () {
        document.querySelectorAll('.tabHeader.selected').forEach(function (el) {
            el.classList.remove('selected');
        });
        document.querySelectorAll('.tab').forEach(function (el) {
            el.classList.add('hidden');
        });
    }
    ,

    sortByName: function (a, b) {
        var aName = a.tabindex;
        var bName = b.tabindex;
        return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    }
    ,
}
;