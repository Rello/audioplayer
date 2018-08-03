<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2018 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;

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
			IConfig $configManager,
			IL10N $l10n
			) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
		$this->userId = $userId;
		$this->configManager = $configManager;
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
			'cyrillic' => $this->configManager->getUserValue($this->userId, $this->appName, 'cyrillic'),
			'path' => $this->configManager->getUserValue($this->userId, $this->appName, 'path'),
			'navigationShown' => $this->configManager->getUserValue($this->userId, $this->appName, 'navigation'),
            'volume' => $this->configManager->getUserValue($this->userId, $this->appName, 'volume') ?: '100',
			'notification' => $this->getNotification(),
            'audioplayer_editor' => 'false',
		));
		return $response;
	}

	private function getNotification() {
        $scanner_timestamp = $this->configManager->getUserValue($this->userId, $this->appName, 'scanner_timestamp', 0);
        if ($scanner_timestamp === 0) {
            $this->configManager->setUserValue($this->userId, $this->appName, 'scanner_timestamp', time());
        }
        $app_version = $this->configManager->getAppValue($this->appName, 'installed_version', '0.0.0');
        $scanner_version = $this->configManager->getUserValue($this->userId, $this->appName, 'scanner_version', '0.0.0');
        //\OCP\Util::writeLog('audioplayer', 'scanner version: '.$scanner_version, \OCP\Util::DEBUG);
        if (version_compare($scanner_version, '2.3.0', '<')) {
            return '<a href="https://github.com/rello/audioplayer/blob/master/CHANGELOG.md">' . $this->l10n->t('Please reset and rescan library to make use of new features.') . ' ' . $this->l10n->t('More information…') . '</a>';
        } else {
            return null;
        }
	}	
}
