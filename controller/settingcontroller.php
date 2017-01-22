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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IConfig;
use \OCP\IL10N;
use \OCP\IDb;
use \OCP\Files\IRootFolder;

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
			IConfig $configManager,
			IRootFolder $rootFolder
			) {
		parent::__construct($appName, $request);
		$this->appname = $appName;
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->configManager = $configManager;
		$this->rootFolder = $rootFolder;
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

	/**
	 * @NoAdminRequired
	 */
	public function userPath() {
		$path = $this->params('value');
			try {
				$element = $this->rootFolder->getUserFolder($this -> userId)->get($path);
			} catch (\OCP\Files\NotFoundException $e) {
				return new JSONResponse(array('success' => false));
			}
			
			if ($path[0] !== '/') {
				$path = '/' . $path;
			}
			if ($path[strlen($path)-1] !== '/') {
				$path .= '/';
			}
			$this->configManager->setUserValue($this->userId, $this->appname, 'path', $path);
		return new JSONResponse(array('success' => true));
	}

}
