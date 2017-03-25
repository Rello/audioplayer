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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IDb;
use OCP\Share\IManager;
use \OCA\audioplayer\Http\ImageResponse;

/**
 * Controller class for main page.
 */
class MusicController extends Controller {
	
	private $userId;
	private $l10n;
	private static $sortType='album';
	private $db;
	/** @var IManager */
	private $shareManager;
	
	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDb $db,
			IManager $shareManager
		) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->shareManager = $shareManager;
	}
	/**
	*@PublicPage
	 * @NoCSRFRequired
	 * 
	 */
	public function getPublicAudioStream(){
		$pToken  = $this->params('token');	
		$file = $this->params('file');
		if (!empty($pToken)) {
			$linkItem = \OCP\Share::getShareByToken($pToken);
			if (!(is_array($linkItem) && isset($linkItem['uid_owner']))) {
				exit;
			}
			// seems to be a valid share
			$rootLinkItem = \OCP\Share::resolveReShare($linkItem);
			$user = $rootLinkItem['uid_owner'];
		   
			// Setup filesystem
			\OC\Files\Filesystem::init($user, '/' . $user . '/files');
			$startPath = \OC\Files\Filesystem::getPath($linkItem['file_source']) ;
		  	if((string)$linkItem['item_type'] === 'file'){
				$filenameAudio=$startPath;
			}else{
				$filenameAudio=$startPath.'/'.rawurldecode($file);
			}
			
			\OC::$server->getSession()->close();
			
			$stream = new \OCA\audioplayer\AudioStream($filenameAudio,$user);
			$stream -> start();
		} 
	}

	/**
	*@PublicPage
	 * @NoCSRFRequired
	 * 
	 */
	public function getPublicAudioInfo($file){
		$pToken  = $this->params('token');	
		if (!empty($pToken)) {
			$share = $this->shareManager->getShareByToken($pToken);
			$fileid = $share->getNodeId();
			$fileowner = $share->getShareOwner();

			\OCP\Util::writeLog('audioplayer', 'fileid: '.$fileid, \OCP\Util::DEBUG);
			\OCP\Util::writeLog('audioplayer', 'fileowner: '.$fileowner, \OCP\Util::DEBUG);

			$SQL="SELECT `AT`.`title`,`AG`.`name` AS `genre`,`AB`.`name` AS `album`,`AT`.`artist_id`,`AT`.`length`,`AT`.`bitrate`,`AT`.`year`,`AA`.`name` AS `artist`, ROUND((`AT`.`bitrate` / 1000 ),0) AS `bitrate` 
						FROM `*PREFIX*audioplayer_tracks` `AT`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
						LEFT JOIN `*PREFIX*audioplayer_genre` `AG` ON `AT`.`genre_id` = `AG`.`id`
						LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 			WHERE  `AT`.`user_id` = ? AND `AT`.`file_id` = ?
			 			ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC
			 			";
				 
			$stmt = $this->db->prepareQuery($SQL);
			$result = $stmt->execute(array($fileowner, $fileid));
			$row = $result->fetchRow();
						
			if($row['year'] === '0') $row['year'] = $this->l10n->t('Unknown');

			if($row['title']){
				$result=[
					'status' => 'success',
					'data' => $row];
			}else{
				$result=[
				'status' => 'error',
				'data' => 'nodata'];
			}
			$response = new JSONResponse();
			$response -> setData($result);
			return $response;

			\OC::$server->getSession()->close();
		} 
	}

	/**
	*@NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * 
	 */
	public function getAudioStream(){
		
		$pFile = $this->params('file');
		$filename = rawurldecode($pFile);
		$user = $this->userId;
		\OC::$server->getSession()->close();
		$stream = new \OCA\audioplayer\AudioStream($filename,$user);
		$stream -> start();
	}


	/**
	 * @NoAdminRequired
	 * 
	 */
	public function getMusic(){
			
		$aSongs = $this->loadSongs();
    	$aAlbums = $this->loadAlbums();
		\OC::$server->getSession()->close();
		
		if(is_array($aAlbums)){
			$result=[
					'status' => 'success',
					'data' => ['albums'=>$aAlbums,'songs'=>$aSongs]
				];
		}else{
			$result=[
					'status' => 'success',
					'data' =>'nodata'
				];
		}
		
		$response = new JSONResponse();
		$response -> setData($result);
		return $response;
//		ob_start('ob_gzhandler');
//		header('Content-type: application/json');
//		echo json_encode($result);
//		ob_end_flush();
//		die();
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function loadAlbums(){
			
		$SQL="SELECT  `AA`.`id`,`AA`.`name`,`AA`.`cover`,`AA`.`artist_id`
						FROM `*PREFIX*audioplayer_albums` `AA`
			 			WHERE  `AA`.`user_id` = ?
			 			ORDER BY `AA`.`name` ASC
			 			";
						
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		$aAlbums=array();
		while( $row = $result->fetchRow()) {
			$row['artist'] = $this->loadArtistsToAlbum($row['id'],$row['artist_id']);	

			if ($row['name'] === $this->l10n->t('Unknown') AND $row['artist'] === $this->l10n->t('Various Artists')) {
				$row['cover'] = 'true';	
			}elseif ($row['cover'] === null){
				$row['cover'] = '';
			}else{
				$row['cover'] = 'true';	
			}
 			array_splice($row, 3, 1);
			$aAlbums[$row['id']] = $row;
		}
		if(empty($aAlbums)){
  			return false;
 		}else{
			$aAlbums = $this->sortArrayByFields($aAlbums); 
			return $aAlbums;
		}
	}
	
	private function loadArtistsToAlbum($iAlbumId, $ARtistID){
		# load albumartist if available
		# if no albumartist, we will load all artists from the tracks
		# if all the same - display it as album artist
		# if different track-artists, display "various"
		if ((int)$ARtistID !== 0){
			$stmt = $this->db->prepareQuery( 'SELECT `name`  FROM `*PREFIX*audioplayer_artists` WHERE  `id` = ?' );
			$result = $stmt->execute(array($ARtistID));
			$row = $result->fetchRow();
			return $row['name'];
		} else {
    		$stmt = $this->db->prepareQuery( 'SELECT distinct(`artist_id`) FROM `*PREFIX*audioplayer_tracks` WHERE  `album_id` = ?' );
			$result = $stmt->execute(array($iAlbumId));
			$TArtist = $result->fetchRow();
			$rowCount = $result->rowCount();

			if($rowCount === 1){
				$stmt = $this->db->prepareQuery( 'SELECT `name`  FROM `*PREFIX*audioplayer_artists` WHERE  `id` = ?' );
				$result = $stmt->execute(array($TArtist['artist_id']));
				$row = $result->fetchRow();
				return $row['name'];
			}else{
				return (string) $this->l10n->t('Various Artists');
			}
		}
    }
	
	public function loadSongs(){
//		$SQL="SELECT  `AT`.`id`,`AT`.`title`,`AT`.`number`,`AT`.`album_id`,`AT`.`artist_id`,`AT`.`length`,`AT`.`file_id`,`AT`.`bitrate`,`AT`.`mimetype`,`AA`.`name` AS `artistname` FROM `*PREFIX*audioplayer_tracks` `AT`
		$SQL="SELECT  `AT`.`id`,`AT`.`title`,`AT`.`number`,`AT`.`album_id`,`AT`.`length`,`AT`.`file_id`,`AT`.`mimetype`,`AA`.`name` AS `artistname` FROM `*PREFIX*audioplayer_tracks` `AT`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
			 			WHERE  `AT`.`user_id` = ?
			 			ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC
			 			";
			
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		$aSongs=array();
		
		while( $row = $result->fetchRow()) {
			$file_not_found = false;
			try {
				$path = \OC\Files\Filesystem::getPath($row['file_id']);
			} catch (\Exception $e) {
				$file_not_found = true;
       		}
			
			if($file_not_found === false){
				# Beta for Streaming testing; should not have any impact as this filetype is not used
				if ($row['mimetype'] === 'audio/x-mpegurl') {
					$row['link'] = rawurlencode($row['title']);
				}else{	
					$row['link'] = '?file='.rawurlencode($path);
				}	
				$aSongs[$row['album_id']][] = $row;
			}else{
				$this->deleteFromDB($row['id'],$row['album_id']);
			}	
		}
		if(empty($aSongs)){
  			return false;
 		}else{
 			return $aSongs;
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function searchProperties($searchquery){
		$searchresult = array();
		$SQL = "SELECT `id`,`name` FROM `*PREFIX*audioplayer_albums` WHERE (LOWER(`name`) LIKE LOWER(?)) AND `user_id` = ?";
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array('%'.addslashes($searchquery).'%', $this->userId));
		if (!is_null($result)) {
			while( $row = $result->fetchRow()) {			
				$searchresult[] = [
					'id' => 'Album-'.$row['id'],
					'name' => $this->l10n->t('Album').': '.$row['name'],
				];
			}
		}

		$SQL = "SELECT `id`,`name` FROM `*PREFIX*audioplayer_artists` WHERE (LOWER(`name`) LIKE LOWER(?)) AND `user_id` = ?";
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array('%'.addslashes($searchquery).'%', $this->userId));
		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				$searchresult[] = [
					'id' => 'Artist-'.$row['id'],
					'name' => $this->l10n->t('Artist').': '.$row['name'],
				];
			}
		}
		
		$SQL = "SELECT `album_id`, `title` FROM `*PREFIX*audioplayer_tracks` WHERE (LOWER(`title`) LIKE LOWER(?)) AND `user_id` = ?";
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array('%'.addslashes($searchquery).'%', $this->userId));
		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				$searchresult[] = [
					'id' => 'Album-'.$row['album_id'],
					'name' => $this->l10n->t('Title').': '.$row['title'],
				];
			}
		}
		
		if(is_array($searchresult)) {
			return $searchresult;
		} else {
			return array();
		}
	}

	/**
	* @NoAdminRequired
	* 
	*/
	public function resetMediaLibrary($userId = null, $output = null){
	
		if($userId !== null) {
			$this->occ_job = true;
			$this->userId = $userId;
		} else {
			$this->occ_job = false;
		}
			
		$stmt = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ?' );
		$stmt->execute(array($this->userId));
		
		$stmt2 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_artists` WHERE `user_id` = ?' );
		$stmt2->execute(array($this->userId));	
		
		$stmt2 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_genre` WHERE `user_id` = ?' );
		$stmt2->execute(array($this->userId));
		
		$SQL1="SELECT `id` FROM `*PREFIX*audioplayer_albums` WHERE `user_id` = ?";
		$stmt5 = $this->db->prepareQuery($SQL1);
		$result5 = $stmt5->execute(array($this->userId));
		if(!is_null($result5)) {
			while($row = $result5->fetchRow()) {
				$stmt6 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_album_artists` WHERE `album_id` = ?' );
				$stmt6->execute(array($row['id']));
			}
		}
		
		$stmt2 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_albums` WHERE `user_id` = ?' );
		$stmt2->execute(array($this->userId));
		
		$SQL="SELECT `id` FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ?";
		$stmt3 = $this->db->prepareQuery($SQL);
		$result = $stmt3->execute(array($this->userId));
		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				$stmt4 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` WHERE `playlist_id` = ?' );
				$stmt4->execute(array($row['id']));
			}
		}

		$stmt2 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ?' );
		$stmt2->execute(array($this->userId));

		$result=[
					'status' => 'success',
					'msg' =>'all good'
				];
		
		// applies if scanner is not started via occ
		if(!$this->occ_job) { 
			$response = new JSONResponse();
			$response -> setData($result);
			return $response;
		} else {
			$output->writeln("Reset finished");
		}
	}
	
	private function deleteFromDB($Id,$iAlbumId){
		
		$stmtCountAlbum = $this->db->prepareQuery( 'SELECT COUNT(`album_id`) AS `ALBUMCOUNT`  FROM `*PREFIX*audioplayer_tracks` WHERE `album_id` = ? ' );
		$resultAlbumCount = $stmtCountAlbum->execute(array($iAlbumId));
		$rowAlbum = $resultAlbumCount->fetchRow();
		if((int)$rowAlbum['ALBUMCOUNT'] === 1){
			$stmt2 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_albums` WHERE `id` = ? AND `user_id` = ?' );
			$stmt2->execute(array($iAlbumId, $this->userId));
		}
		
		$stmt = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ? AND `id` = ?' );
		$stmt->execute(array($this->userId, $Id));
		
	}
	
	private function sortArrayByFields($data)
	{
		foreach ($data as $key => $row) {
    		$first[$key] = $row['artist'];
    		$second[$key] = $row['name'];
		}
		array_multisort($first, SORT_ASC, SORT_STRING|SORT_FLAG_CASE,$second , SORT_ASC, SORT_STRING|SORT_FLAG_CASE, $data);
		return $data;
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function getCover(){
		$album=$this->params('album');
		$cover = '';
		
		$SQL="SELECT  `cover`, `name`, `artist_id`
				FROM `*PREFIX*audioplayer_albums` 
			 	WHERE  `user_id` = ? AND `id` = ? 
			 	";
			
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId, $album));
		$aAlbums=array();
		while( $row = $result->fetchRow()) {
			$artist = $this->loadArtistsToAlbum($album,$row['artist_id']);
			$cover = $row['cover'];	
			if ($row['name'] === $this->l10n->t('Unknown') AND $artist === $this->l10n->t('Various Artists')){
				$cover = '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';
			}
		}
		
		//$etag = md5($cover);		
		//if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        //	if (str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag) {
        //    	header('HTTP/1.1 304 Not Modified');
        //    	//$response->setStatus(Http::STATUS_NOT_MODIFIED);
        //    	exit();
       	//	}
    	//}
		$imageData = base64_decode($cover);
		return new ImageResponse(array('mimetype' => 'image/jpg','content' => $imageData));
	}
}
