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

/**
 * Controller class for Sidebar.
 */
class SidebarController extends Controller {
	
	private $userId;
    private $db;
    private $l10n;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId,
            IL10N $l10n,
            IDBConnection $db
			) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
        $this->l10n = $l10n;
		$this->userId = $userId;
        $this->db = $db;
	}

    /**
     * @NoAdminRequired
     * @param $trackid
     * @return JSONResponse
     */
	public function getAudioInfo($trackid) {
        $SQL = "SELECT `AT`.`title` AS `Title`,`AG`.`name` AS `Genre`,`AB`.`name` AS `Album`,
					`AT`.`length` AS `Length`,`AT`.`year` AS `Year`,`AA`.`name` AS `Artist`,
					ROUND((`AT`.`bitrate` / 1000 ),0) AS `Bitrate`, `AT`.`disc` AS `Disc`,
					`AT`.`number` AS `Track`, `AT`.`composer` AS `Composer`, `AT`.`subtitle` AS `Subtitle`, `AT`.`mimetype` AS `MIME type`
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

        if ($row['Year'] === '0') $row['Year'] = $this->l10n->t('Unknown');

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
        $response -> setData($result);
        return $response;
	}
}
