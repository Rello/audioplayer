<?php
namespace OCA\audioplayer\Service;

use OCA\audioplayer\Db\MusicMapper;
use OCA\audioplayer\Http\AudioStream;
use OCP\AppFramework\PublicShareController;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\ISession;
use OCP\Share\IManager;
use OCP\Share\IShare;

class MusicService
{
    private string $userId;
    private IL10N $l10n;
    private IManager $shareManager;
    private IRootFolder $rootFolder;
    private MusicMapper $mapper;
    private ISession $session;

    public function __construct(string $userId, IL10N $l10n, IManager $shareManager, IRootFolder $rootFolder, MusicMapper $mapper, ISession $session)
    {
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->shareManager = $shareManager;
        $this->rootFolder = $rootFolder;
        $this->mapper = $mapper;
        $this->session = $session;
    }

    /**
     * Reject access to a password-protected public share unless the current
     * session has already authenticated to that share token, mirroring the
     * gate used by core's public share endpoints
     * (\OCP\AppFramework\PublicShareController). Without this, any holder of the
     * share token could read/stream the shared audio without the share password.
     */
    private function assertShareIsPubliclyAccessible(IShare $share): void
    {
        if ($share->getPassword() === null) {
            return;
        }
        $authenticated = json_decode(
            (string)($this->session->get(PublicShareController::DAV_AUTHENTICATED_FRONTEND) ?? '[]'),
            true
        );
        if (is_array($authenticated)
            && isset($authenticated[$share->getToken()])
            && hash_equals((string)$share->getPassword(), (string)$authenticated[$share->getToken()])) {
            return;
        }
        throw new NotFoundException('The shared file is password protected');
    }

    public function getPublicAudioInfo(string $token): ?array
    {
        $share = $this->shareManager->getShareByToken($token);
        $this->assertShareIsPubliclyAccessible($share);
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
        $this->assertShareIsPubliclyAccessible($share);
        $fileOwner = $share->getShareOwner();

        $userFolder = $this->rootFolder->getUserFolder($fileOwner);
        $nodes = $userFolder->getById($share->getNodeId());
        $pfile = array_shift($nodes);
        $path = $pfile->getPath();
        $segments = explode('/', trim($path, '/'), 3);
        $startPath = $segments[2];

        $filenameAudio = $startPath . '/' . rawurldecode($file);

        $node = $userFolder->get($filenameAudio);
        if (!$node instanceof File) {
            throw new NotFoundException($filenameAudio);
        }

        return new AudioStream($node);
    }

    public function createAudioStream(?string $file, ?string $t): AudioStream
    {
        $userFolder = $this->rootFolder->getUserFolder($this->userId);
        if ($t) {
            $fileId = $this->mapper->getFileId($this->userId, (int)$t);
            $nodes = $userFolder->getById($fileId);
            $node = array_shift($nodes);
        } else {
            $filename = rawurldecode($file);
            $node = $userFolder->get($filename);
        }

        if (!$node instanceof File) {
            throw new NotFoundException((string)($file ?? $t));
        }

        return new AudioStream($node);
    }
}
