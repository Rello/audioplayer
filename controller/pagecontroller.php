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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;

/**
 * Controller class for main page.
 */
class PageController extends Controller {
	
	private $userId;
	private $l10n;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n 
			) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->l10n = $l10n;
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
		));

		return $response;
	}	
}
