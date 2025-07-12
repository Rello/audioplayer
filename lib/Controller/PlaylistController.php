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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDBConnection;
use OCA\audioplayer\Service\PlaylistService;
use OCA\audioplayer\DB\PlaylistMapper;

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
     * @NoAdminRequired
     * @param $playlist
     * @return null|JSONResponse
     */
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
     * @NoAdminRequired
     *
     * @param integer $plId
     * @param string $newname
     * @return JSONResponse
     */
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
     * @NoAdminRequired
     * @param $playlistid
     * @param $songid
     * @param $sorting
     * @return bool
     */
    public function addTrackToPlaylist($playlistid, $songid, $sorting)
    {
        return $this->service->addTrackToPlaylist((int)$playlistid, (int)$songid, (int)$sorting);
    }

    /**
     * @NoAdminRequired
     * @param $playlistid
     * @param $songids
     * @return JSONResponse
     */
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
     * @NoAdminRequired
     * @param $playlistid
     * @param $trackid
     * @return bool
     */
    public function removeTrackFromPlaylist($playlistid, $trackid)
    {
        return $this->service->removeTrackFromPlaylist((int)$playlistid, (int)$trackid);
    }

    /**
     * @NoAdminRequired
     * @param $playlistid
     * @return bool|JSONResponse
     */
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
