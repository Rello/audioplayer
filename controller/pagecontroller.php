<?php
/**
 * ownCloud - Audios
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

namespace OCA\Audios\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;

/**
 * Controller class for main page.
 */
class PageController extends Controller {
	
	private $userId;
	private $l10n;
	
	

	public function __construct($appName, IRequest $request, $userId, $l10n) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
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
		
		$csp->addAllowedFrameDomain('*');	
		
		$maxUploadFilesize = \OCP\Util::maxUploadFilesize('/');
		 
		$response = new TemplateResponse('audios', 'index');
		$response->setContentSecurityPolicy($csp);
		$response->setParams(array(
			'uploadMaxFilesize' => $maxUploadFilesize,
			'uploadMaxHumanFilesize' => \OCP\Util::humanFileSize($maxUploadFilesize),
		));

		return $response;
	}
	
	
	
}