<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2019 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IDbConnection;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\ILogger;
use OCP\IDateTimeZone;

/**
 * Controller class for main page.
 */
class ScannerController extends Controller
{

    private $userId;
    private $l10n;
    private $abscount = 0;
    private $progress;
    private $currentSong;
    private $iDublicate = 0;
    private $iAlbumCount = 0;
    private $numOfSongs;
    private $db;
    private $configManager;
    private $occ_job = false;
    private $no_fseek = false;
    private $languageFactory;
    private $rootFolder;
    private $ID3Tags;
    private $cyrillic;
    private $logger;
    private $parentId_prev = false;
    private $folderpicture = false;
    private $DBController;
    private $IDateTimeZone;
    private $SettingController;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDbConnection $db,
        IConfig $configManager,
        IFactory $languageFactory,
        IRootFolder $rootFolder,
        ILogger $logger,
        DbController $DBController,
        SettingController $SettingController,
        IDateTimeZone $IDateTimeZone
    )
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->configManager = $configManager;
        $this->languageFactory = $languageFactory;
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
        $this->DBController = $DBController;
        $this->SettingController = $SettingController;
        $this->IDateTimeZone = $IDateTimeZone;
    }

    /**
     * @NoAdminRequired
     *
     */
    public function getImportTpl()
    {
        $params = [];
        $response = new TemplateResponse('audioplayer', 'part.import', $params, '');
        return $response;
    }

    /**
     * @NoAdminRequired
     *
     * @param $userId
     * @param $output
     * @param $scanstop
     * @return bool|JSONResponse
     * @throws \OCP\Files\NotFoundException
     * @throws \getid3_exception
     */
    public function scanForAudios($userId = null, $output = null, $scanstop = null)
    {
        if (isset($scanstop)) {
            $this->DBController->setSessionValue('scanner_running', 'stopped', $this->userId);
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
        } else {
            $output = new NullOutput();
        }

        $output->writeln("Start processing of <info>audio files</info>");

        $counter = 0;
        $counter_new = 0;
        $error_count = 0;
        $duplicate_tracks = 0;
        $error_file = 0;
        $this->cyrillic = $this->configManager->getUserValue($this->userId, $this->appName, 'cyrillic');
        $this->DBController->setSessionValue('scanner_running', 'active', $this->userId);

        $this->updateProgress(0, $output);
        $this->setScannerVersion();

        if (!class_exists('getid3_exception')) {
            require_once __DIR__ . '/../../3rdparty/getid3/getid3.php';
        }
        $getID3 = new \getID3;
        $getID3->setOption(['encoding' => 'UTF-8',
            'option_tag_id3v1' => false,
            'option_tag_id3v2' => true,
            'option_tag_lyrics3' => false,
            'option_tag_apetag' => false,
            'option_tags_process' => true,
            'option_tags_html' => false
        ]);

        $audios = $this->getAudioObjects($output);
        $streams = $this->getStreamObjects($output);

        if ($this->cyrillic === 'checked') $output->writeln("Cyrillic processing activated", OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln("Start processing of <info>audio files</info>", OutputInterface::VERBOSITY_VERBOSE);

        foreach ($audios as $audio) {

            //check if scan is still supposed to run, or if dialog was closed in web already
            if (!$this->occ_job) {
                $scan_running = $this->DBController->getSessionValue('scanner_running');
                if ($scan_running === 'stopped') break;
            }

            $this->currentSong = $audio->getPath();
            $this->updateProgress(intval(($this->abscount / $this->numOfSongs) * 100), $output);
            $counter++;
            $this->abscount++;

            if ($this->checkFileChanged($audio)) {
                $this->DBController->deleteFromDB($audio->getId(), $userId);
            }

            $this->analyze($audio, $getID3, $output);

            # catch issue when getID3 does not bring a result in case of corrupt file or fpm-timeout
            if (!isset($this->ID3Tags['bitrate']) AND !isset($this->ID3Tags['playtime_string'])) {
                $this->logger->debug('Error with getID3. Does not seem to be a valid audio file: ' . $audio->getPath(), array('app' => 'audioplayer'));
                $output->writeln("       Error with getID3. Does not seem to be a valid audio file", OutputInterface::VERBOSITY_VERBOSE);
                $error_file .= $audio->getName() . '<br />';
                $error_count++;
                continue;
            }

            $album = $this->getID3Value(array('album'));
            $genre = $this->getID3Value(array('genre'));
            $artist = $this->getID3Value(array('artist'));
            $name = $this->getID3Value(array('title'), $audio->getName());
            $trackNr = $this->getID3Value(array('track_number'), '');
            $composer = $this->getID3Value(array('composer'), '');
            $year = $this->getID3Value(array('year', 'creation_date', 'date'), 0);
            $subtitle = $this->getID3Value(array('subtitle', 'version'), '');
            $disc = $this->getID3Value(array('part_of_a_set', 'discnumber', 'partofset', 'disc_number'), 1);
            $isrc = $this->getID3Value(array('isrc'), '');
            $copyright = $this->getID3Value(array('copyright_message', 'copyright'), '');

            $iGenreId = $this->DBController->writeGenreToDB($this->userId, $genre);
            $iArtistId = $this->DBController->writeArtistToDB($this->userId, $artist);

            # write albumartist if available
            # if no albumartist, NO artist is stored on album level
            # in DBController loadArtistsToAlbum() takes over deriving the artists from the album tracks
            # MP3, FLAC & MP4 have different tags for albumartist
            $iAlbumArtistId = NULL;
            $album_artist = $this->getID3Value(array('band', 'album_artist', 'albumartist', 'album artist'), '0');

            if ($album_artist !== '0') {
                $iAlbumArtistId = $this->DBController->writeArtistToDB($this->userId, $album_artist);
            }

            $parentId = $audio->getParent()->getId();
            $return = $this->DBController->writeAlbumToDB($this->userId, $album, (int)$year, $iAlbumArtistId, $parentId);
            $iAlbumId = $return['id'];
            $this->iAlbumCount = $this->iAlbumCount + $return['albumcount'];

            $bitrate = 0;
            if (isset($this->ID3Tags['bitrate'])) {
                $bitrate = $this->ID3Tags['bitrate'];
            }

            $playTimeString = '';
            if (isset($this->ID3Tags['playtime_string'])) {
                $playTimeString = $this->ID3Tags['playtime_string'];
            }

            $this->getAlbumArt($audio, $iAlbumId, $parentId, $output);

            $aTrack = [
                'title' => $this->truncate($name, '256'),
                'number' => $this->normalizeInteger($trackNr),
                'artist_id' => (int)$iArtistId,
                'album_id' => (int)$iAlbumId,
                'length' => $playTimeString,
                'file_id' => (int)$audio->getId(),
                'bitrate' => (int)$bitrate,
                'mimetype' => $audio->getMimetype(),
                'genre' => (int)$iGenreId,
                'year' => $this->truncate($this->normalizeInteger($year), 4, ''),
                'disc' => $this->normalizeInteger($disc),
                'subtitle' => $this->truncate($subtitle, '256'),
                'composer' => $this->truncate($composer, '256'),
                'folder_id' => $parentId,
                'isrc' => $this->truncate($isrc, '12'),
                'copyright' => $this->truncate($copyright, '256'),
            ];

            $return = $this->DBController->writeTrackToDB($this->userId, $aTrack);
            if ($return['dublicate'] === 1) {
                $this->logger->debug('Duplicate file: ' . $audio->getPath(), array('app' => 'audioplayer'));
                $output->writeln("       This title is a duplicate and already existing", OutputInterface::VERBOSITY_VERBOSE);
                $duplicate_tracks .= $audio->getPath() . '<br />';
                $this->iDublicate = $this->iDublicate + $return['dublicate'];
            }
            $counter_new++;
        }

        $output->writeln("Start processing of <info>stream files</info>", OutputInterface::VERBOSITY_VERBOSE);
        foreach ($streams as $stream) {
            //check if scan is still supposed to run, or if dialog was closed in web already
            if (!$this->occ_job) {
                $scan_running = $this->DBController->getSessionValue('scanner_running');
                if ($scan_running !== 'active') break;
            }

            $this->currentSong = $stream->getPath();
            $this->updateProgress(intval(($this->abscount / $this->numOfSongs) * 100), $output);
            $counter++;
            $this->abscount++;

            $title = $this->truncate($stream->getName(), '256');
            $aStream = [
                'title' => substr($title, 0, strrpos($title, ".")),
                'artist_id' => 0,
                'album_id' => 0,
                'file_id' => (int)$stream->getId(),
                'bitrate' => 0,
                'mimetype' => $stream->getMimetype(),
            ];
            $return = $this->DBController->writeStreamToDB($this->userId, $aStream);
            if ($return['dublicate'] === 1) {
                $this->logger->debug('Duplicate file: ' . $audio->getPath(), array('app' => 'audioplayer'));
                $output->writeln("       This title is a duplicate and already existing", OutputInterface::VERBOSITY_VERBOSE);
                $duplicate_tracks .= $audio->getPath() . '<br />';
                $this->iDublicate = $this->iDublicate + $return['dublicate'];
            }
            $counter_new++;
        }

        $this->setScannerTimestamp();

        $message = (string)$this->l10n->t('Scanning finished!') . '<br />';
        $message .= (string)$this->l10n->t('Audios found: ') . $counter . '<br />';
        $message .= (string)$this->l10n->t('Written to library: ') . ($counter_new - $this->iDublicate) . '<br />';
        $message .= (string)$this->l10n->t('Albums found: ') . $this->iAlbumCount . '<br />';
        if ($error_count >> 0) {
            $message .= '<br /><b>' . (string)$this->l10n->t('Errors: ') . $error_count . '<br />';
            $message .= (string)$this->l10n->t('If rescan does not solve this problem the files are broken') . '</b>';
            $message .= '<br />' . $error_file . '<br />';
        }
        if ($this->iDublicate >> 0) {
            $message .= '<br /><b>' . (string)$this->l10n->t('Duplicates found: ') . ($this->iDublicate) . '</b>';
            $message .= '<br />' . $duplicate_tracks . '<br />';
        }

        // different outputs when web or occ
        if (!$this->occ_job) {
            $this->DBController->setSessionValue('scanner_running', '', $this->userId);
            $this->DBController->setSessionValue('scanner_progress', '', $this->userId);
            $result = [
                'status' => 'success',
                'message' => $message
            ];
            $response = new JSONResponse();
            $response->setData($result);
            return $response;
        } else {
            $output->writeln("Audios found: " . ($counter) . "");
            $output->writeln("Duplicates found: " . ($this->iDublicate) . "");
            $output->writeln("Written to library: " . ($counter_new - $this->iDublicate) . "");
            $output->writeln("Albums found: " . ($this->iAlbumCount) . "");
            $output->writeln("Errors: " . ($error_count) . "");
            return true;
        }
    }

    /**
     * @param $percentage
     * @param OutputInterface $output
     * @return bool
     */
    private function updateProgress($percentage, OutputInterface $output = null)
    {
        $this->progress = $percentage;

        if (!$this->occ_job) {
            $currentIntArray = [
                'percent' => $this->progress,
                'all' => $this->numOfSongs,
                'current' => $this->abscount,
                'currentsong' => $this->currentSong
            ];
            $currentIntArray = json_encode($currentIntArray);
            $this->DBController->setSessionValue('scanner_progress', $currentIntArray, $this->userId);
        } else {
            $output->writeln("   " . $this->currentSong . "</info>", OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        return true;
    }

    /**
     * if the scanner is started on an empty library, the current app version is stored
     *
     */
    private function setScannerVersion()
    {
        $stmt = $this->db->prepare('SELECT COUNT(`id`) AS `TRACKCOUNT`  FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ? ');
        $stmt->execute(array($this->userId));
        $row = $stmt->fetch();
        if ((int)$row['TRACKCOUNT'] === 0) {
            $app_version = $this->configManager->getAppValue($this->appName, 'installed_version', '0.0.0');
            $this->configManager->setUserValue($this->userId, $this->appName, 'scanner_version', $app_version);
        }
    }

    /**
     * Add track to db if not exist
     *
     * @param OutputInterface $output
     * @return array
     * @throws \OCP\Files\NotFoundException
     */
    private function getAudioObjects(OutputInterface $output = null)
    {
        $audios_clean = array();
        $audioPath = $this->configManager->getUserValue($this->userId, $this->appName, 'path');
        $userView = $this->rootFolder->getUserFolder($this->userId);

        if ($audioPath !== null && $audioPath !== '/' && $audioPath !== '') {
            $userView = $userView->get($audioPath);
        }

        $audios_mp3 = $userView->searchByMime('audio/mpeg');
        $audios_m4a = $userView->searchByMime('audio/mp4');
        $audios_ogg = $userView->searchByMime('audio/ogg');
        $audios_wav = $userView->searchByMime('audio/wav');
        $audios_flac = $userView->searchByMime('audio/flac');
        $audios = array_merge($audios_mp3, $audios_m4a, $audios_ogg, $audios_wav, $audios_flac);

        $output->writeln("Scanned Folder: " . $userView->getPath(), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln("<info>Total audio files:</info> " . count($audios), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln("Checking audio files to be skipped", OutputInterface::VERBOSITY_VERBOSE);

        // get all fileids which are in an excluded folder
        $stmt = $this->db->prepare('SELECT `fileid` from `*PREFIX*filecache` WHERE `parent` IN (SELECT `parent` FROM `*PREFIX*filecache` WHERE `name` = ? OR `name` = ? ORDER BY `fileid` ASC)');
        $stmt->execute(array('.noAudio', '.noaudio'));
        $results = $stmt->fetchAll();
        $resultExclude = array_column($results, 'fileid');

        // get all fileids which are already in the Audio Player Database
        $stmt = $this->db->prepare('SELECT `file_id` FROM `*PREFIX*audioplayer_tracks` WHERE `user_id` = ? ');
        $stmt->execute(array($this->userId));
        $results = $stmt->fetchAll();
        $resultExisting = array_column($results, 'file_id');

        foreach ($audios as $audio) {
            $current_id = $audio->getID();
            if (in_array($current_id, $resultExclude)) {
                $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => excluded", OutputInterface::VERBOSITY_VERY_VERBOSE);
            } elseif (in_array($current_id, $resultExisting)) {
                if ($this->checkFileChanged($audio)) {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => indexed title changed => reindex", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    array_push($audios_clean, $audio);
                } else {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => already indexed", OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            } else {
                array_push($audios_clean, $audio);
            }
        }
        $this->numOfSongs = count($audios_clean);
        $output->writeln("Final audio files to be processed: " . $this->numOfSongs, OutputInterface::VERBOSITY_VERBOSE);
        return $audios_clean;
    }

    /**
     * check changed timestamps
     *
     * @param object $audio
     * @return bool
     */
    private function checkFileChanged($audio)
    {
        $modTime = $audio->getMTime();
        $scannerTime = $this->getScannerTimestamp();
        if ($modTime >= $scannerTime - 300) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * check the timestamp of the last scan to derive changed files
     *
     */
    private function getScannerTimestamp()
    {
        return $this->configManager->getUserValue($this->userId, $this->appName, 'scanner_timestamp', 300);
    }

    /**
     * Add track to db if not exist
     *
     * @param OutputInterface $output
     * @return array
     * @throws \OCP\Files\NotFoundException
     */
    private function getStreamObjects(OutputInterface $output = null)
    {
        $audios_clean = array();
        $audioPath = $this->configManager->getUserValue($this->userId, $this->appName, 'path');
        $userView = $this->rootFolder->getUserFolder($this->userId);

        if ($audioPath !== null && $audioPath !== '/' && $audioPath !== '') {
            $userView = $userView->get($audioPath);
        }

        $audios_mpegurl = $userView->searchByMime('audio/mpegurl');
        $audios_scpls = $userView->searchByMime('audio/x-scpls');
        $audios_xspf = $userView->searchByMime('application/xspf+xml');
        $audios = array_merge($audios_mpegurl, $audios_scpls, $audios_xspf);
        $output->writeln("<info>Total stream files:</info> " . count($audios), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln("Checking stream files to be skipped", OutputInterface::VERBOSITY_VERBOSE);

        // get all fileids which are in an excluded folder
        $stmt = $this->db->prepare('SELECT `fileid` from `*PREFIX*filecache` WHERE `parent` IN (SELECT `parent` FROM `*PREFIX*filecache` WHERE `name` = ? OR `name` = ? ORDER BY `fileid` ASC)');
        $stmt->execute(array('.noAudio', '.noaudio'));
        $results = $stmt->fetchAll();
        $resultExisting = array_column($results, 'fileid');

        // get all fileids which are already in the Audio Player Database
        $stmt = $this->db->prepare('SELECT `file_id` FROM `*PREFIX*audioplayer_streams` WHERE `user_id` = ? ');
        $stmt->execute(array($this->userId));
        $results = $stmt->fetchAll();
        $resultExclude = array_column($results, 'file_id');

        foreach ($audios as $audio) {
            $current_id = $audio->getID();

            if (in_array($current_id, $resultExclude)) {
                $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => excluded", OutputInterface::VERBOSITY_VERY_VERBOSE);
            } elseif (in_array($current_id, $resultExisting)) {
                if ($this->checkFileChanged($audio)) {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => indexed file changed => reindex", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    array_push($audios_clean, $audio);
                } else {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => already indexed", OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            } else {
                array_push($audios_clean, $audio);
            }
        }
        $this->numOfSongs = $this->numOfSongs + count($audios_clean);
        $output->writeln("Final stream files to be processed: " . count($audios_clean), OutputInterface::VERBOSITY_VERBOSE);
        return $audios_clean;
    }

    /**
     * Analyze ID3 Tags
     * if fseek is not possible, libsmbclient-php is not installed or an external storage is used which does not support this.
     * then fallback to slow extraction via tmpfile
     *
     * @param $audio object
     * @param $getID3 object
     * @param OutputInterface $output
     * @return void
     */
    private function analyze($audio, $getID3, OutputInterface $output = null)
    {
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
                $ThisFileInfo = $getID3->analyze($audio->getPath(), $audio->getSize(), '', $handle);
            } else {
                if (!$this->no_fseek) {
                    $output->writeln("Attention: Only slow indexing due to server config. See Audio Player wiki on GitHub for details.", OutputInterface::VERBOSITY_VERBOSE);
                    $this->logger->debug('Attention: Only slow indexing due to server config. See Audio Player wiki on GitHub for details.', array('app' => 'audioplayer'));
                    $this->no_fseek = true;
                }
                $fileName = $audio->getStorage()->getLocalFile($audio->getInternalPath());
                $ThisFileInfo = $getID3->analyze($fileName);

                if (!$audio->getStorage()->isLocal($audio->getInternalPath())) {
                    unlink($fileName);
                }
            }
        }
        if ($this->cyrillic === 'checked') $ThisFileInfo = $this->cyrillic($ThisFileInfo);
        \getid3_lib::CopyTagsToComments($ThisFileInfo);

        $this->ID3Tags = $ThisFileInfo;
        return;
    }

    /**
     * Add track to db if not exist
     *
     * @param array $ThisFileInfo
     * @return array
     */
    private function cyrillic($ThisFileInfo)
    {
        //$this->logger->debug('cyrillic handling activated', array('app' => 'audioplayer'));
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
     * Get specific ID3 tags from array
     *
     * @param string[] $ID3Value
     * @param string $defaultValue
     * @return string
     */
    private function getID3Value($ID3Value, $defaultValue = null)
    {
        $c = count($ID3Value);
        //	\OCP\Util::writeLog('audioplayer', 'album: '.$this->ID3Tags['comments']['album'][0], \OCP\Util::DEBUG);
        for ($i = 0; $i < $c; $i++) {
            if (isset($this->ID3Tags['comments'][$ID3Value[$i]][0]) and rawurlencode($this->ID3Tags['comments'][$ID3Value[$i]][0]) !== '%FF%FE') {
                return $this->ID3Tags['comments'][$ID3Value[$i]][0];
            } elseif ($i === $c - 1 AND $defaultValue !== null) {
                return $defaultValue;
            } elseif ($i === $c - 1) {
                return (string)$this->l10n->t('Unknown');
            }
        }
    }

    /**
     * extract cover art from folder or from audio file
     * folder/cover.jpg/png
     *
     * @param object $audio
     * @param integer $iAlbumId
     * @param integer $parentId
     * @param OutputInterface|null $output
     * @return boolean|null
     */
    private function getAlbumArt($audio, $iAlbumId, $parentId, OutputInterface $output = null)
    {
        if ($parentId === $this->parentId_prev) {
            if ($this->folderpicture) {
                $output->writeln("     Reusing previous folder image", OutputInterface::VERBOSITY_VERY_VERBOSE);
                $this->processImageString($iAlbumId, $this->folderpicture->getContent());
            } elseif (isset($this->ID3Tags['comments']['picture'][0]['data'])) {
                $data = $this->ID3Tags['comments']['picture'][0]['data'];
                $this->processImageString($iAlbumId, $data);
            }
        } else {
            $this->folderpicture = false;
            if ($audio->getParent()->nodeExists('cover.jpg')) {
                $this->folderpicture = $audio->getParent()->get('cover.jpg');
            } elseif ($audio->getParent()->nodeExists('cover.png')) {
                $this->folderpicture = $audio->getParent()->get('cover.png');
            } elseif ($audio->getParent()->nodeExists('folder.jpg')) {
                $this->folderpicture = $audio->getParent()->get('folder.jpg');
            } elseif ($audio->getParent()->nodeExists('Folder.jpg')) {
                $this->folderpicture = $audio->getParent()->get('Folder.jpg');
            } elseif ($audio->getParent()->nodeExists('folder.png')) {
                $this->folderpicture = $audio->getParent()->get('folder.png');
            }

            if ($this->folderpicture) {
                $this->processImageString($iAlbumId, $this->folderpicture->getContent());
                $output->writeln("     Alternative album art: " . $this->folderpicture->getInternalPath(), OutputInterface::VERBOSITY_VERY_VERBOSE);
            } elseif (isset($this->ID3Tags['comments']['picture'])) {
                $data = $this->ID3Tags['comments']['picture'][0]['data'];
                $this->processImageString($iAlbumId, $data);
            }
            $this->parentId_prev = $parentId;
        }
        return true;
    }

    /**
     * create image string from rawdata and store as album cover
     *
     * @param integer $iAlbumId
     * @param $data
     * @return boolean
     */
    private function processImageString($iAlbumId, $data)
    {
        $image = new \OCP\Image();
        if ($image->loadFromdata($data)) {
            if (($image->width() <= 250 && $image->height() <= 250) || $image->centerCrop(250)) {
                $imgString = $image->__toString();
                $this->DBController->writeCoverToAlbum($this->userId, $iAlbumId, $imgString);
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
    private function truncate($string, $length, $dots = "...")
    {
        return (strlen($string) > $length) ? mb_strcut($string, 0, $length - strlen($dots)) . $dots : $string;
    }

    /**
     * validate unsigned int values
     *
     * @param string $value
     * @return int value
     */
    private function normalizeInteger($value)
    {
        // convert format '1/10' to '1' and '-1' to null
        $tmp = explode('/', $value);
        $tmp = explode('-', $tmp[0]);
        $value = $tmp[0];
        if (is_numeric($value) && ((int)$value) > 0) {
            $value = (int)$value;
        } else {
            $value = 0;
        }
        return $value;
    }

    /**
     * set the timestamp of the last scan to derive changed files
     *
     */
    private function setScannerTimestamp()
    {
        $this->configManager->setUserValue($this->userId, $this->appName, 'scanner_timestamp', time());
    }

    /**
     * Report scanning Progress back to web frontend - e.g. progress bar
     * @NoAdminRequired
     *
     */
    public function getProgress()
    {
        $aCurrent = $this->DBController->getSessionValue('scanner_progress');
        if ($aCurrent !== '') {
            $aCurrent = json_decode($aCurrent);
            $numSongs = (isset($aCurrent->{'all'}) ? $aCurrent->{'all'} : 0);
            $currentSongCount = (isset($aCurrent->{'current'}) ? $aCurrent->{'current'} : 0);
            $currentSong = (isset($aCurrent->{'currentsong'}) ? $aCurrent->{'currentsong'} : '');
            $percent = (isset($aCurrent->{'percent'}) ? $aCurrent->{'percent'} : 0);

            $params = ['status' => 'success', 'percent' => $percent, 'msg' => $currentSong, 'prog' => $percent . '% (' . $currentSongCount . ' / ' . $numSongs . ')'];
        } else {
            $params = ['status' => 'false'];
        }
        $response = new JSONResponse($params);
        return $response;
    }

    /**
     * @NoAdminRequired
     *
     * @throws \OCP\Files\NotFoundException
     */
    public function checkNewTracks()
    {
        // get only the relevant audio files
        $output = new NullOutput();
        $this->getAudioObjects($output);
        $this->getStreamObjects($output);
        if ($this->numOfSongs !== 0) {
            return 'true';
        } else {
            return 'false';
        }
    }
}
