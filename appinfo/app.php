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

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
    'OCA\Files::loadAdditionalScripts',
    function() {
        \OCP\Util::addScript('audioplayer', 'soundmanager2-nodebug-jsmin');
        \OCP\Util::addScript('audioplayer', 'viewer/viewer');
        \OCP\Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');
    }
);
$eventDispatcher->addListener(
    'OCA\Files_Sharing::loadAdditionalScripts',
    function() {
        \OCP\Util::addScript('audioplayer', 'soundmanager2-nodebug-jsmin');
        \OCP\Util::addScript('audioplayer', 'viewer/viewer');
        \OCP\Util::addScript('audioplayer', 'sharing/sharing');
        \OCP\Util::addScript('audioplayer', 'sharing/berniecode-animator-min');
        \OCP\Util::addScript('audioplayer', 'sharing/360player-min');
        \OCP\Util::addStyle('audioplayer', 'sharing/360player');
        \OCP\Util::addStyle('audioplayer', 'sharing/360player-visualization');
        \OCP\Util::addStyle('audioplayer', 'sharing/sharing');
        \OCP\Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');
    }
);

$app = new Application();
$app->registerFileHooks();
$app->registerUserHooks();

$c = $app->getContainer();
// add an navigation entry

$navigationEntry = function() use ($c) {
	return [
		'id' => $c->getAppName(),
		'order' => 6,
		'name' => $c->query('L10N')->t('Audio Player'),
		'href' => $c->query('URLGenerator')->linkToRoute('audioplayer.page.index'),
		'icon' => $c->query('URLGenerator')->imagePath('audioplayer', 'app.svg'),
	];
};
$c->getServer()->getNavigationManager()->add($navigationEntry);

$c->getServer()->getSearch()->registerProvider('OCA\audioplayer\Search\Provider', array('app'=>'audioplayer', 'apps' => array('files')));	

#\OCP\App::registerPersonal($c->query('AppName'), 'lib/Settings/User');
