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
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

/**
 * Controller class for main page.
 */
class MusicController extends Controller
{

    private $userId;
    private $l10n;
    private $db;
    private $shareManager;
    private $logger;
    private $DBController;
    private $rootFolder;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db,
        IManager $shareManager,
        LoggerInterface $logger,
        DbController $DBController,
        IRootFolder $rootFolder
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->shareManager = $shareManager;
        $this->logger = $logger;
        $this->DBController = $DBController;
        $this->rootFolder = $rootFolder;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @param $token
     * @return JSONResponse
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Share\Exceptions\ShareNotFound
     */
    public function getPublicAudioInfo($token)
    {
        if (!empty($token)) {
            $share = $this->shareManager->getShareByToken($token);
            $fileid = $share->getNodeId();
            $fileowner = $share->getShareOwner();

            //\OCP\Util::writeLog('audioplayer', 'fileid: '.$fileid, \OCP\Util::DEBUG);
            //\OCP\Util::writeLog('audioplayer', 'fileowner: '.$fileowner, \OCP\Util::DEBUG);

            $SQL = "SELECT `AT`.`title`,`AG`.`name` AS `genre`,`AB`.`name` AS `album`,`AT`.`artist_id`,
					`AT`.`length`,`AT`.`bitrate`,`AT`.`year`,`AA`.`name` AS `artist`,
					ROUND((`AT`.`bitrate` / 1000 ),0) AS `bitrate`, `AT`.`disc`,
					`AT`.`number`, `AT`.`composer`, `AT`.`subtitle`, `AT`.`mimetype`, `AB`.`id` AS `album_id` , `AB`.`artist_id` AS `albumArtist_id`
					, `AT`.`isrc` , `AT`.`copyright`
						FROM `*PREFIX*audioplayer_tracks` `AT`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
						LEFT JOIN `*PREFIX*audioplayer_genre` `AG` ON `AT`.`genre_id` = `AG`.`id`
						LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 			WHERE  `AT`.`user_id` = ? AND `AT`.`file_id` = ?
			 			ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC
			 			";

            $stmt = $this->db->prepare($SQL);
            $stmt->execute(array($fileowner, $fileid));
            $row = $stmt->fetch();

            if (isset($row['title'])) {
                $artist = $this->DBController->loadArtistsToAlbum($row['album_id'], $row['albumArtist_id']);
                $row['albumartist'] = $artist;

                if ($row['year'] === '0') $row['year'] = $this->l10n->t('Unknown');

                $result = [
                    'status' => 'success',
                    'data' => $row];
            } else {
                $result = [
                    'status' => 'error',
                    'data' => 'nodata'];
            }
            return new JSONResponse($result);
        }
    }

    /**
     * Stream files in OCP withing link-shared folder
     * @PublicPage
     * @NoCSRFRequired
     * @param $token
     * @param $file
     * @throws \OCP\Files\NotFoundException
     */
    public function getPublicAudioStream($token, $file)
    {
        if (!empty($token)) {
            $share = $this->shareManager->getShareByToken($token);
            $fileowner = $share->getShareOwner();

            // Setup filesystem
            $nodes = $this->rootFolder->getUserFolder($fileowner)->getById($share->getNodeId());
            $pfile = array_shift($nodes);
            $path = $pfile->getPath();
            $segments = explode('/', trim($path, '/'), 3);
            $startPath = $segments[2];

            $filenameAudio = $startPath . '/' . rawurldecode($file);

            \OC::$server->getSession()->close();
            $stream = new \OCA\audioplayer\Http\AudioStream($filenameAudio, $fileowner);
            $stream->start();
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param $file
     * @param $t
     */
    public function getAudioStream($file, $t)
    {

        if ($t) {
            $fileId = $this->DBController->getFileId($t);
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
            $file = array_shift($nodes);
            $path = $file->getPath();
            $segments = explode('/', trim($path, '/'), 3);
            $filename = $segments[2];
        } else {
            $filename = rawurldecode($file);
        }

        $user = $this->userId;
        \OC::$server->getSession()->close();
        $stream = new \OCA\audioplayer\Http\AudioStream($filename, $user);
        $stream->start();
    }
}
