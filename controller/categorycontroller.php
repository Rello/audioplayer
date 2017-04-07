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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IDb;

/**
 * Controller class for main page.
 */
class CategoryController extends Controller {
	
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
	public function getCategory(){
		$category=$this->params('category');
			
		$playlists= $this->getCategoryforUser($category);
	
		if(is_array($playlists)){
			$result=[
				'status' => 'success',
				'data' => $playlists
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
	
	private function getCategoryforUser($category){
	
		$aPlaylists=array();
		if($category === 'Artist') {
			$SQL="SELECT  distinct(AT.`artist_id`) AS `id`, AA.`name`, LOWER(AA.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_tracks` AT
						JOIN `*PREFIX*audioplayer_artists` AA
						on AA.`id` = AT.`artist_id`
			 			WHERE  AT.`user_id` = ?
			 			ORDER BY LOWER(AA.`name`) ASC
			 			";
		} elseif ($category === 'Genre') {
			$SQL="SELECT  `id`, `name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_genre`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
		} elseif ($category === 'Year') {
			$SQL="SELECT distinct(`year`) as `id` ,`year` as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			ORDER BY `id` ASC
			 			";
		} elseif ($category === 'Title') {
			$SQL="SELECT distinct('0') as `id` ,'" . $this->l10n->t('Titles') . "' as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			";
		} elseif ($category === 'Playlist') {
			$SQL="SELECT  `id`,`name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_playlists`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
			// **after v1.5.0**   $aPlaylists[] = array("id"=>"X1", "name" =>$this->l10n->t('Favorites'));
			$aPlaylists[] = array("id" => "X2", "name" => $this->l10n->t('Recently Added'));
			// **after v1.5.0**   $aPlaylists[] = array("id"=>"X3", "name" =>$this->l10n->t('Recently Played'));
			// **after v1.5.0**   $aPlaylists[] = array("id"=>"X4", "name" =>$this->l10n->t('Most Played'));
			// **after v1.5.0**   $aPlaylists[] = array("id"=>"X5", "name" =>$this->l10n->t('Top Rated'));
			$aPlaylists[] = array("id" => "", "name" => "");
		} elseif ($category === 'Folder') {
			$SQL="SELECT  distinct(FC.`fileid`) AS `id`,FC.`name`, LOWER(FC.`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_tracks` AT
						JOIN `*PREFIX*filecache` FC
						on FC.`fileid` = AT.`folder_id`
			 			WHERE  AT.`user_id` = ?
			 			ORDER BY LOWER(FC.`name`) ASC
			 			";
		} elseif ($category === 'Album') {
			$SQL="SELECT  `id`,`name`, LOWER(`name`) AS `lower` 
						FROM `*PREFIX*audioplayer_albums`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
		}	
			
		$stmt =$this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		while( $row = $result->fetchRow()) {
 			array_splice($row, 2, 1);
 			if($row['name'] === '0') $row['name'] = $this->l10n->t('Unknown');
			$row['counter'] = $this->getCountForCategory($category,$row['id']);
			$aPlaylists[]=$row;
		}
		
		if(empty($aPlaylists)){
  			return false;
 		} else {
 			return $aPlaylists;
		}
	}

	private function getCountForCategory($category,$categoryId){

		if($category === 'Artist') {
			$SQL="SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
			 		WHERE  `AT`.`artist_id` = ? 
			 		AND `AT`.`user_id` = ?
			 		";
		} elseif ($category === 'Genre') {
			$SQL="SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`genre_id` = ?  
					AND `AT`.`user_id` = ?
					";
		} elseif ($category === 'Year') {
			$SQL="SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`year` = ? 
					AND `AT`.`user_id` = ?
					";
		} elseif ($category === 'Title') {
			$SQL="SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`id` > ? 
					AND `AT`.`user_id` = ?
					";
		} elseif ($category === 'Playlist') {
			$SQL="SELECT  COUNT(`AP`.`track_id`) AS `count`
					FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
			 		WHERE  `AP`.`playlist_id` = ?
			 		";
		} elseif ($category === 'Folder') {
			$SQL="SELECT  COUNT(`AT`.`id`) AS `count`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					WHERE `AT`.`folder_id` = ? 
					AND `AT`.`user_id` = ?
					";
		} elseif ($category === 'Album') {
			$SQL="SELECT  COUNT(`id`) AS `count` 
					FROM `*PREFIX*audioplayer_tracks`
					WHERE `album_id` = ? 
					AND `user_id` = ?
			 		";
		}

		$stmt = $this->db->prepareQuery($SQL);
		if ($category === 'Playlist') {
			$result = $stmt->execute(array($categoryId));
		} else {
			$result = $stmt->execute(array($categoryId, $this->userId));
		}
		while( $row = $result->fetchRow()) {
			$count = $row['count'];
		}
		return $count;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function getCategoryItems(){
		$category=$this->params('category');
		$categoryId=$this->params('id');
			
		$itmes = $this->getItemsforCatagory($category,$categoryId);
	
		if(is_array($itmes)){
			$result=[
				'status' => 'success',
				'data' => $itmes
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

	
	private function getItemsforCatagory($category,$categoryId){

		$aTracks=array();		
		$SQL_select = "SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`, `AT`.`file_id`, `AB`.`id` AS `cover_id`, `AB`.`cover`, LOWER(`AT`.`title`) AS `lower`";
		$SQL_from 	= " FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`";
		$SQL_order	= " ORDER BY LOWER(`AT`.`title`) ASC";

		if($category === 'Artist') {
			$SQL = 	$SQL_select . $SQL_from .
				"WHERE  `AT`.`artist_id` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Genre') {
			$SQL = 	$SQL_select . $SQL_from .
				"WHERE `AT`.`genre_id` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Year') {
			$SQL = 	$SQL_select . $SQL_from .
				"WHERE `AT`.`year` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Title') {
			$SQL = 	$SQL_select . $SQL_from .
				"WHERE `AT`.`id` > ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Playlist') {
			if ($categoryId === "X1") { // Favorites
			} elseif ($categoryId === "X2") { // Recently Added
				$SQL = 	$SQL_select . $SQL_from .
			 		"WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AT`.`file_id` DESC
			 		Limit 25";
			} elseif ($categoryId === "X3") { // Recently Played
			} elseif ($categoryId === "X4") { // Most Played
			} elseif ($categoryId === "X5") { // Top Rated
			} else {
				$SQL = $SQL_select ." , `AP`.`sortorder`" .
					"FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
					LEFT JOIN `*PREFIX*audioplayer_tracks` `AT` ON `AP`.`track_id` = `AT`.`id`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AP`.`playlist_id` = ?
					AND `AT`.`user_id` = ? 
			 		ORDER BY `AP`.`sortorder` ASC";
			}
		} elseif ($category === 'Folder') {
			$SQL = 	$SQL_select . $SQL_from .
				"WHERE `AT`.`folder_id` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Album') {
			$SQL = 	$SQL_select . $SQL_from .
 				"WHERE `AB`.`id` = ? AND `AB`.`user_id` = ?" .
			 	$SQL_order;
		}

		$stmt = $this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($categoryId, $this->userId));
				
		while( $row = $result->fetchRow()) {
			$file_not_found = false;	
			try {
				$path = \OC\Files\Filesystem::getPath($row['file_id']);
			} catch (\Exception $e) {
				$file_not_found = true;
       			}
       		
       			if($file_not_found === false){
				if ($row['cover'] === null) {
					$row['cover_id'] = '';
				} 
 				array_splice($row, 8, 2);
				$path = rtrim($path,"/");
				$row['link'] = '?file='.rawurlencode($path);
				$aTracks[]=$row;
			}else{
				$this->deleteFromDB($row['id'],$row['cover_id']);
			}	
		}
		
		if(empty($aTracks)){
  			return false;
 		}else{
 			return $aTracks;
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
}
