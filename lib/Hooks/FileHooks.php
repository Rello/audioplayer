<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */
 
namespace OCA\audioplayer\Hooks;
use OCA\audioplayer\Controller;
use \OCP\Files\FileInfo;
use Psr\Log\LoggerInterface;

class FileHooks {

    private $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * delete track from library after file deletion
     * @param array $params
     * @throws \OCP\AppFramework\QueryException
     */
	public static function deleteTrack($params) {

		$view = \OC\Files\Filesystem::getView();
		$node = $view->getFileInfo($params['path']);

        #$this->logger->debug('Hook delete id: '.$node->getId(), array('app' => 'audioplayer'));
		if ($node->getType() === FileInfo::TYPE_FILE) {
			$app = new \OCA\audioplayer\AppInfo\Application();
        	$container = $app->getContainer();
            $container->query(\OCA\audioplayer\Controller\DbController::class)->deleteFromDB($node->getId());
		}
	}    
}
