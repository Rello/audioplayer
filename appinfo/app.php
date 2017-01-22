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
 
$request = \OC::$server->getRequest();
	
	if (isset($request->server['REQUEST_URI'])) {
		$url = $request->server['REQUEST_URI'];
		if (preg_match('%/apps/files(/.*)?%', $url)	|| preg_match('%/s/(/.*)?%', $url)) {
			\OCP\Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');		
			\OCP\Util::addStyle( 'audioplayer', 'style');
			
			\OCP\Util::addScript( 'audioplayer', 'soundmanager2-nodebug-jsmin' );
			\OCP\Util::addScript( 'audioplayer', 'viewer' );
		}
	}

$app = new Application();
$c = $app->getContainer();
// add an navigation entry

$navigationEntry = function () use ($c) {
	return [
		'id' => $c->getAppName(),
		'order' => 22,
		'name' => $c->query('L10N')->t('Audio Player'),
		'href' => $c->query('URLGenerator')->linkToRoute('audioplayer.page.index'),
		'icon' => $c->query('URLGenerator')->imagePath('audioplayer', 'app.svg'),
	];
};
$c->getServer()->getNavigationManager()->add($navigationEntry);

$c->getServer()->getSearch()->registerProvider('OCA\audioplayer\Search\Provider', array('app'=>'audioplayer','apps' => array('files')));	

\OCP\App::registerPersonal($c->query('AppName'), 'settings/user');
