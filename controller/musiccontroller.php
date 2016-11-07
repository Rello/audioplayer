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
			if($row['cover'] === null){
				$row['backgroundColor'] = '#D3D3D3';
				$row['titlecolor'] = '#333333';
				$row['cover'] = '';
			}else{
				$row['backgroundColor'] = '#D3D3D3';
				$row['titlecolor'] = '#333333';
				$row['cover'] = 'data:image/jpg;base64,'.$row['cover'];	
			}
			$aAlbums[$row['id']] = $row;
		}
		if(is_array($aAlbums)){
			$aAlbums=self::sortAlbums($aAlbums,'artist');	
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
	
	 public static function sortAlbums($files, $sortAttribute = 'name', $sortDescending = false) {
		$sortFunc = 'compareAlbumNames';
		self::$sortType=$sortAttribute;
		usort($files, array('\OCA\audioplayer\Controller\MusicController', $sortFunc));
		if ($sortDescending) {
			$files = array_reverse($files);
		}
		return $files;
	}
	
   public static function compareAlbumNames($a, $b) {
			return \OCP\Util::naturalSortCompare($a[self::$sortType], $b[self::$sortType]);
	}
		
}
