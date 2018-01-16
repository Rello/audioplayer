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
use OCP\L10N\IFactory;
use OCP\IDbConnection;
use OCP\Files\IRootFolder;
use \OC\Files\View; //remove when editAudioFiles is updated and toTmpFile alternative
use OCP\ILogger;

/**
 * Controller class for main page.
 */
class EditorController extends Controller {
	
	private $userId;
	private $l10n;
	private $db;
	private $iAlbumCount = 0;
	private $languageFactory;
	private $rootFolder;
    private $logger;
		public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDbConnection $db, 
			IFactory $languageFactory,
			IRootFolder $rootFolder,
            ILogger $logger
        ) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->languageFactory = $languageFactory;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
	}
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function editAudioFile($songFileId) {
		$resultData = [];
        $this->logger->debug('songFileId: '.$songFileId, array('app' => 'audioplayer'));

		if (!class_exists('getid3_exception')) {
			require_once __DIR__.'/../../3rdparty/getid3/getid3.php';
		}
		
		$userView = new View('/'.$this -> userId.'/files');
		$path = $userView->getPath($songFileId);
		$fileInfo = $userView -> getFileInfo($path);
		
		if ($fileInfo['permissions'] & \OCP\Constants::PERMISSION_UPDATE) {
		
			$localFile = $userView->getLocalFile($path);
			$getID3 = new \getID3;
			$ThisFileInfo = $getID3->analyze($localFile);
			\getid3_lib::CopyTagsToComments($ThisFileInfo);
			$resultData['localPath'] = $path;
			$resultData['title'] = $fileInfo['name'];
			if (isset($ThisFileInfo['comments']['title'][0])) {
				$resultData['title'] = $ThisFileInfo['comments']['title'][0];
			}
			$resultData['album'] = '';
			if (isset($ThisFileInfo['comments']['album'][0])) {
				$resultData['album'] = $ThisFileInfo['comments']['album'][0];
			}
			$resultData['genre'] = '';
			if (isset($ThisFileInfo['comments']['genre'][0])) {
				$resultData['genre'] = $ThisFileInfo['comments']['genre'][0];
			}
			$resultData['artist'] = '';
			if (isset($ThisFileInfo['comments']['artist'][0])) {
				$resultData['artist'] = $ThisFileInfo['comments']['artist'][0];
			}
			$resultData['year'] = '';
			if (isset($ThisFileInfo['comments']['year'][0])) {
				$resultData['year'] = $ThisFileInfo['comments']['year'][0];
			}
			$resultData['track'] = '';
			if (isset($ThisFileInfo['comments']['track_number'][0])) {
				$resultData['track'] = $ThisFileInfo['comments']['track_number'][0];
			}
			
			$resultData['subtitle'] = '';
			if (isset($ThisFileInfo['comments']['subtitle'][0])) {
				$resultData['subtitle'] = $ThisFileInfo['comments']['subtitle'][0];
			}

			$resultData['composer'] = '';
			if (isset($ThisFileInfo['comments']['composer'][0])) {
				$resultData['composer'] = $ThisFileInfo['comments']['composer'][0];
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
			if (isset($ThisFileInfo['comments']['picture'])) {
				$resultData['isPhoto'] = '1';	
				$data = $ThisFileInfo['comments']['picture'][0]['data'];
				$image = new \OCP\Image();
				if ($image->loadFromdata($data)) {
					if (($image->width() <= 150 && $image->height() <= 150) || $image->resize(150)) {
						\OC::$server->getCache()->set('edit-audioplayer-foto-'.$songFileId, $image -> data(), 600);	
						$imgString = $image->__toString();
						$resultData['mimeType'] = $ThisFileInfo['comments']['picture'][0]['image_mime'];
						$resultData['poster'] = $imgString;
					}
				}
				
			}
			
			$resultData['tmpkey'] = 'edit-audioplayer-foto-'.$songFileId;
			
			$SQL = "SELECT  `AA`.`id`,`AA`.`name` FROM `*PREFIX*audioplayer_albums` `AA`
				 			WHERE  `AA`.`user_id` = ?
				 			ORDER BY `AA`.`name` ASC
				 			";
				
			$stmt = $this->db->prepare($SQL);
			$stmt->execute(array($this->userId));			
			$rowAlbums = $stmt->fetchAll();
			array_unshift($rowAlbums, ['id' =>0, 'name' =>(string) $this->l10n->t('- choose -')]);
			$resultData['albums'] = $rowAlbums;
			 
			$SQL = "SELECT  `id`,`name` FROM `*PREFIX*audioplayer_artists` 
				 			WHERE  `user_id` = ? 
				 			ORDER BY `name` ASC
				 			";
			$stmt = $this->db->prepare($SQL);
			$stmt->execute(array($this->userId));			
			$rowArtists = $stmt->fetchAll();
			array_unshift($rowArtists, ['id' =>0, 'name' =>(string) $this->l10n->t('- choose -')]);
			$resultData['artists'] = $rowArtists;

			$SQL = "SELECT  `id`,`name` FROM `*PREFIX*audioplayer_genre` 
				 			WHERE  `user_id` = ? 
				 			ORDER BY `name` ASC
				 			";
				
			$stmt = $this->db->prepare($SQL);
			$stmt->execute(array($this->userId));
			$rowGenre = $stmt->fetchAll();
			array_unshift($rowGenre, ['id' =>0, 'name' =>(string) $this->l10n->t('- choose -')]);
			$resultData['genres'] = $rowGenre;
			 
			$result = [
				'status' => 'success',
				'data' => $resultData,
			];
			$response = new JSONResponse();		
			$response -> setData($result);
			return $response;
		} else {
			$result = [
				'status' => 'error',
			];
			$response = new JSONResponse();		
			$response -> setData($result);
			return $response;
		}
		
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function saveAudioFileData($songFileId, $trackId, $year, $title, $artist, $existartist, $album, $existalbum, $track, $tracktotal, $genre, $existgenre, $addcover, $imgsrc, $imgmime) {
		$pTrackId = $trackId;		
		$pYear = $year;
		$pTitle = $title;
		$pArtist = $artist;
		$pExistArtist = $existartist;
		$pAlbum = $album;
		$pExistAlbum = $existalbum;
		$pTrack = $track;
		$pTrackTotal = $tracktotal;
		$pGenre = $genre;
		$pExistGenre = $existgenre;
		$addCoverToAlbum = $addcover;
		$pImgSrc = $imgsrc;
		$pImgMime = $imgmime;
		$trackNumber = '';
		if (!empty($pTrack)) {
			$trackNumber = $pTrack.(!empty($pTrackTotal) ? '/'.$pTrackTotal : '');
		}
		
		if (!class_exists('getid3_exception')) {
			require_once __DIR__.'/../../3rdparty/getid3/getid3.php';
		}
		
		require_once __DIR__.'/../../3rdparty/getid3/write.php';
		
		$TextEncoding = 'UTF-8';
		$userView = new View('/'.$this -> userId.'/files');
		$path = $userView->getPath($songFileId);
		
		if (\OC\Files\Filesystem::isUpdatable($path)) {
			if ($pAlbum !== '') {
				$addAlbum = $pAlbum;
			} elseif ($pExistAlbum !== (string) $this->l10n->t('- choose -') && $pExistAlbum !== (string) $this->l10n->t('Unknown')) { 
				$addAlbum = $pExistAlbum;
			} else {
				$addAlbum = '';
			}
			
			if ($pArtist !== '') {
				$addArtist = $pArtist;
			} elseif ($pExistArtist !== (string) $this->l10n->t('- choose -') && $pExistArtist !== (string) $this->l10n->t('Unknown')) { 
				$addArtist = $pExistArtist;
			} else {
				$addArtist = '';
			}
			
			if ($pGenre !== '') {
				$addGenre = $pGenre;
			} elseif ($pExistGenre !== (string) $this->l10n->t('- choose -') && $pExistGenre !== (string) $this->l10n->t('Unknown')) { 
				$addGenre = $pExistGenre;
			} else {
				$addGenre = '';
			}
				
			$resultData = [
				'year' => [$pYear],
				'title' => [$pTitle],
				'artist' => [$addArtist],
				'album' => [$addAlbum],
				'track_number' => [$trackNumber],
				'genre' => [$addGenre]
				
			];
			$imgString = '';
			if ($pImgSrc !== '') {
				$image = new \OCP\Image();
				if ($image->loadFromBase64($pImgSrc)) {
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
				} else {
						
						$SQL = "SELECT `AT`.`album_id`,`AT`.`artist_id`,`AA`.`name`,`AR`.`name` AS artistname,`AT`.`genre_id`, `AG`.`name` AS genrename
									FROM `*PREFIX*audioplayer_tracks` `AT`
									LEFT JOIN  `*PREFIX*audioplayer_albums` `AA` ON `AT`.`album_id`= `AA`.`id`
									LEFT JOIN  `*PREFIX*audioplayer_artists` `AR` ON `AT`.`artist_id`= `AR`.`id`
									LEFT JOIN  `*PREFIX*audioplayer_genre` `AG` ON `AT`.`genre_id`= `AG`.`id`
						  			WHERE `AT`.`id` = ? AND `AT`.`user_id` = ?";	
						$stmt = $this->db->prepare($SQL);
						$stmt->execute(array($pTrackId, $this->userId));
						$row = $stmt->fetch();
						
						$albumName = $row['name'];
						$albumId = $row['album_id'];
						$artistName = $row['artistname'];
						$artistId = $row['artist_id'];
						$genreName = $row['genrename'];
						$genreId = $row['genre_id'];
						$newAlbumId = $albumId;
						
						if ($pGenre !== '') {
							$addGenre = $pGenre;
						} elseif ($pExistGenre !== (string) $this->l10n->t('- choose -')) { 
							$addGenre = $pExistGenre;
						} else {
							$addGenre = '';
						}
						if ($addGenre !== '' && $addGenre !== $genreName) {
							$genreId = $this->writeGenreToDB($addGenre);
						}	
			
						if ($pArtist !== '') {
							$addArtist = $pArtist;
						} elseif ($pExistArtist !== (string) $this->l10n->t('- choose -')) { 
							$addArtist = $pExistArtist;
						} else {
							$addArtist = '';
						}
						if ($addArtist !== '' && $addArtist !== $artistName) {
							$artistId = $this->writeArtistToDB($addArtist);
						}
						if ($pAlbum !== '') {
							$addAlbum = $pAlbum;
						} elseif ($pExistAlbum !== (string) $this->l10n->t('- choose -')) { 
							$addAlbum = $pExistAlbum;
						} else {
							$addAlbum = '';
						}
						if ($addAlbum !== '' && $addAlbum !== $albumName) {
							$newAlbumId = $this->writeAlbumToDB($addAlbum, $pYear, $artistId);
							
							//check for other songs if not then delete album
							$stmt = $this->db->prepare('SELECT COUNT(`album_id`) AS `ALBUMCOUNT`  FROM `*PREFIX*audioplayer_tracks` WHERE `album_id` = ?');
							$stmt->execute(array($albumId));
							$rowAlbum = $stmt->fetch();
							if ((int) $rowAlbum['ALBUMCOUNT'] === 1) {
								$stmt = $this->db->prepare('DELETE FROM `*PREFIX*audioplayer_albums` WHERE `id` = ? AND `user_id` = ?');
								$stmt->execute(array($albumId, $this->userId));
							}
							
							
						}
						
						$returnData = array();
						$returnData['imgsrc'] = '';
						$returnData['prefcolor'] = '';
						if ($pImgMime !== '' && $addCoverToAlbum === 'true') {
							$this->writeCoverToAlbum($newAlbumId, $imgString);
							$returnData['imgsrc'] = 'data:image/jpg;base64,'.$imgString;
						}
					
					$returnData['albumname'] = $addAlbum;
					$returnData['albumid'] = $newAlbumId;
					$returnData['oldalbumid'] = $albumId;
					
					$SQL = "UPDATE `*PREFIX*audioplayer_tracks` SET `title`= ?, `album_id`= ?, `artist_id`= ?, `number`= ?, `genre_id`= ? WHERE `id` = ? AND `user_id` = ?";	
					$stmt = $this->db->prepare($SQL);
					$stmt->execute(array($pTitle, $newAlbumId, $artistId, (int) $pTrack, $genreId, $pTrackId, $this->userId));
						
					$result = [
						'status' => 'success',
						'data' => $returnData,
					];
				}
			} else {
				if (is_array($tagwriter->errors)) {
                    $tagwriter->errors = implode("\n", $tagwriter->errors);
                }
                $this->logger->debug($tagwriter->errors, array('app' => 'audioplayer'));

				$result = [
						'status' => 'error',
						'msg' => (string) $tagwriter->errors,
					];
			}
		} else {
			$result = [
						'status' => 'error_write',
						'msg' => 'not writeable',
					];
		}
		
		$response = new JSONResponse();		
		$response -> setData($result);
		return $response;
		
	}
	
	private function writeCoverToAlbum($iAlbumId, $sImage) {
    		
		$stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `cover`= ?, `bgcolor`= ? WHERE `id` = ? AND `user_id` = ?');
		$stmt->execute(array($sImage, '', $iAlbumId, $this->userId));
		return true;
	}
	
	/**
	 * Add album to db if not exist
	 * 
	 *@param string $sAlbum
	 *@param string $sYear
	 *@param int $iArtistId
	 * 
	 * @return int id
	 */
	
	private function writeAlbumToDB($sAlbum, $sYear, $iArtistId) {
		$sAlbum = $this->truncate($sAlbum, '256');	
		$sYear = $this->normalizeInteger($sYear);			
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_albums', ['user_id' => $this->userId, 'name' => $sAlbum])) {
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_albums');
			if ($iArtistId) {
				$stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `year`= ?, `artist_id`= ? WHERE `id` = ? AND `user_id` = ?');
				$stmt->execute(array((int) $sYear, $iArtistId, $insertid, $this->userId));
			} else {
				$stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `year`= ? WHERE `id` = ? AND `user_id` = ?');					
				$stmt->execute(array((int) $sYear, $insertid, $this->userId));
			} 
			$this->iAlbumCount++;
			return $insertid;
		} else {
			$stmt = $this->db->prepare('SELECT `id`, `artist_id` FROM `*PREFIX*audioplayer_albums` WHERE `user_id` = ? AND `name` = ?');
			$stmt->execute(array($this->userId, $sAlbum));
			$row = $stmt->fetch();
			if ((int) $row['artist_id'] !== (int) $iArtistId) {
				$various_id = $this->writeArtistToDB($this->l10n->t('Various Artists'));
				$stmt = $this->db->prepare('UPDATE `*PREFIX*audioplayer_albums` SET `artist_id`= ? WHERE `id` = ? AND `user_id` = ?');					
				$stmt->execute(array($various_id, $row['id'], $this->userId));
			} 
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
	 
	private function writeGenreToDB($sGenre) {
		$sGenre = $this->truncate($sGenre, '256');		
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_genre', ['user_id' => $this->userId, 'name' => $sGenre])) {
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_genre');
			return $insertid;
		} else {
			$stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_genre` WHERE `user_id` = ? AND `name` = ?');
			$stmt->execute(array($this->userId, $sGenre));
			$row = $stmt->fetch();
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
	private function writeArtistToDB($sArtist) {
		$sArtist = $this->truncate($sArtist, '256');
		if ($this->db->insertIfNotExist('*PREFIX*audioplayer_artists', ['user_id' => $this->userId, 'name' => $sArtist])) {
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_artists');
			return $insertid;
		} else {
			$stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_artists` WHERE `user_id` = ? AND `name` = ?');
			$stmt->execute(array($this->userId, $sArtist));
			$row = $stmt->fetch();
			return $row['id'];
		}
	}
			
	/**
	 * truncates fiels do DB-field size
	 * 
	 * @param string $string
	 * @param string $length
	 * @param $dots
	 * @return string
	 */
	private function truncate($string, $length, $dots = "...") {
		return (strlen($string) > $length) ? mb_strcut($string, 0, $length - strlen($dots)) . $dots : $string;
	}

	/**
	 * validate unsigned int values
	 *
	 * @param string $value
	 * @return int value
	 */

	private function normalizeInteger($value) {
		// convert format '1/10' to '1' and '-1' to null
		$tmp = explode('/', $value);
		$tmp = explode('-', $tmp[0]);
		$value = $tmp[0];
		if (is_numeric($value) && ((int) $value) > 0) {
			$value = (int) $value;
		} else {
			$value = 0;
		}
		return $value;
	}				
}
