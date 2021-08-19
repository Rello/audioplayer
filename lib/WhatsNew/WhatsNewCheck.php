<?php
declare(strict_types=1);
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

namespace OCA\audioplayer\WhatsNew;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Psr\Log\LoggerInterface;

class WhatsNewCheck
{
    public const RESPONSE_NO_CONTENT = 0;
    public const RESPONSE_USE_CACHE = 1;
    public const RESPONSE_HAS_CONTENT = 2;
    /** @var IClientService */
    protected $clientService;
    /** @var WhatsNewMapper */
    private $mapper;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(IClientService $clientService, WhatsNewMapper $mapper, LoggerInterface $logger)
    {
        $this->clientService = $clientService;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /**
     * @param string $version
     * @return array
     * @throws DoesNotExistException
     */
    public function getChangesForVersion(string $version): array
    {
        $version = $this->normalizeVersion($version);
        $changesInfo = $this->mapper->getChanges($version);
        $changesData = json_decode($changesInfo->getData(), true);
        if (empty($changesData)) {
            throw new DoesNotExistException('Unable to decode changes info');
        }
        return $changesData;
    }

    /**
     * returns a x.y.z form of the provided version. Extra numbers will be
     * omitted, missing ones added as zeros.
     */
    public function normalizeVersion(string $version): string
    {
        $versionNumbers = array_slice(explode('.', $version), 0, 3);
        $versionNumbers[0] = $versionNumbers[0] ?: '0'; // deal with empty input
        while (count($versionNumbers) < 3) {
            // changelog server expects x.y.z, pad 0 if it is too short
            $versionNumbers[] = 0;
        }
        return implode('.', $versionNumbers);
    }

    /**
     * @param string $uri
     * @param string $version
     * @return array
     * @throws \Exception
     */
    public function check(string $uri, string $version): array
    {
        try {
            $version = $this->normalizeVersion($version);
            $changesInfo = $this->mapper->getChanges($version);
            if ($changesInfo->getLastCheck() + 1800 > time()) {
                return json_decode($changesInfo->getData(), true);
            }
        } catch (DoesNotExistException $e) {
            $changesInfo = new WhatsNewResult();
        }

        $response = $this->queryChangesServer($uri, $changesInfo);

        switch ($this->evaluateResponse($response)) {
            case self::RESPONSE_NO_CONTENT:
                return [];
            case self::RESPONSE_USE_CACHE:
                return json_decode($changesInfo->getData(), true);
            case self::RESPONSE_HAS_CONTENT:
            default:
                $data = $this->extractData($response->getBody());
                $changesInfo->setData(json_encode($data));
                $changesInfo->setEtag($response->getHeader('Etag'));
                $this->cacheResult($changesInfo, $version);

                return $data;
        }
    }

    /**
     * @throws \Exception
     */
    protected function queryChangesServer(string $uri, WhatsNewResult $entry): IResponse
    {
        $headers = [];
        if ($entry->getEtag() !== '') {
            $headers['If-None-Match'] = [$entry->getEtag()];
        }

        $entry->setLastCheck(time());
        $client = $this->clientService->newClient();
        return $client->get($uri, [
            'headers' => $headers,
        ]);
    }

    protected function evaluateResponse(IResponse $response): int
    {
        if ($response->getStatusCode() === 304) {
            return self::RESPONSE_USE_CACHE;
        } elseif ($response->getStatusCode() === 404) {
            return self::RESPONSE_NO_CONTENT;
        } elseif ($response->getStatusCode() === 200) {
            return self::RESPONSE_HAS_CONTENT;
        }
        return self::RESPONSE_NO_CONTENT;
    }

    protected function extractData($body): array
    {
        $data = [];
        if ($body) {
            $loadEntities = libxml_disable_entity_loader(true);
            $xml = @simplexml_load_string($body);
            libxml_disable_entity_loader($loadEntities);
            if ($xml !== false) {
                $data['changelogURL'] = (string)$xml->changelog['href'];
                $data['whatsNew'] = [];
                foreach ($xml->whatsNew as $infoSet) {
                    $data['whatsNew'][(string)$infoSet['lang']] = [
                        'regular' => (array)$infoSet->regular->item,
                        'admin' => (array)$infoSet->admin->item,
                    ];
                }
            } else {
                libxml_clear_errors();
            }
        }
        return $data;
    }

    protected function cacheResult(WhatsNewResult $entry, string $version)
    {
        if ($entry->getVersion() === $version) {
            $this->mapper->update($entry);
        } else {
            $entry->setVersion($version);
            $this->mapper->insert($entry);
        }
    }
}