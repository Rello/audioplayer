<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2021 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\audioplayer\Service\MusicService;

/**
 * Controller class for main page.
 */
class MusicController extends Controller
{
    private MusicService $service;

    public function __construct(string $appName, IRequest $request, MusicService $service)
    {
        parent::__construct($appName, $request);
        $this->service = $service;
    }

    /**
     * @param $token
     * @return JSONResponse
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Share\Exceptions\ShareNotFound
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function getPublicAudioInfo($token)
    {
        if (empty($token)) {
            return new JSONResponse(['status' => 'error', 'data' => 'nodata']);
        }

        $row = $this->service->getPublicAudioInfo($token);
        if ($row) {
            $result = ['status' => 'success', 'data' => $row];
        } else {
            $result = ['status' => 'error', 'data' => 'nodata'];
        }

        return new JSONResponse($result);
    }

    /**
     * Stream files in OCP withing link-shared folder
     * @param $token
     * @param $file
     * @throws \OCP\Files\NotFoundException
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function getPublicAudioStream($token, $file)
    {
        if (empty($token)) {
            return;
        }

        $stream = $this->service->createPublicAudioStream($token, $file);
        \OC::$server->getSession()->close();
        $stream->start();
    }

    /**
     * @param $file
     * @param $t
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getAudioStream($file, $t)
    {
        $stream = $this->service->createAudioStream($file, $t);
        \OC::$server->getSession()->close();
        $stream->start();
    }
}
