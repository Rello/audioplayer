<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDbConnection;
use OCP\ITagManager;

/**
 * Controller class for Sidebar.
 */
class SidebarController extends Controller
{

    private $userId;
    private $db;
    private $l10n;
    private $tagger;
    private $tagManager;
    private $DBController;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        ITagManager $tagManager,
        IDBConnection $db,
        DbController $DBController
    )
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->l10n = $l10n;
        $this->userId = $userId;
        $this->tagManager = $tagManager;
        $this->tagger = null;
        $this->db = $db;
        $this->DBController = $DBController;
    }

    /**
     * @NoAdminRequired
     * @param $trackid
     * @return JSONResponse
     */
    public function getAudioInfo($trackid)
    {
        $SQL = "SELECT `AT`.`title` AS `Title`,
                      `AA`.`name` AS `Artist`,
                      `AB`.`artist_id` AS `Album Artist`,
                      `AB`.`name` AS `Album`,
                      `AG`.`name` AS `Genre`,
					  `AT`.`length` AS `Length`,
					  `AT`.`year` AS `Year`,
                      `AT`.`disc` AS `Disc`,
                      `AT`.`number` AS `Track`,
                      `AT`.`composer` AS `Composer`,
                      `AT`.`subtitle` AS `Subtitle`,
                      ROUND((`AT`.`bitrate` / 1000 ),0) AS `Bitrate`,
                      `AT`.`mimetype` AS `MIME type`,
					  `AT`.`file_id`, 
					  `AB`.`id` AS `album_id`
						FROM `*PREFIX*audioplayer_tracks` `AT`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
						LEFT JOIN `*PREFIX*audioplayer_genre` `AG` ON `AT`.`genre_id` = `AG`.`id`
						LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 			WHERE  `AT`.`user_id` = ? AND `AT`.`id` = ?
			 			ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC
			 			";

        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, $trackid));
        $row = $stmt->fetch();

        $this->tagger = $this->tagManager->load('files');
        $favorites = $this->tagger->getFavorites();
        if (in_array($row['file_id'], $favorites)) {
            $row['fav'] = "t";
        } else {
            $row['fav'] = "f";
        }

        $artist = $this->DBController->loadArtistsToAlbum($row['album_id'], $row['Album Artist']);
        $row['Album Artist'] = $artist;

        if ($row['Year'] === '0') $row['Year'] = $this->l10n->t('Unknown');

        array_splice($row, 13, 2);

        if ($row['Title']) {
            $result = [
                'status' => 'success',
                'data' => $row];
        } else {
            $result = [
                'status' => 'error',
                'data' => 'nodata'];
        }
        $response = new JSONResponse();
        $response->setData($result);
        return $response;
    }

    /**
     * @NoAdminRequired
     * @param $trackid
     * @return JSONResponse
     */
    public function getPlaylists($trackid)
    {
        $playlists = $this->DBController->getPlaylistsForTrack($this->userId, $trackid);
        if ($playlists) {
            $result = [
                'status' => 'success',
                'data' => $playlists];
        } else {
            $result = [
                'status' => 'error',
                'data' => 'nodata'];
        }
        $response = new JSONResponse();
        $response->setData($result);
        return $response;
    }

}
