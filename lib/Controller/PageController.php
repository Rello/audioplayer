<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2020 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\AppFramework\Http\ContentSecurityPolicy;

/**
 * Controller class for main page.
 */
class PageController extends Controller
{

    private $userId;
    private $l10n;
    private $configManager;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IConfig $configManager,
        IL10N $l10n
    )
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->userId = $userId;
        $this->configManager = $configManager;
        $this->l10n = $l10n;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @throws \OCP\PreConditionNotMetException
     */
    public function index()
    {

        if ($this->configManager->getAppValue('audioplayer_sonos', 'enabled') === "yes" AND $this->configManager->getAppValue('audioplayer_sonos', 'sonos') === "checked") {
            $audioplayer_sonos = $this->configManager->getUserValue($this->userId, 'audioplayer_sonos', 'sonos') ?: false;
        } else {
            $audioplayer_sonos = false;
        }

        \OC::$server->getEventDispatcher()->dispatch('OCA\audioplayer::loadAdditionalScripts');

        $response = new TemplateResponse('audioplayer', 'index');
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedMediaDomain('*'); //required for external m3u playlists
        $response->setContentSecurityPolicy($csp);
        $response->setParams([
            'audioplayer_navigationShown' => $this->configManager->getUserValue($this->userId, $this->appName, 'navigation'),
            'audioplayer_view' => $this->configManager->getUserValue($this->userId, $this->appName, 'view') ?: 'pictures',
            'audioplayer_volume' => $this->configManager->getUserValue($this->userId, $this->appName, 'volume') ?: '100',
            'audioplayer_sonos' => $audioplayer_sonos,
        ]);
        return $response;
    }
}
