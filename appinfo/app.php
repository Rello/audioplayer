<?php

/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2020 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\AppInfo;

use OCP\Util;

$app = new Application();
$app->registerFileHooks();
$app->registerUserHooks();

\OC::$server->getEventDispatcher()->addListener(
    'OCA\Files::loadAdditionalScripts',
    function () {
        Util::addScript('audioplayer', 'viewer/viewer');
        Util::addScript('audioplayer', 'viewer/search');
        Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');
    }
);

\OC::$server->getEventDispatcher()->addListener(
    'OCA\Files_Sharing::loadAdditionalScripts',
    function () {
        Util::addScript('audioplayer', 'viewer/viewer');
        Util::addScript('audioplayer', 'sharing/sharing');
        Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');
    }
);

$navigationEntry = function () {
    return [
        'id' => 'audioplayer',
        'order' => 6,
        'name' => \OC::$server->getL10N('audioplayer')->t('Audio Player'),
        'href' => \OC::$server->getURLGenerator()->linkToRoute('audioplayer.page.index'),
        'icon' => \OC::$server->getURLGenerator()->imagePath('audioplayer', 'app.svg'),
    ];
};
\OC::$server->getNavigationManager()->add($navigationEntry);

\OC::$server->getSearch()->registerProvider('OCA\audioplayer\Search\Provider', array('app' => 'audioplayer', 'apps' => array('files')));