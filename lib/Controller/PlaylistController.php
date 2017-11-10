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
			IDbConnection $db
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
	public function addPlaylist($playlist){
		 if($playlist !== ''){	
			 $aResult = $this->writePlaylistToDB($playlist);
			 if($aResult['msg'] === 'new'){
				 $result=[
					'status' => 'success',
					'data' => ['playlist' => $playlist]
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
		 } else {
		     return null;
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
					
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_playlists');
			
			$result = ['msg'=>'new','id' => $insertid];
			
			return $result;
			
		}else{
			$stmt = $this->db->prepare( 'SELECT `id` FROM `*PREFIX*audioplayer_playlists` WHERE `user_id` = ? AND `name` = ?' );
			$stmt->execute(array($this->userId, $sName));
			$row = $stmt->fetch();
			
			$result = ['msg'=>'exist','id' => $row['id']];
			return $result;
		}	
	
	}
	
	private function updatePlaylistToDB($id,$sName){
		$stmt = $this->db->prepare( 'UPDATE `*PREFIX*audioplayer_playlists` SET `name` = ? WHERE `user_id`= ? AND `id`= ?' );
		$stmt->execute(array($sName, $this->userId, $id));
		return true;
	}
		
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function addTrackToPlaylist($playlistid, $songid, $sorting){
		try {
			$this->db->insertIfNotExist('*PREFIX*audioplayer_playlist_tracks',
				array(
					'playlist_id' => $playlistid,
					'track_id' => $songid,
					'sortorder' => (int) $sorting,
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
	public function sortPlaylist($playlistid, $songids){
		$iTrackIds = explode(';', $songids);
		$counter = 1;	
		foreach($iTrackIds as $trackId){
			$stmt = $this->db->prepare( 'UPDATE `*PREFIX*audioplayer_playlist_tracks` SET `sortorder` = ? WHERE `playlist_id` = ? AND `track_id` = ?' );
		    $stmt->execute(array($counter, $playlistid,$trackId));
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
	public function removeTrackFromPlaylist($playlistid, $songid){
		try {
			$sql = 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` '
					. 'WHERE `playlist_id` = ? AND `track_id` = ?';
			$stmt = $this->db->prepare($sql);
			$stmt->execute(array($playlistid, $songid));
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
	public function removePlaylist($playlistid){
		 try {
			$sql = 'DELETE FROM `*PREFIX*audioplayer_playlists` '
					. 'WHERE `id` = ? AND `user_id` = ?';
			$stmt = $this->db->prepare($sql);
			$stmt->execute(array($playlistid, $this->userId));	
				
			$sql = 'DELETE FROM `*PREFIX*audioplayer_playlist_tracks` '
					. 'WHERE `playlist_id` = ?';
			$stmt = $this->db->prepare($sql);
			$stmt->execute(array($playlistid));
		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__.', exception: '.$e->getMessage(),\OCP\Util::ERROR);
			return false;
		}
		
		  $result=[
			'status' => 'success',
			'data' => ['playlist' => $playlistid]
		  ];
		  
		 $response = new JSONResponse();
		 $response -> setData($result);
		 return $response;
	}
}
