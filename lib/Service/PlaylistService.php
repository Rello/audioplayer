<?php
namespace OCA\audioplayer\Service;

use OCA\audioplayer\DB\PlaylistMapper;

class PlaylistService
{
    private $userId;
    private $mapper;

    public function __construct(string $userId, PlaylistMapper $mapper)
    {
        $this->userId = $userId;
        $this->mapper = $mapper;
    }

    public function addPlaylist(string $name): array
    {
        $existing = $this->mapper->findByUserAndName($this->userId, $name);
        if ($existing !== null) {
            return ['msg' => 'exist', 'id' => $existing];
        }

        $id = $this->mapper->insertPlaylist($this->userId, $name);
        return ['msg' => 'new', 'id' => $id];
    }

    public function updatePlaylist(int $playlistId, string $name): bool
    {
        return $this->mapper->updatePlaylistName($playlistId, $this->userId, $name);
    }

    public function addTrackToPlaylist(int $playlistId, int $trackId, int $order): bool
    {
        if ($this->mapper->trackExists($playlistId, $trackId)) {
            return false;
        }
        $this->mapper->insertTrack($playlistId, $trackId, $order);
        return true;
    }

    public function sortPlaylist(int $playlistId, array $trackIds): void
    {
        $counter = 1;
        foreach ($trackIds as $trackId) {
            if ($trackId === '') {
                continue;
            }
            $this->mapper->updateTrackOrder($playlistId, (int)$trackId, $counter);
            $counter++;
        }
    }

    public function removeTrackFromPlaylist(int $playlistId, int $trackId): bool
    {
        $this->mapper->deleteTrack($playlistId, $trackId);
        return true;
    }

    public function removePlaylist(int $playlistId): bool
    {
        $this->mapper->deletePlaylist($playlistId, $this->userId);
        $this->mapper->deletePlaylistTracks($playlistId);
        return true;
    }
}
