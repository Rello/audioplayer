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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IDbConnection;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use \OC\Files\View; //remove when editAudioFiles is updated and toTmpFile alternative

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
	private $occ_job;
	private $no_fseek = false;
		public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDbConnection $db, 
			IConfig $configManager, 
			IFactory $languageFactory,
			IRootFolder $rootFolder
			) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
		$this->configManager = $configManager;
		$this->languageFactory = $languageFactory;
		$this->rootFolder = $rootFolder;
	}
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function editAudioFile($songFileId) {
		$resultData = [];
			\OCP\Util::writeLog('audioplayer', 'songFileId: '.$songFileId, \OCP\Util::DEBUG);
		
		if (!class_exists('getid3_exception')) {
			require_once __DIR__.'/../../3rdparty/getid3/getid3.php';
		}
		
		$userView = new View('/'.$this -> userId.'/files');
		$path = $userView->getPath($songFileId);
		$fileInfo = $userView -> getFileInfo($path);
		
		if ($fileInfo['permissions'] & \OCP\PERMISSION_UPDATE) {
		
			$localFile = $userView->getLocalFile($path);
			//\OCP\Util::writeLog('audioplayer','local: '.$path,\OCP\Util::DEBUG);
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
						\OCP\Util::writeLog('audioplayer', $tagwriter->errors, \OCP\Util::DEBUG);				

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
	public function scanForAudios($userId = null, $output = null, $debug = null, $progresskey, $scanstop) {

		$pProgresskey = $progresskey;
		$this->occ_job = false;
		
		if (isset($scanstop)) {
			\OC::$server->getCache()->remove($pProgresskey);
			$params = ['status' => 'stopped'];
			$response = new JSONResponse($params);
			return $response;				
		}
	
		// check if scanner is started from web or occ
		if ($userId !== null) {
			$this->occ_job = true;
			$this->userId = $userId;
			$languageCode = $this->configManager->getUserValue($userId, 'core', 'lang');
			$this->l10n = $this->languageFactory->get('audioplayer', $languageCode);
		} 
		
		$folderpicture = false;
		$this->progresskey = $pProgresskey;
		$parentId_prev = false;
		$counter = 0;
		$counter_new 			= 0;
		$error_count 			= 0;
		$error_file = 0;
		$this->iAlbumCount = 0;
		$this->iDublicate 		= 0;
		$cyrillic_support 		= $this->configManager->getUserValue($this->userId, $this->appName, 'cyrillic');
		$TextEncoding = 'UTF-8';
		$option_tag_id3v1   	= false; // Read and process ID3v1 tags
		$option_tag_id3v2   	= true; // Read and process ID3v2 tags
		$option_tag_lyrics3		= false; // Read and process Lyrics3 tags
		$option_tag_apetag      = false; // Read and process APE tags
		$option_tags_process    = true; // Copy tags to root key 'tags' and encode to $this->encoding
		$option_tags_html       = false; // Copy tags to root key 'tags_html' properly translated from various encodings to HTML entities
		
		if (!class_exists('getid3_exception')) {
			require_once __DIR__.'/../../3rdparty/getid3/getid3.php';
		}
		$getID3 = new \getID3;
		$getID3->setOption(array('encoding'=>$TextEncoding, 
								'option_tag_id3v1'=>$option_tag_id3v1, 
								'option_tag_id3v2'=>$option_tag_id3v2,
								'option_tag_lyrics3'=>$option_tag_lyrics3,
								'option_tag_apetag'=>$option_tag_apetag,
								'option_tags_process'=>$option_tags_process,
								'option_tags_html'=>$option_tags_html
								));

		// ??? to be checked why ???
		\OC::$server->getSession()->close();

		if (!$this->occ_job) $this->updateProgress(0, $output, $debug);
					
		// get only the relevant audio files
		$audios = $this->getAudioObjects($output, $debug);

		// get only the relevant stream files
		$streams = $this->getStreamObjects($output, $debug);
			    								
		if ($debug) $output->writeln("Start processing of <info>ID3s</info>");
		foreach ($audios as $audio) {
			
				//check if scan is still supposed to run, or if dialog was closed in web already
				if (!$this->occ_job) {
					$scan_running = \OC::$server->getCache()->get($pProgresskey);
					if (!$scan_running) break;
				}

				$this->currentSong = $audio->getPath();
				$this->updateProgress(intval(($this->abscount / $this->numOfSongs) * 100), $output, $debug);
				$counter++;
				$this->abscount++;

				$ThisFileInfo = $this->analyze($audio, $getID3, $output, $debug);				
				if ($cyrillic_support === 'checked') $ThisFileInfo = $this->cyrillic($ThisFileInfo);
				\getid3_lib::CopyTagsToComments($ThisFileInfo);

				# catch issue when getID3 does not bring a result in case of corrupt file or fpm-timeout
				if (!isset($ThisFileInfo['bitrate']) AND !isset($ThisFileInfo['playtime_string'])) {
					\OCP\Util::writeLog('audioplayer', 'Error with getID3. Does not seem to be a valid audio file: '.$audio->getPath(), \OCP\Util::DEBUG);
					if ($debug) $output->writeln("       Error with getID3. Does not seem to be a valid audio file");
					$error_file .= $audio->getName().'<br />';
					$error_count++;
					continue;
				}

				$album = (string) $this->l10n->t('Unknown');
				if (isset($ThisFileInfo['comments']['album'][0]) and rawurlencode($ThisFileInfo['comments']['album'][0]) !== '%FF%FE') {
					$album = $ThisFileInfo['comments']['album'][0];
				}

				$genre = (string) $this->l10n->t('Unknown');
				if (isset($ThisFileInfo['comments']['genre'][0])) {
					$genre = $ThisFileInfo['comments']['genre'][0];
				}				
				$iGenreId = $this->writeGenreToDB($genre);

				$year = 0;
				$keys = ['year', 'creation_date', 'date'];
				$c = count($keys);
				for ($i = 0; $i < $c; $i++) {
					if (isset($ThisFileInfo['comments'][$keys[$i]][0]) and rawurlencode($ThisFileInfo['comments'][$keys[$i]][0]) !== '%FF%FE') {
						$year = $ThisFileInfo['comments'][$keys[$i]][0];
						break;
					}
				}
								
				$artist = (string) $this->l10n->t('Unknown');
				if (isset($ThisFileInfo['comments']['artist'][0]) and rawurlencode($ThisFileInfo['comments']['artist'][0]) !== '%FF%FE') {
					$artist = $ThisFileInfo['comments']['artist'][0];
				}
				$iArtistId = $this->writeArtistToDB($artist);

				# write albumartist if available
				# if no albumartist, NO artist is stored on album level
				# in musiccontroller loadArtistsToAlbum() takes over deriving the artists from the album tracks
				# MP3, FLAC & MP4 have different tags for albumartist
				$iAlbumArtistId = NULL;
				$album_artist = NULL;				
				$keys = ['band', 'album_artist', 'albumartist', 'album artist'];
				$c = count($keys);
				for ($i = 0; $i < $c; $i++) {
					if (isset($ThisFileInfo['comments'][$keys[$i]][0]) and rawurlencode($ThisFileInfo['comments'][$keys[$i]][0]) !== '%FF%FE') {
						$album_artist = $ThisFileInfo['comments'][$keys[$i]][0];
						break;
					}
				}
				if (isset($album_artist)) { $iAlbumArtistId = $this->writeArtistToDB($album_artist); }
				$iAlbumId = $this->writeAlbumToDB($album, (int) $year, $iAlbumArtistId);

				$name = $audio->getName();
				if (isset($ThisFileInfo['comments']['title'][0]) and rawurlencode($ThisFileInfo['comments']['title'][0]) !== '%FF%FE') {
					$name = $ThisFileInfo['comments']['title'][0];
				}
				
				$trackNumber = '';
				if (isset($ThisFileInfo['comments']['track_number'][0])) {
					$trackNumber = $ThisFileInfo['comments']['track_number'][0];
				}
				
				$bitrate = 0;
				if (isset($ThisFileInfo['bitrate'])) {
					$bitrate = $ThisFileInfo['bitrate'];
				}
				
				$parentId = $audio->getParent()->getId();

				if ($parentId === $parentId_prev AND $folderpicture) {
					if ($debug) $output->writeln("     Reusing previous folder image");
					$this->getFolderPicture($iAlbumId, $folderpicture->getContent());
				} else {
					$folderpicture = false;
					if ($audio->getParent()->nodeExists('cover.jpg')) {
						$folderpicture = $audio->getParent()->get('cover.jpg');
					} elseif ($audio->getParent()->nodeExists('cover.png')) {
						$folderpicture = $audio->getParent()->get('cover.png');
					} elseif ($audio->getParent()->nodeExists('folder.jpg')) {
						$folderpicture = $audio->getParent()->get('folder.jpg');
					} elseif ($audio->getParent()->nodeExists('folder.png')) {
						$folderpicture = $audio->getParent()->get('folder.png');
					}
					
					if ($folderpicture) {
						$this->getFolderPicture($iAlbumId, $folderpicture->getContent());
						if ($debug) $output->writeln("     Alternative album art: ".$folderpicture->getInternalPath());
					} elseif (isset($ThisFileInfo['comments']['picture'])) {
						$data = $ThisFileInfo['comments']['picture'][0]['data'];
						$this->getFolderPicture($iAlbumId, $data);
					}					
					$parentId_prev = $parentId;
				}
				
				$playTimeString = '';
				if (isset($ThisFileInfo['playtime_string'])) {
					$playTimeString = $ThisFileInfo['playtime_string'];
				}
			
				$subtitle = '';
				$keys = ['subtitle', 'version'];
				$c = count($keys);
				for ($i = 0; $i < $c; $i++) {
					if (isset($ThisFileInfo['comments'][$keys[$i]][0]) and rawurlencode($ThisFileInfo['comments'][$keys[$i]][0]) !== '%FF%FE') {
						$subtitle = $ThisFileInfo['comments'][$keys[$i]][0];
						break;
					}
				}

				$composer = '';
				if (isset($ThisFileInfo['comments']['composer'][0]) and rawurlencode($ThisFileInfo['comments']['composer'][0]) !== '%FF%FE') {
					$composer = $ThisFileInfo['comments']['composer'][0];
				}

				# write discnumber if available
				# if no discumber, discnumber is set to 1
				# MP3, FLAC & MP4 have different tags for discnumber
				$disc = 1;
				$keys = ['part_of_a_set', 'discnumber', 'partofset', 'disc_number'];
				$c = count($keys);
				for ($i = 0; $i < $c; $i++) {
					if (isset($ThisFileInfo['comments'][$keys[$i]][0]) and rawurlencode($ThisFileInfo['comments'][$keys[$i]][0]) !== '%FF%FE') {
						$disc = $ThisFileInfo['comments'][$keys[$i]][0];
						break;
					}
				}

				$aTrack = [
					'title' => $this->truncate($name, '256'),
					'number' => $this->normalizeInteger($trackNumber),
					'artist_id' => (int) $iArtistId,
					'album_id' =>(int) $iAlbumId,
					'length' => $playTimeString,
					'file_id' => (int) $audio->getId(),
					'bitrate' => (int) $bitrate,
					'mimetype' => $audio->getMimetype(),
					'genre' => (int) $iGenreId,
					'year' => $this->truncate($this->normalizeInteger($year), 4, ''),
					'disc' => $this->normalizeInteger($disc),
					'subtitle' => $this->truncate($subtitle, '256'),
					'composer' => $this->truncate($composer, '256'),
					'folder_id' => $parentId,
				];
				
				$this->writeTrackToDB($aTrack);
				$counter_new++;
		}

		if ($debug) $output->writeln("Start processing of <info>Streams</info>");
		foreach ($streams as $stream) {
				//check if scan is still supposed to run, or if dialog was closed in web already
				if (!$this->occ_job) {
					$scan_running = \OC::$server->getCache()->get($pProgresskey);
					if (!$scan_running) break;
				}

				$this->currentSong = $stream->getPath();
				$this->updateProgress(intval(($this->abscount / $this->numOfSongs) * 100), $output, $debug);
				$counter++;
				$this->abscount++;

				$name = $stream->getName();
				$aStream = [
					'title' => $this->truncate($name, '256'),
					'artist_id' => 0,
					'album_id' => 0,
					'file_id' => (int) $stream->getId(),
					'bitrate' => 0,
					'mimetype' => $stream->getMimetype(),
				];
				$this->writeStreamToDB($aStream);
				$counter_new++;
		}

		
		$message = (string) $this->l10n->t('Scanning finished!').'<br />';
		$message .= (string) $this->l10n->t('Audios found: ').$counter.'<br />';
		$message .= (string) $this->l10n->t('Duplicates found: ').($this->iDublicate).'<br />';
		$message .= (string) $this->l10n->t('Written to music library: ').($counter_new - $this->iDublicate).'<br />';
		$message .= (string) $this->l10n->t('Albums found: ').$this->iAlbumCount.'<br />';
		if ($error_count >> 0) {
			$message .= '<br /><b>'.(string) $this->l10n->t('Errors: ').$error_count.'<br />';
			$message .= (string) $this->l10n->t('If rescan does not solve this problem the files are broken').'</b>';
			$message .= '<br />'.$error_file.'<br />';
		}
		
		$result = [
				'status' => 'success',
				'message' => $message
			];
			
		// different outputs when web or occ
		if (!$this->occ_job) { 
			\OC::$server->getCache()->remove($this->progresskey);
			$response = new JSONResponse();
			$response -> setData($result);
			return $response;
		} else {
			$output->writeln("Audios found: ".($counter)."");
			$output->writeln("Duplicates found: ".($this->iDublicate)."");
			$output->writeln("Written to music library: ".($counter_new - $this->iDublicate)."");
			$output->writeln("Albums found: ".($this->iAlbumCount)."");
			$output->writeln("Errors: ".($error_count)."");
		}
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
	 * Add track to db if not exist
	 * 
	 *@param array $aTrack
	 *
	 * @return null|integer
	 */
	private function writeTrackToDB($aTrack) {
			
		$SQL = 'SELECT id FROM *PREFIX*audioplayer_tracks WHERE `user_id`= ? AND `title`= ? AND `number`= ? 
				AND `artist_id`= ? AND `album_id`= ? AND `length`= ? AND `bitrate`= ? 
				AND `mimetype`= ? AND `genre_id`= ? AND `year`= ?
				AND `disc`= ? AND `composer`= ? AND `subtitle`= ?';
		$stmt = $this->db->prepare($SQL);
		$stmt->execute(array($this->userId, 
					 $aTrack['title'],
					 $aTrack['number'],
					 $aTrack['artist_id'],
					 $aTrack['album_id'],
					 $aTrack['length'],
					 $aTrack['bitrate'],
					 $aTrack['mimetype'],
					 $aTrack['genre'],
					 $aTrack['year'],
					 $aTrack['disc'],
					 $aTrack['composer'],
					 $aTrack['subtitle'],
				));
		$row = $stmt->fetch();
		if (isset($row['id'])) {
			$this->iDublicate++;
		} else {
			$stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_tracks` (`user_id`,`title`,`number`,`artist_id`,`album_id`,`length`,`file_id`,`bitrate`,`mimetype`,`genre_id`,`year`,`folder_id`,`disc`,`composer`,`subtitle`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$stmt->execute(array($this->userId, 
					 $aTrack['title'],
					 $aTrack['number'],
					 $aTrack['artist_id'],
					 $aTrack['album_id'],
					 $aTrack['length'],
					 $aTrack['file_id'],
					 $aTrack['bitrate'],
					 $aTrack['mimetype'],
					 $aTrack['genre'],
					 $aTrack['year'],
					 $aTrack['folder_id'],
					 $aTrack['disc'],
					 $aTrack['composer'],
					 $aTrack['subtitle'],
				));
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_tracks');
			return $insertid;
		}
	}

	/**
	 * Add stream to db if not exist
	 *@param array $aStream
	 *
	 * @return null|integer
	 */
	private function writeStreamToDB($aStream) {			
		$stmt = $this->db->prepare('SELECT `id` FROM `*PREFIX*audioplayer_streams` WHERE `user_id` = ? AND `file_id` = ? ');
		$stmt->execute(array($this->userId, $aStream['file_id']));
		$row = $stmt->fetch();
		if (isset($row['id'])) {
			$this->iDublicate++;
		} else {
			$stmt = $this->db->prepare('INSERT INTO `*PREFIX*audioplayer_streams` (`user_id`,`title`,`file_id`,`mimetype`) VALUES(?,?,?,?)');
			$stmt->execute(array($this->userId, 
					 $aStream['title'],
					 $aStream['file_id'],
					 $aStream['mimetype'],
			));
			$insertid = $this->db->lastInsertId('*PREFIX*audioplayer_streams');
			return $insertid;
		}
	}
	
	/**
	 * Report scanning Progress back to web frontend - e.g. progress bar
	 * @NoAdminRequired
	 * 
	 */
	public function getProgress($progresskey) {
		$pProgresskey = $progresskey;
		\OC::$server->getSession()->close();
					
		$aCurrent = \OC::$server->getCache()->get($pProgresskey);
		if ($aCurrent) {
				$aCurrent = json_decode($aCurrent);
				$numSongs = (isset($aCurrent->{'all'}) ? $aCurrent->{'all'}:0);
				$currentSongCount = (isset($aCurrent->{'current'}) ? $aCurrent->{'current'}:0);
				$currentSong = (isset($aCurrent->{'currentsong'}) ? $aCurrent->{'currentsong'}:'');
				$percent = (isset($aCurrent->{'percent'}) ? $aCurrent->{'percent'}:0);
			
				$params = ['status' => 'success', 'percent' =>$percent, 'msg' => $currentSong, 'prog' => $percent.'% ('.$currentSongCount.' / '.$numSongs.')'];
		} else {
			$params = ['status' => 'false'];
		}
		$response = new JSONResponse($params);
		return $response;	
	}

	/*
	 * @brief updates the progress var
	 * @param integer $percentage
	 * @return boolean
	 */
	private function updateProgress($percentage, $output = null, $debug = null) {
		$this->progress = $percentage;
		$currentIntArray = [
			'percent' => $this->progress,
			'all' => $this->numOfSongs,
			'current' => $this->abscount,
			'currentsong' => $this->currentSong
		];
		
		if (!$this->occ_job) {
			$currentIntArray = json_encode($currentIntArray);
			\OC::$server->getCache()->set($this->progresskey, $currentIntArray, 300);
		} elseif ($debug) {
			$output->writeln("   ".$this->currentSong."</info>");
		}
		return true;
	}

	/**
	 * Add track to db if not exist
	 * 
	 *@param array $ThisFileInfo
	 * @return array
	 */
	private function cyrillic($ThisFileInfo) {
		#\OCP\Util::writeLog('audioplayer', 'cyrillic', \OCP\Util::DEBUG);				
		// Check, if this tag was win1251 before the incorrect "8859->utf" convertion by the getid3 lib
		foreach (array('id3v1', 'id3v2') as $ttype) {
			$ruTag = 0;
			if (isset($ThisFileInfo['tags'][$ttype])) {
				// Check, if this tag was win1251 before the incorrect "8859->utf" convertion by the getid3 lib
				foreach (array('album', 'artist', 'title', 'band', 'genre') as $tkey) {
					if (isset($ThisFileInfo['tags'][$ttype][$tkey])) {
						if (preg_match('#[\\xA8\\B8\\x80-\\xFF]{4,}#', iconv('UTF-8', 'ISO-8859-1', $ThisFileInfo['tags'][$ttype][$tkey][0]))) {
							$ruTag = 1;
							break;
						}
					}
				}	
				// Now make a correct conversion
				if ($ruTag === 1) {
					foreach (array('album', 'artist', 'title', 'band', 'genre') as $tkey) {
						if (isset($ThisFileInfo['tags'][$ttype][$tkey])) {
							$ThisFileInfo['tags'][$ttype][$tkey][0] = iconv('UTF-8', 'ISO-8859-1', $ThisFileInfo['tags'][$ttype][$tkey][0]);
							$ThisFileInfo['tags'][$ttype][$tkey][0] = iconv('Windows-1251', 'UTF-8', $ThisFileInfo['tags'][$ttype][$tkey][0]);
						}
					}
				}
			}
		}
		return $ThisFileInfo;
	}			

	/**
	 * Add track to db if not exist
	 * 
	 *@param array $output
	 * @return array
	 */
	private function getAudioObjects($output = null, $debug = null) {

		$new_array = array();
		$audios_clean = array();
		$audioPath = $this->configManager->getUserValue($this->userId, $this->appName, 'path');
		$userView = $this->rootFolder->getUserFolder($this -> userId);

		if ($audioPath !== null && $audioPath !== '/' && $audioPath !== '') {
			$userView = $userView->get($audioPath);
		}

		$audios_mp3 = $userView->searchByMime('audio/mpeg');
		$audios_m4a = $userView->searchByMime('audio/mp4');
		$audios_ogg = $userView->searchByMime('audio/ogg');
		$audios_wav = $userView->searchByMime('audio/wav');
		$audios_flac = $userView->searchByMime('audio/flac');
		$audios = array_merge($audios_mp3, $audios_m4a, $audios_ogg, $audios_wav, $audios_flac);

		if ($debug) {
			$output->writeln("Scanned Folder: ".$userView->getPath());
		}
		if ($debug) {
			$output->writeln("Total audio files: ".count($audios));
		}

		// get all fileids which are in an excluded folder
			$stmt = $this->db->prepare('SELECT `fileid` from `*PREFIX*filecache` WHERE `parent` IN (SELECT `parent` FROM `*PREFIX*filecache` WHERE `name` = ? OR `name` = ? ORDER BY `fileid` ASC)');
			$stmt->execute(array('.noAudio', '.noaudio'));
			$results = $stmt->fetchAll();
			foreach ($results as $row) {
				array_push($new_array, $row['fileid']);
			}
			$resultExclude = $new_array;
			//if ($debug) $output->writeln("Excluded ids (.noAdmin): ".implode(",", $resultExclude));
		
		// get all fileids which are already in the Audio Player Database
			$new_array = array();
			$stmt = $this->db->prepare('SELECT `file_id` FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ? ');
			$stmt->execute(array($this->userId));
			$results = $stmt->fetchAll();
			foreach ($results as $row) {
				array_push($new_array, $row['file_id']);
			}
			$resultExisting = $new_array;
			$new_array = null;
			//if ($debug) $output->writeln("Existing ids (already scanned): ".implode(",", $resultExisting)."</info>");

		if ($debug) $output->writeln("Checking all files whether they can be <info>skipped</info>");
		foreach ($audios as $audio) {
			$current_id = $audio->getID();
			
			if (in_array($current_id, $resultExclude)) {
				if ($debug) $output->writeln("   ".$current_id." - ".$audio->getPath()."  => excluded");
			} elseif (in_array($current_id, $resultExisting)) {
				if ($debug) $output->writeln("   ".$current_id." - ".$audio->getPath()."  => already indexed");
			} else {
				array_push($audios_clean, $audio);
			} 
		}
		$this->numOfSongs = count($audios_clean);
		if ($debug) $output->writeln("Final audio files to be processed: ".$this->numOfSongs);
		return $audios_clean;
	}

	/**
	 * Add track to db if not exist
	 * 
	 *@param array $output
	 * @return array
	 */
	private function getStreamObjects($output = null, $debug = null) {

		$new_array = array();
		$audios_clean = array();
		$audioPath = $this->configManager->getUserValue($this->userId, $this->appName, 'path');
		$userView = $this->rootFolder->getUserFolder($this -> userId);

		if ($audioPath !== null && $audioPath !== '/' && $audioPath !== '') {
			$userView = $userView->get($audioPath);
		}

		$audios_mpegurl = $userView->searchByMime('audio/mpegurl');
		$audios_scpls = $userView->searchByMime('audio/x-scpls');
		$audios_xspf = $userView->searchByMime('application/xspf+xml');
		$audios = array_merge($audios_mpegurl, $audios_scpls, $audios_xspf);
		if ($debug) {
			$output->writeln("Total stream files: ".count($audios));
		}

		// get all fileids which are in an excluded folder
			$stmt = $this->db->prepare('SELECT `fileid` from `*PREFIX*filecache` WHERE `parent` IN (SELECT `parent` FROM `*PREFIX*filecache` WHERE `name` = ? OR `name` = ? ORDER BY `fileid` ASC)');
			$stmt->execute(array('.noAudio', '.noaudio'));
			$results = $stmt->fetchAll();
			foreach ($results as $row) {
				array_push($new_array, $row['fileid']);
			}
			$resultExclude = $new_array;
			//if ($debug) $output->writeln("Excluded ids (.noAdmin): ".implode(",", $resultExclude));
		
		// get all fileids which are already in the Audio Player Database
			$new_array = array();
			$stmt = $this->db->prepare('SELECT `file_id` FROM `*PREFIX*audioplayer_streams` WHERE `user_id` = ? ');
			$stmt->execute(array($this->userId));
			$results = $stmt->fetchAll();
			foreach ($results as $row) {
				array_push($new_array, $row['file_id']);
			}
			$resultExisting = $new_array;
			//if ($debug) $output->writeln("Existing ids (already scanned): ".implode(",", $resultExisting)."</info>");

		if ($debug) $output->writeln("Checking all streams whether they can be <info>skipped</info>");
		foreach ($audios as $audio) {
			$current_id = $audio->getID();
			
			if (in_array($current_id, $resultExclude)) {
				if ($debug) $output->writeln("   ".$current_id." - ".$audio->getPath()."  => excluded");
			} elseif (in_array($current_id, $resultExisting)) {
				if ($debug) $output->writeln("   ".$current_id." - ".$audio->getPath()."  => already indexed");
			} else {
				array_push($audios_clean, $audio);
			} 
		}
		$this->numOfSongs = $this->numOfSongs + count($audios_clean);
		if ($debug) $output->writeln("Final streaming files to be processed: ".count($audios_clean));
		return $audios_clean;
	}
	
	/**
	 * get picture from folder of audio file
	 * folder/cover.jpg/png
	 * 
	 * @param integer $iAlbumId
	 * @param $data
	 * @return boolean
	 */
	private function getFolderPicture($iAlbumId, $data) {

		$image = new \OCP\Image();
 		if ($image->loadFromdata($data)) {
			if (($image->width() <= 250 && $image->height() <= 250) || $image->centerCrop(250)) {
				$imgString = $image->__toString();
				$this->writeCoverToAlbum($iAlbumId, $imgString);
			}
		}
		return true;
	}
	
	/**
	 * truncates fiels do DB-field size
	 * 
	 * @param $string
	 * @param $length
	 * @param $dots
	 * @return string
	 */
	private function truncate($string, $length, $dots = "...") {
		return (strlen($string) > $length) ? mb_strcut($string, 0, $length - strlen($dots)) . $dots : $string;
	}

	/**
	 * validate unsigned int values
	 * 
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

	/**
	 * Analyze ID3 Tags
	 * if fseek is not possible, libsmbclient-php is not installed or an external storage is used which does not support this.
	 * then fallback to slow extraction via tmpfile
	 * 
	 * @param $audio object
	 * @param $getID3 object
	 * @return $ThisFileInfo
	 */
	private function analyze($audio, $getID3, $output = null, $debug = null) {

		if ($audio->getMimetype() === 'audio/mpegurl' or $audio->getMimetype() === 'audio/x-scpls' or $audio->getMimetype() === 'application/xspf+xml') {
			$ThisFileInfo = array();
			$ThisFileInfo['comments']['genre'][0] = 'Stream';
			$ThisFileInfo['comments']['artist'][0] = 'Stream';
			$ThisFileInfo['comments']['album'][0] = 'Stream';
			$ThisFileInfo['bitrate'] = 0;
			$ThisFileInfo['playtime_string'] = 0;
		} else {
			$handle = $audio->fopen('rb');
			if (@fseek($handle, -24, SEEK_END) === 0) {
					$ThisFileInfo = $getID3->analyze($audio->getPath(), $handle, $audio->getSize());
			} else {
				if (!$this->no_fseek) {
					if ($debug) {
						$output->writeln("Attention: Only slow indexing due to server config. See Audio Player wiki on GitHub for details.");
					}
					\OCP\Util::writeLog('audioplayer', 'Attention: Only slow indexing due to server config. See Audio Player wiki on GitHub for details.', \OCP\Util::DEBUG);
					$this->no_fseek = true;
				}
				$fileName = $audio->getStorage()->getLocalFile($audio->getInternalPath());				
				$ThisFileInfo = $getID3->analyze($fileName);

				if (!$audio->getStorage()->isLocal($audio->getInternalPath())) {
					unlink($fileName);
				}	
			} 
		} 
		return $ThisFileInfo;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function checkNewTracks() {
		
		// get only the relevant audio files
		$this->getAudioObjects();
		$this->getStreamObjects();
		if ($this->numOfSongs !== 0) {
			return 'true';
		} else {
			return 'false';
		}
	}
				
}
