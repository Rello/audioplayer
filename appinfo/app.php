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

$app = \OC::$server->query(\OCA\audioplayer\AppInfo\Application19::class);
$app->register();

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

\OC::$server->getSearch()->registerProvider('OCA\audioplayer\Search\Provider19', array('app' => 'audioplayer', 'apps' => array('files')));