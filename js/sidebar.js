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
        let trackid;
        if (typeof trkid !== 'undefined') {
            trackid = trkid;
        } else {
            let targetPlaylistItem = evt.target.closest('li');
            trackid = targetPlaylistItem.getAttribute('data-trackid');
        }

        let appsidebar = document.getElementById('app-sidebar');

        if (appsidebar.dataset.trackid === trackid) {
            OCA.Audioplayer.Sidebar.hideSidebar();
        } else {
            let getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
            let trackData = document.querySelector('li[data-trackid="' + trackid + '"]');
            let cover = trackData ? trackData.getAttribute('data-cover') : '';
            let sidebarThumbnail = document.getElementById('sidebarThumbnail');
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

            let starIcon = document.getElementById('sidebarFavorite');
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
            let selectedHeader = document.querySelector('.tabHeader.selected');
            if (selectedHeader) {
                selectedHeader.click();
            }
            OCA.Audioplayer.UI.resizePlaylist();
        }
    },

    registerSidebarTab: function (tab) {
        let id = tab.id;
        this.sidebar_tabs[id] = tab;
    },

    constructTabs: function () {
        let tab = {};

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

        let items = _.map(OCA.Audioplayer.Sidebar.sidebar_tabs, function (item) {
            return item;
        });
        items.sort(OCA.Audioplayer.Sidebar.sortByName);

        for (tab in items) {
            let li = document.createElement('li');
            li.classList.add('tabHeader');
            li.setAttribute('id', items[tab].id);
            li.setAttribute('tabindex', items[tab].tabindex);
            let atag = document.createElement('a');
            atag.textContent = items[tab].name;
            atag.title = items[tab].name;
            li.appendChild(atag);
            document.querySelector('.tabHeaders').appendChild(li);

            let div = document.createElement('div');
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
        let trackid = document.getElementById('app-sidebar').dataset.trackid;

        OCA.Audioplayer.Sidebar.resetView();
        document.getElementById('tabHeaderMetadata').classList.add('selected');
        let metadataTabView = document.getElementById('metadataTabView');
        metadataTabView.classList.remove('hidden');
        metadataTabView.innerHTML = '<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>';

        fetch(
            OC.generateUrl('apps/audioplayer/getaudioinfo') + '?trackid=' + encodeURIComponent(trackid),
            {method: 'GET', headers: OCA.Audioplayer.headers()}
        ).then(function (response) {
            return response.json();
        }).then(function (jsondata) {
            let table;
            if (jsondata.status === 'success') {
                table = document.createElement('div');
                table.style.display = 'table';
                table.classList.add('table');

                let audioinfo = jsondata.data;
                for (let m in audioinfo) {
                    let tablerow = document.createElement('div');
                    tablerow.style.display = 'table-row';
                    let tablekey = document.createElement('div');
                    tablekey.classList.add('key');
                    tablekey.textContent = t('audioplayer', m);
                    let tablevalue = document.createElement('div');
                    tablevalue.classList.add('value');
                    tablevalue.textContent = audioinfo[m];
                    if (m === 'Path') {
                        tablevalue.textContent = '';
                        let tablevalueDownload = document.createElement('a');
                        tablevalueDownload.setAttribute('href', OC.linkToRemote('webdav' + audioinfo[m]));
                        tablevalueDownload.textContent = audioinfo[m];
                        tablevalue.appendChild(tablevalueDownload);
                    }
                    tablerow.appendChild(tablekey);
                    tablerow.appendChild(tablevalue);

                    if (m === 'fav' && audioinfo[m] === 't') {
                        let fav = document.getElementById('sidebarFavorite');
                        fav.classList.remove('icon-star');
                        fav.classList.add('icon-starred');
                        fav.title = t('files', 'Favorited');
                        audioinfo[m] = '';
                    } else if (m === 'fav') {
                        let fav2 = document.getElementById('sidebarFavorite');
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

            let metadataTabView = document.getElementById('metadataTabView');
            metadataTabView.innerHTML = '';
            metadataTabView.appendChild(table);
        });
    },

    playlistsTabView: function () {
        let trackid = document.getElementById('app-sidebar').dataset.trackid;

        OCA.Audioplayer.Sidebar.resetView();
        document.getElementById('tabHeaderPlaylists').classList.add('selected');
        let playlistsTabView = document.getElementById('playlistsTabView');
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
            let table;
            if (jsondata.status === 'success') {
                table = document.createElement('div');
                table.style.display = 'table';
                table.classList.add('table');
                let audioinfo = jsondata.data;
                for (let m in audioinfo) {
                    let spanDelete = document.createElement('a');
                    spanDelete.setAttribute('class', 'icon icon-delete toolTip');
                    spanDelete.dataset.listid = audioinfo[m].playlist_id;
                    spanDelete.dataset.trackid = trackid;
                    spanDelete.title = t('audioplayer', 'Remove');
                    spanDelete.addEventListener('click', OCA.Audioplayer.Playlists.removeSongFromPlaylist);

                    let tablerow = document.createElement('div');
                    tablerow.style.display = 'table-row';
                    tablerow.dataset.id = audioinfo[m].playlist_id;
                    let tablekey = document.createElement('div');
                    tablekey.classList.add('key');
                    tablekey.appendChild(spanDelete);

                    let tablevalue = document.createElement('div');
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

            let playlistsTabView = document.getElementById('playlistsTabView');
            playlistsTabView.innerHTML = '';
            playlistsTabView.appendChild(table);

        });
    },

    addonsTabView: function () {
        OCA.Audioplayer.Sidebar.resetView();
        document.getElementById('tabHeaderAddons').classList.add('selected');
        let html = '<div style="margin-left: 2em; background-position: initial;" class="icon-info">';
        html += '<p style="margin-left: 2em;">' + t('audioplayer', 'Available Audio Player Add-Ons:') + '</p>';
        html += '<p style="margin-left: 2em;"><br></p>';
        html += '<a href="https://github.com/rello/audioplayer_sonos"  target="_blank" >';
        html += '<p style="margin-left: 2em;">- ' + t('audioplayer', 'SONOS playback') + '</p>';
        html += '</a></div>';
        let addonsTabView = document.getElementById('addonsTabView');
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
        let aName = a.tabindex;
        let bName = b.tabindex;
        return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    }
    ,
}
;