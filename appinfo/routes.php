<?php
/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	['name' => 'scanner#scanForAudios', 'url' => '/scanforaudiofiles', 'verb' => 'POST'],
	['name' => 'scanner#editAudioFile', 'url' => '/editaudiofile', 'verb' => 'GET'],
	['name' => 'scanner#saveAudioFileData', 'url' => '/saveaudiofiledata', 'verb' => 'POST'],
	['name' => 'music#getMusic', 'url' => '/getmusic', 'verb' => 'GET'],
	['name' => 'music#getAudioStream', 'url' => '/getaudiostream', 'verb' => 'GET'],
	['name' => 'music#resetMediaLibrary', 'url' => '/resetmedialibrary', 'verb' => 'GET'],
	['name' => 'music#getPublicAudioStream', 'url' => '/getpublicaudiostream{file}', 'verb' => 'GET'],
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
	]]);
