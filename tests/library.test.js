/** Run with node --test tests/library.test.js. No browser or Nextcloud required. */
'use strict';
const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');

function loadLibrary(value = '') {
    const calls = [];
    const context = vm.createContext({
        OCA: {Audioplayer: {}}, CSS: {escape: value => value},
        document: {getElementById: () => ({value}), querySelector: selector => (selector.includes('Bogus') || selector.includes('Home')) ? null : {}},
        AbortController, URLSearchParams
    });
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../js/library.js'), 'utf8'), context);
    const library = context.OCA.Audioplayer.Library;
    library.open = (...args) => calls.push(['open', ...args]);
    library.restorePlayer = (...args) => calls.push(['restorePlayer', ...args]);
    return {context, library, calls};
}

test('new users start on Albums', () => {
    const {library, calls} = loadLibrary();
    library.restore([]);
    assert.deepEqual(calls[0], ['open', 'Album', '', null, false]);
});

test('existing users keep their previous library view and resume position', () => {
    const {library, calls} = loadLibrary();
    const saved = ['Title', '0', '42', '75'];
    library.restore(saved);
    assert.deepEqual(calls[0], ['open', 'Title', '0', null, false]);
    saved[3] = '0';
    assert.equal(calls[1][1][3], '75');
});

test('removed home migrates to Albums and retains playback context', () => {
    const {library, calls} = loadLibrary(JSON.stringify({category: 'Home'}));
    library.restore(['Playlist', 'X3', '42', '75']);
    assert.deepEqual(calls[0], ['open', 'Album', '', null, false]);
    assert.equal(calls[1][1][2], '42');
});

test('remembered browsing view is independent of last playback category', () => {
    const {library, calls} = loadLibrary(JSON.stringify({category: 'Artist', item: '12'}));
    library.restore(['Playlist', 'X1', '42', '75']);
    assert.deepEqual(calls[0], ['open', 'Artist', '12', null, false]);
    assert.equal(calls[1][1][0], 'Playlist');
});

test('invalid remembered destinations recover to Albums', () => {
    const {library, calls} = loadLibrary(JSON.stringify({category: 'Bogus'}));
    library.restore(['Title', '0']);
    assert.deepEqual(calls[0], ['open', 'Album', '', null, false]);
});

test('category buttons collapse and reopen without resetting a selected item', () => {
    const {context, library, calls} = loadLibrary();
    context.OCA.Audioplayer.Core = {CategorySelectors: ['Album', '4']};
    library.expandedCategory = 'Album';
    library.setCategoryExpanded = (category, expanded) => {
        calls.push(['expand', category, expanded]);
        library.expandedCategory = expanded ? category : null;
    };
    library.navigate('Album');
    library.navigate('Album');
    assert.deepEqual(calls, [['expand', 'Album', false], ['expand', 'Album', true]]);
    assert.equal(context.OCA.Audioplayer.Core.CategorySelectors[1], '4');
});

test('Playlists opens its full index from either dedicated smart playlist', () => {
    for (const item of ['X1', 'X2']) {
        const {context, library, calls} = loadLibrary();
        context.OCA.Audioplayer.Core = {CategorySelectors: ['Playlist', item]};
        library.navigate('Playlist');
        assert.deepEqual(calls, [['open', 'Playlist', '']]);
    }
});

function loadPlayer() {
    const saved = [];
    const elements = {
        html5Audio: {children: [], childElementCount: 0, currentTime: 30, duration: 100, volume: 1},
        audioPreload: {},
        progressBar: {getContext: () => ({clearRect() {}, fillRect() {}}), clientWidth: 100, clientHeight: 20},
        playerSeek: {setAttribute() {}},
        playerVolume: {value: 1}, startTime: {}, endTime: {}
    };
    const context = vm.createContext({
        OCA: {Audioplayer: {Core: {CategorySelectors: ['Artist', '12']}, Backend: {setUserValue: (...args) => saved.push(args)}}},
        document: {getElementById: id => elements[id], addEventListener() {}}
    });
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../js/player.js'), 'utf8'), context);
    return {player: context.OCA.Audioplayer.Player, elements, saved};
}

