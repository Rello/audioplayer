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
use \OCP\Files\FileInfo;

class FileHooks {

	/**
	 * delete track from library after file deletion
	 * @param array $params
	 */
	public static function deleteTrack($params) {

		$view = \OC\Files\Filesystem::getView();
		$node = $view->getFileInfo($params['path']);
        
		\OCP\Util::writeLog('audioplayer','Hook delete id: '.$node->getId(),\OCP\Util::DEBUG);
		if ($node->getType() === FileInfo::TYPE_FILE) {
			$app = new \OCA\audioplayer\AppInfo\Application();
        	$container = $app->getContainer();
	    	$container->query(\OCA\audioplayer\Controller\CategoryController::class)->deleteFromDB($node->getId());
		}
	}    
}
