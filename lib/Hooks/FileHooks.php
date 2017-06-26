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
	 * @param \OCP\Files\Node $node pointing to the file
	 */
	public static function deleteTrack($node) {
\OCP\Util::writeLog('audioplayer','test',\OCP\Util::DEBUG);		
		$app = new \OCA\audioplayer\AppInfo\Application();
        $container = $app->getContainer();
		\OCP\Util::writeLog('audioplayer','songFileId: '.$node->getId(),\OCP\Util::DEBUG);
		if ($node->getType() == FileInfo::TYPE_FILE) {
	    	$container->query(\OCA\audioplayer\Controller\CategoryController::class)->deleteFromDB($node->getId(),null);
		}
		else {
			foreach ($node->getDirectoryListing() as $child) {
				FileHooks::deleteTrack($child);
			}
		}
	}    
}
