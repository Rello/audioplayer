<?php
namespace OCA\audioplayer\Db;

use OCP\IDBConnection;

class SidebarMapper
{
    private $db;

    public function __construct(IDBConnection $db)
    {
        $this->db = $db;
    }

    public function findTrackInfo(int $userId, ?int $trackId = null, ?int $fileId = null): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select([
                'AT.title',
                'AT.subtitle',
                'AA.name AS Artist',
                'AB.artist_id AS album_artist',
                'AT.composer',
                'AB.name AS Album',
                'AG.name AS Genre',
                'AT.year',
                'AT.disc',
                'AT.number',
                'AT.length',
                $qb->createFunction('ROUND((AT.bitrate / 1000 ),0)') . ' AS Bitrate',
                'AT.mimetype',
                'AT.isrc',
                'AT.copyright',
                'AT.file_id',
                'AB.id AS album_id',
                'AT.id'
            ])
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_artists', 'AA', $qb->expr()->eq('AT.artist_id', 'AA.id'))
            ->leftJoin('AT', 'audioplayer_genre', 'AG', $qb->expr()->eq('AT.genre_id', 'AG.id'))
            ->leftJoin('AT', 'audioplayer_albums', 'AB', $qb->expr()->eq('AT.album_id', 'AB.id'))
            ->where($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));

        if ($trackId !== null) {
            $qb->andWhere($qb->expr()->eq('AT.id', $qb->createNamedParameter($trackId)));
        } elseif ($fileId !== null) {
            $qb->andWhere($qb->expr()->eq('AT.file_id', $qb->createNamedParameter($fileId)));
        }
        $qb->orderBy('AT.album_id')
            ->addOrderBy('AT.number');

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        return $row ?: [];
    }

    public function getFileId(int $userId, int $trackId): ?int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('file_id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($trackId)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        return $row['file_id'] ?? null;
    }

    public function findPlaylistsForTrack(int $userId, int $trackId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('AP.playlist_id', 'AN.name')
            ->from('audioplayer_playlist_tracks', 'AP')
            ->leftJoin('AP', 'audioplayer_playlists', 'AN', $qb->expr()->eq('AP.playlist_id', 'AN.id'))
            ->where($qb->expr()->eq('AN.user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('AP.track_id', $qb->createNamedParameter($trackId)))
            ->orderBy($qb->func()->lower('AN.name'));
        $result = $qb->execute();
        $rows = $result->fetchAll();
        $result->closeCursor();
        return $rows;
    }

    public function getArtistName(int $artistId): ?string
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('name')
            ->from('audioplayer_artists')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($artistId)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        return $row['name'] ?? null;
    }

    public function getDistinctArtistIdsForAlbum(int $albumId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('DISTINCT(`artist_id`) AS artist_id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('album_id', $qb->createNamedParameter($albumId)));
        $result = $qb->execute();
        $rows = $result->fetchAll();
        $result->closeCursor();
        return array_column($rows, 'artist_id');
    }
}
