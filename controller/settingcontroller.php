<?php
/**
 * ownCloud - Audio Player
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
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
use \OCP\IConfig;
use \OCP\IL10N;
use \OCP\IDb;

/**
 * Controller class for main page.
 */
class SettingController extends Controller {
	
	private $userId;
	private $l10n;
	private $db;
	private $configManager;
	
	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDb $db,
			IConfig $configManager
			) {
		parent::__construct($appName, $request);
		$this->appname = $appName;
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->configManager = $configManager;
	}

	/**
	 * @NoAdminRequired
	 */
	public function setValue() {
		$success = false;
		$type = $this->params('type');
		$value = $this->params('value');
		//\OCP\Util::writeLog('audioplayer', 'settings save: '.$type.$value, \OCP\Util::DEBUG);
		$this->configManager->setUserValue($this->userId, $this->appname, $type, $value);
		$success = true;
		return new JSONResponse(array('success' => $success));
	}

	/**
	 * @NoAdminRequired
	 */
	public function getValue() {
		$value = 'false';
		$type = $this->params('type');
		$value = $this->configManager->getUserValue($this->userId, $this->appname, $type);

		//\OCP\Util::writeLog('audioplayer', 'settings load: '.$type.$value, \OCP\Util::DEBUG);

		if($value !== ''){
			$result=[
					'status' => 'success',
					'value' => $value
				];
		}else{
			$result=[
					'status' => 'false',
					'value' =>'nodata'
				];
		}
		
		$response = new JSONResponse();
		$response -> setData($result);
		return $response;
	}

}
