<?php
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

namespace OCA\audioplayer\AppInfo;

return [
    'routes' => [
	['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
    ['name' => 'playlist#addTrackToPlaylist', 'url' => '/addtracktoplaylist', 'verb' => 'POST'],
    ['name' => 'playlist#addPlaylist', 'url' => '/addplaylist', 'verb' => 'POST'],
    ['name' => 'playlist#updatePlaylist', 'url' => '/updateplaylist', 'verb' => 'POST'],
    ['name' => 'playlist#sortPlaylist', 'url' => '/sortplaylist', 'verb' => 'POST'],
    ['name' => 'playlist#removePlaylist', 'url' => '/removeplaylist', 'verb' => 'POST'],
    ['name' => 'playlist#removeTrackFromPlaylist', 'url' => '/removetrackfromplaylist', 'verb' => 'POST'],
	['name' => 'scanner#getImportTpl', 'url' => '/getimporttpl', 'verb' => 'GET'],
	['name' => 'scanner#scanForAudios', 'url' => '/scanforaudiofiles', 'verb' => 'GET'],
	['name' => 'scanner#checkNewTracks', 'url' => '/checknewtracks', 'verb' => 'POST'],
	['name' => 'music#getAudioStream', 'url' => '/getaudiostream', 'verb' => 'GET'],
    ['name' => 'music#getPublicAudioStream', 'url' => '/getpublicaudiostream', 'verb' => 'GET'],
    ['name' => 'db#resetMediaLibrary', 'url' => '/resetmedialibrary', 'verb' => 'GET'],
	['name' => 'music#getPublicAudioInfo', 'url' => '/getpublicaudioinfo', 'verb' => 'GET'],
    ['name' => 'cover#getCover', 'url' => '/getcover/{album}', 'verb' => 'GET'],

    ['name' => 'setting#setValue', 'url' => '/setvalue', 'verb' => 'GET'],
	['name' => 'setting#getValue', 'url' => '/getvalue', 'verb' => 'GET'],
	['name' => 'setting#userPath', 'url' => '/userpath', 'verb' => 'POST'],
    ['name' => 'setting#setFavorite', 'url' => '/setfavorite', 'verb' => 'GET'],
    ['name' => 'setting#setStatistics', 'url' => '/setstatistics', 'verb' => 'GET'],
    ['name' => 'setting#admin', 'url' => '/admin', 'verb' => 'POST'],

    ['name' => 'category#getCategoryItems', 'url' => '/getcategoryitems', 'verb' => 'GET'],
    ['name' => 'category#getCategoryItemCovers', 'url' => '/getcategoryitemcovers', 'verb' => 'GET'],
    ['name' => 'category#getTracks', 'url' => '/gettracks', 'verb' => 'GET'],
    ['name' => 'sidebar#getAudioInfo', 'url' => '/getaudioinfo', 'verb' => 'GET'],
    ['name' => 'sidebar#getPlaylists', 'url' => '/getplaylists', 'verb' => 'POST'],

    // whatsnew
    ['name' => 'whatsNew#get', 'url' => '/whatsnew', 'verb' => 'GET'],
    ['name' => 'whatsNew#dismiss', 'url' => '/whatsnew', 'verb' => 'POST'],
    ]
];