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

/**
 * Controller class for main page.
 */
class PlaylistController extends Controller {
	
	private $userId;
	private $l10n;
	private $db;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDb $db
		) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function addPlaylist(){
		 	//filter_var()
		 $newplaylist=$this->params('playlist');
		
		 if($newplaylist !== ''){	
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
	
	 /**
     * @NoAdminRequired
	   * 
	   * @param $plId tag id
	   * @param $newname new name for tag
     */
	public function updatePlaylist($plId,$newname){
			
		if($this->updatePlaylistToDB($plId,$newname)){
			$params = [
			'status' => 'success',
			];
		}else{
			$params = [
				'status' => 'error',
			];
		}
		
		$response = new JSONResponse($params);
		return $response;
	}
	
	private function writePlaylistToDB($sName){
			
		
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_playlists', ['user_id' => $this->userId, 'name' => $sName])) {
					
			$insertid = $this->db->getInsertId('*PREFIX*audioplayer_playlists');
			
			$result = ['msg'=>'new','id' => $insertid];
			
			return $result;
			
		}else{
			$stmt = $this->db->prepareQuery( 'SELECT `id` FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ? AND `name` = ?' );
			$result = $stmt->execute(array($this->userId, $sName));
			$row = $result->fetchRow();
			
			$result = ['msg'=>'exist','id' => $row['id']];
			return $result;
		}	
	
	}
	
	private function updatePlaylistToDB($id,$sName){
		$stmt = $this->db->prepareQuery( 'UPDATE `*PREFIX*audioplayer_playlists` SET `name` = ? WHERE `user_id`= ? AND `id`= ?' );
		$result = $stmt->execute(array($sName, $this->userId, $id));
		
		return true;
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
			$this->db->insertIfNotExist('*PREFIX*audioplayer_playlist_tracks',
				array(
					'playlist_id' => $iPlaylistId,
					'track_id' => $iTrackId,
					'sortorder' => (int) $iSortOrder,
				));
		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),\OCP\Util::ERROR);
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
			$stmt = $this->db->prepareQuery( 'UPDATE `*PREFIX*audioplayer_playlist_tracks` SET `sortorder` = ? WHERE `playlist_id` = ? AND `track_id` = ?' );
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
			$sql = 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` '
					. 'WHERE `playlist_id` = ? AND `track_id` = ?';
			$stmt = $this->db->prepareQuery($sql);
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
			$sql = 'DELETE FROM `*PREFIX*audioplayer_playlists` '
					. 'WHERE `id` = ? AND `user_id` = ?';
			$stmt = $this->db->prepareQuery($sql);
			$result = $stmt->execute(array($iPlaylistId, $this->userId));	
				
			$sql = 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` '
					. 'WHERE `playlist_id` = ?';
			$stmt = $this->db->prepareQuery($sql);
			$result = $stmt->execute(array($iPlaylistId));
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
		$stmt = \OCP\DB::prepare('SELECT `id` FROM `*PREFIX*audioplayer_playlists` '
				. 'WHERE `user_id` = ?');
		$result = $stmt->execute(array($arguments['uid']));	
			
			
		$sql = 'DELETE FROM `*PREFIX*audioplayer_playlists` '
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
		if(substr_count($calendarcolor, '#') === 1) {
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
