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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IDbConnection;
use OCP\ITagManager;

/**
 * Controller class for main page.
 */
class CategoryController extends Controller {
	
	private $userId;
	private $l10n;
	private $db;
	private $tagger;
	private $tagManager;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDBConnection $db,
			ITagManager $tagManager
			) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->tagManager = $tagManager;
		$this->tagger = null;
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
			$aPlaylists[] = array("id"=>"X1", "name"=>$this->l10n->t('Favorites'));
			$aPlaylists[] = array("id"=>"X2", "name"=> $this->l10n->t('Recently Added'));
			$aPlaylists[] = array("id"=>"X3", "name" =>$this->l10n->t('Recently Played'));
			$aPlaylists[] = array("id"=>"X4", "name" =>$this->l10n->t('Most Played'));
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
		
		if ($SQL) {	
			$stmt = $this->db->prepare($SQL);
			$stmt->execute(array($this->userId));
			$results = $stmt->fetchAll();
			foreach($results as $row) {
 				array_splice($row, 2, 1);
 				if($row['name'] === '0') $row['name'] = $this->l10n->t('Unknown');
				$row['counter'] = $this->getCountForCategory($category,$row['id']);
				$aPlaylists[] = $row;
			}
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

		$stmt = $this->db->prepare($SQL);
		if ($category === 'Playlist') {
			$stmt->execute(array($categoryId));
		} else {
			$stmt->execute(array($categoryId, $this->userId));
		}
		$results = $stmt->fetchAll();
		foreach($results as $row) {
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
		$headers = $this->getHeadersforCatagory($category);
	
		if(is_array($itmes)){
			$result=[
				'status' => 'success',
				'data' => $itmes,
				'header' => $headers,				
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
		$SQL_select = "SELECT  `AT`.`id` , `AT`.`title`  AS `cl1`,`AT`.`length` AS `len`,`AA`.`name` AS `cl2`, `AB`.`name` AS `cl3`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AB`.`name`) AS `lower`";
		$SQL_from 	= " FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`";
		$SQL_order	= " ORDER BY LOWER(`AB`.`name`) ASC, `AT`.`disc` ASC, `AT`.`number` ASC";

		if($category === 'Artist') {
			$SQL_select = "SELECT  `AT`.`id` , `AT`.`title`  AS `cl1`,`AB`.`name` AS `cl2`,`AT`.`length` AS `len`,`AT`.`year` AS `cl3`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AB`.`name`) AS `lower`";
			$SQL = $SQL_select . $SQL_from .
				"WHERE  `AT`.`artist_id` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Genre') {
			$SQL = $SQL_select . $SQL_from .
				"WHERE `AT`.`genre_id` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Year') {
			$SQL = $SQL_select . $SQL_from .
				"WHERE `AT`.`year` = ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Title') {
			$SQL = $SQL_select . $SQL_from .
				"WHERE `AT`.`id` > ? AND `AT`.`user_id` = ?" .
			 	$SQL_order;
		} elseif ($category === 'Playlist') {
			if ($categoryId === "X1") { // Favorites
				$SQL = "SELECT  `AT`.`id` , `AT`.`title`  AS `cl1`,`AT`.`length` AS `len`,`AA`.`name` AS `cl2`, `AB`.`name` AS `cl3`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AT`.`title`) AS `lower`" . 
					$SQL_from .
					"WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ?" .
			 		" ORDER BY LOWER(`AT`.`title`) ASC";
			} elseif ($categoryId === "X2") { // Recently Added
				$SQL = 	$SQL_select . $SQL_from .
			 		"WHERE `AT`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AT`.`file_id` DESC
			 		Limit 25";
			} elseif ($categoryId === "X3") { // Recently Played
				$SQL = 	$SQL_select . $SQL_from .
			 		"LEFT JOIN `*PREFIX*audioplayer_statistics` `AS` ON `AT`.`id` = `AS`.`track_id`
			 		WHERE `AS`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AS`.`playtime` DESC
			 		Limit 25";
			} elseif ($categoryId === "X4") { // Most Played
				$SQL = 	$SQL_select . $SQL_from .
			 		"LEFT JOIN `*PREFIX*audioplayer_statistics` `AS` ON `AT`.`id` = `AS`.`track_id`
			 		WHERE `AS`.`id` <> ? AND `AT`.`user_id` = ? 
			 		ORDER BY `AS`.`playcount` DESC
			 		Limit 25";
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
			$SQL_select = "SELECT  `AT`.`id` , `AT`.`title`  AS `cl1`,`AT`.`number`  AS `num`,`AT`.`length` AS `len`,`AA`.`name` AS `cl2`, `AT`.`disc` AS `dsc`, `AT`.`file_id` AS `fid`, `AT`.`mimetype` AS `mim`, `AB`.`id` AS `cid`, `AB`.`cover`, LOWER(`AT`.`title`) AS `lower`";
			$SQL = 	$SQL_select . $SQL_from .
 				"WHERE `AB`.`id` = ? AND `AB`.`user_id` = ?" .
			 	" ORDER BY `AT`.`disc` ASC, `AT`.`number` ASC";
		}

		$this->tagger = $this->tagManager->load('files');
		$favorites = $this->tagger->getFavorites();
				
		$stmt = $this->db->prepare($SQL);
		$stmt->execute(array($categoryId, $this->userId));
		$results = $stmt->fetchAll();
		foreach($results as $row) {
			$file_not_found = false;	
			try {
				$path = \OC\Files\Filesystem::getPath($row['fid']);
			} catch (\Exception $e) {
				$file_not_found = true;
       		}
       		
       		if($file_not_found === false){
				if ($row['cover'] === null) {
					$row['cid'] = '';
				} 
 				array_splice($row, 9, 2);
				$path = rtrim($path,"/");
				$row['lin'] = rawurlencode($path);
				if (in_array($row['fid'], $favorites)) {
					$row['fav'] = "t";
				} else {
					$row['fav'] = "f";
				}
				
				if ($category === 'Album') {
					$row['cl3'] = $row['dsc'].'-'.$row['num'];
				} 
				
				if ($categoryId === "X1" AND !in_array($row['fid'], $favorites)) {
				} else {
					$aTracks[]=$row;
				}
			} else {
				$this->deleteFromDB($row['id'],$row['cid']);
			}	
		}
		
		if(empty($aTracks)){
  			return false;
 		}else{
 			return $aTracks;
		}
	}

	public function getHeadersforCatagory($category){
		$headers=array();		
		if($category === 'Artist') {
			$headers = ['col1' => $this->l10n->t('Title'), 'col2' => $this->l10n->t('Album'), 'col3' => $this->l10n->t('Year')];
		} elseif ($category === 'Album') {
			$headers = ['col1' => $this->l10n->t('Title'), 'col2' => $this->l10n->t('Artist'), 'col3' => $this->l10n->t('Disc').'-'.$this->l10n->t('Track')];
		} else {
			$headers = ['col1' => $this->l10n->t('Title'), 'col2' => $this->l10n->t('Artist'), 'col3' => $this->l10n->t('Album')];
		}
 		return $headers;

	}

	private function deleteFromDB($Id,$iAlbumId){		
		$stmtCountAlbum = $this->db->prepare( 'SELECT COUNT(`album_id`) AS `ALBUMCOUNT`  FROM `*PREFIX*audioplayer_tracks` WHERE `album_id` = ? ' );
		$stmtCountAlbum->execute(array($iAlbumId));
		$rowAlbum = $stmtCountAlbum->fetch();
		if((int)$rowAlbum['ALBUMCOUNT'] === 1){
			$stmt = $this->db->prepare( 'DELETE FROM `*PREFIX*audioplayer_albums` WHERE `id` = ? AND `user_id` = ?' );
			$stmt->execute(array($iAlbumId, $this->userId));
		}
		
		$stmt = $this->db->prepare( 'DELETE FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ? AND `id` = ?' );
		$stmt->execute(array($this->userId, $Id));		
	}

	/**
	 * @NoAdminRequired
	 */
	public function setFavorite() {
		$fileId = $this->params('fileId');
		$isFavorite = $this->params('isFavorite');
		$this->tagger = $this->tagManager->load('files');
		
		if ($isFavorite === "true") {
			$return = $this->tagger->removeFromFavorites($fileId);
 		} else {
			$return = $this->tagger->addToFavorites($fileId);
 		}
 		return $return;
	}
	/**
	 * @NoAdminRequired
	 */
	public function setStatistics() {
		$track_id = $this->params('track_id');
		$date = new \DateTime();
		$playtime = $date->getTimestamp();
		
		$SQL='SELECT id, playcount FROM *PREFIX*audioplayer_statistics WHERE `user_id`= ? AND `track_id`= ?';
		$stmt = $this->db->prepare($SQL);
		$stmt->execute(array($this->userId, $track_id));
		$row = $stmt->fetch();
		if (isset($row['id'])) {
			$playcount = $row['playcount'] + 1;
			$stmt = $this->db->prepare( 'UPDATE `*PREFIX*audioplayer_statistics` SET `playcount`= ?, `playtime`= ? WHERE `id` = ?');					
			$stmt->execute(array($playcount, $playtime, $row['id']));
			return 'update';
		} else {
			$stmt = $this->db->prepare( 'INSERT INTO `*PREFIX*audioplayer_statistics` (`user_id`,`track_id`,`playtime`,`playcount`) VALUES(?,?,?,?)' );
			$stmt->execute(array($this->userId, $track_id, $playtime, 1));
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_statistics');
			return $insertid;
		}
	}
}
