<?php
/**
 * ownCloud - Audios
 *
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

namespace OCA\Audios\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;
use \OC\Files\View;

/**
 * Controller class for main page.
 */
class ScannerController extends Controller {
	
	private $userId;
	private $l10n;
	private $path;
	

	public function __construct($appName, IRequest $request, $userId, $l10n) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function editAudioFile() {
		$songFileId=$this->params('songFileId');
		$resultData=[];
		
		if(!class_exists('getid3_exception')) {
			require_once __DIR__ . '/../3rdparty/getid3/getid3.php';
		}
		
		
		
		$userView =  new View('/' . $this -> userId. '/files');
		$path= $userView->getPath($songFileId);
		$localFile = $userView->getLocalFile($path);
		
		$getID3 = new \getID3;
		$ThisFileInfo = $getID3->analyze($localFile);
		\getid3_lib::CopyTagsToComments($ThisFileInfo);
		$resultData['localPath'] = $localPath;
		$resultData['title'] = '';
		if(isset($ThisFileInfo['comments']['title'][0])){
			$resultData['title']=$ThisFileInfo['comments']['title'][0];
		}
		$resultData['album'] = '';
		if(isset($ThisFileInfo['comments']['album'][0])){
			$resultData['album'] = $ThisFileInfo['comments']['album'][0];
		}
		$resultData['genre'] = '';
		if(isset($ThisFileInfo['comments']['genre'][0])){
			$resultData['genre'] = $ThisFileInfo['comments']['genre'][0];
		}
		$resultData['artist'] = '';
		if(isset($ThisFileInfo['comments']['artist'][0])){
			$resultData['artist'] = $ThisFileInfo['comments']['artist'][0];
		}
		
		$resultData['year'] = '';
		if(isset($ThisFileInfo['comments']['year'][0])){
			$resultData['year'] = $ThisFileInfo['comments']['year'][0];
		}
		
		$result = [
			'status' => 'success',
			'data' => $resultData,
		];
		$response = new JSONResponse();		
		$response -> setData($result);
		return $response;
		
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function saveAudioFileData() {
		
		$songFileId=$this->params('songFileId');
		$pYear=$this->params('year');
		
		$resultData=[
			'year' => [$pYear]
		];
		
		if(!class_exists('getid3_exception')) {
			require_once __DIR__ . '/../3rdparty/getid3/getid3.php';
		}
		
		require_once __DIR__ . '/../3rdparty/getid3/write.php';
		
		$TextEncoding = 'UTF-8';
		$userView =  new View('/' . $this -> userId. '/files');
		$path= $userView->getPath($songFileId);
		if(\OC\Files\Filesystem::isUpdatable($path)){
			
			$getID3 = new \getID3;
			$getID3->setOption(array('encoding'=>$TextEncoding));
			
			$tagwriter = new \getid3_writetags;
			$localFile = $userView->getLocalFile($path);
			//\OCP\Util::writeLog('audios','local: '.$localFile,\OCP\Util::DEBUG);
			$tagwriter->filename = $localFile;
			$tagwriter->tagformats = array('id3v2.3');
			$tagwriter->overwrite_tags    = true;
			$tagwriter->remove_other_tags = true;
			$tagwriter->tag_encoding      = $TextEncoding;
			
			$TagData = array(
				'year'          => array('2004'),
			);
			
			$tagwriter->tag_data = $TagData;
			
			if ($tagwriter->WriteTags()) {
				if (!empty($tagwriter->warnings)) {
					$result = [
						'status' => 'error',
						'msg' => (string) $tagwriter->warnings,
					];
				}else{
					$result = [
						'status' => 'success',
						'msg' => 'all good',
					];
				}
			}else {
				$result = [
						'status' => 'error',
						'msg' => (string) $tagwriter->errors,
					];
			}
		}else{
			$result = [
						'status' => 'error',
						'msg' => 'not writeable',
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
	public function scanForAudios() {
	
		if(!class_exists('getid3_exception')) {
			require_once __DIR__ . '/../3rdparty/getID3/getid3/getid3.php';
		}
		
		
        $userView =  new View('/' . $this -> userId . '/files');
		$audios = $userView->searchByMime('audio/mpeg');
		$tempArray=array();
		$counter = 0;
		foreach($audios as $audio) {
		  	
			//new Audio Found
			if($this->checkIfTrackDbExists($audio['fileid']) === false){
			   $TextEncoding = 'UTF-8';
			
				$getID3 = new \getID3;
				$ThisFileInfo = $getID3->analyze($userView->getLocalFile($audio['path']));
				\getid3_lib::CopyTagsToComments($ThisFileInfo);
					
				$album = (string) $this->l10n->t('Various');
				if(isset($ThisFileInfo['comments']['album'][0])){
					$album=$ThisFileInfo['comments']['album'][0];
				}
				$genre = '';
				if(isset($ThisFileInfo['comments']['genre'][0])){
					$genre=$ThisFileInfo['comments']['genre'][0];
				}
				
				$iGenreId=0;
				if($genre!=''){
					$iGenreId= $this->writeGenreToDB($genre);
				}
				
				$year = '';
				if(isset($ThisFileInfo['comments']['year'][0])){
					$year=$ThisFileInfo['comments']['year'][0];
				}
				
				$iAlbumId= $this->writeAlbumToDB($album,$year,$iGenreId);
				
				$artist = (string) $this->l10n->t('Various Artists');
				if(isset($ThisFileInfo['comments']['artist'][0])){
					$artist=$ThisFileInfo['comments']['artist'][0];
				}
				
				$iArtistId= $this->writeArtistToDB($artist);
				
				$this->writeArtistToAlbum($iAlbumId,$iArtistId);
				
				$name = $audio['name'];
				if(isset($ThisFileInfo['comments']['title'][0])){
					$name=$ThisFileInfo['comments']['title'][0];
					
				}
				
				$trackNumber = '';
				if(isset($ThisFileInfo['comments']['track_number'][0])){
					$trackNumber=$ThisFileInfo['comments']['track_number'][0];
				}
				
				$bitrate = 0;
				if(isset($ThisFileInfo['bitrate'])){
					$bitrate=$ThisFileInfo['bitrate'];
				}
				/*
				$comment = '';
				if(isset($ThisFileInfo['comments']['comment'][0])){
					$comment=$ThisFileInfo['comments']['comment'][0];
				}*/
				
				$cleanTrackNumber=$trackNumber;
				if(stristr($trackNumber,'/')){
					$temp=explode('/',$trackNumber);
					$cleanTrackNumber=trim($temp[0]);
				}
				
				if(isset($ThisFileInfo['comments']['picture'])){
					$data=$ThisFileInfo['comments']['picture'][0]['data'];
					$image = new \OCP\Image();
					if($image->loadFromdata($data)) {
						if(($image->width() <= 250 && $image->height() <= 250) || $image->resize(250)) {
							$imgString=$image->__toString();
							$getDominateColor = $this->getDominateColorOfImage($imgString);
							$this->writeCoverToAlbum($iAlbumId,$imgString,$getDominateColor);
							$poster='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';base64,'.$imgString;
						}
					}
					
				}
			
				
				$aTrack = [
					'title' => $name,
					'number' =>(int)$cleanTrackNumber,
					'artist_id' => (int)$iArtistId,
					'album_id' =>(int) $iAlbumId,
					'length' => $ThisFileInfo['playtime_string'],
					'file_id' => (int)$audio['fileid'],
					'bitrate' => (int)$bitrate,
					'mimetype' => $audio['mimetype'],
				];
				
				$trackId=$this->writeTrackToDB($aTrack);
				
				$counter++;
			}
 			
			
		}

		$result=[
				'status' => 'success',
				'counter' => $counter
			];
			
		$response = new JSONResponse();
		$response -> setData($result);
		return $response;
		
	}

	
	private function writeCoverToAlbum($iAlbumId,$sImage,$aBgColor){
    		
    	$stmtCount = \OCP\DB::prepare( 'SELECT `cover` FROM `*PREFIX*audios_albums` WHERE `id` = ? AND `user_id` = ?' );
		$resultCount = $stmtCount->execute(array ($iAlbumId, $this->userId));
		$row = $resultCount->fetchRow();
		if($row['cover'] === null){
			$aBgColor=json_encode($aBgColor);
			$stmt = \OCP\DB::prepare( 'UPDATE `*PREFIX*audios_albums` SET `cover`= ?, `bgcolor`= ? WHERE `id` = ? AND `user_id` = ?' );
			$result = $stmt->execute(array($sImage, $aBgColor, $iAlbumId, $this->userId));
			return true;
		}else{
			return false;
		}
    }
	
	private function writeAlbumToDB($sAlbum,$sYear,$iGenreId){
		
		//Test If exist
		$stmtCount = \OCP\DB::prepare( 'SELECT `id`, COUNT(`id`)  AS COUNTID FROM `*PREFIX*audios_albums` WHERE `user_id` = ? AND `name` = ?' );
		$resultCount = $stmtCount->execute(array($this->userId, $sAlbum));
		$row = $resultCount->fetchRow();
		if($row['COUNTID'] > 0){
			return $row['id'];
		}else{
			$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*audios_albums` (`user_id`,`name`,`year`,`genre_id`) VALUES(?,?,?,?)' );
			$result = $stmt->execute(array($this->userId, $sAlbum, $sYear,$iGenreId));
			$insertid = \OCP\DB::insertid('*PREFIX*audios_albums');
			
			return $insertid;
		}
	}
	
	private function writeGenreToDB($sGenre){
		//Test If exist
		$stmtCount = \OCP\DB::prepare( 'SELECT `id`, COUNT(`id`)  AS COUNTID FROM `*PREFIX*audios_genre` WHERE `user_id` = ? AND `name` = ?' );
		$resultCount = $stmtCount->execute(array($this->userId, $sGenre));
		$row = $resultCount->fetchRow();
		if($row['COUNTID'] > 0){
			return $row['id'];
		}else{	
			$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*audios_genre` (`user_id`,`name`) VALUES(?,?)' );
			$result = $stmt->execute(array($this->userId, $sGenre));
			$insertid = \OCP\DB::insertid('*PREFIX*audios_genre');
			
			return $insertid;
		}
	}
	
	private function writeArtistToDB($sArtist){
		//Test If exist
		$stmtCount = \OCP\DB::prepare( 'SELECT `id`, COUNT(`id`)  AS COUNTID FROM `*PREFIX*audios_artists` WHERE `user_id` = ? AND `name` = ?' );
		$resultCount = $stmtCount->execute(array($this->userId, $sArtist));
		$row = $resultCount->fetchRow();
		if($row['COUNTID'] > 0){
			return $row['id'];
		}else{	
			$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*audios_artists` (`user_id`,`name`) VALUES(?,?)' );
			$result = $stmt->execute(array($this->userId, $sArtist));
			$insertid = \OCP\DB::insertid('*PREFIX*audios_artists');
			
			return $insertid;
		}
	}
	
	private function writeArtistToAlbum($iAlbumId,$iArtistId){
		//Test If exist
		$stmtCount = \OCP\DB::prepare( 'SELECT `artist_id`  FROM `*PREFIX*audios_album_artists` WHERE `artist_id` = ? AND `album_id` = ?' );
		$resultCount = $stmtCount->execute(array($iArtistId, $iAlbumId));
		$row = $resultCount->fetchRow();
		if((int)$row['artist_id'] === (int) $iArtistId){
			return true;
		}else{		
			$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*audios_album_artists` (`artist_id`,`album_id`) VALUES(?,?)' );
			$result = $stmt->execute(array($iArtistId, $iAlbumId));
			return true;
		}
	}
	
	private function writeTrackToDB($aTrack){
		//Test If exist
		$stmtCount = \OCP\DB::prepare( 'SELECT `id`, COUNT(`id`)  AS COUNTID FROM `*PREFIX*audios_tracks` WHERE `user_id` = ? AND `file_id` = ?' );
		$resultCount = $stmtCount->execute(array($this->userId, $aTrack['file_id']));
		$row = $resultCount->fetchRow();
		if($row['COUNTID'] > 0){
			return $row['id'];
		}else{		
			$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*audios_tracks` (`user_id`,`title`,`number`,`artist_id`,`album_id`,`length`,`file_id`,`bitrate`,`mimetype`) VALUES(?,?,?,?,?,?,?,?,?)' );
			$result = $stmt->execute(array($this->userId, $aTrack['title'], $aTrack['number'], $aTrack['artist_id'], $aTrack['album_id'], $aTrack['length'], $aTrack['file_id'], $aTrack['bitrate'], $aTrack['mimetype']));
			$insertid = \OCP\DB::insertid('*PREFIX*audios_tracks');
			
			return $insertid;
		}
		
	}
	
	private function checkIfTrackDbExists($fileid){
		$stmtCount = \OCP\DB::prepare( 'SELECT `id`, COUNT(`id`)  AS COUNTID FROM `*PREFIX*audios_tracks` WHERE `user_id` = ? AND `file_id` = ?' );
		$resultCount = $stmtCount->execute(array($this->userId, $fileid));
		$row = $resultCount->fetchRow();
		if($row['COUNTID'] > 0){
			return true;
		}else{
			return false;
		}
	}
	
	private function getDominateColorOfImage($img){
	$data = base64_decode($img);	
	$img =imagecreatefromstring($data);	
	
	$rTotal = 0;
	$gTotal =0;
	$bTotal = 0;	
	$total=0;
	for ($x=0;$x<imagesx($img);$x++) {
		for ($y=0;$y<imagesy($img);$y++) {
			$rgb = imagecolorat($img,$x,$y);
			$r   = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b   = $rgb & 0xFF;
	 		
	 		$rTotal += $r;
			$gTotal += $g;
			$bTotal += $b;
			$total++;
		}
	}
	 
	 $returnDominateColor=[
	 'red' => round($rTotal/$total),
	 'green' => round($gTotal/$total),
	 'blue' => round($bTotal/$total)
	 ];
	
	return $returnDominateColor;
	
}
	
	
}