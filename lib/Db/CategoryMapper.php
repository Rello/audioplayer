<?php
namespace OCA\audioplayer\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class CategoryMapper
{
    private IDBConnection $db;
    private IL10N $l10n;

    public function __construct(IDBConnection $db, IL10N $l10n)
    {
        $this->db = $db;
        $this->l10n = $l10n;
    }

    public function getArtists(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->selectAlias('AT.artist_id', 'id')
            ->addSelect('AA.name')
            ->selectAlias($qb->func()->lower('AA.name'), 'lower')
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_artists', 'AA', $qb->expr()->eq('AT.artist_id', 'AA.id'))
            ->where($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)))
            ->groupBy('AT.artist_id')
            ->addGroupBy('AA.name')
            ->orderBy($qb->func()->lower('AA.name'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getGenres(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->addSelect('name')
            ->selectAlias($qb->func()->lower('name'), 'lower')
            ->from('audioplayer_genre')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy($qb->func()->lower('name'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getYears(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->selectDistinct('year', 'id')
            ->addSelect('year', 'name')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('year', 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getStreams(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->selectAlias('file_id', 'id')
            ->addSelect('title AS name')
            ->selectAlias($qb->func()->lower('title'), 'lower')
            ->from('audioplayer_streams')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy($qb->func()->lower('title'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getPlaylists(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->addSelect('name')
            ->selectAlias($qb->func()->lower('name'), 'lower')
            ->from('audioplayer_playlists')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy($qb->func()->lower('name'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getFolders(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->selectAlias('FC.fileid', 'id')
            ->addSelect('FC.name')
            ->selectAlias($qb->func()->lower('FC.name'), 'lower')
            ->from('audioplayer_tracks', 'AT')
            ->join('AT', 'filecache', 'FC', $qb->expr()->eq('FC.fileid', 'AT.folder_id'))
            ->where($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)))
            ->groupBy('FC.fileid')
            ->addGroupBy('FC.name')
            ->orderBy($qb->func()->lower('FC.name'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getAlbums(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('AB.id')
            ->addSelect('AB.name')
            ->selectAlias($qb->func()->lower('AB.name'), 'lower')
            ->from('audioplayer_albums', 'AB')
            ->where($qb->expr()->eq('AB.user_id', $qb->createNamedParameter($userId)))
            ->orderBy($qb->func()->lower('AB.name'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getAlbumArtists(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->selectDistinct('AB.artist_id AS id')
            ->addSelect('AA.name')
            ->selectAlias($qb->func()->lower('AA.name'), 'lower')
            ->from('audioplayer_albums', 'AB')
            ->join('AB', 'audioplayer_artists', 'AA', $qb->expr()->eq('AB.artist_id', 'AA.id'))
            ->where($qb->expr()->eq('AB.user_id', $qb->createNamedParameter($userId)))
            ->orderBy($qb->func()->lower('AA.name'), 'ASC');
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getAlbumCovers(string $userId, string $category, ?string $categoryId): array
    {
        $columnMap = [
            'Artist' => 'AT.artist_id',
            'Genre' => 'AT.genre_id',
            'Album' => 'AB.id',
            'Album Artist' => 'AB.artist_id',
            'Year' => 'AT.year',
            'Folder' => 'AT.folder_id',
        ];
        $qb = $this->db->getQueryBuilder();
        $function = $qb->createFunction('CASE WHEN ' . $qb->getColumnName('AB.cover') . ' IS NOT NULL THEN ' . $qb->getColumnName('AB.id') . ' ELSE NULL END');
        $qb->select('AB.id')
            ->addSelect('AB.name')
            ->selectAlias($qb->func()->lower('AB.name'), 'lower')
            ->addSelect('AA.id AS art')
            ->selectAlias($function, 'cid')
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_albums', 'AB', $qb->expr()->eq('AT.album_id', 'AB.id'))
            ->leftJoin('AB', 'audioplayer_artists', 'AA', $qb->expr()->eq('AB.artist_id', 'AA.id'))
            ->where($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)))
            ->groupBy('AB.id')
            ->addGroupBy('AA.id')
            ->addGroupBy('AB.name')
            ->orderBy($qb->func()->lower('AB.name'), 'ASC');
        if ($categoryId !== null && isset($columnMap[$category])) {
            $qb->andWhere($qb->expr()->eq($columnMap[$category], $qb->createNamedParameter($categoryId)));
        }
        $statement = $qb->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();
        return $rows;
    }

    public function getTrackCount(string $category, string $categoryId, string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        switch ($category) {
            case 'Artist':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_tracks', 'AT')
                    ->where($qb->expr()->eq('AT.artist_id', $qb->createNamedParameter((int)$categoryId)))
                    ->andWhere($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));
                break;
            case 'Genre':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_tracks', 'AT')
                    ->where($qb->expr()->eq('AT.genre_id', $qb->createNamedParameter((int)$categoryId)))
                    ->andWhere($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));
                break;
            case 'Year':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_tracks', 'AT')
                    ->where($qb->expr()->eq('AT.year', $qb->createNamedParameter((int)$categoryId)))
                    ->andWhere($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));
                break;
            case 'Title':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_tracks', 'AT')
                    ->where($qb->expr()->gt('AT.id', $qb->createNamedParameter(0)))
                    ->andWhere($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));
                break;
            case 'Playlist':
                $qb->selectAlias($qb->func()->count('track_id'), 'count')
                    ->from('audioplayer_playlist_tracks')
                    ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter((int)$categoryId)));
                break;
            case 'Folder':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_tracks', 'AT')
                    ->where($qb->expr()->eq('AT.folder_id', $qb->createNamedParameter((int)$categoryId)))
                    ->andWhere($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));
                break;
            case 'Album':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_tracks', 'AT')
                    ->where($qb->expr()->eq('AT.album_id', $qb->createNamedParameter((int)$categoryId)))
                    ->andWhere($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)));
                break;
            case 'Album Artist':
                $qb->selectAlias($qb->func()->count('AT.id'), 'count')
                    ->from('audioplayer_albums', 'AB')
                    ->join('AB', 'audioplayer_tracks', 'AT', $qb->expr()->eq('AB.id', 'AT.album_id'))
                    ->where($qb->expr()->eq('AB.artist_id', $qb->createNamedParameter((int)$categoryId)))
                    ->andWhere($qb->expr()->eq('AB.user_id', $qb->createNamedParameter($userId)));
                break;
            default:
                return 0;
        }
        $statement = $qb->execute();
        $row = $statement->fetch();
        $statement->closeCursor();
        return (int)($row['count'] ?? 0);
    }

    public function loadArtistsToAlbum(int $albumId, int $artistId): string
    {
        if ($artistId !== 0) {
            $qb = $this->db->getQueryBuilder();
            $qb->select('name')
                ->from('audioplayer_artists')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($artistId)));
            $statement = $qb->execute();
            $row = $statement->fetch();
            $statement->closeCursor();
            return $row['name'] ?? '';
        }
        $qb = $this->db->getQueryBuilder();
        $qb->selectDistinct('artist_id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('album_id', $qb->createNamedParameter($albumId)));
        $statement = $qb->execute();
        $artist = $statement->fetch();
        $rowCount = $statement->rowCount();
        $statement->closeCursor();
        if ($rowCount === 1 && $artist) {
            $qb = $this->db->getQueryBuilder();
            $qb->select('name')
                ->from('audioplayer_artists')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($artist['artist_id'])));
            $statement = $qb->execute();
            $row = $statement->fetch();
            $statement->closeCursor();
            return $row['name'] ?? '';
        }
        return (string)$this->l10n->t('Various Artists');
    }
}
