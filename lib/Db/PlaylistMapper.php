<?php
namespace OCA\audioplayer\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class PlaylistMapper extends QBMapper
{
    public const PLAYLIST_TABLE = 'audioplayer_playlists';
    public const TRACK_TABLE = 'audioplayer_playlist_tracks';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::PLAYLIST_TABLE);
    }

    public function findByUserAndName(string $userId, string $name): ?int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from(self::PLAYLIST_TABLE)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($name)));
        $stmt = $qb->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row ? (int)$row['id'] : null;
    }

    public function insertPlaylist(string $userId, string $name): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::PLAYLIST_TABLE)
            ->values([
                'user_id' => $qb->createNamedParameter($userId),
                'name' => $qb->createNamedParameter($name),
            ])->execute();
        return (int)$this->db->lastInsertId('*PREFIX*' . self::PLAYLIST_TABLE);
    }

    public function updatePlaylistName(int $id, string $userId, string $name): bool
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update(self::PLAYLIST_TABLE)
            ->set('name', $qb->createNamedParameter($name))
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($id)))
            ->execute();
        return true;
    }

    public function trackExists(int $playlistId, int $trackId): bool
    {
        $qb = $this->db->getQueryBuilder();
        $qb->selectAlias($qb->func()->count('track_id'), 'tracks')
            ->from(self::TRACK_TABLE)
            ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter($playlistId)))
            ->andWhere($qb->expr()->eq('track_id', $qb->createNamedParameter($trackId)));
        $stmt = $qb->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return (int)$row['tracks'] > 0;
    }

    public function insertTrack(int $playlistId, int $trackId, int $sortOrder): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::TRACK_TABLE)
            ->values([
                'playlist_id' => $qb->createNamedParameter($playlistId),
                'track_id' => $qb->createNamedParameter($trackId),
                'sortorder' => $qb->createNamedParameter($sortOrder),
            ])->execute();
    }

    public function updateTrackOrder(int $playlistId, int $trackId, int $sortOrder): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update(self::TRACK_TABLE)
            ->set('sortorder', $qb->createNamedParameter($sortOrder))
            ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter($playlistId)))
            ->andWhere($qb->expr()->eq('track_id', $qb->createNamedParameter($trackId)))
            ->execute();
    }

    public function deleteTrack(int $playlistId, int $trackId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TRACK_TABLE)
            ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter($playlistId)))
            ->andWhere($qb->expr()->eq('track_id', $qb->createNamedParameter($trackId)))
            ->execute();
    }

    public function deletePlaylist(int $playlistId, string $userId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::PLAYLIST_TABLE)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($playlistId)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->execute();
    }

    public function deletePlaylistTracks(int $playlistId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TRACK_TABLE)
            ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter($playlistId)))
            ->execute();
    }
}
