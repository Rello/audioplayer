<?php

/**
 * ownCloud - Audios
 *
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
 
 namespace OCA\Audios\AppInfo;
 
$request = \OC::$server->getRequest();
	
	if (isset($request->server['REQUEST_URI'])) {
		$url = $request->server['REQUEST_URI'];
		if (preg_match('%index.php/apps/files(/.*)?%', $url)	|| preg_match('%index.php/s/(/.*)?%', $url)) {
			\OCP\Util::addStyle('mp3_player', '3rdparty/fontello/css/fontello');		
			\OCP\Util::addStyle( 'mp3_player', 'style');
			
			\OCP\Util::addScript( 'mp3_player', 'soundmanager2' );
			\OCP\Util::addScript( 'mp3_player', 'viewer' );
		}
	}

$app = new Application();
$c = $app->getContainer();
// add an navigation entry
$navigationEntry = function () use ($c) {
	return [
		'id' => $c->getAppName(),
		'order' => 22,
		'name' => $c->query('L10N')->t('MP3 Player'),
		'href' => $c->query('URLGenerator')->linkToRoute('mp3_player.page.index'),
		'icon' => $c->query('URLGenerator')->imagePath('mp3_player', 'app.svg'),
	];
};
$c->getServer()->getNavigationManager()->add($navigationEntry);

$c->getServer()->getSearch()->registerProvider('OCA\mp3_player\Search\Provider', array('app'=>'mp3_player','apps' => array('files')));	


