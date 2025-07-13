<?php
namespace OCA\audioplayer\Db;

use OCP\IDBConnection;
use OCP\IL10N;
use OCP\DB\QueryBuilder\IQueryBuilder;

class MusicMapper
{
    private IDBConnection $db;
    private IL10N $l10n;

    public function __construct(IDBConnection $db, IL10N $l10n)
    {
        $this->db = $db;
        $this->l10n = $l10n;
    }

    public function getTrackInfoForFile(string $userId, int $fileId): ?array
    {
        $qb = $this->db->getQueryBuilder();

        $bitrateFunction = $qb->createFunction('ROUND((' . $qb->getColumnName('AT.bitrate') . ' / 1000),0)');

        $qb->select('AT.title')
            ->selectAlias('AG.name', 'genre')
            ->selectAlias('AB.name', 'album')
            ->addSelect('AT.artist_id')
            ->addSelect('AT.length')
            ->addSelect('AT.bitrate')
            ->addSelect('AT.year')
            ->selectAlias('AA.name', 'artist')
            ->selectAlias($bitrateFunction, 'bitrate')
            ->addSelect('AT.disc')
            ->addSelect('AT.number')
            ->addSelect('AT.composer')
            ->addSelect('AT.subtitle')
            ->addSelect('AT.comment')
            ->addSelect('AT.mimetype')
            ->selectAlias('AB.id', 'album_id')
            ->selectAlias('AB.artist_id', 'albumArtist_id')
            ->addSelect('AT.isrc')
            ->addSelect('AT.copyright')
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_artists', 'AA', $qb->expr()->eq('AT.artist_id', 'AA.id'))
            ->leftJoin('AT', 'audioplayer_genre', 'AG', $qb->expr()->eq('AT.genre_id', 'AG.id'))
            ->leftJoin('AT', 'audioplayer_albums', 'AB', $qb->expr()->eq('AT.album_id', 'AB.id'))
            ->where($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('AT.file_id', $qb->createNamedParameter($fileId)))
            ->orderBy('AT.album_id', 'ASC')
            ->addOrderBy('AT.number', 'ASC');

        $statement = $qb->execute();
        $row = $statement->fetch();
        $statement->closeCursor();

        return $row ?: null;
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

    public function getFileId(string $userId, int $trackId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('file_id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($trackId)));

        $statement = $qb->execute();
        $row = $statement->fetch();
        $statement->closeCursor();

        return (int)($row['file_id'] ?? 0);
    }
}
