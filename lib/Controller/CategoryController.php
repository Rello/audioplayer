<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\InvalidPathException;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDbConnection;
use OCP\ITagManager;
use OCP\Files\IRootFolder;
use OCP\ILogger;
use OCP\Files\NotFoundException;

/**
 * Controller class for main page.
 */
class CategoryController extends Controller
{

    private $userId;
    private $l10n;
    private $db;
    private $tagger;
    private $tagManager;
    private $rootFolder;
    private $logger;
    private $DBController;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db,
        ITagManager $tagManager,
        IRootFolder $rootFolder,
        ILogger $logger,
        DbController $DBController
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->tagManager = $tagManager;
        $this->tagger = null;
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
        $this->DBController = $DBController;
    }

    /**
     * @NoAdminRequired
     *
     */
    public function getCategory($category)
    {
        $playlists = $this->getCategoryforUser($category);

        $result = empty($playlists) ? [
            'status' => 'nodata'
        ] : [
            'status' => 'success',
            'data' => $playlists
        ];
        $response = new JSONResponse();
        $response->setData($result);
        return $response;
    }

    /**
     * Get the categories items for a user
     *
     * @param string $category
     * @return array
     */
    private function getCategoryforUser($category)
    {
        $SQL = null;
        $aPlaylists = array();
        if ($category === 'Artist') {
            $SQL = "SELECT  DISTINCT(`AT`.`artist_id`) AS `id`, `AA`.`name`, LOWER(`AA`.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_tracks` `AT`
						JOIN `*PREFIX*audioplayer_artists` `AA`
						ON `AA`.`id` = `AT`.`artist_id`
			 			WHERE  `AT`.`user_id` = ?
			 			ORDER BY LOWER(`AA`.`name`) ASC
			 			";
        } elseif ($category === 'Genre') {
            $SQL = "SELECT  `id`, `name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_genre`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
        } elseif ($category === 'Year') {
            $SQL = "SELECT DISTINCT(`year`) AS `id` ,`year` AS `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			ORDER BY `id` ASC
			 			";
        } elseif ($category === 'Title') {
            $SQL = "SELECT distinct('0') as `id` ,'" . $this->l10n->t('All Titles') . "' as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			";
        } elseif ($category === 'Playlist') {
            $aPlaylists[] = array("id" => "X1", "name" => $this->l10n->t('Favorites'));
            $aPlaylists[] = array("id" => "X2", "name" => $this->l10n->t('Recently Added'));
            $aPlaylists[] = array("id" => "X3", "name" => $this->l10n->t('Recently Played'));
            $aPlaylists[] = array("id" => "X4", "name" => $this->l10n->t('Most Played'));
            $aPlaylists[] = array("id" => "", "name" => "");

            // Stream files are shown directly
            $SQL = "SELECT  `file_id` AS `id`, `title` AS `name`, LOWER(`title`) AS `lower` 
						FROM `*PREFIX*audioplayer_streams`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`title`) ASC
			 			";
            $stmt = $this->db->prepare($SQL);
            $stmt->execute(array($this->userId));
            $results = $stmt->fetchAll();
            foreach ($results as $row) {
                array_splice($row, 2, 1);
                $row['id'] = 'S' . $row['id'];
                $aPlaylists[] = $row;
            }
            $aPlaylists[] = array("id" => "", "name" => "");

            // regular playlists are selected
            $SQL = "SELECT  `id`,`name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_playlists`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";

        } elseif ($category === 'Folder') {
            $SQL = "SELECT  DISTINCT(`FC`.`fileid`) AS `id`, `FC`.`name`, LOWER(`FC`.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_tracks` `AT`
						JOIN `*PREFIX*filecache` `FC`
						ON `FC`.`fileid` = `AT`.`folder_id`
			 			WHERE `AT`.`user_id` = ?
			 			ORDER BY LOWER(`FC`.`name`) ASC
			 			";
        } elseif ($category === 'Album') {
            $SQL = "SELECT  `AB`.`id` , `AB`.`name`, LOWER(`AB`.`name`) AS `lower` , `AA`.`id` AS `art`, `AB`.`cover` AS `cid` 
						FROM `*PREFIX*audioplayer_albums` `AB`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` 
						ON `AB`.`artist_id` = `AA`.`id`
			 			WHERE `AB`.`user_id` = ?
			 			ORDER BY LOWER(`AB`.`name`) ASC
			 			";
        } elseif ($category === 'Album Artist') {
            $SQL = "SELECT  DISTINCT(`AB`.`artist_id`) AS `id`, `AA`.`name`, LOWER(`AA`.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_albums` `AB`
						JOIN `*PREFIX*audioplayer_artists` `AA`
						ON `AB`.`artist_id` = `AA`.`id`
			 			WHERE `AB`.`user_id` = ?
			 			ORDER BY LOWER(`AA`.`name`) ASC
			 			";

        }

        if (isset($SQL)) {
            $stmt = $this->db->prepare($SQL);
            $stmt->execute(array($this->userId));
            $results = $stmt->fetchAll();
            foreach ($results as $row) {
                if ($category === 'Album') {
                    $row['art'] = $this->DBController->loadArtistsToAlbum($row['id'], $row['art']);
                    if ($row['cid'] !== null) {
                        $row['cid'] = $row['id'];
                    } else {
                        $row['cid'] = '';
                    }
                }
                if ($category === 'Album')
                    array_splice($row, 2, 1);
                if ($row['name'] === '0' OR $row['name'] === '') $row['name'] = $this->l10n->t('Unknown');
                $row['counter'] = $this->getCountForCategory($category, $row['id']);
                $aPlaylists[] = $row;
            }
        }
        return $aPlaylists;
    }

    /**
     * Get the number of items for a category item
     *
     * @param string $category
     * @param integer $categoryId
     * @return integer
     */
    private function getCountForCategory($category, $categoryId)
    {
        $SQL = null;
        if ($category === 'Artist') {
            $SQL = "SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
			 		WHERE  `AT`.`artist_id` = ? 
			 		AND `AT`.`user_id` = ?
			 		";
        } elseif ($category === 'Genre') {
            $SQL = "SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`genre_id` = ?  
					AND `AT`.`user_id` = ?
					";
        } elseif ($category === 'Year') {
            $SQL = "SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`year` = ? 
					AND `AT`.`user_id` = ?
					";
        } elseif ($category === 'Title') {
            $SQL = "SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`id` > ? 
					AND `AT`.`user_id` = ?
					";
        } elseif ($category === 'Playlist') {
            $SQL = "SELECT  COUNT(`AP`.`track_id`) AS `count`
					FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
			 		WHERE  `AP`.`playlist_id` = ?
			 		";
        } elseif ($category === 'Folder') {
            $SQL = "SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`folder_id` = ? 
					AND `AT`.`user_id` = ?
					";
        } elseif ($category === 'Album') {
            $SQL = "SELECT  COUNT(`id`) AS `count` 
					FROM `*PREFIX*audioplayer_tracks`
					WHERE `album_id` = ? 
					AND `user_id` = ?
			 		";
        } elseif ($category === 'Album Artist') {
            $SQL = "SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_albums` `AB` 
                    JOIN `*PREFIX*audioplayer_tracks` `AT`
					ON `AB`.`id` = `AT`.`album_id`
			 		WHERE  `AB`.`artist_id` = ? 
			 		AND `AB`.`user_id` = ?
			 		";
        }

        $stmt = $this->db->prepare($SQL);
        if ($category === 'Playlist') {
            $stmt->execute(array($categoryId));
        } else {
            $stmt->execute(array($categoryId, $this->userId));
        }
        $results = $stmt->fetch();
        return $results['count'];
    }

    /**
     * AJAX function to get playlist titles for a selected category
     * @NoAdminRequired
     *
     * @param string $category
     * @param string $categoryId
     * @return JSONResponse
     */
    public function getCategoryItems($category, $categoryId)
    {
        $albums = 0;
        if ($categoryId[0] === "S") $category = "Stream";
        if ($categoryId[0] === "P") $category = "Playlist";
        $itmes = $this->getItemsforCatagory($category, $categoryId);
        $headers = $this->getHeadersforCatagory($category);
        if ($category === 'Artist') $albums = $this->getAlbumCountForCategory($category, $categoryId);

        $result = !empty($itmes) ? [
            'status' => 'success',
            'data' => $itmes,
            'header' => $headers,
            'albums' => $albums,
        ] : [
            'status' => 'nodata',
        ];
        $response = new JSONResponse();
        $response->setData($result);
        return $response;
    }

    /**
     * Get playlist titles for a selected category
     *
     * @param string $category
     * @param string $categoryId
     * @return array
     */
    private function getItemsforCatagory($category, $categoryId)
    {
        $SQL = null;
        $favorite = false;
        $aTracks = array();
        $SQL_select = "SELECT  `AT`.`id`, `AT`.`title`  AS `cl1`, `AA`.`name` AS `cl2`, `AB`.`name` AS `cl3`, `AT`.`length` AS `len`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AB`.`name`) AS `lower`";
        $SQL_from = " FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`";
        $SQL_order = " ORDER BY LOWER(`AB`.`name`) ASC, `AT`.`disc` ASC, `AT`.`number` ASC";

        if ($category === 'Artist') {
            $SQL_select = "SELECT  `AT`.`id`, `AT`.`title`  AS `cl1`, `AB`.`name` AS `cl2`, `AT`.`year` AS `cl3`, `AT`.`length` AS `len`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AB`.`name`) AS `lower`";
            $SQL = $SQL_select . $SQL_from .
                "WHERE  `AT`.`artist_id` = ? AND `AT`.`user_id` = ?" .
                $SQL_order;
        } elseif ($category === 'Genre') {
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AT`.`genre_id` = ? AND `AT`.`user_id` = ?" .
                $SQL_order;
        } elseif ($category === 'Year') {
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AT`.`year` = ? AND `AT`.`user_id` = ?" .
                $SQL_order;
        } elseif ($category === 'Title') {
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AT`.`id` > ? AND `AT`.`user_id` = ?" .
                $SQL_order;
        } elseif ($category === 'Playlist' AND $categoryId === "X1") { // Favorites
            $SQL = "SELECT  `AT`.`id` , `AT`.`title`  AS `cl1`,`AA`.`name` AS `cl2`, `AB`.`name` AS `cl3`,`AT`.`length` AS `len`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AT`.`title`) AS `lower`" .
                $SQL_from .
                "WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ?" .
                " ORDER BY LOWER(`AT`.`title`) ASC";
            $categoryId = 0; //overwrite to integer for PostgreSQL
            $favorite = true;
        } elseif ($category === 'Playlist' AND $categoryId === "X2") { // Recently Added
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AT`.`file_id` DESC
			 		Limit 100";
            $categoryId = 0;
        } elseif ($category === 'Playlist' AND $categoryId === "X3") { // Recently Played
            $SQL = $SQL_select . $SQL_from .
                "LEFT JOIN `*PREFIX*audioplayer_stats` `AS` ON `AT`.`id` = `AS`.`track_id`
			 		WHERE `AS`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AS`.`playtime` DESC
			 		Limit 50";
            $categoryId = 0;
        } elseif ($category === 'Playlist' AND $categoryId === "X4") { // Most Played
            $SQL = $SQL_select . $SQL_from .
                "LEFT JOIN `*PREFIX*audioplayer_stats` `AS` ON `AT`.`id` = `AS`.`track_id`
			 		WHERE `AS`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AS`.`playcount` DESC
			 		Limit 25";
            $categoryId = 0;
        } elseif ($category === 'Playlist') {
            $SQL = $SQL_select . " , `AP`.`sortorder`" .
                "FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
					LEFT JOIN `*PREFIX*audioplayer_tracks` `AT` ON `AP`.`track_id` = `AT`.`id`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AP`.`playlist_id` = ?
					AND `AT`.`user_id` = ? 
			 		ORDER BY `AP`.`sortorder` ASC";
        } elseif ($category === 'Stream') {
            $aTracks = $this->StreamParser(intval(substr($categoryId, 1)));
            return $aTracks;

        } elseif ($category === 'Folder') {
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AT`.`folder_id` = ? AND `AT`.`user_id` = ?" .
                $SQL_order;
        } elseif ($category === 'Album') {
            $SQL_select = "SELECT  `AT`.`id`, `AT`.`title` AS `cl1`, `AA`.`name` AS `cl2`, `AT`.`length` AS `len`, `AT`.`disc` AS `dsc`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AT`.`title`) AS `lower`,`AT`.`number`  AS `num`";
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AB`.`id` = ? AND `AB`.`user_id` = ?" .
                " ORDER BY `AT`.`disc` ASC, `AT`.`number` ASC";
        } elseif ($category === 'Album Artist') {
            $SQL = $SQL_select . $SQL_from .
                "WHERE  `AB`.`artist_id` = ? AND `AT`.`user_id` = ?" .
                $SQL_order;
        }

        $this->tagger = $this->tagManager->load('files');
        $favorites = $this->tagger->getFavorites();

        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($categoryId, $this->userId));
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            if ($row['cover'] === null) {
                $row['cid'] = '';
            }
            if ($category === 'Album') {
                $row['cl3'] = $row['dsc'] . '-' . $row['num'];
            }
            array_splice($row, 8, 3);
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($row['fid']);
            $file = array_shift($nodes);
            if ($file === null) {
                $this->logger->debug('removed/unshared file found => remove '.$row['fid'], array('app' => 'audioplayer'));
                $this->DBController->deleteFromDB($row['fid'], $this->userId);
                continue;
            }
            $path = $file->getPath();
            $segments = explode('/', trim($path, '/'), 3);
            $row['lin'] = rawurlencode($segments[2]);
            if (is_array($favorites) AND in_array($row['fid'], $favorites)) {
                $row['fav'] = "t";
            } else {
                $row['fav'] = "f";
            }

            if ($favorite AND is_array($favorites) AND !in_array($row['fid'], $favorites)) {
            } else {
                $aTracks[] = $row;
            }
        }
        return $aTracks;
    }

    /**
     * Extract steam urls from playlist files
     *
     * @param integer $fileId
     * @return array
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\InvalidPathException
     */
    private function StreamParser($fileId)
    {
        $tracks = array();
        $x = 0;
        $title = null;
        $userView = $this->rootFolder->getUserFolder($this->userId);
        //$this->logger->debug('removed/unshared file found => remove '.$row['fid'], array('app' => 'audioplayer'));

        $streamfile = $userView->getById($fileId);
        $file_type = $streamfile[0]->getMimetype();
        $file_content = $streamfile[0]->getContent();

        if ($file_type === 'audio/x-scpls') {
            $stream_data = parse_ini_string($file_content, true, INI_SCANNER_RAW);
            $stream_rows = isset($stream_data['playlist']['NumberOfEntries']) ? $stream_data['playlist']['NumberOfEntries'] : $stream_data['playlist']['numberofentries'];
            for ($i = 1; $i <= $stream_rows; $i++) {
                $title = $stream_data['playlist']['Title' . $i];
                $file = $stream_data['playlist']['File' . $i];
                preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $file, $matches);

                if ($matches[0]) {
                    $row = array();
                    $row['id'] = $fileId . $i;
                    $row['fid'] = $fileId . $i;
                    $row['cl1'] = $matches[0][0];
                    $row['cl2'] = '';
                    $row['cl3'] = '';
                    $row['len'] = '';
                    $row['mim'] = $file_type;
                    $row['cid'] = '';
                    $row['lin'] = $matches[0][0];
                    $row['fav'] = 'f';
                    if ($title) $row['cl1'] = $title;
                    $tracks[] = $row;
                }
            }
        } else {
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $file_content) as $line) {
                if (substr($line, 0, 8) === '#EXTINF:') {
                    $extinf = explode(',', substr($line, 8));
                    $title = $extinf[1];
                }
                preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $line, $matches);

                if ($matches[0]) {
                    $x++;
                    $row = array();
                    $row['id'] = $fileId . $x;
                    $row['fid'] = $fileId . $x;
                    $row['cl1'] = $matches[0][0];
                    $row['cl2'] = '';
                    $row['cl3'] = '';
                    $row['len'] = '';
                    $row['mim'] = $file_type;
                    $row['cid'] = '';
                    $row['lin'] = $matches[0][0];
                    $row['fav'] = 'f';
                    if ($title) $row['cl1'] = $title;
                    $title = null;
                    $tracks[] = $row;
                } elseif (preg_match('/^[^"<>|:]*$/',$line)) {
                    $path = explode('/', $streamfile[0]->getInternalPath());
                    array_shift($path);
                    array_pop($path);
                    array_push($path, $line);
                    $path = implode('/', $path);
                    $x++;

                    try {
                        $fileId = $this->rootFolder->getUserFolder($this->userId)->get($path)->getId();
                        $track = $this->DBController->getTrackInfo(null,$fileId);

                        $row = array();
                        $row['id'] = $track['id'];
                        $row['fid'] = $track['file_id'];
                        $row['cl1'] = $track['Title'];
                        $row['cl2'] = $track['Artist'];
                        $row['cl3'] = $track['Album'];
                        $row['len'] = $track['Length'];
                        $row['mim'] = $track['MIME type'];
                        $row['cid'] = '';
                        $row['lin'] = rawurlencode($path);
                        $row['fav'] = $track['fav'];
                        if ($title) $row['cl1'] = $title;
                        $tracks[] = $row;
                        $title = null;
                    } catch (NotFoundException $e) {
                        //File is not known in the filecache and will be ignored;
                    }
                }
            }
        }
        return $tracks;
    }

    /**
     * Get playlist dependend headers
     *
     * @param string $category
     * @return array
     */
    private function getHeadersforCatagory($category)
    {
        if ($category === 'Artist') {
            return ['col1' => $this->l10n->t('Title'), 'col2' => $this->l10n->t('Album'), 'col3' => $this->l10n->t('Year'), 'col4' => $this->l10n->t('Length')];
        } elseif ($category === 'Album') {
            return ['col1' => $this->l10n->t('Title'), 'col2' => $this->l10n->t('Artist'), 'col3' => $this->l10n->t('Disc') . '-' . $this->l10n->t('Track'), 'col4' => $this->l10n->t('Length')];
        } elseif ($category === 'x_Stream') { //temporary disabled; need to separate streams and playlists
            return ['col1' => $this->l10n->t('URL'), 'col2' => $this->l10n->t(''), 'col3' => $this->l10n->t(''), 'col4' => $this->l10n->t('')];
        } else {
            return ['col1' => $this->l10n->t('Title'), 'col2' => $this->l10n->t('Artist'), 'col3' => $this->l10n->t('Album'), 'col4' => $this->l10n->t('Length')];
        }
    }

    /**
     * Count the number of albums within the artist selection
     *
     * @param string $category
     * @param string $categoryId
     * @return integer
     */
    private function getAlbumCountForCategory($category, $categoryId)
    {
        $SQL = null;
        if ($category === 'Artist') {
            $SQL = "SELECT  COUNT(DISTINCT `AT`.`album_id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
			 		WHERE  `AT`.`artist_id` = ? 
			 		AND `AT`.`user_id` = ?
			 		";
        }

        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($categoryId, $this->userId));
        $results = $stmt->fetch();
        return $results['count'];
    }

}
