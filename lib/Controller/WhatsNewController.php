<?php
/**
 * Audioplayer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright 2020 Marcel Scherello
 */

namespace OCA\audioplayer\Controller;

use OCA\audioplayer\WhatsNew\WhatsNewCheck;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class WhatsNewController extends Controller
{

    /** @var IConfig */
    protected $config;
    /** @var IUserSession */
    private $userSession;
    /** @var WhatsNewCheck */
    private $whatsNewService;
    /** @var IFactory */
    private $langFactory;
    private $logger;
    private $AppName;

    public function __construct(
        string $AppName,
        IRequest $request,
        IUserSession $userSession,
        IConfig $config,
        WhatsNewCheck $whatsNewService,
        IFactory $langFactory,
        LoggerInterface $logger
    )
    {
        parent::__construct($AppName, $request);
        $this->AppName = $AppName;
        $this->config = $config;
        $this->userSession = $userSession;
        $this->whatsNewService = $whatsNewService;
        $this->langFactory = $langFactory;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     */
    public function get(): DataResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException("Acting user cannot be resolved");
        }
        $lastRead = $this->config->getUserValue($user->getUID(), $this->AppName, 'whatsNewLastRead', 0);
        $currentVersion = $this->whatsNewService->normalizeVersion($this->config->getAppValue($this->AppName, 'installed_version'));

        if (version_compare($lastRead, $currentVersion, '>=')) {
            return new DataResponse([], Http::STATUS_NO_CONTENT);
        }

        try {
            $iterator = $this->langFactory->getLanguageIterator();
            $whatsNew = $this->whatsNewService->getChangesForVersion($currentVersion);

            $resultData = [
                'changelogURL' => $whatsNew['changelogURL'],
                'product' => 'Audioplayer',
                'version' => $currentVersion,
            ];
            do {
                $lang = $iterator->current();
                if (isset($whatsNew['whatsNew'][$lang])) {
                    $resultData['whatsNew'] = $whatsNew['whatsNew'][$lang];
                    break;
                }
                $iterator->next();
            } while ($lang !== 'en' && $iterator->valid());
            return new DataResponse($resultData);
        } catch (DoesNotExistException $e) {
            return new DataResponse([], Http::STATUS_NO_CONTENT);
        }
    }

    /**
     * @NoAdminRequired
     *
     * @param string $version
     * @return DataResponse
     * @throws DoesNotExistException
     * @throws \OCP\PreConditionNotMetException
     */
    public function dismiss($version)
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException("Acting user cannot be resolved");
        }
        $version = $this->whatsNewService->normalizeVersion($version);
        // checks whether it's a valid version, throws an Exception otherwise
        $this->whatsNewService->getChangesForVersion($version);
        $this->config->setUserValue($user->getUID(), $this->AppName, 'whatsNewLastRead', $version);
        return new DataResponse();
    }
}