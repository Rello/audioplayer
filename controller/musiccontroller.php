<?php
/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\audioplayer\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IDb;

/**
 * Controller class for main page.
 */
class MusicController extends Controller {
	
	private $userId;
	private $l10n;
	private static $sortType='album';
	private $db;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDb $db
		) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
	}
	/**
	*@PublicPage
	 * @NoCSRFRequired
	 * 
	 */
	public function getPublicAudioStream($file){
		$pToken  = $this->params('token');	
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
	public function getPublicAudioInfo(){
		$file  = $this->params('file');	
		$pToken  = $this->params('token');	
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
			\OCP\Util::writeLog('audioplayer', $filenameAudio.' '.$user, \OCP\Util::DEBUG);
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
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function loadAlbums(){
			
		$SQL="SELECT  `AA`.`id`,`AA`.`name`,`AA`.`year`,`AA`.`cover`,`AA`.`bgcolor`,`AG`.`name` AS `genrename` FROM `*PREFIX*audioplayer_albums` `AA`
						LEFT JOIN `*PREFIX*audioplayer_genre` `AG` ON `AA`.`genre_id` = `AG`.`id`
			 			WHERE  `AA`.`user_id` = ?
			 			ORDER BY `AA`.`name` ASC
			 			";
			
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		$aAlbums='';
		while( $row = $result->fetchRow()) {
			$row['artist'] = $this->loadArtistsToAlbum($row['id']);	
			$row['backgroundColor'] = '#D3D3D3';
			$row['titlecolor'] = '#333333';
			if ($row['name'] === $this->l10n->t('Unknown') AND $row['artist'] === $this->l10n->t('Various Artists')){
				$row['cover'] = 'data:image/jpg;base64,'.'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';
			}elseif($row['cover'] === null){
				$row['cover'] = '';
			}else{
				$row['cover'] = 'data:image/jpg;base64,'.$row['cover'];	
			}
			$aAlbums[$row['id']] = $row;
		}
		if(is_array($aAlbums)){
			$aAlbums = $this->sortArrayByFields($aAlbums); 
			return $aAlbums;
		}else{
			return false;
		}
	}
	
	private function loadArtistsToAlbum($iAlbumId){
		# load albumartist if available
		# if no albumartist, we will load all artists from the tracks
		# if all the same - display it as album artist
		# if different track-artists, display "various"
    	$stmt = $this->db->prepareQuery( 'SELECT `artist_id` FROM `*PREFIX*audioplayer_albums` WHERE `id` = ?' );
		$result = $stmt->execute(array($iAlbumId));
		$AArtist = $result->fetchRow();
		if ((int)$AArtist['artist_id'] !== 0){
			$stmt = $this->db->prepareQuery( 'SELECT `name`  FROM `*PREFIX*audioplayer_artists` WHERE  `id` = ?' );
			$result = $stmt->execute(array($AArtist['artist_id']));
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
		$SQL="SELECT  `AT`.`id`,`AT`.`title`,`AT`.`number`,`AT`.`album_id`,`AT`.`artist_id`,`AT`.`length`,`AT`.`file_id`,`AT`.`bitrate`,`AT`.`mimetype`,`AA`.`name` AS `artistname` FROM `*PREFIX*audioplayer_tracks` `AT`
						LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
			 			WHERE  `AT`.`user_id` = ?
			 			ORDER BY `AT`.`album_id` ASC,`AT`.`number` ASC
			 			";
			
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		$aSongs='';
		
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
					$row['link'] = \OC::$server->getURLGenerator()->linkToRoute('audioplayer.music.getAudioStream').'?file='.rawurlencode($path);
				}	
				$aSongs[$row['album_id']][] = $row;
			}else{
				$this->deleteFromDB($row['id'],$row['album_id'],$row['artist_id'],$row['file_id']);
			}	
		}
		if(is_array($aSongs)){
			return $aSongs;
		}else{
			return false;
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public  function searchProperties($searchquery){
		$SQL="SELECT  `id`,`name` FROM `*PREFIX*audioplayer_albums` WHERE (LOWER(`name`) LIKE LOWER(?) OR `year` LIKE ?) AND `user_id` = ?";
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array('%'.addslashes($searchquery).'%', '%'.addslashes($searchquery).'%', $this->userId));
		$aAlbum ='';
		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
			
				$aAlbum[] = [
					'id' => $row['id'],
					'name' => 'Album: '.$row['name'],
				];
			}
		}
		
		$SQL="SELECT  `AT`.`title`,`AA`.`name`,`AA`.`id`,`AR`.`name` AS artistname FROM `*PREFIX*audioplayer_tracks` `AT` 
					LEFT JOIN `*PREFIX*audioplayer_albums` `AA` ON `AT`.`album_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AR` ON `AT`.`artist_id` = `AR`.`id`
					WHERE   (LOWER(`AT`.`title`) LIKE LOWER(?)  OR LOWER(`AR`.`name`) LIKE LOWER(?) ) AND `AT`.`user_id` = ?";
				 
		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array('%'.addslashes($searchquery).'%', '%'.addslashes($searchquery).'%', $this->userId));
		$aTrack ='';
		if(!is_null($result)) {
			while( $row = $result->fetchRow()) {
				$aTrack[] = [
					'id' => $row['id'],
					'name' => 'Track: '.$row['title'].' - '.$row['artistname'].'  ('.$row['name'].')',
				];
			}
		}
		
		if(is_array($aAlbum) && is_array($aTrack)){
			$result=array_merge($aAlbum,$aTrack);
			return $result;
		}elseif(is_array($aAlbum) && !is_array($aTrack)){
			return $aAlbum;
		}elseif(is_array($aTrack) && !is_array($aAlbum)){
				//\OCP\Util::writeLog('audioplayer','COUNTARRAYALBUM:'.count($aAlbum),\OCP\Util::DEBUG);		
			return $aTrack;
		}elseif(!is_array($aTrack) && !is_array($aAlbum)){
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
	
	private function deleteFromDB($Id,$iAlbumId,$iArtistId,$fileId){
		
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
		array_multisort($first, SORT_ASC, SORT_STRING,$second , SORT_ASC, SORT_STRING, $data);
		return $data;
	}

		
}
