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

use OCA\audioplayer\Service\CoverService;
use OCP\AppFramework\Attributes\NoAdminRequired;
use OCP\AppFramework\Attributes\NoCSRFRequired;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCA\audioplayer\Http\ImageResponse;

class CoverController extends Controller
{
    private $coverService;

    public function __construct(
        $appName,
        IRequest $request,
        CoverService $coverService
    ) {
        parent::__construct($appName, $request);
        $this->coverService = $coverService;
    }

    /**
     * @param int $album
     * @return ImageResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getCover(int $album)
    {
        $cover = $this->coverService->getCover($album);
        $imageData = base64_decode($cover);
        return new ImageResponse(['mimetype' => 'image/jpg', 'content' => $imageData]);
    }
}
