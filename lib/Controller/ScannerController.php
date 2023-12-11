<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2021 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use Doctrine\DBAL\DBALException;
use Exception;
use getID3;
use getid3_exception;
use getid3_lib;
use OC;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Image;
use OCP\PreconditionNotMetException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IDBConnection;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use OCP\IDateTimeZone;
use OCP\IEventSource;
use OCP\IEventSourceFactory;

/**
 * Controller class for main page.
 */
class ScannerController extends Controller
{

    private $userId;
    private $l10n;
    private $iDublicate = 0;
    private $iAlbumCount = 0;
    private $numOfSongs;
    private $db;
    private $configManager;
    private $occJob = false;
    private $noFseek = false;
    private $languageFactory;
    private $rootFolder;
    private $ID3Tags;
    private $cyrillic;
    private $logger;
    private $parentIdPrevious = 0;
    private $folderPicture = false;
    private $DBController;
    private $IDateTimeZone;
    private $SettingController;
    private $eventSource;
    private $lastUpdated;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db,
        IConfig $configManager,
        IFactory $languageFactory,
        IRootFolder $rootFolder,
        LoggerInterface $logger,
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
        $this->lastUpdated = time();

        // @TODO: Remove method_exists when min-version="28"
		if (method_exists(\OC::$server, 'createEventSource')) {
			$this->eventSource = \OC::$server->createEventSource();
		} else {
			$this->eventSource = \OCP\Server::get(IEventSourceFactory::class)->create();
		}
    }

    /**
     * @NoAdminRequired
     *
     */
    public function getImportTpl()
    {
        $params = [];
        return new TemplateResponse('audioplayer', 'part.import', $params, '');
    }

    /**
     * @NoAdminRequired
     *
     * @param $userId
     * @param $output
     * @param $scanstop
     * @return bool|JSONResponse
     * @throws NotFoundException
     * @throws getid3_exception
     */
    public function scanForAudios($userId = null, $output = null, $scanstop = null)
    {
        set_time_limit(0);
        if (isset($scanstop)) {
            $this->DBController->setSessionValue('scanner_running', 'stopped', $this->userId);
            $params = ['status' => 'stopped'];
            return new JSONResponse($params);
        }

        // check if scanner is started from web or occ
        if ($userId !== null) {
            $this->occJob = true;
            $this->userId = $userId;
            $languageCode = $this->configManager->getUserValue($userId, 'core', 'lang');
            $this->l10n = $this->languageFactory->get('audioplayer', $languageCode);
        } else {
            $output = new NullOutput();
        }

        $output->writeln("Start processing of <info>audio files</info>");

        $counter = 0;
        $error_count = 0;
        $duplicate_tracks = '';
        $error_file = '';
        $this->cyrillic = $this->configManager->getUserValue($this->userId, $this->appName, 'cyrillic');
        $this->DBController->setSessionValue('scanner_running', 'active', $this->userId);

        $this->setScannerVersion();

        if (!class_exists('getid3_exception')) {
            require_once __DIR__ . '/../../3rdparty/getid3/getid3.php';
        }
        $getID3 = new getID3;
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

        $commitThreshold = max(200, intdiv(count($audios), 10));
        $this->DBController->beginTransaction();
        try {
            foreach ($audios as &$audio) {
                if ($this->scanCancelled()) { break; }

                $counter++;
                try {
                    $scanResult = $this->scanAudio($audio, $getID3, $output);
                    if ($scanResult === 'error') {
                        $error_file .= $audio->getPath() . '<br />';
                        $error_count++;
                    } else if ($scanResult === 'duplicate') {
                        $duplicate_tracks .= $audio->getPath() . '<br />';
                        $this->iDublicate++;
                    }
                } catch (getid3_exception $e) {
                    $this->logger->error('getID3 error while building library: '. $e);
                    continue;
                }

                if ($this->timeForUpdate()) {
                    $this->updateProgress($counter, $audio->getPath(), $output);
                }
                if ($counter % $commitThreshold == 0) {
                    $this->DBController->commit();
                    $output->writeln("Status committed to database", OutputInterface::VERBOSITY_VERBOSE);
                    $this->DBController->beginTransaction();
                }
            }

            $output->writeln("Start processing of <info>stream files</info>", OutputInterface::VERBOSITY_VERBOSE);
            foreach ($streams as &$stream) {
                if ($this->scanCancelled()) { break; }

                $counter++;
                $scanResult = $this->scanStream($stream, $output);
                if ($scanResult === 'duplicate') {
                    $duplicate_tracks .= $stream->getPath() . '<br />';
                    $this->iDublicate++;
                }

                if ($this->timeForUpdate()) {
                    $this->updateProgress($counter, $stream->getPath(), $output);
                }
            }
            $this->setScannerTimestamp();
            $this->DBController->commit();
        } catch (DBALException $e) {
            $this->logger->error('DB error while building library: '. $e);
            $this->DBController->rollBack();
        } catch (Exception $e) {
            $this->logger->error('Error while building library: '. $e);
            $this->DBController->commit();
        }

        // different outputs when web or occ
        if (!$this->occJob) {
            $message = $this->composeResponseMessage($counter, $error_count, $duplicate_tracks, $error_file);
            $this->DBController->setSessionValue('scanner_running', '', $this->userId);
            $response = [
                'message' => $message
            ];
            $response = json_encode($response);
            $this->eventSource->send('done', $response);
            $this->eventSource->close();
            return new JSONResponse();
        } else {
            $output->writeln("Audios found: " . ($counter) . "");
            $output->writeln("Duplicates found: " . ($this->iDublicate) . "");
            $output->writeln("Written to library: " . ($counter - $this->iDublicate - $error_count) . "");
            $output->writeln("Albums found: " . ($this->iAlbumCount) . "");
            $output->writeln("Errors: " . ($error_count) . "");
            return true;
        }
    }

    /**
     * Check whether scan got cancelled by user
     * @return bool
     */
    private function scanCancelled() {
        //check if scan is still supposed to run, or if dialog was closed in web already
        if (!$this->occJob) {
            $scan_running = $this->DBController->getSessionValue('scanner_running');
            return ($scan_running !== 'active');
        }
    }

    /**
     * Process audio track and insert it into DB
     * @param object $audio audio object to scan
     * @param object $getID3 ID3 tag helper from getid3 library
     * @param OutputInterface|null $output
     * @return string
     */
    private function scanAudio($audio, $getID3, $output) {
        if ($this->checkFileChanged($audio)) {
            $this->DBController->deleteFromDB($audio->getId(), $this->userId);
        }

        $this->analyze($audio, $getID3, $output);

        # catch issue when getID3 does not bring a result in case of corrupt file or fpm-timeout
        if (!isset($this->ID3Tags['bitrate']) AND !isset($this->ID3Tags['playtime_string'])) {
            $this->logger->debug('Error with getID3. Does not seem to be a valid audio file: ' . $audio->getPath(), array('app' => 'audioplayer'));
            $output->writeln("       Error with getID3. Does not seem to be a valid audio file", OutputInterface::VERBOSITY_VERBOSE);
            return 'error';
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
            'title' => $this->truncateStrings($name, '256'),
            'number' => $this->normalizeInteger($trackNr),
            'artist_id' => (int)$iArtistId,
            'album_id' => (int)$iAlbumId,
            'length' => $playTimeString,
            'file_id' => (int)$audio->getId(),
            'bitrate' => (int)$bitrate,
            'mimetype' => $audio->getMimetype(),
            'genre' => (int)$iGenreId,
            'year' => $this->truncateStrings($this->normalizeInteger($year), 4, ''),
            'disc' => $this->normalizeInteger($disc),
            'subtitle' => $this->truncateStrings($subtitle, '256'),
            'composer' => $this->truncateStrings($composer, '256'),
            'folder_id' => $parentId,
            'isrc' => $this->truncateStrings($isrc, '12'),
            'copyright' => $this->truncateStrings($copyright, '256'),
        ];

        $return = $this->DBController->writeTrackToDB($this->userId, $aTrack);
        if ($return['dublicate'] === 1) {
            $this->logger->debug('Duplicate file: ' . $audio->getPath(), array('app' => 'audioplayer'));
            $output->writeln("       This title is a duplicate and already existing", OutputInterface::VERBOSITY_VERBOSE);
            return 'duplicate';
        }
        return 'success';
    }

    /**
     * Process stream and insert it into DB
     * @param object $stream stream object to scan
     * @param OutputInterface|null $output
     * @return string
     */
    private function scanStream($stream, $output) {
        $title = $this->truncateStrings($stream->getName(), '256');
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
            $this->logger->debug('Duplicate file: ' . $stream->getPath(), array('app' => 'audioplayer'));
            $output->writeln("       This title is a duplicate and already existing", OutputInterface::VERBOSITY_VERBOSE);
            return 'duplicate';
        }
        return 'success';
    }

    /**
     * Summarize scan results in a message
     * @param $counter number of tracks
     * @param integer $error_count number of invalid files
     * @param string $duplicate_tracks list of invalid files
     * @param $error_file
     * @return string
     */
    private function composeResponseMessage($counter,
                                            $error_count,
                                            $duplicate_tracks,
                                            $error_file) {
        $message = (string)$this->l10n->t('Scanning finished!') . '<br />';
        $message .= (string)$this->l10n->t('Audios found:') . ' ' . $counter . '<br />';
        $message .= (string)$this->l10n->t('Written to library:') . ' ' . ($counter - $this->iDublicate - $error_count) . '<br />';
        $message .= (string)$this->l10n->t('Albums found:') . ' ' . $this->iAlbumCount . '<br />';
        if ($error_count > 0) {
            $message .= '<br /><b>' . (string)$this->l10n->t('Errors:') . ' ' . $error_count . '<br />';
            $message .= (string)$this->l10n->t('If rescan does not solve this problem the files are broken') . '</b>';
            $message .= '<br />' . $error_file . '<br />';
        }
        if ($this->iDublicate > 0) {
            $message .= '<br /><b>' . (string)$this->l10n->t('Duplicates found:') . ' ' . ($this->iDublicate) . '</b>';
            $message .= '<br />' . $duplicate_tracks . '<br />';
        }
        return $message;
    }

    /**
     * Give feedback to user via appropriate output
     * @param integer $filesProcessed
     * @param string $currentFile
     * @param OutputInterface|null $output
     */
    private function updateProgress($filesProcessed, $currentFile, OutputInterface $output = null)
    {
        if (!$this->occJob) {
            $response = [
                'filesProcessed' => $filesProcessed,
                'filesTotal' => $this->numOfSongs,
                'currentFile' => $currentFile
            ];
            $response = json_encode($response);
            $this->eventSource->send('progress', $response);
        } else {
            $output->writeln("   " . $currentFile . "</info>", OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
    }

    /**
     * Prevent flood over the wire
     * @return bool
     */
    private function timeForUpdate()
    {
        if ($this->occJob) {
            return true;
        }
        $now = time();
        if ($now - $this->lastUpdated >= 1) {
            $this->lastUpdated = $now;
            return true;
        }
        return false;
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
     * @throws NotFoundException
     * @throws \OCP\Files\InvalidPathException
     */
    private function getAudioObjects(OutputInterface $output = null)
    {
        $audioPath = $this->configManager->getUserValue($this->userId, $this->appName, 'path');
        $userView = $this->rootFolder->getUserFolder($this->userId);

        if ($audioPath !== null && $audioPath !== '/' && $audioPath !== '') {
            try {
                $userView = $userView->get($audioPath);
            } catch (InvalidPathException $e) {
                $output->writeln("!Error: Selected scan folder is not existing");
                return;
            } catch (NotFoundException $e) {
                $output->writeln("!Error: Selected scan folder is not existing");
                return;
            }
        }

        $audios_mp3 = $userView->searchByMime('audio/mpeg');
        $audios_m4a = $userView->searchByMime('audio/mp4');
        $audios_ogg = $userView->searchByMime('audio/ogg');
        $audios_wav = $userView->searchByMime('audio/wav');
        $audios_flac = $userView->searchByMime('audio/flac');
        $audios_aif = $userView->searchByMime('audio/x-aiff');
        $audios_aac = $userView->searchByMime('audio/aac');
        $audios = array_merge($audios_mp3, $audios_m4a, $audios_ogg, $audios_wav, $audios_flac, $audios_aif, $audios_aac);

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

        foreach ($audios as $key => &$audio) {
            $current_id = $audio->getID();
            if (in_array($current_id, $resultExclude)) {
                $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => excluded", OutputInterface::VERBOSITY_VERY_VERBOSE);
                unset($audios[$key]);
            } elseif (in_array($current_id, $resultExisting)) {
                if ($this->checkFileChanged($audio)) {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => indexed title changed => reindex", OutputInterface::VERBOSITY_VERY_VERBOSE);
                } else {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => already indexed", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    unset($audios[$key]);
                }
            }
        }
        $this->numOfSongs = count($audios);
        $output->writeln("Final audio files to be processed: " . $this->numOfSongs, OutputInterface::VERBOSITY_VERBOSE);
        return $audios;
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
     * @throws NotFoundException
     */
    private function getStreamObjects(OutputInterface $output = null)
    {
        $audios_clean = array();
        $audioPath = $this->configManager->getUserValue($this->userId, $this->appName, 'path');
        $userView = $this->rootFolder->getUserFolder($this->userId);

        if ($audioPath !== null && $audioPath !== '/' && $audioPath !== '') {
            try {
                $userView = $userView->get($audioPath);
            } catch (InvalidPathException $e) {
                $output->writeln("!Error: Selected scan folder is not existing");
                return;
            } catch (NotFoundException $e) {
                $output->writeln("!Error: Selected scan folder is not existing");
                return;
            }
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
        $resultExclude = array_column($results, 'fileid');

        // get all fileids which are already in the Audio Player Database
        $stmt = $this->db->prepare('SELECT `file_id` FROM `*PREFIX*audioplayer_streams` WHERE `user_id` = ? ');
        $stmt->execute(array($this->userId));
        $results = $stmt->fetchAll();
        $resultExisting = array_column($results, 'file_id');

        foreach ($audios as $key => &$audio) {
            $current_id = $audio->getID();
            if (in_array($current_id, $resultExclude)) {
                $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => excluded", OutputInterface::VERBOSITY_VERY_VERBOSE);
                unset($audios[$key]);
            } elseif (in_array($current_id, $resultExisting)) {
                if ($this->checkFileChanged($audio)) {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => indexed file changed => reindex", OutputInterface::VERBOSITY_VERY_VERBOSE);
                } else {
                    $output->writeln("   " . $current_id . " - " . $audio->getPath() . "  => already indexed", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    unset($audios[$key]);
                }
            }
        }
        $this->numOfSongs = $this->numOfSongs + count($audios);
        $output->writeln("Final stream files to be processed: " . count($audios_clean), OutputInterface::VERBOSITY_VERBOSE);
        return $audios;
    }

    /**
     * Analyze ID3 Tags
     * if fseek is not possible, libsmbclient-php is not installed or an external storage is used which does not support this.
     * then fallback to slow extraction via tmpfile
     *
     * @param $audio object
     * @param $getID3 object
     * @param OutputInterface $output
     */
    private function analyze($audio, $getID3, OutputInterface $output = null)
    {
        $this->ID3Tags = array();
        $ThisFileInfo = array();
        if ($audio->getMimetype() === 'audio/mpegurl' or $audio->getMimetype() === 'audio/x-scpls' or $audio->getMimetype() === 'application/xspf+xml') {
            $ThisFileInfo['comments']['genre'][0] = 'Stream';
            $ThisFileInfo['comments']['artist'][0] = 'Stream';
            $ThisFileInfo['comments']['album'][0] = 'Stream';
            $ThisFileInfo['bitrate'] = 0;
            $ThisFileInfo['playtime_string'] = 0;
        } else {

            $availability =  $audio->getStorage()->getAvailability();
            if (!$availability['available']) {
                $output->writeln("Some external storage is not available", OutputInterface::VERBOSITY_VERBOSE);
                $this->logger->debug('Some external storage is not available', array('app' => 'audioplayer'));
            } else {
                $handle = $audio->fopen('rb');
                if (is_resource($handle) && @fseek($handle, -24, SEEK_END) === 0) {
                    $ThisFileInfo = $getID3->analyze($audio->getPath(), $audio->getSize(), '', $handle);
                } else {
                    if (!$this->noFseek) {
                        $output->writeln("Attention: Only slow indexing due to server config. See Audio Player wiki on GitHub for details.", OutputInterface::VERBOSITY_VERBOSE);
                        $this->logger->debug('Attention: Only slow indexing due to server config. See Audio Player wiki on GitHub for details.', array('app' => 'audioplayer'));
                        $this->noFseek = true;
                    }
                    $fileName = $audio->getStorage()->getLocalFile($audio->getInternalPath());
                    $ThisFileInfo = $getID3->analyze($fileName);

                    if (!$audio->getStorage()->isLocal($audio->getInternalPath())) {
                        unlink($fileName);
                    }
                }
                if ($this->cyrillic === 'checked') $ThisFileInfo = $this->convertCyrillic($ThisFileInfo);
                getid3_lib::CopyTagsToComments($ThisFileInfo);
            }
        }
        $this->ID3Tags = $ThisFileInfo;
    }

    /**
     * Concert cyrillic characters
     *
     * @param array $ThisFileInfo
     * @return array
     */
    private function convertCyrillic($ThisFileInfo)
    {
        //$this->logger->debug('cyrillic handling activated', array('app' => 'audioplayer'));
        // Check, if this tag was win1251 before the incorrect "8859->utf" convertion by the getid3 lib
        foreach (array('id3v1', 'id3v2') as $ttype) {
            $ruTag = 0;
            if (isset($ThisFileInfo['tags'][$ttype])) {
                // Check, if this tag was win1251 before the incorrect "8859->utf" convertion by the getid3 lib
                foreach (array('album', 'artist', 'title', 'band', 'genre') as $tkey) {
                    if (isset($ThisFileInfo['tags'][$ttype][$tkey])) {
                        if (preg_match('#[\\xA8\\B8\\x80-\\xFF]{4,}#', iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $ThisFileInfo['tags'][$ttype][$tkey][0]))) {
                            $ruTag = 1;
                            break;
                        }
                    }
                }
                // Now make a correct conversion
                if ($ruTag === 1) {
                    foreach (array('album', 'artist', 'title', 'band', 'genre') as $tkey) {
                        if (isset($ThisFileInfo['tags'][$ttype][$tkey])) {
                            $ThisFileInfo['tags'][$ttype][$tkey][0] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $ThisFileInfo['tags'][$ttype][$tkey][0]);
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
                return preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $this->ID3Tags['comments'][$ID3Value[$i]][0]);
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
        if ($parentId === $this->parentIdPrevious) {
            if ($this->folderPicture) {
                $output->writeln("     Reusing previous folder image", OutputInterface::VERBOSITY_VERY_VERBOSE);
                $this->processImageString($iAlbumId, $this->folderPicture->getContent());
            } elseif (isset($this->ID3Tags['comments']['picture'][0]['data'])) {
                $data = $this->ID3Tags['comments']['picture'][0]['data'];
                $this->processImageString($iAlbumId, $data);
            }
        } else {
            $this->folderPicture = false;
            if ($audio->getParent()->nodeExists('cover.jpg')) {
                $this->folderPicture = $audio->getParent()->get('cover.jpg');
            } elseif ($audio->getParent()->nodeExists('Cover.jpg')) {
                $this->folderPicture = $audio->getParent()->get('Cover.jpg');
            } elseif ($audio->getParent()->nodeExists('cover.jpeg')) {
                $this->folderPicture = $audio->getParent()->get('cover.jpeg');
            } elseif ($audio->getParent()->nodeExists('Cover.jpeg')) {
                $this->folderPicture = $audio->getParent()->get('Cover.jpeg');
            } elseif ($audio->getParent()->nodeExists('cover.png')) {
                $this->folderPicture = $audio->getParent()->get('cover.png');
            } elseif ($audio->getParent()->nodeExists('Cover.png')) {
                $this->folderPicture = $audio->getParent()->get('Cover.png');
            } elseif ($audio->getParent()->nodeExists('folder.jpg')) {
                $this->folderPicture = $audio->getParent()->get('folder.jpg');
            } elseif ($audio->getParent()->nodeExists('Folder.jpg')) {
                $this->folderPicture = $audio->getParent()->get('Folder.jpg');
            } elseif ($audio->getParent()->nodeExists('folder.jpeg')) {
                $this->folderPicture = $audio->getParent()->get('folder.jpeg');
            } elseif ($audio->getParent()->nodeExists('Folder.jpeg')) {
                $this->folderPicture = $audio->getParent()->get('Folder.jpeg');
            } elseif ($audio->getParent()->nodeExists('folder.png')) {
                $this->folderPicture = $audio->getParent()->get('folder.png');
            } elseif ($audio->getParent()->nodeExists('Folder.png')) {
                $this->folderPicture = $audio->getParent()->get('Folder.png');
            } elseif ($audio->getParent()->nodeExists('front.jpg')) {
                $this->folderPicture = $audio->getParent()->get('front.jpg');
            } elseif ($audio->getParent()->nodeExists('Front.jpg')) {
                $this->folderPicture = $audio->getParent()->get('Front.jpg');
            } elseif ($audio->getParent()->nodeExists('front.jpeg')) {
                $this->folderPicture = $audio->getParent()->get('front.jpeg');
            } elseif ($audio->getParent()->nodeExists('Front.jpeg')) {
                $this->folderPicture = $audio->getParent()->get('Front.jpeg');
            } elseif ($audio->getParent()->nodeExists('front.png')) {
                $this->folderPicture = $audio->getParent()->get('front.png');
            } elseif ($audio->getParent()->nodeExists('Front.png')) {
                $this->folderPicture = $audio->getParent()->get('Front.png');
            }

            if ($this->folderPicture) {
                $output->writeln("     Alternative album art: " . $this->folderPicture->getInternalPath(), OutputInterface::VERBOSITY_VERY_VERBOSE);
                $this->processImageString($iAlbumId, $this->folderPicture->getContent());
            } elseif (isset($this->ID3Tags['comments']['picture'])) {
                $data = $this->ID3Tags['comments']['picture'][0]['data'];
                $this->processImageString($iAlbumId, $data);
            }
            $this->parentIdPrevious = $parentId;
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
        $image = new Image();
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
    private function truncateStrings($string, $length, $dots = "...")
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
     * @NoAdminRequired
     *
     * @throws NotFoundException
     */
    public function checkNewTracks()
    {
        // get only the relevant audio files
        $output = new NullOutput();
        $this->getAudioObjects($output);
        $this->getStreamObjects($output);
        return ($this->numOfSongs !== 0);
    }
}
