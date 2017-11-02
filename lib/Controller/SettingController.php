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

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\ITagManager;
use OCP\IDbConnection;

/**
 * Controller class for main page.
 */
class SettingController extends Controller {
	
	private $userId;
	private $configManager;
	private $rootFolder;
    private $tagger;
    private $tagManager;
    private $db;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IConfig $configManager,
            IDBConnection $db,
            ITagManager $tagManager,
            IRootFolder $rootFolder
			) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
		$this->userId = $userId;
		$this->configManager = $configManager;
        $this->db = $db;
        $this->tagManager = $tagManager;
        $this->tagger = null;
        $this->rootFolder = $rootFolder;
	}

	/**
	 * @NoAdminRequired
	 */
	public function setValue($type, $value) {
		//\OCP\Util::writeLog('audioplayer', 'settings save: '.$type.$value, \OCP\Util::DEBUG);
		$this->configManager->setUserValue($this->userId, $this->appName, $type, $value);
		return new JSONResponse(array('success' => 'true'));
	}

	/**
	 * @NoAdminRequired
	 */
	public function getValue($type) {
		$value = $this->configManager->getUserValue($this->userId, $this->appName, $type);

		//\OCP\Util::writeLog('audioplayer', 'settings load: '.$type.$value, \OCP\Util::DEBUG);

		if ($value !== '') {
			$result = [
					'status' => 'success',
					'value' => $value
				];
		} else {
			$result = [
					'status' => 'false',
					'value' =>'nodata'
				];
		}
		
		$response = new JSONResponse();
		$response -> setData($result);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function userPath($value) {
		$path = $value;
			try {
				$this->rootFolder->getUserFolder($this -> userId)->get($path);
			} catch (\OCP\Files\NotFoundException $e) {
				return new JSONResponse(array('success' => false));
			}
			
			if ($path[0] !== '/') {
				$path = '/'.$path;
			}
			if ($path[strlen($path) - 1] !== '/') {
				$path .= '/';
			}
			$this->configManager->setUserValue($this->userId, $this->appName, 'path', $path);
		return new JSONResponse(array('success' => true));
	}

    /**
     * @NoAdminRequired
     *
     */
    public function setFavorite($fileId, $isFavorite) {
        $this->tagger = $this->tagManager->load('files');

        if ($isFavorite === "true") {
            $return = $this->tagger->removeFromFavorites($fileId);
        } else {
            $return = $this->tagger->addToFavorites($fileId);
        }
        return $return;
    }

    /**
     * @NoAdminRequired
     */
    public function setStatistics($track_id) {
        $date = new \DateTime();
        $playtime = $date->getTimestamp();

        $SQL='SELECT `id`, `playcount` FROM `*PREFIX*audioplayer_stats` WHERE `user_id`= ? AND `track_id`= ?';
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, $track_id));
        $row = $stmt->fetch();
        if (isset($row['id'])) {
            $playcount = $row['playcount'] + 1;
            $stmt = $this->db->prepare( 'UPDATE `*PREFIX*audioplayer_stats` SET `playcount`= ?, `playtime`= ? WHERE `id` = ?');
            $stmt->execute(array($playcount, $playtime, $row['id']));
            return 'update';
        } else {
            $stmt = $this->db->prepare( 'INSERT INTO `*PREFIX*audioplayer_stats` (`user_id`,`track_id`,`playtime`,`playcount`) VALUES(?,?,?,?)' );
            $stmt->execute(array($this->userId, $track_id, $playtime, 1));
            $insertid = $this->db->lastInsertId('*PREFIX*audioplayer_stats');
            return $insertid;
        }
    }
}
