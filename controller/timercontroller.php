<?php
/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 * @copyright 
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\audioplayer\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;

/**
 * Controller class for main page.
 */
class TimerController extends Controller {
	
	private $userId;
	private $l10n;
	private $db;
	

	public function __construct($appName, IRequest $request, $userId, $l10n, $db) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function getTimer(){
			$SQL="SELECT  `id`,`time` FROM `*PREFIX*audioplayer_timer` 
				 			WHERE  `user_id` = ?";

			$stmt = $this->db->prepareQuery($SQL);
			$result = $stmt->execute(array($this->userId));		
			$row = $result->fetchRow();

		return $row['time'];
	}
		
	public function setTimer(){
			$timer_user = $this->params('timer_user');
			$timer_time = $this->params('timer_time');
			
			$sql = 'DELETE FROM `*PREFIX*audioplayer_timer` '
					. 'WHERE `user_id` = ?';
			$stmt = $this->db->prepareQuery($sql);
			$result = $stmt->execute(array($timer_user));
			
				
			if ($timer_time !== 0 AND $this->db->insertIfNotExist('*PREFIX*audioplayer_timer', ['user_id' => $timer_user, 'time' => $timer_time])) {
				$insertid = $this->db->getInsertId('*PREFIX*audioplayer_timer');
				$result = ['msg'=>'new','id' => $insertid];
			
			}			
			

			return $result;
	}
}