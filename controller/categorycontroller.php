<?php
/**
 * ownCloud - Audio Player
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
			$aPlayLists='';
			foreach($playlists as $playinfo){
				$aPlayLists[]=['info' => $playinfo, 'songids' => $this->getSongIdsForCategory($category,$playinfo['id'])];
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


	
	private function getCategoryforUser($category){
	
		if($category === 'Artist') {
			$SQL="SELECT  distinct(AT.`artist_id`) AS `id`, AA.`name` 
						FROM `*PREFIX*audioplayer_tracks` AT
						JOIN `*PREFIX*audioplayer_artists` AA
						on AA.`id` = AT.`artist_id`
			 			WHERE  AT.`user_id` = ?
			 			ORDER BY LOWER(AA.`name`) ASC
			 			";
		} elseif ($category === 'Genre') {
			$SQL="SELECT  `id`,`name` FROM `*PREFIX*audioplayer_genre`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
		} elseif ($category === 'Year') {
			$SQL="SELECT distinct(`year`) as `id` ,`year` as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			ORDER BY `id` ASC
			 			";
		} elseif ($category === 'All') {
			$SQL="SELECT distinct('0') as `id` ,'".(string)$this->l10n->t('All')."' as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			";
		} elseif ($category === 'Playlist') {
			$SQL="SELECT  `id`,`name` 
						FROM `*PREFIX*audioplayer_playlists`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
		} elseif ($category === 'Folder') {
			$SQL="SELECT  distinct(FC.`fileid`) AS `id`,FC.`name` 
						FROM `*PREFIX*audioplayer_tracks` AT
						JOIN `*PREFIX*filecache` FC
						on FC.`fileid` = AT.`folder_id`
			 			WHERE  AT.`user_id` = ?
			 			ORDER BY LOWER(FC.`name`) ASC
			 			";
		}	
			
		$stmt =$this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		$aPlaylists='';
		while( $row = $result->fetchRow()) {
			$bg = $this->genColorCodeFromText(trim($row['name']),40,8);
			$row['backgroundColor']=$bg;
			$row['color']=$this->generateTextColor($bg);
			$aPlaylists[]=$row;
		}
		
		if(is_array($aPlaylists)){
			return $aPlaylists;
		}else{
			return false;
		}
	}
	
	private function getSongIdsForCategory($category,$categoryId){

		if($category === 'Artist') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AT`.`artist_id` = ? 
			 		AND `AT`.`user_id` = ?
			 		ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'Genre') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`genre_id` = ?  
					AND `AT`.`user_id` = ?
					ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'Year') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`year` = ? 
					AND `AT`.`user_id` = ?
					ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'All') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`id` > ? 
					AND `AT`.`user_id` = ? 
					ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'Playlist') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
					LEFT JOIN `*PREFIX*audioplayer_tracks` `AT` ON `AP`.`track_id` = `AT`.`id`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AP`.`playlist_id` = ?
			 		ORDER BY `AP`.`sortorder` ASC
			 		";
		} elseif ($category === 'Folder') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`folder_id` = ? 
					AND `AT`.`user_id` = ?
					ORDER BY `AT`.`title` ASC";
		}

		$stmt = $this->db->prepareQuery($SQL);
		if ($category === 'Playlist') {
			$result = $stmt->execute(array($categoryId));
		} else {
				$result = $stmt->execute(array($categoryId, $this->userId));
		}
		$aTracks=[];
		while( $row = $result->fetchRow()) {
		
			try {
				$path = \OC\Files\Filesystem::getPath($row['file_id']);
			} catch (\Exception $e) {
				$file_not_found = true;
       		}
			$row['link'] = \OC::$server->getURLGenerator()->linkToRoute('audioplayer.music.getAudioStream').'?file='.rawurlencode($path);

			//$aTracks[]=$row['id'];
			$aTracks[]=$row;
		}
		
		
		return $aTracks;
		
	}
	
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
