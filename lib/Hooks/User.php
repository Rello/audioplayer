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
 
namespace OCA\audioplayer\Hooks;
use OCA\audioplayer\Controller;

class User {
	public static function deleteUser($params) {
		$userId = $params['uid'];
		$app = new Application();
        	$container = $app->getContainer();
	        $container->query(MusicController::class)->resetMediaLibrary($userId);
	}    
}
