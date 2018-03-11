<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2017 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDbConnection;
use OCP\Share\IManager;
use OCP\ILogger;

/**
 * Controller class for main page.
 */
class DbController extends Controller
{

    private $userId;
    private $l10n;
    private $db;
    private $occ_job;
    private $shareManager;
    private $logger;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDbConnection $db,
        IManager $shareManager,
        ILogger $logger
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->shareManager = $shareManager;
        $this->logger = $logger;
    }

    public function loadArtistsToAlbum($iAlbumId, $ARtistID)
    {
        # load albumartist if available
        # if no albumartist, we will load all artists from the tracks
        # if all the same - display it as album artist
        # if different track-artists, display "various"
        if ((int)$ARtistID !== 0) {
            $stmt = $this->db->prepare('SELECT `name`  FROM `*PREFIX*audioplayer_artists` WHERE  `id` = ?');
            $stmt->execute(array($ARtistID));
            $row = $stmt->fetch();
            return $row['name'];
        } else {
            $stmt = $this->db->prepare('SELECT DISTINCT(`artist_id`) FROM `*PREFIX*audioplayer_tracks` WHERE  `album_id` = ?');
            $stmt->execute(array($iAlbumId));
            $TArtist = $stmt->fetch();
            $rowCount = $stmt->rowCount();

            if ($rowCount === 1) {
                $stmt = $this->db->prepare('SELECT `name`  FROM `*PREFIX*audioplayer_artists` WHERE  `id` = ?');
                $stmt->execute(array($TArtist['artist_id']));
                $row = $stmt->fetch();
                return $row['name'];
            } else {
                return (string)$this->l10n->t('Various Artists');
            }
        }
    }

    /**
     * @NoAdminRequired
     * @param $searchquery
     * @return array
     */
    public function searchProperties($searchquery)
    {
        $searchresult = array();
        $SQL = "SELECT `id`,`name` FROM `*PREFIX*audioplayer_albums` WHERE (LOWER(`name`) LIKE LOWER(?)) AND `user_id` = ?";
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array('%' . addslashes($searchquery) . '%', $this->userId));
        $results = $stmt->fetchAll();
        if (!is_null($results)) {
            foreach ($results as $row) {
                $searchresult[] = [
                    'id' => 'Album-' . $row['id'],
                    'name' => $this->l10n->t('Album') . ': ' . $row['name'],
                ];
            }
        }

        $SQL = "SELECT `id`,`name` FROM `*PREFIX*audioplayer_artists` WHERE (LOWER(`name`) LIKE LOWER(?)) AND `user_id` = ?";
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array('%' . addslashes($searchquery) . '%', $this->userId));
        $results = $stmt->fetchAll();
        if (!is_null($results)) {
            foreach ($results as $row) {
                $searchresult[] = [
                    'id' => 'Artist-' . $row['id'],
                    'name' => $this->l10n->t('Artist') . ': ' . $row['name'],
                ];
            }
        }

        $SQL = "SELECT `album_id`, `title` FROM `*PREFIX*audioplayer_tracks` WHERE (LOWER(`title`) LIKE LOWER(?)) AND `user_id` = ?";
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array('%' . addslashes($searchquery) . '%', $this->userId));
        $results = $stmt->fetchAll();
        if (!is_null($results)) {
            foreach ($results as $row) {
                $searchresult[] = [
                    'id' => 'Album-' . $row['album_id'],
                    'name' => $this->l10n->t('Title') . ': ' . $row['title'],
                ];
            }
        }

        if (is_array($searchresult)) {
            return $searchresult;
        } else {
            return array();
        }
    }

    /**
     * @NoAdminRequired
     * @param int $userId
     * @param $output
     * @param $hook
     *
     */
    public function resetMediaLibrary($userId = null, $output = null, $hook = null)
    {

        if ($userId !== null) {
            $this->occ_job = true;
            $this->userId = $userId;
        } else {
            $this->occ_job = false;
        }

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_artists` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_genre` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_albums` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));
        $results = $stmt->fetchAll();
        if (!is_null($results)) {
            foreach ($results as $row) {
                $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_playlist_tracks` WHERE `playlist_id` = ?');
                $stmt->execute(array($row['id']));
            }
        }

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_stats` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_streams` WHERE `user_id` = ?');
        $stmt->execute(array($this->userId));

        $result = [
            'status' => 'success',
            'msg' => 'all good'
        ];

        // applies if scanner is not started via occ
        if (!$this->occ_job) {
            $response = new JSONResponse();
            $response->setData($result);
            return $response;
        } elseif ($hook === null) {
            $output->writeln("Reset finished");
        } else {
            $this->logger->info('Library of "' . $userId . '" reset due to user deletion', array('app' => 'audioplayer'));
        }
        return true;
    }

    /**
     * Delete single title from audio player tables
     * @NoAdminRequired
     * @param int $file_id
     */
    public function deleteFromDB($file_id)
    {
        $this->logger->debug('deleteFromDB: ' . $file_id, array('app' => 'audioplayer'));

        $stmt = $this->db->prepare('SELECT `album_id`, `id` FROM `*PREFIX*audioplayer_tracks` WHERE `file_id` = ?  AND `user_id` = ?');
        $stmt->execute(array($file_id, $this->userId));
        $row = $stmt->fetch();
        $AlbumId = $row['album_id'];
        $TrackId = $row['id'];

        $stmt = $this->db->prepare('SELECT COUNT(`album_id`) AS `ALBUMCOUNT`  FROM `*PREFIX*audioplayer_tracks` WHERE `album_id` = ? ');
        $stmt->execute(array($AlbumId));
        $row = $stmt->fetch();
        if ((int)$row['ALBUMCOUNT'] === 1) {
            $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_albums` WHERE `id` = ? AND `user_id` = ?');
            $stmt->execute(array($AlbumId, $this->userId));
        }

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_tracks` WHERE  `file_id` = ? AND `user_id` = ?');
        $stmt->execute(array($file_id, $this->userId));

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_streams` WHERE  `file_id` = ? AND `user_id` = ?');
        $stmt->execute(array($file_id, $this->userId));

        $stmt = $this->db->prepare('SELECT `playlist_id` FROM `*PREFIX*audioplayer_playlist_tracks` WHERE `track_id` = ?');
        $stmt->execute(array($TrackId));
        $row = $stmt->fetch();
        $PlaylistId = $row['playlist_id'];

        $stmt = $this->db->prepare('SELECT COUNT(`playlist_id`) AS `PLAYLISTCOUNT` FROM `*PREFIX*audioplayer_playlist_tracks` WHERE `playlist_id` = ? ');
        $stmt->execute(array($PlaylistId));
        $row = $stmt->fetch();
        if ((int)$row['PLAYLISTCOUNT'] === 1) {
            $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_playlists` WHERE `id` = ? AND `user_id` = ?');
            $stmt->execute(array($PlaylistId, $this->userId));
        }

        $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_playlist_tracks` WHERE  `track_id` = ?');
        $stmt->execute(array($TrackId));
    }
}
