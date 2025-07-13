<?php
namespace OCA\audioplayer\Service;

use OCA\audioplayer\Db\SidebarMapper;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ITagManager;
use Psr\Log\LoggerInterface;

class SidebarService
{
    private $userId;
	private $logger;
    private $l10n;
    private $tagManager;
    private $mapper;
    private $rootFolder;

    public function __construct(
		$userId,
        IL10N $l10n,
		LoggerInterface $logger,
        ITagManager $tagManager,
        SidebarMapper $mapper,
        IRootFolder $rootFolder
    ) {
        $this->userId = $userId;
        $this->l10n = $l10n;
		$this->logger = $logger;
        $this->tagManager = $tagManager;
        $this->mapper = $mapper;
        $this->rootFolder = $rootFolder;
    }

    public function getAudioInfo(int $trackId): array
    {
		$this->logger->info('user id: ' . $this->userId);
        $row = $this->mapper->findTrackInfo($this->userId, $trackId, null);
        if (empty($row)) {
            return [];
        }
		$this->logger->info('row: ' . json_encode($row));

        $row['Album Artist'] = $this->getAlbumArtistName($row['album_id'], (int)$row['artist_id']);

        if ($row['year'] === '0') {
            $row['year'] = $this->l10n->t('Unknown');
        }
        if ($row['Bitrate'] !== '') {
            $row['Bitrate'] = $row['Bitrate'] . ' kbps';
        }

		foreach (['file_id', 'id', 'album_id', 'artist_id'] as $column) {
			if (isset($row[$column])) {
				unset($row[$column]);
			}
		}
        $fileId = $row['file_id'];
        if ($fileId !== null) {
            $nodes = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
            if (!empty($nodes)) {
                $node = $nodes[0];
                $path = $this->rootFolder->getUserFolder($this->userId)->getRelativePath($node->getPath());
                $row['Path'] = join('/', array_map('rawurlencode', explode('/', $path)));
            }
        }
		$this->logger->info('row2: ' . json_encode($row));
        $favorites = $this->tagManager->load('files')->getFavorites();
        $row['fav'] = in_array($row['file_id'], $favorites) ? 't' : 'f';

		ksort($row);
        return $row;
    }

    public function getPlaylists(int $trackId): array
    {
        $results = $this->mapper->findPlaylistsForTrack($this->userId, $trackId);
        $playlists = [];
        foreach ($results as $row) {
            $playlists[] = [
                'playlist_id' => $row['playlist_id'],
                'name' => $row['name'],
            ];
        }
        return $playlists;
    }

    private function getAlbumArtistName(int $albumId, int $artistId)
    {
        if ($artistId !== 0) {
            return $this->mapper->getArtistName($artistId);
        }
        $artists = $this->mapper->getDistinctArtistIdsForAlbum($albumId);
        if (count($artists) === 1) {
            return $this->mapper->getArtistName($artists[0]);
        }
        return (string)$this->l10n->t('Various Artists');
    }
}
