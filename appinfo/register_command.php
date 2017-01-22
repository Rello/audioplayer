<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */

namespace OCA\audioplayer\AppInfo;

$app = new Application();
$c = $app->getContainer();
$userManager = $c->getServer()->getUserManager();

$application->add(new \OCA\audioplayer\Command\Scan(
	$userManager, 
	$c->query(\OCA\audioplayer\Controller\ScannerController::class)
));

$application->add(new \OCA\audioplayer\Command\Reset(
	$userManager, 
	$c->query(\OCA\audioplayer\Controller\MusicController::class)
));

