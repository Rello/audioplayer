<?php
namespace OCA\audioplayer\Service;

use OCA\audioplayer\DB\MusicMapper;
use OCA\audioplayer\Http\AudioStream;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\Share\IManager;

class MusicService
{
    private string $userId;
    private IL10N $l10n;
    private IManager $shareManager;
    private IRootFolder $rootFolder;
    private MusicMapper $mapper;

    public function __construct(string $userId, IL10N $l10n, IManager $shareManager, IRootFolder $rootFolder, MusicMapper $mapper)
    {
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->shareManager = $shareManager;
        $this->rootFolder = $rootFolder;
        $this->mapper = $mapper;
    }

    public function getPublicAudioInfo(string $token): ?array
    {
        $share = $this->shareManager->getShareByToken($token);
        $fileId = $share->getNodeId();
        $fileOwner = $share->getShareOwner();

        $row = $this->mapper->getTrackInfoForFile($fileOwner, (int)$fileId);
        if (!$row) {
            return null;
        }

        $artist = $this->mapper->loadArtistsToAlbum((int)$row['album_id'], (int)$row['albumArtist_id']);
        $row['albumartist'] = $artist;
        if ($row['year'] === '0') {
            $row['year'] = $this->l10n->t('Unknown');
        }
        return $row;
    }

    public function createPublicAudioStream(string $token, string $file): AudioStream
    {
        $share = $this->shareManager->getShareByToken($token);
        $fileOwner = $share->getShareOwner();

        $nodes = $this->rootFolder->getUserFolder($fileOwner)->getById($share->getNodeId());
        $pfile = array_shift($nodes);
        $path = $pfile->getPath();
        $segments = explode('/', trim($path, '/'), 3);
        $startPath = $segments[2];

        $filenameAudio = $startPath . '/' . rawurldecode($file);

        return new AudioStream($filenameAudio, $fileOwner);
    }

    public function createAudioStream(?string $file, ?string $t): AudioStream
    {
        if ($t) {
            $fileId = $this->mapper->getFileId($this->userId, (int)$t);
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
            $fileNode = array_shift($nodes);
            $path = $fileNode->getPath();
            $segments = explode('/', trim($path, '/'), 3);
            $filename = $segments[2];
        } else {
            $filename = rawurldecode($file);
        }

        return new AudioStream($filename, $this->userId);
    }
}
