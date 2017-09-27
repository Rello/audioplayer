<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2017 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;

/**
 * Controller class for main page.
 */
class PageController extends Controller {
	
	private $userId;
	private $l10n;
	private $configManager;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IConfig $configManager
			) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
		$this->userId = $userId;
		$this->configManager = $configManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
				
		$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$csp->addAllowedStyleDomain('data:');
		$csp->addAllowedImageDomain('\'self\'');
		$csp->addAllowedImageDomain('data:');
		$csp->addAllowedImageDomain('*');
		$csp->addAllowedMediaDomain('*');		
		$csp->addAllowedFrameDomain('*');	
		
		$maxUploadFilesize = \OCP\Util::maxUploadFilesize('/');
		 
		$response = new TemplateResponse('audioplayer', 'index');
		$response->setContentSecurityPolicy($csp);
		$response->setParams(array(
			'uploadMaxFilesize' => $maxUploadFilesize,
			'uploadMaxHumanFilesize' => \OCP\Util::humanFileSize($maxUploadFilesize),
			'cyrillic' => $this->configManager->getUserValue($this->userId, $this->appName, 'cyrillic'),
			'path' => $this->configManager->getUserValue($this->userId, $this->appName, 'path'),
			'navigation' => $this->configManager->getUserValue($this->userId, $this->appName, 'navigation'),
		));
		return $response;
	}	
}
