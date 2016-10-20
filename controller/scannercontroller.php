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
use \OC\Files\View;
use \OCP\IConfig;

/**
 * Controller class for main page.
 */
class ScannerController extends Controller {
	
	private $userId;
	private $l10n;
	private $path;
	private $abscount = 0;
	private $progress;
	private $progresskey;
	private $currentSong;
	private $iDublicate = 0;
	private $iAlbumCount = 0;
	private $numOfSongs;
	private $db;
	private $configManager;

	public function __construct($appName, IRequest $request, $userId, $l10n, $db, IConfig $configManager) {
		parent::__construct($appName, $request);
		$this->appname = $appName;
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->configManager = $configManager;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function editAudioFile() {
		$songFileId=(int)$this->params('songFileId');
		$resultData=[];
		
		#if(!class_exists('getid3_exception')) {
			require_once __DIR__ . '/../3rdparty/getid3/getid3.php';
		#}
		
		$userView =  new View('/' . $this -> userId. '/files');
		$path = $userView->getPath($songFileId);
		$fileInfo = $userView -> getFileInfo($path);
		
		if($fileInfo['permissions'] & \OCP\PERMISSION_UPDATE){
		
			$localFile = $userView->getLocalFile($path);
			//\OCP\Util::writeLog('audioplayer','local: '.$path,\OCP\Util::DEBUG);
			$getID3 = new \getID3;
			$ThisFileInfo = $getID3->analyze($localFile);
			\getid3_lib::CopyTagsToComments($ThisFileInfo);
			$resultData['localPath'] = $path;
			$resultData['title'] = $fileInfo['name'];
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
			$resultData['track'] = '';
			if(isset($ThisFileInfo['comments']['track_number'][0])){
				$resultData['track'] = $ThisFileInfo['comments']['track_number'][0];
			}
			
			$resultData['tracktotal'] = '';
			$resultData['track'] = '';
			
			if (!empty($ThisFileInfo['comments']['track_number']) && is_array($ThisFileInfo['comments']['track_number'])) {
				$RawTrackNumberArray = $ThisFileInfo['comments']['track_number'];
			} elseif (!empty($ThisFileInfo['comments']['track']) && is_array($ThisFileInfo['comments']['track'])) {
				$RawTrackNumberArray = $ThisFileInfo['comments']['track'];
			} else {
				$RawTrackNumberArray = array();
			}
			
			foreach ($RawTrackNumberArray as $key => $value) {
				if (strlen($value) > strlen($resultData['track'])) {
					// ID3v1 may store track as "3" but ID3v2/APE would store as "03/16"
					$resultData['track'] = $value;
				}
			}
			if (strstr($resultData['track'], '/')) {
				list($resultData['track'], $resultData['tracktotal']) = explode('/', $resultData['track']);
			}
			
			$resultData['poster'] = '';
			$resultData['isPhoto'] = '0';
			$resultData['mimeType'] = '';
			if(isset($ThisFileInfo['comments']['picture'])){
				$resultData['isPhoto'] = '1';	
				$data = $ThisFileInfo['comments']['picture'][0]['data'];
				$image = new \OCP\Image();
				if($image->loadFromdata($data)) {
					if(($image->width() <= 150 && $image->height() <= 150) || $image->resize(150)) {
						\OC::$server->getCache()->set('edit-audioplayer-foto-' . $songFileId, $image -> data(), 600);	
						$imgString = $image->__toString();
						$resultData['mimeType'] = $ThisFileInfo['comments']['picture'][0]['image_mime'];
						$resultData['poster'] = $imgString;
					}
				}
				
			}
			
			$resultData['tmpkey'] = 'edit-audioplayer-foto-' . $songFileId;
			
			$SQL="SELECT  `AA`.`id`,`AA`.`name` FROM `*PREFIX*audioplayer_albums` `AA`
				 			WHERE  `AA`.`user_id` = ?
				 			ORDER BY `AA`.`name` ASC
				 			";
				
			$stmt = $this->db->prepareQuery($SQL);
			$result = $stmt->execute(array($this->userId));			
			$rowAlbums = $result->fetchAll();
			array_unshift($rowAlbums,['id' =>0,'name' =>(string)$this->l10n->t('- choose -')]);
			$resultData['albums']=$rowAlbums;
			 

			$SQL1="SELECT  `id`,`name` FROM `*PREFIX*audioplayer_artists` 
				 			WHERE  `user_id` = ? 
				 			ORDER BY `name` ASC
				 			";
				
			$stmt1 = $this->db->prepareQuery($SQL1);
			$result1 = $stmt1->execute(array($this->userId));			
			$rowArtists = $result1->fetchAll();
			array_unshift($rowArtists,['id' =>0,'name' =>(string)$this->l10n->t('- choose -')]);
			$resultData['artists'] = $rowArtists;


			$SQL2="SELECT  `id`,`name` FROM `*PREFIX*audioplayer_genre` 
				 			WHERE  `user_id` = ? 
				 			ORDER BY `name` ASC
				 			";
				
			$stmt2 = $this->db->prepareQuery($SQL2);
			$result2 = $stmt2->execute(array($this->userId));
			$rowGenre = $result2->fetchAll();
			array_unshift($rowGenre,['id' =>0,'name' =>(string)$this->l10n->t('- choose -')]);
			$resultData['genres'] = $rowGenre;
			 
			 //Genre
			# $ArrayOfGenresTemp = \getid3_id3v1::ArrayOfGenres();   // get the array of genres
			#$ArrayOfGenres[] = ['name' =>(string)$this->l10n->t('- choose -')];
			#foreach ($ArrayOfGenresTemp as $key => $value) {      // change keys to match displayed value
			#	$ArrayOfGenres[] = ['name' => $value];
			#}
			#
			#unset($ArrayOfGenresTemp);                            // remove temporary array
			#
			#usort($ArrayOfGenres,array('OCA\audioplayer\Controller\ScannerController','compareGenreNames'));   
			#                   
			# $resultData['genres'] = $ArrayOfGenres;
			 
			$result = [
				'status' => 'success',
				'data' => $resultData,
			];
			$response = new JSONResponse();		
			$response -> setData($result);
			return $response;
		}else{
			$result = [
				'status' => 'error',
			];
			$response = new JSONResponse();		
			$response -> setData($result);
			return $response;
		}
		
	}
	
	public static function compareGenreNames($a, $b) {
			return \OCP\Util::naturalSortCompare($a['name'], $b['name']);
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function saveAudioFileData() {
		
		$songFileId=$this->params('songFileId');
		$pTrackId = $this->params('trackId');
		
		$pYear=$this->params('year');
		$pTitle=$this->params('title');
		$pArtist=$this->params('artist');
		$pExistArtist = $this->params('existartist');
		
		$pAlbum=$this->params('album');
		$pExistAlbum=$this->params('existalbum');
		$pTrack=$this->params('track');
		$pTrackTotal=$this->params('tracktotal');
		$pGenre = $this->params('genre');
		$pExistGenre = $this->params('existgenre');
		
		$addCoverToAlbum = $this->params('addcover');
		
		$pImgSrc=$this->params('imgsrc');
		$pImgMime=$this->params('imgmime');

		$trackNumber = '';
		if (!empty($pTrack)) {
			$trackNumber = $pTrack.(!empty($pTrackTotal) ? '/'.$pTrackTotal : '');
		}
		
		#if(!class_exists('getid3_exception')) {
			require_once __DIR__ . '/../3rdparty/getid3/getid3.php';
		#}
		
		require_once __DIR__ . '/../3rdparty/getid3/write.php';
		
		$TextEncoding = 'UTF-8';
		$userView =  new View('/' . $this -> userId. '/files');
		$path= $userView->getPath($songFileId);
		
		if(\OC\Files\Filesystem::isUpdatable($path)){
			if($pAlbum !== ''){
				$addAlbum = $pAlbum;
			} elseif ($pExistAlbum !== (string)$this->l10n->t('- choose -') && $pExistAlbum !== (string)$this->l10n->t('Unknown')) { 
				$addAlbum = $pExistAlbum;
			} else {
				$addAlbum = '';
			}
			
			if($pArtist !== ''){
				$addArtist = $pArtist;
			} elseif ($pExistArtist !== (string)$this->l10n->t('- choose -') && $pExistArtist !== (string)$this->l10n->t('Unknown')) { 
				$addArtist = $pExistArtist;
			} else {
				$addArtist = '';
			}
			
			if($pGenre !== ''){
				$addGenre = $pGenre;
			} elseif ($pExistGenre !== (string)$this->l10n->t('- choose -') && $pExistGenre !== (string)$this->l10n->t('Unknown')) { 
				$addGenre = $pExistGenre;
			} else {
				$addGenre = '';
			}
				
			$resultData=[
				'year' => [$pYear],
				'title' => [$pTitle],
				'artist' => [$addArtist],
				'album' => [$addAlbum],
				'track_number' => [$trackNumber],
				'genre' => [$addGenre]
				
			];
			$imgString = '';
			if($pImgSrc !== ''){
				$image = new \OCP\Image();
				if($image->loadFromBase64($pImgSrc)) {
					$imgString = $image ->__toString();	
					$resultData['attached_picture'][0]['data']          = $image -> data();
					$resultData['attached_picture'][0]['picturetypeid'] = 3;
					$resultData['attached_picture'][0]['description']   = 'Cover Image';
					$resultData['attached_picture'][0]['mime']          = $pImgMime;
				}
			}
			$getID3 = new \getID3;
			$getID3->setOption(array('encoding'=>$TextEncoding));
			
			$tagwriter = new \getid3_writetags;
			$localFile = $userView->getLocalFile($path);
			//\OCP\Util::writeLog('audioplayer','local: '.$localFile,\OCP\Util::DEBUG);
			$tagwriter->filename = $localFile;
			$tagwriter->tagformats = array('id3v2.3');
			$tagwriter->overwrite_tags    = true;
			$tagwriter->remove_other_tags = true;
			$tagwriter->tag_encoding      = $TextEncoding;
			
			$tagwriter->tag_data = $resultData;
			
			if ($tagwriter->WriteTags()) {
				if (!empty($tagwriter->warnings)) {
					$result = [
						'status' => 'error',
						'msg' => (string) $tagwriter->warnings,
					];
				}else{
						
					$albumId = 0;
					$artistId = 0;
					
						$SQL="SELECT `AT`.`album_id`,`AT`.`artist_id`,`AA`.`name`,`AR`.`name` AS artistname,`AT`.`genre_id`, `AG`.`name` AS genrename
									FROM `*PREFIX*audioplayer_tracks` `AT`
									LEFT JOIN  `*PREFIX*audioplayer_albums` `AA` ON `AT`.`album_id`= `AA`.`id`
									LEFT JOIN  `*PREFIX*audioplayer_artists` `AR` ON `AT`.`artist_id`= `AR`.`id`
									LEFT JOIN  `*PREFIX*audioplayer_genre` `AG` ON `AT`.`genre_id`= `AG`.`id`
						  			WHERE `AT`.`id` = ? AND `AT`.`user_id` = ?";	
						$stmt = $this->db->prepareQuery($SQL);
						$result = $stmt->execute(array($pTrackId, $this->userId));
						$row = $result->fetchRow()	;
						
						$albumName = $row['name'];
						$albumId = $row['album_id'];
						$artistName = $row['artistname'];
						$artistId = $row['artist_id'];
						$genreName = $row['genrename'];
						$genreId = $row['genre_id'];
						$newAlbumId = $albumId;
						

						if($pGenre !== ''){
							$addGenre = $pGenre;
						} elseif ($pExistGenre !== (string)$this->l10n->t('- choose -')) { 
							$addGenre = $pExistGenre;
						} else {
							$addGenre = '';
						}

						if($addGenre !== '' && $addGenre !== $genreName){
							$genreId = $this->writeGenreToDB($addGenre);
						}	
			
						if($pArtist !== ''){
							$addArtist = $pArtist;
						} elseif ($pExistArtist !== (string)$this->l10n->t('- choose -')) { 
							$addArtist = $pExistArtist;
						} else {
							$addArtist = '';
						}

						if($addArtist !== '' && $addArtist !== $artistName){
							$artistId = $this->writeArtistToDB($addArtist);
						}

						if($pAlbum !== ''){
							$addAlbum = $pAlbum;
						} elseif ($pExistAlbum !== (string)$this->l10n->t('- choose -')) { 
							$addAlbum = $pExistAlbum;
						} else {
							$addAlbum = '';
						}

						if($addAlbum !== '' && $addAlbum !== $albumName){
							$newAlbumId = $this->writeAlbumToDB($addAlbum,$pYear,$artistId);
							
							//check for other songs if not then delete album
							$stmtCountAlbum = $this->db->prepareQuery( 'SELECT COUNT(`album_id`) AS `ALBUMCOUNT`  FROM `*PREFIX*audioplayer_tracks` WHERE `album_id` = ?' );
							$resultAlbumCount = $stmtCountAlbum->execute(array($albumId));
							$rowAlbum = $resultAlbumCount->fetchRow();
							if((int)$rowAlbum['ALBUMCOUNT'] === 1){
								$stmt2 = $this->db->prepareQuery( 'DELETE FROM `*PREFIX*audioplayer_albums` WHERE `id` = ? AND `user_id` = ?' );
								$stmt2->execute(array($albumId, $this->userId));
							}
							
							
						}
												
						$returnData['imgsrc']='';
						$returnData['prefcolor'] = '';
						if($pImgMime !== '' && $addCoverToAlbum === 'true'){
							$getDominateColor = $this->getDominateColorOfImage($imgString);
							$this->writeCoverToAlbum($newAlbumId,$imgString,$getDominateColor);
							
							$returnData['prefcolor'] = 'rgba('.$getDominateColor['red'].','.$getDominateColor['green'].','.$getDominateColor['blue'].',0.7)';
							$returnData['imgsrc'] = 'data:image/jpg;base64,'.$imgString;
						}
					
					$returnData['albumname'] = $addAlbum;
					$returnData['albumid'] = $newAlbumId;
					$returnData['oldalbumid'] = $albumId;
					
					$SQL="UPDATE `*PREFIX*audioplayer_tracks` SET `title`= ?, `album_id`= ?, `artist_id`= ?, `number`= ?, `genre_id`= ? WHERE `id` = ? AND `user_id` = ?";	
					$stmt = $this->db->prepareQuery($SQL);
					$result = $stmt->execute(array($pTitle, $newAlbumId, $artistId,(int)$pTrack, $genreId, $pTrackId, $this->userId));
						
					$result = [
						'status' => 'success',
						'data' => $returnData,
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
	public function getImportTpl(){
		
		$params = [];	
		$response = new TemplateResponse('audioplayer', 'part.import',$params, '');  
        
        return $response;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function scanForAudios() {
	
		$pProgresskey = $this -> params('progresskey');
		$pGetprogress = $this -> params('getprogress');
		\OC::$server->getSession()->close();
				
		if (isset($pProgresskey) && isset($pGetprogress)) {
				
				
				$aCurrent = \OC::$server->getCache()->get($pProgresskey);
				$aCurrent = json_decode($aCurrent);
				
				$numSongs = (isset($aCurrent->{'all'})?$aCurrent->{'all'}:0);
				$currentSongCount = (isset($aCurrent->{'current'})?$aCurrent->{'current'}:0);
				$currentSong = (isset($aCurrent->{'currentsong'})?$aCurrent->{'currentsong'}:'');
				$percent = (isset($aCurrent->{'percent'})?$aCurrent->{'percent'}:'');
			
				if($percent === ''){
					$percent = 0;
				}
				$params = [
					'status' => 'success',
					'percent' =>$percent ,
					'currentmsg' => $currentSong.' '.$percent.'% ('.$currentSongCount.'/'.$numSongs.')'
				];
				$response = new JSONResponse($params);
				return $response;	
		}
		
#		if(!class_exists('getid3_exception')) {
			require_once __DIR__ . '/../3rdparty/getid3/getid3.php';
#		}

        $userView =  new View('/' . $this -> userId . '/files');
		$audios_mp3 = $userView->searchByMime('audio/mpeg');
		$audios_m4a = $userView->searchByMime('audio/mp4');
		$audios_ogg = $userView->searchByMime('audio/ogg');
		$audios_wav = $userView->searchByMime('audio/wav');
		$audios = array_merge($audios_mp3, $audios_m4a, $audios_ogg, $audios_wav);

		$tempArray=array();
		
		$this->numOfSongs = count($audios);
		
		$this->progresskey = $pProgresskey;
		$currentIntArray=[
			'percent' => 0,
			'all' => $this->numOfSongs,
			'current' => 0,
			'currentsong' => ''
		];
		
		$currentIntArray = json_encode($currentIntArray);
		\OC::$server->getCache()->set($this->progresskey, $currentIntArray, 100);
		$counter = 0;
		$counter_new = 0;
		$error_count = 0;
		$error_file = 0;
		$debug_detail = \OC::$server->getConfig()->getSystemValue("audioplayer_debug");
		$cyrillic_support = $this->configManager->getUserValue($this->userId, $this->appname, 'cyrillic');

		$TextEncoding 		= 'UTF-8';
		$option_tag_id3v1   = false;  // Read and process ID3v1 tags
		$option_tag_id3v2   = true;  // Read and process ID3v2 tags
		$option_tag_lyrics3       = false;  // Read and process Lyrics3 tags
		$option_tag_apetag        = false;  // Read and process APE tags
		$option_tags_process      = true;  // Copy tags to root key 'tags' and encode to $this->encoding
		$option_tags_html         = false;  // Copy tags to root key 'tags_html' properly translated from various encodings to HTML entities

		$getID3 = new \getID3;
		$getID3->setOption(array('encoding'=>$TextEncoding, 
								'option_tag_id3v1'=>$option_tag_id3v1, 
								'option_tag_id3v2'=>$option_tag_id3v2,
								'option_tag_lyrics3'=>$option_tag_lyrics3,
								'option_tag_apetag'=>$option_tag_apetag,
								'option_tags_process'=>$option_tags_process,
								'option_tags_html'=>$option_tags_html
								));
								
		foreach($audios as $audio) {
		  	
			register_shutdown_function(array($this, 'shutDownFunction'), $audio['path']);		
			if ($debug_detail === true) {
				#\OCP\Util::writeLog('audioplayer', 'file-id: '.$audio['fileid'].' track path : '.$audio['path'], \OCP\Util::DEBUG);
			}
			
			if($this->checkIfTrackDbExists($audio['fileid']) === false){
				
				$fileName = $userView->toTmpFile($audio['path']);		
				$ThisFileInfo = $getID3->analyze($fileName);
				unlink($fileName);
			
				// Cyrillic id3 workaround
				// Tag is 1251 if there are 4+ upper half of ASCII table symbols glued together.
				if($cyrillic_support === 'checked') {
					#\OCP\Util::writeLog('audioplayer', 'cyrillic', \OCP\Util::DEBUG);				
					// Check, if this tag was win1251 before the incorrect "8859->utf" convertion by the getid3 lib
					foreach (array('id3v1', 'id3v2') as $ttype) {
						$ruTag = 0;
						if (isset($ThisFileInfo['tags'][$ttype])) {
							// Check, if this tag was win1251 before the incorrect "8859->utf" convertion by the getid3 lib
							foreach (array('album', 'artist', 'title', 'band', 'genre') as $tkey) {
								if(isset($ThisFileInfo['tags'][$ttype][$tkey])) {
									if (preg_match('#[\\xA8\\B8\\x80-\\xFF]{4,}#', iconv('UTF-8', 'ISO-8859-1', $ThisFileInfo['tags'][$ttype][$tkey][0]))) {
										$ruTag = 1;
										break;
									}
								}
							}	
							// Now make a correct conversion
							if($ruTag === 1) {
								foreach (array('album', 'artist', 'title', 'band') as $tkey) {
									if(isset($ThisFileInfo['tags'][$ttype][$tkey])) {
										$ThisFileInfo['tags'][$ttype][$tkey][0] = iconv('UTF-8', 'ISO-8859-1', $ThisFileInfo['tags'][$ttype][$tkey][0]);
										$ThisFileInfo['tags'][$ttype][$tkey][0] = iconv('Windows-1251', 'UTF-8', $ThisFileInfo['tags'][$ttype][$tkey][0]);
									}
								}
							}
						}
					}
				}				


				\getid3_lib::CopyTagsToComments($ThisFileInfo);

				# catch issue when getID3 does not bring a result in case of corrupt file or fpm-timeout
				if (!isset($ThisFileInfo['bitrate']) AND !isset($ThisFileInfo['playtime_string'])) {
					\OCP\Util::writeLog('audioplayer', 'Error with getID3. Does not seem to be a valid audio file: '.$audio['path'], \OCP\Util::DEBUG);
					$counter++;
					$this->abscount++;
					$this->updateProgress(intval(($this->abscount / $this->numOfSongs)*100));
					$error_file.=$audio['name'].'<br />';
					$error_count++;
					continue;
				}

				$album = (string) $this->l10n->t('Unknown');
				if(isset($ThisFileInfo['comments']['album'][0])){
					$album=$ThisFileInfo['comments']['album'][0];
				}

				$genre = (string) $this->l10n->t('Unknown');
				if(isset($ThisFileInfo['comments']['genre'][0])){
					$genre=$ThisFileInfo['comments']['genre'][0];
				}				
				$iGenreId= $this->writeGenreToDB($genre);
				
				$year = 0;
				if(isset($ThisFileInfo['comments']['year'][0])){
					$year=$ThisFileInfo['comments']['year'][0];
				}
								
				$artist = (string) $this->l10n->t('Unknown');
				if(isset($ThisFileInfo['comments']['artist'][0])){
					$artist=$ThisFileInfo['comments']['artist'][0];
				}
				$iArtistId= $this->writeArtistToDB($artist);
				
				# write albumartist if available
				# if no albumartist, no artist is stored
				# in musiccontroller loadArtistsToAlbum() we will use this
				# if no album artist is stored, load all artists from the tracks
				# if all the same - display it as album artist
				# if different track-artists, display "various"
				# if Album Artist is maintained
				if(isset($ThisFileInfo['comments']['band'][0])){
					$album_artist=$ThisFileInfo['comments']['band'][0];
					$iAlbumArtistId= $this->writeArtistToDB($album_artist);
					$iAlbumId = $this->writeAlbumToDB($album,(int)$year,$iAlbumArtistId);
				} else {
					$iAlbumId = $this->writeAlbumToDB($album,(int)$year,NULL);
				}

				$name = $audio['name'];
				if(isset($ThisFileInfo['comments']['title'][0])){
					$name=$ThisFileInfo['comments']['title'][0];
					
				}
				$this->currentSong = $name.' - '.$artist;
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
				
				$playTimeString = '';
				if(isset($ThisFileInfo['playtime_string'])){
					$playTimeString=$ThisFileInfo['playtime_string'];
				}

				$aTrack = [
					'title' => $name,
					'number' =>(int)$cleanTrackNumber,
					'artist_id' => (int)$iArtistId,
					'album_id' =>(int) $iAlbumId,
					'length' => $playTimeString,
					'file_id' => (int)$audio['fileid'],
					'bitrate' => (int)$bitrate,
					'mimetype' => $audio['mimetype'],
					'genre' => (int)$iGenreId,
					'year' => (int)$year,
				];
				
				$this->writeTrackToDB($aTrack);
				$counter_new++;
				
			}
			$counter++;
			$this->abscount++;
			$this->updateProgress(intval(($this->abscount / $this->numOfSongs)*100));
			
		}
		
		\OC::$server->getCache()->remove($this->progresskey);
		
		$message=(string)$this->l10n->t('Scanning finished!').'<br />';
		$message.=(string)$this->l10n->t('Audios found: ').$counter.'<br />';
		#$message.=(string)$this->l10n->t('Duplicates found: ').$this->iDublicate.'<br />';
		$message.=(string)$this->l10n->t('Written to music library: ').($counter_new - $this->iDublicate).'<br />';
		$message.=(string)$this->l10n->t('Albums found: ').$this->iAlbumCount.'<br />';
		if ($error_count>>0) {
			$message.='<br /><b>'.(string)$this->l10n->t('Errors: ').$error_count.'<br />';
			$message.=(string)$this->l10n->t('If rescan does not solve this problem the files are broken').'</b>';
			$message.='<br />'.$error_file.'<br />';
		}
		
		$result=[
				'status' => 'success',
				'message' => $message
			];
			
		$response = new JSONResponse();
		$response -> setData($result);
		return $response;
		
	}

	
	private function writeCoverToAlbum($iAlbumId,$sImage,$aBgColor){
    		
    	$stmtCount = $this->db->prepareQuery( 'SELECT `cover` FROM `*PREFIX*audioplayer_albums` WHERE `id` = ? AND `user_id` = ?' );
		$resultCount = $stmtCount->execute(array ($iAlbumId, $this->userId));
		$row = $resultCount->fetchRow();
		if($row['cover'] === null){
			$aBgColor=json_encode($aBgColor);
			$stmt = $this->db->prepareQuery( 'UPDATE `*PREFIX*audioplayer_albums` SET `cover`= ?, `bgcolor`= ? WHERE `id` = ? AND `user_id` = ?' );
			$result = $stmt->execute(array($sImage, $aBgColor, $iAlbumId, $this->userId));
			return true;
		}else{
			return false;
		}
    }
	
	/**
	 * Add album to db if not exist
	 * 
	 *@param string $sAlbum
	 *@param string $sYear
	 *@param int $iGenreId
	 *@param int $iArtistId
	 * 
	 * @return int id
	 */
	
	private function writeAlbumToDB($sAlbum,$sYear,$iArtistId){
		
			if ($this->db->insertIfNotExist('*PREFIX*audioplayer_albums', ['user_id' => $this->userId, 'name' => $sAlbum])) {
				$insertid = $this->db->getInsertId('*PREFIX*audioplayer_albums');
				if ($iArtistId) {
					$stmt = $this->db->prepareQuery( 'UPDATE `*PREFIX*audioplayer_albums` SET `year`= ?, `artist_id`= ? WHERE `id` = ? AND `user_id` = ?' );
					$stmt->execute(array((int)$sYear, $iArtistId, $insertid, $this->userId));
				} else {
					$stmt = $this->db->prepareQuery( 'UPDATE `*PREFIX*audioplayer_albums` SET `year`= ? WHERE `id` = ? AND `user_id` = ?' );
					$stmt->execute(array((int)$sYear, $insertid, $this->userId));
				} 
				$this->iAlbumCount++;
				return $insertid;
			}else{
				$stmt = $this->db->prepareQuery( 'SELECT `id` FROM `*PREFIX*audioplayer_albums` WHERE `user_id` = ? AND `name` = ?' );
				$result = $stmt->execute(array($this->userId, $sAlbum));
				$row = $result->fetchRow();
				
				return $row['id'];
			}
		
	}
	
	/**
	 * Add genre to db if not exist
	 * 
	 *@param string $sGenre
	 *
	 * @return int id
	 */
	 
	private function writeGenreToDB($sGenre){
		//Test If exist
		
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_genre', ['user_id' => $this->userId, 'name' => $sGenre])) {
					
			$insertid = $this->db->getInsertId('*PREFIX*audioplayer_genre');
					
			return $insertid;
			
		}else{
			$stmt = $this->db->prepareQuery( 'SELECT `id` FROM `*PREFIX*audioplayer_genre` WHERE `user_id` = ? AND `name` = ?' );
			$result = $stmt->execute(array($this->userId, $sGenre));
			$row = $result->fetchRow();
			
			return $row['id'];
		}
		
	}
	
	
	/**
	 * Add artist to db if not exist
	 * 
	 *@param string $sArtist
	 *
	 * @return int id
	 */
	private function writeArtistToDB($sArtist){
		
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_artists', ['user_id' => $this->userId, 'name' => $sArtist])) {
					
			$insertid = $this->db->getInsertId('*PREFIX*audioplayer_artists');
					
			return $insertid;
			
		}else{
			$stmt = $this->db->prepareQuery( 'SELECT `id` FROM `*PREFIX*audioplayer_artists` WHERE `user_id` = ? AND `name` = ?' );
			$result = $stmt->execute(array($this->userId, $sArtist));
			$row = $result->fetchRow();
			
			return $row['id'];
		}
		
		
	}
	
	/**
	 * Add artist to album if not exist
	 * 
	 *@param int $iAlbumId
	 *@param int $iArtistId
	 *
	 * @return true
	 */
	private function writeArtistToAlbum($iAlbumId,$iArtistId){
		
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_album_artists', ['artist_id' => $iArtistId, 'album_id' => $iAlbumId])) {
					
			return true;
			
		}else{
			//we have an artist nothing to do	
			return true;
		}
		
		
	}
	
	/**
	 * Add track to db if not exist
	 * 
	 *@param array $aTrack
	 *
	 *
	 * @return id
	 */
	private function writeTrackToDB($aTrack){
			
		$SQL='SELECT id FROM *PREFIX*audioplayer_tracks WHERE `user_id`= ? AND `title`= ? AND `number`= ? AND `artist_id`= ? AND `album_id`= ? AND `length`= ? AND `bitrate`= ? AND `mimetype`= ? AND `genre_id`= ? AND `year`= ?';
		$stmt = $this->db->prepareQuery($SQL);
		
		if (strlen($aTrack['title']) > 256) {
			$aTrack['title'] = substr($aTrack['title'], 0, 256);
		}
		
		$result = $stmt->execute(array($this->userId, $aTrack['title'],$aTrack['number'],$aTrack['artist_id'],$aTrack['album_id'],$aTrack['length'],$aTrack['bitrate'],$aTrack['mimetype'],$aTrack['genre'],$aTrack['year']));
		$row = $result->fetchRow();
		if(isset($row['id'])){
			$this->iDublicate++;
		}else{
			$stmt = $this->db->prepareQuery( 'INSERT INTO `*PREFIX*audioplayer_tracks` (`user_id`,`title`,`number`,`artist_id`,`album_id`,`length`,`file_id`,`bitrate`,`mimetype`,`genre_id`,`year`) VALUES(?,?,?,?,?,?,?,?,?,?,?)' );
			$result = $stmt->execute(array($this->userId, $aTrack['title'], $aTrack['number'], $aTrack['artist_id'], $aTrack['album_id'], $aTrack['length'], $aTrack['file_id'], $aTrack['bitrate'], $aTrack['mimetype'],$aTrack['genre'],$aTrack['year']));
			$insertid = $this->db->getInsertId('*PREFIX*audioplayer_tracks');
			return $insertid;
		}
		
		
	}
	
	private function checkIfTrackDbExists($fileid){
		$stmtCount = $this->db->prepareQuery( 'SELECT `id` FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ? AND `file_id` = ? ' );
		$resultCount = $stmtCount->execute(array($this->userId, $fileid));
		$rowCount = $resultCount->rowCount();
		if($rowCount !== 0){
			return true;
		}else{
			return false;
		}
	}
	/*
	 * @brief updates the progress var
	 * @param integer $percentage
	 * @return boolean
	 */
	private function updateProgress($percentage) {
		$this->progress = $percentage;
		$currentIntArray=[
			'percent' => $this->progress,
			'all' => $this->numOfSongs,
			'current' => $this->abscount,
			'currentsong' => $this->currentSong
		];
		$currentIntArray = json_encode($currentIntArray);
		\OC::$server->getCache()->set($this->progresskey,$currentIntArray, 300);
		
		return true;
	}

	private function shutDownFunction($test) { 
	        $error = error_get_last();
		\OCP\Util::writeLog('audioplayer', 'Fatal Error with file: '.$test, \OCP\Util::DEBUG);
		\OCP\Util::writeLog('audioplayer', 'Audio Player can not continue scanning until this file is removed', \OCP\Util::DEBUG);
		\OCP\Util::writeLog('audioplayer', 'Error Message: '.$error['message'], \OCP\Util::DEBUG);
   	 	exit;
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
