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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\InvalidPathException;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDBConnection;
use OCP\ITagManager;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use \OCP\Files\NotFoundException;
use OCA\audioplayer\Categories\Tag;

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
    private $categoriesTag;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db,
        ITagManager $tagManager,
        IRootFolder $rootFolder,
        LoggerInterface $logger,
        DbController $DBController,
        Tag $categoriesTag
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
        $this->categoriesTag = $categoriesTag;
    }

    /**
     * Get the items for the selected category
     *
     * @NoAdminRequired
     * @param $category
     * @return JSONResponse
     */
    public function getCategoryItems($category)
    {
        $SQL = null;
        $aPlaylists = array();
        if ($category === 'Artist') {
            $SQL = 'SELECT  DISTINCT(`AT`.`artist_id`) AS `id`, `AA`.`name`, LOWER(`AA`.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_tracks` `AT`
						JOIN `*PREFIX*audioplayer_artists` `AA`
						ON `AA`.`id` = `AT`.`artist_id`
			 			WHERE  `AT`.`user_id` = ?
			 			ORDER BY LOWER(`AA`.`name`) ASC
			 			';
        } elseif ($category === 'Genre') {
            $SQL = 'SELECT  `id`, `name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_genre`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			';
        } elseif ($category === 'Year') {
            $SQL = 'SELECT DISTINCT(`year`) AS `id` ,`year` AS `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			ORDER BY `id` ASC
			 			';
        } elseif ($category === 'Title') {
            $SQL = "SELECT distinct('0') as `id` ,'" . $this->l10n->t('All Titles') . "' as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			";
        } elseif ($category === 'Playlist') {
            $aPlaylists[] = array('id' => 'X1', 'name' => $this->l10n->t('Favorites'));
            $aPlaylists[] = array('id' => 'X2', 'name' => $this->l10n->t('Recently Added'));
            $aPlaylists[] = array('id' => 'X3', 'name' => $this->l10n->t('Recently Played'));
            $aPlaylists[] = array('id' => 'X4', 'name' => $this->l10n->t('Most Played'));
            //https://github.com/Rello/audioplayer/issues/442
            $aPlaylists[] = array('id' => 'X5', 'name' => $this->l10n->t('50 Random Tracks'));
            $aPlaylists[] = array('id' => '', 'name' => '');

            // Stream files are shown directly
            $SQL = 'SELECT  `file_id` AS `id`, `title` AS `name`, LOWER(`title`) AS `lower` 
						FROM `*PREFIX*audioplayer_streams`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`title`) ASC
			 			';
            $stmt = $this->db->prepare($SQL);
            $stmt->execute(array($this->userId));
            $results = $stmt->fetchAll();
            foreach ($results as $row) {
                array_splice($row, 2, 1);
                $row['id'] = 'S' . $row['id'];
                $aPlaylists[] = $row;
            }
            $aPlaylists[] = array('id' => '', 'name' => '');

            // regular playlists are selected
            $SQL = 'SELECT  `id`,`name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_playlists`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			';

        } elseif ($category === 'Folder') {
            $SQL = 'SELECT  DISTINCT(`FC`.`fileid`) AS `id`, `FC`.`name`, LOWER(`FC`.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_tracks` `AT`
						JOIN `*PREFIX*filecache` `FC`
						ON `FC`.`fileid` = `AT`.`folder_id`
			 			WHERE `AT`.`user_id` = ?
			 			ORDER BY LOWER(`FC`.`name`) ASC
			 			';
        } elseif ($category === 'Album') {
            $SQL = 'SELECT  `AB`.`id` , `AB`.`name`, LOWER(`AB`.`name`) AS `lower`
						FROM `*PREFIX*audioplayer_albums` `AB`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` 
						ON `AB`.`artist_id` = `AA`.`id`
			 			WHERE `AB`.`user_id` = ?
			 			ORDER BY LOWER(`AB`.`name`) ASC
			 			';
        } elseif ($category === 'Album Artist') {
            $SQL = 'SELECT  DISTINCT(`AB`.`artist_id`) AS `id`, `AA`.`name`, LOWER(`AA`.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_albums` `AB`
						JOIN `*PREFIX*audioplayer_artists` `AA`
						ON `AB`.`artist_id` = `AA`.`id`
			 			WHERE `AB`.`user_id` = ?
			 			ORDER BY LOWER(`AA`.`name`) ASC
			 			';
        } elseif ($category === 'Tags') {
            $aPlaylists = $this->categoriesTag->getCategoryItems();
        }

        if (isset($SQL)) {
            $stmt = $this->db->prepare($SQL);
            $stmt->execute(array($this->userId));
            $results = $stmt->fetchAll();
            foreach ($results as $row) {
                array_splice($row, 2, 1);
                if ($row['name'] === '0' OR $row['name'] === '') $row['name'] = $this->l10n->t('Unknown');
                $row['cnt'] = $this->getTrackCount($category, $row['id']);
                $aPlaylists[] = $row;
            }
        }

        $result = empty($aPlaylists) ? [
            'status' => 'nodata'
        ] : [
            'status' => 'success',
            'data' => $aPlaylists
        ];
        return new JSONResponse($result);
    }

    /**
     * Get the covers for the "Album Covers" view
     *
     * @NoAdminRequired
     * @param $category
     * @param $categoryId
     * @return JSONResponse
     */
    public function getCategoryItemCovers($category, $categoryId)
    {
        $whereMatching = array('Artist' => '`AT`.`artist_id`', 'Genre' => '`AT`.`genre_id`', 'Album' => '`AB`.`id`', 'Album Artist' => '`AB`.`artist_id`', 'Year' => '`AT`.`year`', 'Folder' => '`AT`.`folder_id`', 'Tags' => '`AT`.`file_id`');

        $aPlaylists = array();
        $SQL = 'SELECT  `AB`.`id` , `AB`.`name`, LOWER(`AB`.`name`) AS `lower` , `AA`.`id` AS `art`, (CASE  WHEN `AB`.`cover` IS NOT NULL THEN `AB`.`id` ELSE NULL END) AS `cid`';
        $SQL .= ' FROM `*PREFIX*audioplayer_tracks` `AT`';
        $SQL .= ' LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AB`.`id` = `AT`.`album_id`';
        $SQL .= ' LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AA`.`id` = `AB`.`artist_id`';
        $SQL .= ' WHERE `AT`.`user_id` = ? ';
        if ($categoryId) $SQL .= 'AND ' . $whereMatching[$category] . '= ?';
        $SQL .= ' GROUP BY `AB`.`id`, `AA`.`id`, `AB`.`name` ORDER BY LOWER(`AB`.`name`) ASC';

        if ($category === 'Tags') {
            $results = $this->categoriesTag->getCategoryItemCovers($categoryId);
            $SQL = null;
        }

        if (isset($SQL)) {
            $stmt = $this->db->prepare($SQL);
            if ($categoryId) {
                $stmt->execute(array($this->userId, $categoryId));
            } else {
                $stmt->execute(array($this->userId));
            }
            $results = $stmt->fetchAll();
        }

        foreach ($results as $row) {
            $row['art'] = $this->DBController->loadArtistsToAlbum($row['id'], $row['art']);
            array_splice($row, 2, 1);
            if ($row['name'] === '0' OR $row['name'] === '') $row['name'] = $this->l10n->t('Unknown');
            $aPlaylists[] = $row;
        }

        $result = empty($aPlaylists) ? [
            'status' => 'nodata'
        ] : [
            'status' => 'success',
            'data' => $aPlaylists
        ];
        return new JSONResponse($result);
    }

    /**
     * Get the number of tracks for a category item
     *
     * @param string $category
     * @param integer $categoryId
     * @return integer
     */
    private function getTrackCount($category, $categoryId)
    {
        $SQL = array();
        $SQL['Artist'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_tracks` `AT` LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id` WHERE  `AT`.`artist_id` = ? AND `AT`.`user_id` = ?';
        $SQL['Genre'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_tracks` `AT` WHERE  `AT`.`genre_id` = ? AND `AT`.`user_id` = ?';
        $SQL['Year'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_tracks` `AT` WHERE  `AT`.`year` = ? AND `AT`.`user_id` = ?';
        $SQL['Title'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_tracks` `AT` WHERE  `AT`.`id` > ? AND `AT`.`user_id` = ?';
        $SQL['Playlist'] = 'SELECT COUNT(`AP`.`track_id`) AS `count` FROM `*PREFIX*audioplayer_playlist_tracks` `AP` WHERE  `AP`.`playlist_id` = ?';
        $SQL['Folder'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_tracks` `AT` WHERE  `AT`.`folder_id` = ? AND `AT`.`user_id` = ?';
        $SQL['Album'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_tracks` `AT` WHERE  `AT`.`album_id` = ? AND `AT`.`user_id` = ?';
        $SQL['Album Artist'] = 'SELECT COUNT(`AT`.`id`) AS `count` FROM `*PREFIX*audioplayer_albums` `AB` JOIN `*PREFIX*audioplayer_tracks` `AT` ON `AB`.`id` = `AT`.`album_id` WHERE  `AB`.`artist_id` = ? AND `AB`.`user_id` = ?';

        $stmt = $this->db->prepare($SQL[$category]);
        if ($category === 'Playlist') {
            $stmt->execute(array($categoryId));
        } else {
            $stmt->execute(array($categoryId, $this->userId));
        }
        $results = $stmt->fetch();
        return $results['count'];
    }

        /**
     * get the tracks for a selected category or album
     *
     * @NoAdminRequired
     * @param string $category
     * @param string $categoryId
     * @return JSONResponse
     * @throws InvalidPathException
     * @throws NotFoundException
     */
    public function getTracks($category, $categoryId)
    {
        if ($categoryId[0] === 'S') $category = 'Stream';
        if ($categoryId[0] === 'P') $category = 'Playlist';
        $items = $this->getTracksDetails($category, $categoryId);
        $headers = $this->getListViewHeaders($category);

        $result = !empty($items) ? [
            'status' => 'success',
            'data' => $items,
            'header' => $headers,
        ] : [
            'status' => 'nodata',
        ];
        return new JSONResponse($result);
    }

    /**
     * Get the tracks for a selected category or album
     *
     * @param string $category
     * @param string $categoryId
     * @return array
     * @throws InvalidPathException
     */
    private function getTracksDetails($category, $categoryId)
    {
        $SQL = null;
        $favorite = false;
        $aTracks = array();
        $SQL_select = 'SELECT  `AT`.`id`, `AT`.`title`  AS `cl1`, `AA`.`name` AS `cl2`, `AB`.`name` AS `cl3`, `AT`.`length` AS `len`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, (CASE  WHEN `AB`.`cover` IS NOT NULL THEN `AB`.`id` ELSE NULL END) AS `cid`, LOWER(`AB`.`name`) AS `lower`';
        $SQL_from = ' FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`';
        $SQL_order = ' ORDER BY LOWER(`AB`.`name`) ASC, `AT`.`disc` ASC, `AT`.`number` ASC';

        if ($category === 'Artist') {
            $SQL_select = 'SELECT  `AT`.`id`, `AT`.`title`  AS `cl1`, `AB`.`name` AS `cl2`, `AT`.`year` AS `cl3`, `AT`.`length` AS `len`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, (CASE  WHEN `AB`.`cover` IS NOT NULL THEN `AB`.`id` ELSE NULL END) AS `cid`, LOWER(`AB`.`name`) AS `lower`';
            $SQL = $SQL_select . $SQL_from .
                'WHERE  `AT`.`artist_id` = ? AND `AT`.`user_id` = ?' .
                $SQL_order;
        } elseif ($category === 'Genre') {
            $SQL = $SQL_select . $SQL_from .
                'WHERE `AT`.`genre_id` = ? AND `AT`.`user_id` = ?' .
                $SQL_order;
        } elseif ($category === 'Year') {
            $SQL = $SQL_select . $SQL_from .
                'WHERE `AT`.`year` = ? AND `AT`.`user_id` = ?' .
                $SQL_order;
        } elseif ($category === 'Title') {
            $SQL = $SQL_select . $SQL_from .
                'WHERE `AT`.`id` > ? AND `AT`.`user_id` = ?' .
                $SQL_order;
        } elseif ($category === 'Playlist' AND $categoryId === 'X1') { // Favorites
            $SQL = 'SELECT  `AT`.`id` , `AT`.`title`  AS `cl1`,`AA`.`name` AS `cl2`, `AB`.`name` AS `cl3`,`AT`.`length` AS `len`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, (CASE  WHEN `AB`.`cover` IS NOT NULL THEN `AB`.`id` ELSE NULL END) AS `cid`, LOWER(`AT`.`title`) AS `lower`' .
                $SQL_from .
                'WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ?' .
                ' ORDER BY LOWER(`AT`.`title`) ASC';
            $categoryId = 0; //overwrite to integer for PostgreSQL
            $favorite = true;
        } elseif ($category === 'Playlist' AND $categoryId === 'X2') { // Recently Added
            $SQL = $SQL_select . $SQL_from .
                'WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AT`.`file_id` DESC
			 		Limit 100';
            $categoryId = 0;
        } elseif ($category === 'Playlist' AND $categoryId === 'X3') { // Recently Played
            $SQL = $SQL_select . $SQL_from .
                'LEFT JOIN `*PREFIX*audioplayer_stats` `AS` ON `AT`.`id` = `AS`.`track_id`
			 		WHERE `AS`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AS`.`playtime` DESC
			 		Limit 50';
            $categoryId = 0;
        } elseif ($category === 'Playlist' AND $categoryId === 'X4') { // Most Played
            $SQL = $SQL_select . $SQL_from .
                'LEFT JOIN `*PREFIX*audioplayer_stats` `AS` ON `AT`.`id` = `AS`.`track_id`
			 		WHERE `AS`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AS`.`playcount` DESC
			 		Limit 25';
            $categoryId = 0;
        } elseif ($category === 'Playlist' AND $categoryId === "X5") { // 50 Random Tracks
            if ($this->db->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform ||
                $this->db->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL94Platform ||
                $this->db->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform) {
                $order = 'ORDER BY random() Limit 50';
            } else {
                $order = 'ORDER BY RAND() Limit 50';
            }
            $SQL = $SQL_select . $SQL_from .
                "WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ? " . $order;
            $categoryId = 0;
        } elseif ($category === 'Playlist') {
            $SQL = $SQL_select . ' , `AP`.`sortorder`' .
                'FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
					LEFT JOIN `*PREFIX*audioplayer_tracks` `AT` ON `AP`.`track_id` = `AT`.`id`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AP`.`playlist_id` = ?
					AND `AT`.`user_id` = ? 
			 		ORDER BY `AP`.`sortorder` ASC';
        } elseif ($category === 'Stream') {
            $aTracks = $this->StreamParser(intval(substr($categoryId, 1)));
            return $aTracks;
        } elseif ($category === 'Folder') {
            $SQL = $SQL_select . $SQL_from .
                'WHERE `AT`.`folder_id` = ? AND `AT`.`user_id` = ?' .
                $SQL_order;
        } elseif ($category === 'Album') {
            $SQL_select = 'SELECT  `AT`.`id`, `AT`.`title` AS `cl1`, `AA`.`name` AS `cl2`, `AT`.`length` AS `len`, `AT`.`disc` AS `dsc`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, (CASE  WHEN `AB`.`cover` IS NOT NULL THEN `AB`.`id` ELSE NULL END) AS `cid`, LOWER(`AT`.`title`) AS `lower`,`AT`.`number`  AS `num`';
            $SQL = $SQL_select . $SQL_from .
                'WHERE `AB`.`id` = ? AND `AB`.`user_id` = ?' .
                ' ORDER BY `AT`.`disc` ASC, `AT`.`number` ASC';
        } elseif ($category === 'Album Artist') {
            $SQL = $SQL_select . $SQL_from .
                'WHERE  `AB`.`artist_id` = ? AND `AT`.`user_id` = ?' .
                $SQL_order;
        } elseif ($category === 'Tags') {
            $results = $this->categoriesTag->getTracksDetails($categoryId);
            $SQL = null;
        }

        if (isset($SQL)) {
            $stmt = $this->db->prepare($SQL);
            $stmt->execute(array($categoryId, $this->userId));
            $results = $stmt->fetchAll();
        }

        $this->tagger = $this->tagManager->load('files');
        $favorites = $this->tagger->getFavorites();


        if ($category === 'Album') {
            $discNum = array_sum(array_column($results, 'dsc')) / count($results);
        }

        foreach ($results as $row) {
            if ($category === 'Album') {
                if ($row['dsc'] !== $discNum) {
                    $row['cl3'] = $row['dsc'] . '-' . $row['num'];
                } else {
                    $row['cl3'] = $row['num'];
                }
            }
            array_splice($row, 8, 1);
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($row['fid']);
            $file = array_shift($nodes);

            if ($file === null) {
                $this->logger->debug('removed/unshared file found => remove '.$row['fid'], array('app' => 'audioplayer'));
                $this->DBController->deleteFromDB($row['fid'], $this->userId);
                continue;
            }
            if (is_array($favorites) AND in_array($row['fid'], $favorites)) {
                $row['fav'] = 't';
            }

            if ($favorite AND is_array($favorites) AND !in_array($row['fid'], $favorites)) {
                //special handling for Favorites smart playlist;
                //do not display anything that is NOT a fav
            } else {
                array_splice($row, 5, 1);
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
     * @throws InvalidPathException
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
                    $row['cl1'] = $matches[0][0];
                    $row['cl2'] = '';
                    $row['cl3'] = '';
                    $row['len'] = '';
                    $row['mim'] = $file_type;
                    $row['cid'] = '';
                    $row['lin'] = $matches[0][0];
                    if ($title) $row['cl1'] = $title;
                    $tracks[] = $row;
                }
            }
        } else {
            // get the path of the playlist file as reference
            $playlistFilePath = explode('/', ltrim($streamfile[0]->getPath(), '/'));
            // remove leading username
            array_shift($playlistFilePath);
            // remove leading "files/"
            array_shift($playlistFilePath);
            // remove the filename itself
            array_pop($playlistFilePath);

            // read each line of the playlist
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $file_content) as $line) {
                $title = null;
                $artist = null;
                if (empty($line) || $line === '#EXTM3U') continue;
                if (substr($line, 0, 8) === '#EXTINF:') {
                    $extinf = explode(',', substr($line, 8));
                    $extNoDuration = $extinf[1];
                    $extinf = explode(' - ', $extNoDuration);
                    $title = $extinf[1];
                    $artist = $extinf[0];
                    $line = $extinf[2];
                }

                preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $line, $matches);
                if ($matches[0]) {
                    // playlist item is a web stream url
                    $x++;
                    $row = array();
                    $row['id'] = $fileId . $x;
                    $row['cl1'] = $matches[0][0];
                    $row['cl2'] = '';
                    $row['cl3'] = '';
                    $row['len'] = '';
                    $row['mim'] = $file_type;
                    $row['cid'] = '';
                    $row['lin'] = $matches[0][0];
                    if ($title) $row['cl1'] = $title;
                    if ($artist) $row['cl2'] = $artist;
                    $tracks[] = $row;
                } elseif (preg_match('/^[^"<>|:]*$/',$line)) {
                    // playlist item is an internal file
                    if ($line[0] === '/') {
                        // Absolut path
                        $path = $line;
                    } elseif (substr($line, 0, 3) === '../') {
                        // relative one level up => remove the parent folder of the playlist file
                        $path = $playlistFilePath;
                        do {
                            $line = substr($line, 3);
                            array_pop($path);
                        } while (substr($line, 0, 3) === '../');

                        array_push($path, $line);
                        $path = implode('/', $path);
                    } else {
                        // normal relative path
                        $path = $playlistFilePath;

                        array_push($path, $line);
                        $path = implode('/', $path);
                    }
                    $x++;
                    $this->logger->debug('Final path of playlist track: '.$path);

                    try {
                        $fileId = $this->rootFolder->getUserFolder($this->userId)->get($path)->getId();
                        $track = $this->DBController->getTrackInfo(null,$fileId);
                        if (!isset($track['id'])) continue;

                        $row = array();
                        $row['id'] = $track['id'];
                        $row['cl1'] = $track['Title'];
                        $row['cl2'] = $track['Artist'];
                        $row['cl3'] = $track['Album'];
                        $row['len'] = $track['Length'];
                        $row['mim'] = $track['MIME type'];
                        $row['cid'] = '';
                        $row['lin'] = $track['id'];
                        $row['fav'] = $track['fav'];
                        if ($title) $row['cl1'] = $title;
                        if ($artist) $row['cl2'] = $artist;
                        $tracks[] = $row;
                    } catch (NotFoundException $e) {
                        $this->logger->debug('Path is not a valid file: '.$path);
                        // File is not known in the filecache and will be ignored;
                    }
                }
            }
        }
        return $tracks;
    }

    /**
     * Get selection dependend headers for the list view
     *
     * @param string $category
     * @return array
     */
    private function getListViewHeaders($category)
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
}
