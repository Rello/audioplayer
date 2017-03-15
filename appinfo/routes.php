<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2017 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\AppInfo;

use \OCA\audioplayer\AppInfo\Application;

$application = new Application();

$application->registerRoutes($this, ['routes' => [
	['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	['name' => 'playlist#addPlaylist', 'url' => '/addplaylist', 'verb' => 'GET'],
	['name' => 'playlist#addTrackToPlaylist', 'url' => '/addtracktoplaylist', 'verb' => 'GET'],
	['name' => 'playlist#removeTrackFromPlaylist', 'url' => '/removetrackfromplaylist', 'verb' => 'GET'],
	['name' => 'playlist#sortPlaylist', 'url' => '/sortplaylist', 'verb' => 'GET'],
	['name' => 'playlist#removePlaylist', 'url' => '/removeplaylist', 'verb' => 'GET'],
	['name' => 'playlist#updatePlaylist', 'url' => '/updateplaylist', 'verb' => 'GET'],
	['name' => 'scanner#getImportTpl', 'url' => '/getimporttpl', 'verb' => 'GET'],
	['name' => 'scanner#getProgress', 'url' => '/getprogress', 'verb' => 'POST'],
	['name' => 'scanner#scanForAudios', 'url' => '/scanforaudiofiles', 'verb' => 'POST'],
	['name' => 'scanner#editAudioFile', 'url' => '/editaudiofile', 'verb' => 'GET'],
	['name' => 'scanner#saveAudioFileData', 'url' => '/saveaudiofiledata', 'verb' => 'POST'],
	['name' => 'music#getMusic', 'url' => '/getmusic', 'verb' => 'GET'],
	['name' => 'music#getCover', 'url' => '/getcover/{album}', 'verb' => 'GET'],
	['name' => 'music#getAudioStream', 'url' => '/getaudiostream', 'verb' => 'GET'],
	['name' => 'music#resetMediaLibrary', 'url' => '/resetmedialibrary', 'verb' => 'GET'],
	['name' => 'music#getPublicAudioStream', 'url' => '/getpublicaudiostream', 'verb' => 'GET'],
	['name' => 'music#getPublicAudioInfo', 'url' => '/getpublicaudioinfo{file}', 'verb' => 'GET'],
	['name' => 'photo#uploadPhoto',	'url' => '/uploadphoto',	'verb' => 'POST'],
	['name' => 'photo#getImageFromCloud',	'url' => '/getimagefromcloud',	'verb' => 'GET'],
	['name' => 'photo#cropPhoto',	'url' => '/cropphoto',	'verb' => 'POST'],
	['name' => 'photo#saveCropPhoto',	'url' => '/savecropphoto',	'verb' => 'POST'],
	['name' => 'photo#clearPhotoCache',	'url' => '/clearphotocache',	'verb' => 'POST'],
	['name' => 'setting#setValue', 'url' => '/setvalue', 'verb' => 'GET'],
	['name' => 'setting#getValue', 'url' => '/getvalue', 'verb' => 'GET'],
	['name' => 'setting#userPath', 'url' => '/userpath', 'verb' => 'POST'],
	['name' => 'category#getCategory', 'url' => '/getcategory', 'verb' => 'GET'],
	['name' => 'category#getCategoryItems', 'url' => '/getcategoryitems', 'verb' => 'GET'],
	]]);
