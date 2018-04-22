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
class MusicController extends Controller
{

    private $userId;
    private $l10n;
    private $db;
    private $shareManager;
    private $logger;
    private $DBController;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDbConnection $db,
        IManager $shareManager,
        ILogger $logger,
        DbController $DBController
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->shareManager = $shareManager;
        $this->logger = $logger;
        $this->DBController = $DBController;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
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

            $artist = $this->DBController->loadArtistsToAlbum($row['album_id'], $row['albumArtist_id']);
            $row['albumartist'] = $artist;

            if ($row['year'] === '0') $row['year'] = $this->l10n->t('Unknown');

            if ($row['title']) {
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

            \OC::$server->getSession()->close();
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getAudioStream($file)
    {
        $filename = rawurldecode($file);
        $user = $this->userId;
        \OC::$server->getSession()->close();
        $stream = new \OCA\audioplayer\Http\AudioStream($filename, $user);
        $stream->start();
    }

}
