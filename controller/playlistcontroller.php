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
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;

/**
 * Controller class for main page.
 */
class PlaylistController extends Controller {
	
	private $userId;
	private $l10n;
	
	

	public function __construct($appName, IRequest $request, $userId, $l10n) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function getPlaylists(){
			
		$playlists= $this->getPlaylistsforUser();
	
		if(is_array($playlists)){
			$aPlayLists='';
			foreach($playlists as $playinfo){
				$bg = $this->genColorCodeFromText(trim($playinfo['name']),40,8);
				$playinfo['backgroundColor']=$bg;
				$playinfo['color']=$this->generateTextColor($bg);
				$aPlayLists[]=['info' => $playinfo, 'songids' => $this->getSongIdsForPlaylist($playinfo['id'])];
			}
		
			$result=[
				'status' => 'success',
				'data' => ['playlists' => $aPlayLists]
			];
		}else{
			$result=[
				'status' => 'success',
				'data' => 'nodata'
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
	public function addPlaylist(){
		 	//filter_var()
		 $newplaylist=$this->params('playlist');
		
		 if($newplaylist !=''){	
			 $aResult = $this->writePlaylistToDB($newplaylist);
			 if($aResult['msg'] === 'new'){
				 $result=[
					'status' => 'success',
					'data' => ['playlist' => $newplaylist]
				];
			 }
			 if($aResult['msg'] === 'exist'){
				 $result=[
					'status' => 'success',
					'data' => 'exist',
				];
			 }
			 $response = new JSONResponse();
			 $response -> setData($result);
			 return $response;
		 }
	}

	private function writePlaylistToDB($sName){
		//Test If exist
		$stmtCount = \OCP\DB::prepare( 'SELECT `id`, COUNT(`id`)  AS COUNTID FROM `*PREFIX*audios_playlists` WHERE `user_id` = ? AND `name` = ?' );
		$resultCount = $stmtCount->execute(array($this->userId, $sName));
		$row = $resultCount->fetchRow();
		//Name exists
		if((int)$row['COUNTID'] === 1){
			$result = ['msg'=>'exist','id' => $row['id']];
			
			return $result;
		}else{	
			$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*audios_playlists` (`user_id`,`name`) VALUES(?,?)' );
			$result = $stmt->execute(array($this->userId, $sName));
			$insertid = \OCP\DB::insertid('*PREFIX*audios_playlists');
			$result = ['msg'=>'new','id' => $insertid];
			
			return $result;
		}
	}

	private function getPlaylistsforUser(){
		$SQL="SELECT  `id`,`name` FROM `*PREFIX*audios_playlists`
			 			WHERE  `user_id` = ?
			 			ORDER BY `name` ASC
			 			";
			
		$stmt = \OCP\DB::prepare($SQL);
		$result = $stmt->execute(array($this->userId));
		$aPlaylists='';
		while( $row = $result->fetchRow()) {
			$aPlaylists[]=$row;
		}
		
		if(is_array($aPlaylists)){
			return $aPlaylists;
		}else{
			return false;
		}
	}
	
	private function getSongIdsForPlaylist($iPlaylistId){
		$SQL="SELECT  `track_id` FROM `*PREFIX*audios_playlist_tracks`
			 			WHERE  `playlist_id` = ?
			 			ORDER BY `sortorder` ASC
			 			";
			
		$stmt = \OCP\DB::prepare($SQL);
		$result = $stmt->execute(array($iPlaylistId));
		$aTracks=[];
		while( $row = $result->fetchRow()) {
			$aTracks[]=$row['track_id'];
		}
		
		
		return $aTracks;
		
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function addTrackToPlaylist(){
		$iPlaylistId = $this->params('playlistid');
		$iTrackId = $this->params('songid');
		$iSortOrder = $this->params('sorting');
		try {
			\OCP\DB::insertIfNotExist('*PREFIX*audios_playlist_tracks',
				array(
					'playlist_id' => $iPlaylistId,
					'track_id' => $iTrackId,
					'sortorder' => (int) $iSortOrder,
				));
		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				\OCP\Util::ERROR);
			return false;
		}
		return true;
	}
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function sortPlaylist(){
		$iPlaylistId = $this->params('playlistid');
		$pTrackIds = $this->params('songids');
		$iTrackIds = explode(';', $pTrackIds);
			
		$counter = 1;	
		foreach($iTrackIds as $trackId){
			$stmt = \OCP\DB::prepare( 'UPDATE `*PREFIX*audios_playlist_tracks` SET `sortorder` = ? WHERE `playlist_id` = ? AND `track_id` = ?' );
		    $result = $stmt->execute(array($counter, $iPlaylistId,$trackId));
			$counter++;
		}
		$result=[
		'status' => 'success',
		'msg' =>(string) $this->l10n->t('Sorting Playlist success! Playlist reloaded!')
	  ];
	  
	 $response = new JSONResponse();
	 $response -> setData($result);
	 return $response;
		
	}
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function removeTrackFromPlaylist(){
		$iPlaylistId = $this->params('playlistid');
		$iTrackId = $this->params('songid');
		
		try {
			$sql = 'DELETE FROM `*PREFIX*audios_playlist_tracks` '
					. 'WHERE `playlist_id` = ? AND `track_id` = ?';
			$stmt = \OCP\DB::prepare($sql);
			$stmt->execute(array($iPlaylistId, $iTrackId));
		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),
				\OCP\Util::ERROR);
			return false;
		}
		return true;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function removePlaylist(){
		 	
		 
		 $iPlaylistId = $this->params('playlistid');
		 
		 
		 try {
			$sql = 'DELETE FROM `*PREFIX*audios_playlists` '
					. 'WHERE `id` = ? AND `user_id` = ?';
			$stmt = \OCP\DB::prepare($sql);
			$result = $stmt->execute(array($iPlaylistId, $this->userId));	
				
			$sql = 'DELETE FROM `*PREFIX*audios_playlist_tracks` '
					. 'WHERE `playlist_id` = ?';
			$stmt = \OCP\DB::prepare($sql);
			$result = $stmt->execute(array($iPlaylistId));
			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog('core',__METHOD__. 'DB error: ' . \OCP\DB::getErrorMessage(),\OCP\Util::ERROR);
				return false;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),\OCP\Util::ERROR);
			return false;
		}
		
		  $result=[
			'status' => 'success',
			'data' => ['playlist' => $iPlaylistId]
		  ];
		  
		 $response = new JSONResponse();
		 $response -> setData($result);
		 return $response;
		 
		  
	}
	
	/**
	* Delete tags and tag/object relations for a user.
	*
	* For hooking up on post_deleteUser
	*
	* @param array $arguments
	*/
	public static function post_deleteUser($arguments) {
		/*
		$stmt = \OCP\DB::prepare('SELECT `id` FROM `*PREFIX*audios_playlists` '
				. 'WHERE `user_id` = ?');
		$result = $stmt->execute(array($arguments['uid']));	
			
			
		$sql = 'DELETE FROM `*PREFIX*audios_playlists` '
					. 'WHERE `id` = ? AND `user_id` = ?';
		$stmt = \OCP\DB::prepare($sql);
		$result = $stmt->execute(array($arguments['uid']));	*/
	}
	
		/*
	 * @brief generates the text color for the calendar
	 * @param string $calendarcolor rgb calendar color code in hex format (with or without the leading #)
	 * (this function doesn't pay attention on the alpha value of rgba color codes)
	 * @return boolean
	 */
	private function generateTextColor($calendarcolor) {
		if(substr_count($calendarcolor, '#') == 1) {
			$calendarcolor = substr($calendarcolor,1);
		}
		$red = hexdec(substr($calendarcolor,0,2));
		$green = hexdec(substr($calendarcolor,2,2));
		$blue = hexdec(substr($calendarcolor,4,2));
		//recommendation by W3C
		$computation = ((($red * 299) + ($green * 587) + ($blue * 114)) / 1000);
		return ($computation > 130)?'#000000':'#FAFAFA';
	}
	
	
	 /**
     * genColorCodeFromText method
     *
     * Outputs a color (#000000) based Text input
     *
     * (https://gist.github.com/mrkmg/1607621/raw/241f0a93e9d25c3dd963eba6d606089acfa63521/genColorCodeFromText.php)
     *
     * @param String $text of text
     * @param Integer $min_brightness: between 0 and 100
     * @param Integer $spec: between 2-10, determines how unique each color will be
     * @return string $output
	  * 
	  */
	  
	 private function genColorCodeFromText($text, $min_brightness = 100, $spec = 10){
        // Check inputs
        if(!is_int($min_brightness)) throw new \Exception("$min_brightness is not an integer");
        if(!is_int($spec)) throw new \Exception("$spec is not an integer");
        if($spec < 2 or $spec > 10) throw new Exception("$spec is out of range");
        if($min_brightness < 0 or $min_brightness > 255) throw new \Exception("$min_brightness is out of range");

        $hash = md5($text);  //Gen hash of text
        $colors = array();
        for($i=0; $i<3; $i++) {
            //convert hash into 3 decimal values between 0 and 255
            $colors[$i] = max(array(round(((hexdec(substr($hash, $spec * $i, $spec))) / hexdec(str_pad('', $spec, 'F'))) * 255), $min_brightness));
        }

        if($min_brightness > 0) {
            while(array_sum($colors) / 3 < $min_brightness) {
                for($i=0; $i<3; $i++) {
                    //increase each color by 10
                    $colors[$i] += 10;
                }
            }
        }

        $output = '';
        for($i=0; $i<3; $i++) {
            //convert each color to hex and append to output
            $output .= str_pad(dechex($colors[$i]), 2, 0, STR_PAD_LEFT);
        }

        return '#'.$output;
    }
	
	
}