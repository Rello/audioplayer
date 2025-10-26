<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Attributes\NoAdminRequired;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\audioplayer\Service\SidebarService;

/**
 * Controller class for Sidebar.
 */
class SidebarController extends Controller
{

    private $userId;
    private $service;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        SidebarService $service
    )
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->userId = $userId;
        $this->service = $service;
    }

    /**
     * @param $trackid
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function getAudioInfo($trackid)
    {

        $row = $this->service->getAudioInfo((int)$trackid);

        if (!empty($row) && $row['Title']) {
            $result = [
                'status' => 'success',
                'data' => $row,
            ];
        } else {
            $result = [
                'status' => 'error',
                'data' => 'nodata',
            ];
        }
        return new JSONResponse($result);
    }

    /**
     * @param $trackid
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function getPlaylists($trackid)
    {
        $playlists = $this->service->getPlaylists((int)$trackid);
        if (!empty($playlists)) {
            $result = [
                'status' => 'success',
                'data' => $playlists];
        } else {
            $result = [
                'status' => 'error',
                'data' => 'nodata'];
        }
        return new JSONResponse($result);
    }

}
