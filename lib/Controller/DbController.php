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
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;
use OCP\ITagManager;

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
    private $tagManager;
    private $logger;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db,
        ITagManager $tagManager,
        IManager $shareManager,
        LoggerInterface $logger
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->shareManager = $shareManager;
        $this->tagManager = $tagManager;
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
    public function search($searchquery)
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

        $SQL = "SELECT `AA`.`id`, `AA`.`name` 
                FROM `*PREFIX*audioplayer_artists` `AA`
                JOIN `*PREFIX*audioplayer_tracks` `AT`
				ON `AA`.`id` = `AT`.`artist_id`
                WHERE (LOWER(`AA`.`name`) LIKE LOWER(?)) AND `AA`.`user_id` = ?";
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
        return $searchresult;
    }

    /**
     * @NoAdminRequired
     * @param string $userId
     * @param $output
     * @param $hook
     *
     * @return bool|JSONResponse
     */
    public function resetMediaLibrary($userId = null, $output = null, $hook = null)
    {
        if ($userId !== null) {
            $this->occ_job = true;
            $this->userId = $userId;
        } else {
            $this->occ_job = false;
        }

        $this->db->beginTransaction();
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

        $this->db->commit();

        $result = [
            'status' => 'success',
            'msg' => 'all good'
        ];

        // applies if scanner is not started via occ
        if (!$this->occ_job) {
            return new JSONResponse($result);
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
     * @param int $userId
     * @return bool
     */
    public function deleteFromDB($file_id, $userId = null)
    {
        // check if scanner is started from web or occ
        if ($userId !== null) {
            $this->userId = $userId;
        }
        $this->logger->debug('deleteFromDB: ' . $file_id, array('app' => 'audioplayer'));

        $stmt = $this->db->prepare('SELECT `album_id`, `id` FROM `*PREFIX*audioplayer_tracks` WHERE `file_id` = ?  AND `user_id` = ?');
        $stmt->execute(array($file_id, $this->userId));
        $row = $stmt->fetch();

        if (isset($row['id'])) {
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

            if ($row['playlist_id']) {
                $PlaylistId = $row['playlist_id'];

                $stmt = $this->db->prepare('SELECT COUNT(`playlist_id`) AS `PLAYLISTCOUNT` FROM `*PREFIX*audioplayer_playlist_tracks` WHERE `playlist_id` = ? ');
                $stmt->execute(array($PlaylistId));
                $row = $stmt->fetch();
                if ((int)$row['PLAYLISTCOUNT'] === 1) {
                    $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_playlists` WHERE `id` = ? AND `user_id` = ?');
                    $stmt->execute(array($PlaylistId, $this->userId));
                }
            }
            $stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_playlist_tracks` WHERE  `track_id` = ?');
            $stmt->execute(array($TrackId));
        }

        return true;
    }

    /**
     * write cover image data to album
     * @param int $userId
     * @param integer $iAlbumId
     * @param string $sImage
     * @return true
     */
    public function writeCoverToAlbum($userId, $iAlbumId, $sImage)
    {
        $stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `cover`= ?, `bgcolor`= ? WHERE `id` = ? AND `user_id` = ?');
        $stmt->execute(array($sImage, '', $iAlbumId, $userId));
        return true;
    }

    /**
     * Add album to db if not exist
     * @param int $userId
     * @param string $sAlbum
     * @param string $sYear
     * @param int $iArtistId
     * @param int $parentId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function writeAlbumToDB($userId, $sAlbum, $sYear, $iArtistId, $parentId)
    {
        $sAlbum = $this->truncate($sAlbum, '256');
        $sYear = $this->normalizeInteger($sYear);
        $AlbumCount = 0;

        $stmt = $this->db->prepare('SELECT `id`, `artist_id` FROM `*PREFIX*audioplayer_albums` WHERE `user_id` = ? AND `name` = ? AND `folder_id` = ?');
        $stmt->execute(array($userId, $sAlbum, $parentId));
        $row = $stmt->fetch();
        if ($row) {
            if ((int)$row['artist_id'] !== (int)$iArtistId) {
                $various_id = $this->writeArtistToDB($userId, $this->l10n->t('Various Artists'));
                $stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `artist_id`= ? WHERE `id` = ? AND `user_id` = ?');
                $stmt->execute(array($various_id, $row['id'], $userId));
            }
            $insertid = $row['id'];
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_albums` (`user_id`,`name`,`folder_id`) VALUES(?,?,?)');
            $stmt->execute(array($userId, $sAlbum, $parentId));
            $insertid = $this->db->lastInsertId('*PREFIX*audioplayer_albums');
            if ($iArtistId) {
                $stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `year`= ?, `artist_id`= ? WHERE `id` = ? AND `user_id` = ?');
                $stmt->execute(array((int)$sYear, $iArtistId, $insertid, $userId));
            } else {
                $stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `year`= ? WHERE `id` = ? AND `user_id` = ?');
                $stmt->execute(array((int)$sYear, $insertid, $userId));
            }
            $AlbumCount = 1;
        }

        $return = [
            'id' => $insertid,
            'state' => true,
            'albumcount' => $AlbumCount,
        ];
        return $return;
    }

    /**
     * truncates fiels do DB-field size
     *
     * @param $string
     * @param $length
     * @param $dots
     * @return string
     */
    private function truncate($string, $length, $dots = "...")
    {
        return (strlen($string) > $length) ? mb_strcut($string, 0, $length - strlen($dots)) . $dots : $string;
    }

    /**
     * validate unsigned int values
     *
     * @param string $value
     * @return int value
     */
    private function normalizeInteger($value)
    {
        // convert format '1/10' to '1' and '-1' to null
        $tmp = explode('/', $value);
        $tmp = explode('-', $tmp[0]);
        $value = $tmp[0];
        if (is_numeric($value) && ((int)$value) > 0) {
            $value = (int)$value;
        } else {
            $value = 0;
        }
        return $value;
    }

    /**
     * Add artist to db if not exist
     * @param int $userId
     * @param string $sArtist
     * @return int
     */
    public function writeArtistToDB($userId, $sArtist)
    {
        $sArtist = $this->truncate($sArtist, '256');

        $stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_artists` WHERE `user_id` = ? AND `name` = ?');
        $stmt->execute(array($userId, $sArtist));
        $row = $stmt->fetch();
        if ($row) {
            return $row['id'];
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_artists` (`user_id`,`name`) VALUES(?,?)');
            $stmt->execute(array($userId, $sArtist));
            return $this->db->lastInsertId('*PREFIX*audioplayer_artists');
        }
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollBack()
    {
        $this->db->rollBack();
    }

    /**
     * Add genre to db if not exist
     * @param int $userId
     * @param string $sGenre
     * @return int
     */
    public function writeGenreToDB($userId, $sGenre)
    {
        $sGenre = $this->truncate($sGenre, '256');

        $stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_genre` WHERE `user_id` = ? AND `name` = ?');
        $stmt->execute(array($userId, $sGenre));
        $row = $stmt->fetch();
        if ($row) {
            return $row['id'];
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_genre` (`user_id`,`name`) VALUES(?,?)');
            $stmt->execute(array($userId, $sGenre));
            return $this->db->lastInsertId('*PREFIX*audioplayer_genre');
        }
    }

    /**
     * Add track to db if not exist
     * @param int $userId
     * @param array $aTrack
     * @return array
     */
    public function writeTrackToDB($userId, $aTrack)
    {
        $dublicate = 0;
        $insertid = 0;
        $SQL = 'SELECT `id` FROM `*PREFIX*audioplayer_tracks` WHERE `user_id`= ? AND `title`= ? AND `number`= ? 
				AND `artist_id`= ? AND `album_id`= ? AND `length`= ? AND `bitrate`= ? 
				AND `mimetype`= ? AND `genre_id`= ? AND `year`= ?
				AND `disc`= ? AND `composer`= ? AND `subtitle`= ?';
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($userId,
            $aTrack['title'],
            $aTrack['number'],
            $aTrack['artist_id'],
            $aTrack['album_id'],
            $aTrack['length'],
            $aTrack['bitrate'],
            $aTrack['mimetype'],
            $aTrack['genre'],
            $aTrack['year'],
            $aTrack['disc'],
            $aTrack['composer'],
            $aTrack['subtitle'],
        ));
        $row = $stmt->fetch();
        if (isset($row['id'])) {
            $dublicate = 1;
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_tracks` (`user_id`,`title`,`number`,`artist_id`,`album_id`,`length`,`file_id`,`bitrate`,`mimetype`,`genre_id`,`year`,`folder_id`,`disc`,`composer`,`subtitle`,`isrc`,`copyright`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute(array($userId,
                $aTrack['title'],
                $aTrack['number'],
                $aTrack['artist_id'],
                $aTrack['album_id'],
                $aTrack['length'],
                $aTrack['file_id'],
                $aTrack['bitrate'],
                $aTrack['mimetype'],
                $aTrack['genre'],
                $aTrack['year'],
                $aTrack['folder_id'],
                $aTrack['disc'],
                $aTrack['composer'],
                $aTrack['subtitle'],
                $aTrack['isrc'],
                $aTrack['copyright'],
            ));
            $insertid = $this->db->lastInsertId('*PREFIX*audioplayer_tracks');
        }
        $return = [
            'id' => $insertid,
            'state' => true,
            'dublicate' => $dublicate,
        ];
        return $return;
    }

    /**
     * Get audio info for single track
     * @param int $trackId
     * @param int $fileId
     * @return array
     */
    public function getTrackInfo($trackId = null, $fileId = null)
    {
        $SQL = "SELECT `AT`.`title` AS `Title`,
                      `AT`.`subtitle` AS `Subtitle`,
                      `AA`.`name` AS `Artist`,
                      `AB`.`artist_id` AS `Album Artist`,
                      `AT`.`composer` AS `Composer`,
                      `AB`.`name` AS `Album`,
                      `AG`.`name` AS `Genre`,
					  `AT`.`year` AS `Year`,
                      `AT`.`disc` AS `Disc`,
                      `AT`.`number` AS `Track`,
					  `AT`.`length` AS `Length`,
                      ROUND((`AT`.`bitrate` / 1000 ),0) AS `Bitrate`,
                      `AT`.`mimetype` AS `MIME type`,
                      `AT`.`isrc` AS `ISRC`,
                      `AT`.`copyright` AS `Copyright`,
					  `AT`.`file_id`, 
					  `AB`.`id` AS `album_id`,
                      `AT`.`id`
						FROM `*PREFIX*audioplayer_tracks` `AT`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
						LEFT JOIN `*PREFIX*audioplayer_genre` `AG` ON `AT`.`genre_id` = `AG`.`id`
						LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`";

        if ($trackId !== null) {
            $SQL .= " WHERE  `AT`.`user_id` = ? AND `AT`.`id` = ?
			 		ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC ";
            $selectId = $trackId;
        } else {
            $SQL .= " WHERE  `AT`.`user_id` = ? AND `AT`.`file_id` = ?
			 		ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC ";
            $selectId = $fileId;
        }

        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, $selectId));
        $row = $stmt->fetch();

        $favorites = $this->tagManager->load('files')->getFavorites();
        if (in_array($row['file_id'], $favorites)) {
            $row['fav'] = "t";
        } else {
            $row['fav'] = "f";
        }

        return $row;
    }

    /**
     * Get file id for single track
     * @param int $trackId
     * @return int
     */
    public function getFileId($trackId)
    {
        $SQL = "SELECT `file_id` FROM `*PREFIX*audioplayer_tracks` WHERE  `user_id` = ? AND `id` = ?";
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, $trackId));
        $row = $stmt->fetch();
        return $row['file_id'];
    }

    /**
     * Add track to db if not exist
     * @param int $userId
     * @param int $track_id
     * @param string $editKey
     * @param string $editValue
     * @return bool
     */
    public function updateTrack($userId, $track_id, $editKey, $editValue)
    {
        $SQL = 'UPDATE `*PREFIX*audioplayer_tracks` SET `' . $editKey . '` = ? WHERE `user_id` = ? AND `id` = ?';
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($editValue,
            $userId,
            $track_id
        ));
        return true;
    }

    /**
     * Add stream to db if not exist
     * @param int $userId
     * @param array $aStream
     * @return array
     */
    public function writeStreamToDB($userId, $aStream)
    {
        $stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_streams` WHERE `user_id` = ? AND `file_id` = ? ');
        $stmt->execute(array($userId, $aStream['file_id']));
        $row = $stmt->fetch();
        $dublicate = 0;
        $insertid = 0;
        if (isset($row['id'])) {
            $dublicate = 1;
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_streams` (`user_id`,`title`,`file_id`,`mimetype`) VALUES(?,?,?,?)');
            $stmt->execute(array($userId,
                $aStream['title'],
                $aStream['file_id'],
                $aStream['mimetype'],
            ));
            $insertid = $this->db->lastInsertId('*PREFIX*audioplayer_streams');
        }
        $return = [
            'id' => $insertid,
            'state' => true,
            'dublicate' => $dublicate,
        ];
        return $return;
    }

    /**
     * Get all playlists were track is added
     * @param int $userId
     * @param int $trackId
     * @return array
     */
    public function getPlaylistsForTrack($userId, $trackId)
    {
        $playlists = array();
        $SQL = "SELECT  `AP`.`playlist_id` , `AN`.`name`, LOWER(`AN`.`name`) AS `lower`
						FROM `*PREFIX*audioplayer_playlist_tracks` `AP`
						LEFT JOIN `*PREFIX*audioplayer_playlists` `AN` 
						ON `AP`.`playlist_id` = `AN`.`id`
			 			WHERE  `AN`.`user_id` = ?
			 			AND `AP`.`track_id` = ?
			 			ORDER BY LOWER(`AN`.`name`) ASC
			 			";
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($userId, $trackId));
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            array_splice($row, 2, 1);
            $playlists[] = $row;
        }

        return $playlists;
    }

    /**
     * @NoAdminRequired
     * @param $type
     * @param $value
     * @param $userId
     * @return string
     */
    public function setSessionValue($type, $value, $userId)
    {
        if ($userId) {
            $this->userId = $userId;
        }
        //$this->session->set($type, $value);
        $SQL = 'SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid`= ? AND `appid`= ? AND `configkey`= ?';
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, 'audioplayer', $type));
        $row = $stmt->fetch();
        if (isset($row['configvalue'])) {
            $stmt = $this->db->prepare('UPDATE `*PREFIX*preferences` SET `configvalue`= ? WHERE `userid`= ? AND `appid`= ? AND `configkey`= ?');
            $stmt->execute(array($value, $this->userId, 'audioplayer', $type));
            return 'update';
        } else {
            $stmt = $this->db->prepare('INSERT INTO `*PREFIX*preferences` (`userid`,`appid`,`configkey`,`configvalue`) VALUES(?,?,?,?)');
            $stmt->execute(array($this->userId, 'audioplayer', $type, $value));
            return 'insert';
        }
    }

    /**
     * @NoAdminRequired
     * @param $type
     * @return string
     */
    public function getSessionValue($type)
    {
        //return $this->session->get($type);
        $SQL = 'SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid`= ? AND `appid`= ? AND `configkey`= ?';
        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, 'audioplayer', $type));
        $row = $stmt->fetch();
        return $row['configvalue'];
    }
}
