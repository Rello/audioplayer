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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDBConnection;
use OCA\audioplayer\Service\PlaylistService;
use OCA\audioplayer\Db\PlaylistMapper;

/**
 * Controller class for main page.
 */
class PlaylistController extends Controller
{

    private $userId;
    private $l10n;
    private $service;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $mapper = new PlaylistMapper($db);
        $this->service = new PlaylistService($userId, $mapper);
    }

    /**
     * @param $playlist
     * @return null|JSONResponse
     */
    #[NoAdminRequired]
    public function addPlaylist($playlist)
    {
        if ($playlist !== '') {
            $aResult = $this->service->addPlaylist($playlist);
            if ($aResult['msg'] === 'new') {
                $result = [
                    'status' => 'success',
                    'data' => ['playlist' => $playlist]
                ];
            } else {
                $result = [
                    'status' => 'success',
                    'data' => 'exist',
                ];
            }
            return new JSONResponse($result);
        } else {
            return null;
        }
    }


    /**
     * @param integer $plId
     * @param string $newname
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function updatePlaylist($plId, $newname)
    {

        if ($this->service->updatePlaylist($plId, $newname)) {
            $params = [
                'status' => 'success',
            ];
        } else {
            $params = [
                'status' => 'error',
            ];
        }

        return new JSONResponse($params);
    }


    /**
     * @param $playlistid
     * @param $songid
     * @param $sorting
     * @return bool
     */
    #[NoAdminRequired]
    public function addTrackToPlaylist($playlistid, $songid, $sorting)
    {
        return $this->service->addTrackToPlaylist((int)$playlistid, (int)$songid, (int)$sorting);
    }

    /**
     * @param $playlistid
     * @param $songids
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function sortPlaylist($playlistid, $songids)
    {
        $iTrackIds = explode(';', $songids);
        $this->service->sortPlaylist((int)$playlistid, $iTrackIds);
        $result = [
            'status' => 'success',
            'msg' => (string)$this->l10n->t('Sorting Playlist success! Playlist reloaded!')
        ];
        return new JSONResponse($result);
    }

    /**
     * @param $playlistid
     * @param $trackid
     * @return bool
     */
    #[NoAdminRequired]
    public function removeTrackFromPlaylist($playlistid, $trackid)
    {
        return $this->service->removeTrackFromPlaylist((int)$playlistid, (int)$trackid);
    }

    /**
     * @param $playlistid
     * @return bool|JSONResponse
     */
    #[NoAdminRequired]
    public function removePlaylist($playlistid)
    {
        if (!$this->service->removePlaylist((int)$playlistid)) {
            return false;
        }

        $result = [
            'status' => 'success',
            'data' => ['playlist' => $playlistid]
        ];

        return new JSONResponse($result);
    }
}
