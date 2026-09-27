/**
 * Audio Player library navigation and playback restoration.
 * Licensed under the Affero General Public License version 3 or later.
 */
/* global OCA, OC, OCP, t */
'use strict';

OCA.Audioplayer.Library = {
    categoryRequest: 0,
    expandedCategory: null,

    setCategoryExpanded: function (category, expanded) {
        const panel = document.getElementById('library-category-panel');
        const buttons = Array.from(document.querySelectorAll('#library-navigation > li > button'));
        const owner = buttons.find(button => button.dataset.category === category && !button.dataset.item);
        this.expandedCategory = expanded ? category : null;
        buttons.forEach(button => {
            if (button.hasAttribute('aria-expanded')) {
                button.setAttribute('aria-expanded', String(expanded && button === owner));
            }
        });
        if (expanded) (owner ? owner.parentElement : document.getElementById('library-more')).appendChild(panel);
        panel.hidden = !expanded;
    },

    navigate: function (category, item = '') {
        const current = OCA.Audioplayer.Core.CategorySelectors;
        const dedicatedPlaylist = category === 'Playlist' && ['X1', 'X2'].includes(String(current[1]));
        if (!item && current[0] === category && !dedicatedPlaylist) {
            this.setCategoryExpanded(category, this.expandedCategory !== category);
        } else this.open(category, item);
    },

    request: async function (route, params = {}, signal) {
        const response = await fetch(OC.generateUrl('apps/audioplayer/' + route) + '?' + new URLSearchParams(params), {
            headers: OCA.Audioplayer.headers(), signal
        });
        if (!response.ok) throw new Error('Library request failed');
        return response.json();
    },

    button: function (label, action, className) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        if (className) button.className = className;
        button.addEventListener('click', action);
        return button;
    },

    restore: function (saved) {
        const value = document.getElementById('libraryView').value;
        let view;
        try { view = JSON.parse(value); } catch (e) { view = null; }
        if (!value && saved.length) view = {category: saved[0] === 'Albums' ? 'Album' : saved[0], item: saved[1]};
        if (!view || !document.querySelector('#category_selector option[value="' + CSS.escape(view.category) + '"]')) view = {category: 'Album', item: ''};
        if (view.category === 'Playlist' && /^S/.test(view.item || '')) view.category = 'Stream';
        this.open(view.category, view.item || '', null, false);
        this.restorePlayer(saved.slice());
    },

    restorePlayer: async function (saved) {
        const player = OCA.Audioplayer.Player;
        if (!player || !saved[2]) return;
        try {
            const category = saved[0] === 'Albums' ? 'Album' : saved[0];
            const result = await this.request('gettracks', {category, categoryId: saved[1]});
            if (player.currentTrackId || result.status !== 'success') return;
            const rows = result.data.map(track => OCA.Audioplayer.UI.buildTrackRow(track, false));
            const index = rows.findIndex(row => row.dataset.trackid === String(saved[2]));
            if (index < 0) return;
            player.addTracksToSourceList(rows);
            player.currentPlaylist = (category === 'Playlist' && /^S/.test(saved[1]) ? 'Stream' : category) + '-' + saved[1];
            player.currentTrackIndex = index;
            player.trackStartPosition = Number(saved[3]) || 0;
            player.setTrack(false);
            OCA.Audioplayer.UI.indicateCurrentPlayingTrack();
        } catch (error) {
            // A removed or unavailable saved track leaves the player ready for a new selection.
        }
    },

    saveView: function (category, item = '') {
        const value = JSON.stringify({category, item});
        document.getElementById('libraryView').value = value;
        OCA.Audioplayer.Backend.setUserValue('libraryView', value);
    },

    setHeading: function (category, item = '', title) {
        document.getElementById('app-content').classList.remove('is-library-intro');
        const nav = Array.from(document.querySelectorAll('#library-navigation > li > button'));
        const active = nav.find(button => button.dataset.category === category && button.dataset.item === item)
            || nav.find(button => button.dataset.category === category && !button.dataset.item);
        nav.forEach(button => {
            if (button === active) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        const selector = document.getElementById('category_selector');
        selector.toggleAttribute('data-active', !active);
        const option = Array.from(selector.options).find(option => option.value === category);
        document.getElementById('library-title').textContent = title || (active ? active.textContent : option ? option.textContent : t('audioplayer', 'Albums'));
        document.getElementById('library-add-tracks').hidden = !(category === 'Playlist' && item && !/^[XS]/.test(item));
        document.getElementById('view-toggle').hidden = category === 'Album';
    },

    open: function (category, item = '', callback, remember = true) {
        if (category === 'Playlist' && /^S/.test(item)) category = 'Stream';
        this.setCategoryExpanded(category, category !== 'Title' && !(category === 'Playlist' && ['X1', 'X2'].includes(String(item))));
        document.getElementById('myCategory').hidden = false;
        if (OCA.Audioplayer.Core.AjaxCallStatus) OCA.Audioplayer.Core.AjaxCallStatus.abort();
        document.getElementById('newPlaylist').classList.add('ap_hidden');
        document.getElementById('empty-container').style.display = 'none';
        document.getElementById('playlist-container').style.display = 'none';
        document.getElementById('category_selector').value = category;
        OCA.Audioplayer.Core.CategorySelectors = [category, item];
        this.setHeading(category, item);
        if (remember) this.saveView(category, item);
        if (item && OCA.Audioplayer.UI.isMobileNavigation()) OCA.Audioplayer.UI.setNavigationVisible(false);
        OCA.Audioplayer.Category.load(() => {
            const rows = Array.from(document.querySelectorAll('#myCategory li'));
            const selected = rows.find(row => row.dataset.id === String(item));
            if (selected && item !== '') {
                selected.classList.add('active');
                OCA.Audioplayer.Category.handleCategoryClicked(null, callback);
            } else if (category === 'Album') {
                OCA.Audioplayer.Cover.load('Album', '');
            } else {
                this.showCategoryOverview(category, rows);
            }
        });
    },

    categoryIcon: function (category) {
        const existing = document.querySelector('#library-navigation button[data-category="' + category + '"] svg');
        if (existing) return existing.cloneNode(true);
        const paths = {
            'Album Artist': 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z M4 21v-2a8 8 0 0 1 16 0v2',
            Folder: 'M3 6h7l2 2h9v12H3Z',
            Genre: 'M4 4h6v6H4Z M14 4h6v6h-6Z M4 14h6v6H4Z M14 14h6v6h-6Z',
            Tags: 'M3 3h8l10 10-8 8L3 11Z M7 7h.01',
            Year: 'M4 5h16v16H4Z M8 3v4M16 3v4M4 11h16',
        };
        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.classList.add('ap-filter-icon');
        for (const [key, value] of Object.entries({viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.6', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true', focusable: 'false'})) icon.setAttribute(key, value);
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', paths[category] || paths.Genre);
        icon.appendChild(path);
        return icon;
    },

    showCategoryOverview: function (category, rows) {
        const container = document.getElementById('empty-container');
        container.replaceChildren();
        container.style.display = 'block';
        document.getElementById('loading').style.display = 'none';
        document.getElementById('sm2-bar-ui').style.display = 'block';
        document.getElementById('myCategory').hidden = false;
        const list = document.createElement('div');
        list.className = 'ap-category-overview';
        for (const row of rows) {
            if (!row.dataset.id) continue;
            const button = this.button(row.dataset.name, () => {
                document.getElementById('myCategory').hidden = false;
                row.firstElementChild.click();
                document.getElementById('library-menu').focus();
            });
            const icon = this.categoryIcon(category);
            const name = document.createElement('span');
            name.textContent = row.dataset.name;
            const count = document.createElement('span');
            count.className = 'ap-category-count';
            count.textContent = row.querySelector('.counter').textContent;
            button.replaceChildren(icon, name, count);
            list.appendChild(button);
        }
        if (!list.childElementCount) {
            const hint = document.createElement('p');
            hint.textContent = t('audioplayer', 'Nothing here yet');
            container.appendChild(hint);
        } else container.appendChild(list);
    },

    onboarding: function (parent) {
        document.getElementById('app-content').classList.add('is-library-intro');
        const panel = document.createElement('div');
        panel.className = 'ap-empty-state';
        const heading = document.createElement('h3');
        heading.textContent = t('audioplayer', 'Bring your music to life');
        const description = document.createElement('p');
        description.textContent = t('audioplayer', 'Choose a folder in Nextcloud, scan for audio files, then pick something to play. Your files stay in place.');
        const steps = document.createElement('ol');
        [t('audioplayer', 'Choose music folder'), t('audioplayer', 'Scan for audio files'), t('audioplayer', 'Play your music')].forEach(text => {
            const item = document.createElement('li');
            item.textContent = text;
            steps.appendChild(item);
        });
        const path = document.createElement('p');
        path.className = 'ap-folder-path';
        path.textContent = document.getElementById('libraryPath').value || t('audioplayer', 'All Nextcloud files');
        const actions = document.createElement('div');
        actions.className = 'ap-action-row';
        const choose = this.button(t('audioplayer', 'Choose music folder'), () => this.chooseFolder(path), 'button ap-action-button');
        const scan = this.button(t('audioplayer', 'Scan for audio files'), () => OCA.Audioplayer.Settings.openScanDialog(), 'button ap-action-button');
        actions.append(choose, scan);
        panel.append(heading, description, steps, path, actions);
        parent.appendChild(panel);
    },

    chooseFolder: function (label) {
        OC.dialogs.filepicker(t('audioplayer', 'Select a single folder with audio files'), async path => {
            try {
                const response = await fetch(OC.generateUrl('apps/audioplayer/userpath'), {
                    method: 'POST', headers: OCA.Audioplayer.headers(), body: JSON.stringify({value: path})
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error('Invalid path');
                document.getElementById('libraryPath').value = path;
                label.textContent = path;
                OCP.Toast.success(t('audioplayer', 'Music folder saved. You can now scan for audio files.'));
            } catch (error) { OCP.Toast.error(t('audioplayer', 'Could not save the music folder. Please try again.')); }
        }, false, 'httpd/unix-directory', true, 1);
    },

    empty: function (mode) {
        document.getElementById('app-content').classList.remove('is-library-intro');
        document.getElementById('loading').style.display = 'none';
        document.getElementById('playlist-container').style.display = 'none';
        // Browsing an empty category must not hide an active listening session.
        document.getElementById('sm2-bar-ui').style.display = 'block';
        const container = document.getElementById('empty-container');
        container.style.display = 'block';
        container.replaceChildren();
        if (!mode) return this.onboarding(container);
        const panel = document.createElement('div');
        panel.className = 'ap-empty-state';
        const heading = document.createElement('h3');
        const hint = document.createElement('p');
        const item = OCA.Audioplayer.Core.CategorySelectors[1];
        if (mode === 'playlist') {
            heading.textContent = t('audioplayer', 'Start your playlist');
            hint.textContent = t('audioplayer', 'Choose tracks from your library, or drag songs onto this playlist.');
            panel.append(heading, hint, this.button(t('audioplayer', 'Add tracks'), () => this.addTracks(), 'button ap-action-button'));
        } else {
            heading.textContent = item === 'X1' ? t('audioplayer', 'Your favorites start here') : mode === 'selection' ? t('audioplayer', 'Choose your music') : t('audioplayer', 'Nothing here yet');
            hint.textContent = item === 'X1' ? t('audioplayer', 'Star a song to find it here.') : mode === 'selection' ? t('audioplayer', 'Choose an item in the navigation to explore your music.') : t('audioplayer', 'Browse your albums or scan for more music.');
            panel.append(heading, hint, this.button(t('audioplayer', 'Browse albums'), () => this.open('Album'), 'button ap-action-button'));
        }
        container.appendChild(panel);
    },

    addTracks: async function () {
        const playlistId = OCA.Audioplayer.Core.CategorySelectors[1];
        if (!playlistId || /^[XS]/.test(playlistId)) return;
        OCA.Audioplayer.Notification.htmlDialogInitiate(t('audioplayer', 'Add tracks'), null);
        document.getElementById('analyticsDialogContainer').classList.add('ap-track-picker-dialog');
        const container = document.createElement('div');
        const search = document.createElement('input');
        search.type = 'search';
        search.placeholder = t('audioplayer', 'Search tracks');
        search.setAttribute('aria-label', search.placeholder);
        const list = document.createElement('div');
        list.className = 'ap-track-picker';
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        status.textContent = t('audioplayer', 'Loading your music …');
        container.append(search, status, list);
        OCA.Audioplayer.Notification.htmlDialogUpdate(container, '');
        const add = document.getElementById('analyticsDialogBtnGo');
        add.textContent = t('audioplayer', 'Add selected tracks');
        add.setAttribute('aria-disabled', 'true');
        const selected = new Set();
        try {
            const data = await this.request('gettracks', {category: 'Title', categoryId: '0'});
            if (!container.isConnected) return;
            const tracks = data.data || [];
            const existing = new Set(Array.from(document.querySelectorAll('#individual-playlist li')).map(row => row.dataset.trackid));
            const render = () => {
                list.replaceChildren();
                const query = search.value.toLocaleLowerCase();
                const matches = tracks.filter(track => !existing.has(String(track.id)) && (track.cl1 + ' ' + track.cl2 + ' ' + track.cl3).toLocaleLowerCase().includes(query));
                status.textContent = matches.length ? t('audioplayer', 'Select tracks to add to your playlist.') : t('audioplayer', 'No matching tracks.');
                matches.slice(0, 100).forEach(track => {
                    const label = document.createElement('label');
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.checked = selected.has(track.id);
                    checkbox.addEventListener('change', () => {
                        if (checkbox.checked) selected.add(track.id); else selected.delete(track.id);
                        add.setAttribute('aria-disabled', String(selected.size === 0));
                    });
                    const text = document.createElement('span');
                    text.textContent = track.cl1 + ' · ' + track.cl2;
                    text.title = text.textContent;
                    label.append(checkbox, text);
                    list.appendChild(label);
                });
                if (matches.length > 100) status.textContent = t('audioplayer', 'Showing the first 100 tracks. Search to find more.');
            };
            search.addEventListener('input', render);
            render();
            search.focus();
            add.addEventListener('click', async () => {
                if (!selected.size || add.getAttribute('aria-disabled') === 'true') return;
                add.setAttribute('aria-disabled', 'true');
                search.disabled = true;
                list.querySelectorAll('input').forEach(input => { input.disabled = true; });
                try {
                    let sorting = existing.size;
                    for (const id of Array.from(selected)) {
                        const response = await fetch(OC.generateUrl('apps/audioplayer/addtracktoplaylist'), {
                            method: 'POST', headers: OCA.Audioplayer.headers(), body: JSON.stringify({playlistid: playlistId, songid: id, sorting: ++sorting})
                        });
                        if (!response.ok) throw new Error('Adding track failed');
                        const result = await response.json();
                        if (!result || result.status === 'error') throw new Error('Adding track failed');
                        selected.delete(id);
                        existing.add(String(id));
                    }
                    OCA.Audioplayer.Notification.dialogClose();
                    this.open('Playlist', playlistId);
                } catch (error) {
                    status.textContent = t('audioplayer', 'Could not add all tracks. Please try again.');
                    search.disabled = false;
                    list.querySelectorAll('input').forEach(input => { input.disabled = false; });
                    add.setAttribute('aria-disabled', String(selected.size === 0));
                }
            });
        } catch (error) {
            if (container.isConnected) status.textContent = t('audioplayer', 'Could not load your music. Close this dialog and try again.');
        }
    },

    init: function () {
        document.querySelectorAll('#library-navigation > li > button').forEach(button => button.addEventListener('click', () => this.navigate(button.dataset.category, button.dataset.item)));
        document.getElementById('library-add-tracks').addEventListener('click', () => this.addTracks());
        document.getElementById('library-menu').addEventListener('click', () => OCA.Audioplayer.UI.setNavigationVisible(!OCA.Audioplayer.UI.isNavigationVisible()));
        const closeNavigation = () => {
            OCA.Audioplayer.UI.setNavigationVisible(false);
            document.getElementById('library-menu').focus();
        };
        document.getElementById('library-navigation-close').addEventListener('click', closeNavigation);
        document.getElementById('library-navigation-backdrop').addEventListener('click', closeNavigation);
        // Accessible activation for existing span-based row controls and add-on tabs.
        document.addEventListener('keydown', event => {
            if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[role="button"]:not(button)')) {
                event.preventDefault();
                event.stopPropagation();
                event.target.click();
            }
            if (event.key === 'Escape') {
                const dialog = document.getElementById('analyticsDialogContainer');
                if (dialog && !dialog.contains(event.target)) return;
                if (dialog) OCA.Audioplayer.Notification.dialogClose();
                else if (document.getElementById('app-sidebar').dataset.trackid) OCA.Audioplayer.Sidebar.hideSidebar();
                else if (OCA.Audioplayer.UI.isMobileNavigation() && OCA.Audioplayer.UI.isNavigationVisible()) closeNavigation();
                const player = document.getElementById('sm2-bar-ui');
                player.classList.remove('ap-expanded');
                document.getElementById('playerExpand')?.setAttribute('aria-expanded', 'false');
            }
        });
    }
};