test('progress saves the listening session even when browsing another category', () => {
    const {player, saved} = loadPlayer();
    player.currentPlaylist = 'Playlist-X1';
    player.currentTrackId = '42';
    player.initProgressBar();
    assert.deepEqual(saved[0], ['category', 'Playlist-X1-42-30']);
    player.initProgressBar();
    assert.equal(saved.length, 1, 'same second is saved only once');
});

test('live streams disable seeking instead of exposing an infinite slider', () => {
    const {player, elements} = loadPlayer();
    elements.html5Audio.duration = Infinity;
    player.initProgressBar();
    assert.equal(elements.playerSeek.disabled, true);
    assert.equal(elements.playerSeek.max, 0);
});

test('play and skip are safe before choosing any tracks', () => {
    const {player} = loadPlayer();
    assert.doesNotThrow(() => { player.play(); player.next(); player.prev(); });
});

test('volume shortcuts update both the control and the audio element', () => {
    const {player, elements, saved} = loadPlayer();
    player.setVolume(0.4);
    assert.equal(elements.html5Audio.volume, 0.4);
    assert.deepEqual(saved[0], ['volume', 0.4]);
});

test('closing the sidebar before metadata arrives ignores the stale response', async () => {
    let complete;
    let rendered = false;
    const sidebar = {dataset: {trackid: '42'}};
    const tab = {classList: {add() {}, remove() {}}, innerHTML: ''};
    const context = vm.createContext({
        OCA: {Audioplayer: {headers: () => ({})}},
        OC: {generateUrl: value => value, imagePath: () => ''}, t: (app, value) => value,
        document: {
            getElementById: id => id === 'app-sidebar' ? sidebar : sidebar.dataset.trackid ? tab : null,
            querySelectorAll: () => [],
            createElement: () => { rendered = true; throw new Error('Stale response rendered'); }
        },
        fetch: () => new Promise(resolve => { complete = () => resolve({json: async () => ({status: 'success', data: {Title: 'Old track'}})}); })
    });
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../js/sidebar.js'), 'utf8'), context);
    context.OCA.Audioplayer.Sidebar.metadataTabView();
    sidebar.dataset.trackid = '';
    complete();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(rendered, false);
});

test('restoring a saved track primes its queue and position without autoplay', async () => {
    const {context, library} = loadLibrary();
    delete library.restorePlayer;
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../js/library.js'), 'utf8'), context);
    const player = {currentTrackId: 0, addTracksToSourceList(rows) { this.queue = rows; }, setTrack(autoplay) { this.autoplay = autoplay; }};
    context.OCA.Audioplayer.Player = player;
    context.OCA.Audioplayer.UI = {buildTrackRow: track => ({dataset: {trackid: String(track.id)}}), indicateCurrentPlayingTrack() {}};
    context.OCA.Audioplayer.Library.request = async () => ({status: 'success', data: [{id: 41}, {id: 42}]});
    await context.OCA.Audioplayer.Library.restorePlayer(['Title', '0', '42', '75']);
    assert.equal(player.currentTrackIndex, 1);
    assert.equal(player.currentPlaylist, 'Title-0');
    assert.equal(player.trackStartPosition, 75);
    assert.equal(player.autoplay, false);
});

test('an unavailable saved song does not select a different song', async () => {
    const {context} = loadLibrary();
    const player = {currentTrackId: 0, selected: false, setTrack() { this.selected = true; }};
    context.OCA.Audioplayer.Player = player;
    context.OCA.Audioplayer.UI = {buildTrackRow: track => ({dataset: {trackid: String(track.id)}})};
    context.OCA.Audioplayer.Library.request = async () => ({status: 'success', data: [{id: 41}]});
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../js/library.js'), 'utf8'), context);
    context.OCA.Audioplayer.Library.request = async () => ({status: 'success', data: [{id: 41}]});
    await context.OCA.Audioplayer.Library.restorePlayer(['Title', '0', '42', '75']);
    assert.equal(player.currentTrackId, 0);
    assert.equal(player.selected, false);
});
