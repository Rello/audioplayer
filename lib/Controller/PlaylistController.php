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

/**
 * Controller class for main page.
 */
class PlaylistController extends Controller
{

    private $userId;
    private $l10n;
    private $db;

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
        $this->db = $db;
    }

    /**
     * @NoAdminRequired
     * @param $playlist
     * @return null|JSONResponse
     */
    public function addPlaylist($playlist)
    {
        if ($playlist !== '') {
            $aResult = $this->writePlaylistToDB($playlist);
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
     * @param $sName
     * @return array
     */
    private function writePlaylistToDB($sName)
    {
        $stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ? AND `name` = ?');
        $stmt->execute(array($this->userId, $sName));
        $row = $stmt->fetch();
        if ($row) {
            $result = ['msg' => 'exist', 'id' => $row['id']];
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_playlists` (`user_id`,`name`) VALUES(?,?)');
            $stmt->execute(array($this->userId, $sName));
            $insertid = $this->db->lastInsertId('*PREFIX*audioplayer_playlists');
            $result = ['msg' => 'new', 'id' => $insertid];
        }
        return $result;
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

        if ($this->updatePlaylistToDB($plId, $newname)) {
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

    private function updatePlaylistToDB($id, $sName)
    {
        $stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_playlists` SET `name` = ? WHERE `user_id`= ? AND `id`= ?');
        $stmt->execute(array($sName, $this->userId, $id));
        return true;
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
        $stmt = $this->db->prepare('SELECT COUNT(*) AS tracks FROM `*PREFIX*audioplayer_playlist_tracks` WHERE `playlist_id` = ? AND `track_id` = ?');
        $stmt->execute(array($playlistid, $songid));
        $row = $stmt->fetch();
        if ($row['tracks']) {
            return false;
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_playlist_tracks` (`playlist_id`,`track_id`,`sortorder`) VALUES(?,?,?)');
            $stmt->execute(array($playlistid, $songid, (int)$sorting));
            return true;
        }
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
        $counter = 1;
        foreach ($iTrackIds as $trackId) {
            $stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_playlist_tracks` SET `sortorder` = ? WHERE `playlist_id` = ? AND `track_id` = ?');
            $stmt->execute(array($counter, $playlistid, $trackId));
            $counter++;
        }
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
        try {
            $sql = 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` '
                . 'WHERE `playlist_id` = ? AND `track_id` = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($playlistid, $trackid));
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @NoAdminRequired
     * @param $playlistid
     * @return bool|JSONResponse
     */
    public function removePlaylist($playlistid)
    {
        try {
            $sql = 'DELETE FROM `*PREFIX*audioplayer_playlists` '
                . 'WHERE `id` = ? AND `user_id` = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($playlistid, $this->userId));

            $sql = 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` '
                . 'WHERE `playlist_id` = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($playlistid));
        } catch (\Exception $e) {
            return false;
        }

        $result = [
            'status' => 'success',
            'data' => ['playlist' => $playlistid]
        ];

        return new JSONResponse($result);
    }
}
