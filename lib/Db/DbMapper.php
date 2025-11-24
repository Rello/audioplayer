<?php
namespace OCA\audioplayer\Db;

use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Share\IManager;
use OCP\ITagManager;
use Psr\Log\LoggerInterface;

class DbMapper
{
    private $userId;
    private IL10N $l10n;
    private IDBConnection $db;
    private ITagManager $tagManager;
    private LoggerInterface $logger;
    private bool $occ_job = false;

    public function __construct(
		$userId,
        IL10N $l10n,
        IDBConnection $db,
        ITagManager $tagManager,
        LoggerInterface $logger
    ) {
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->tagManager = $tagManager;
        $this->logger = $logger;
    }

    public function loadArtistsToAlbum(int $albumId, int $artistId): string
    {
        if ($artistId !== 0) {
            $qb = $this->db->getQueryBuilder();
            $qb->select('name')
                ->from('audioplayer_artists')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($artistId)));
            $result = $qb->execute();
            $row = $result->fetch();
            $result->closeCursor();
            return $row['name'] ?? '';
        }

        $qb = $this->db->getQueryBuilder();
        $qb->selectDistinct('artist_id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('album_id', $qb->createNamedParameter($albumId)));
        $result = $qb->execute();
        $rows = $result->fetchAll();
        $result->closeCursor();
        if (count($rows) === 1) {
            $artistId = (int)$rows[0]['artist_id'];
            $qb = $this->db->getQueryBuilder();
            $qb->select('name')
                ->from('audioplayer_artists')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($artistId)));
            $result = $qb->execute();
            $row = $result->fetch();
            $result->closeCursor();
            return $row['name'] ?? '';
        }
        return (string)$this->l10n->t('Various Artists');
    }

    public function search(string $searchquery): array
    {
        $searchresult = [];
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'name')
            ->from('audioplayer_albums')
            ->where($qb->expr()->like($qb->func()->lower('name'), $qb->createNamedParameter('%' . strtolower(addslashes($searchquery)) . '%')))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));
        $result = $qb->execute();
        $albums = $result->fetchAll();
        $result->closeCursor();
        foreach ($albums as $row) {
            $searchresult[] = [
                'id' => 'Album-' . $row['id'],
                'name' => $this->l10n->t('Album') . ': ' . $row['name'],
            ];
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('AA.id', 'AA.name')
            ->from('audioplayer_artists', 'AA')
            ->join('AA', 'audioplayer_tracks', 'AT', $qb->expr()->eq('AA.id', 'AT.artist_id'))
            ->where($qb->expr()->like($qb->func()->lower('AA.name'), $qb->createNamedParameter('%' . strtolower(addslashes($searchquery)) . '%')))
            ->andWhere($qb->expr()->eq('AA.user_id', $qb->createNamedParameter($this->userId)));
        $result = $qb->execute();
        $artists = $result->fetchAll();
        $result->closeCursor();
        foreach ($artists as $row) {
            $searchresult[] = [
                'id' => 'Artist-' . $row['id'],
                'name' => $this->l10n->t('Artist') . ': ' . $row['name'],
            ];
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('album_id', 'title')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->like($qb->func()->lower('title'), $qb->createNamedParameter('%' . strtolower(addslashes($searchquery)) . '%')))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));
        $result = $qb->execute();
        $tracks = $result->fetchAll();
        $result->closeCursor();
        foreach ($tracks as $row) {
            $searchresult[] = [
                'id' => 'Album-' . $row['album_id'],
                'name' => $this->l10n->t('Title') . ': ' . $row['title'],
            ];
        }
        return $searchresult;
    }
    public function resetMediaLibrary(?string $userId = null, $output = null, $hook = null)
    {
        if ($userId !== null) {
            $this->occ_job = true;
            $this->userId = $userId;
        } else {
            $this->occ_job = false;
        }

        $this->db->beginTransaction();
        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_tracks')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_artists')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_genre')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_albums')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('audioplayer_playlists')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));
        $result = $qb->execute();
        $results = $result->fetchAll();
        $result->closeCursor();
        foreach ($results as $row) {
            $qb = $this->db->getQueryBuilder();
            $qb->delete('audioplayer_playlist_tracks')
                ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter($row['id'])))
                ->execute();
        }

        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_playlists')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_stats')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $qb = $this->db->getQueryBuilder();
        $qb->delete('audioplayer_streams')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
            ->execute();

        $this->db->commit();

        $result = [
            'status' => 'success',
            'msg' => 'all good',
        ];

        if (!$this->occ_job) {
            return new JSONResponse($result);
        } elseif ($hook === null && $output) {
            $output->writeln('Reset finished');
        } else {
            $this->logger->info('Deleting Audioplayer library for: ' . $userId , ['app' => 'audioplayer']);
        }
        return true;
    }

    public function deleteFromDB(int $fileId, ?string $userId = null): bool
    {
		if ($userId !== null) {
            $this->userId = $userId;
        }
        // $this->logger->debug('deleteFromDB: ' . $fileId, ['app' => 'audioplayer']);

        $qb = $this->db->getQueryBuilder();
        $qb->select('album_id', 'id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        if (isset($row['id'])) {
            $albumId = (int)$row['album_id'];
            $trackId = (int)$row['id'];

            $qb = $this->db->getQueryBuilder();
            $qb->selectAlias($qb->func()->count('album_id'), 'cnt')
                ->from('audioplayer_tracks')
                ->where($qb->expr()->eq('album_id', $qb->createNamedParameter($albumId)));
            $result = $qb->execute();
            $countRow = $result->fetch();
            $result->closeCursor();
            if ((int)$countRow['cnt'] === 1) {
                $qb = $this->db->getQueryBuilder();
                $qb->delete('audioplayer_albums')
                    ->where($qb->expr()->eq('id', $qb->createNamedParameter($albumId)))
                    ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
                    ->execute();
            }

            $qb = $this->db->getQueryBuilder();
            $qb->delete('audioplayer_tracks')
                ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)))
                ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
                ->execute();

            $qb = $this->db->getQueryBuilder();
            $qb->delete('audioplayer_streams')
                ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)))
                ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
                ->execute();

            $qb = $this->db->getQueryBuilder();
            $qb->select('playlist_id')
                ->from('audioplayer_playlist_tracks')
                ->where($qb->expr()->eq('track_id', $qb->createNamedParameter($trackId)));
            $result = $qb->execute();
            $row = $result->fetch();
            $result->closeCursor();

            if ($row && $row['playlist_id']) {
                $playlistId = (int)$row['playlist_id'];
                $qb = $this->db->getQueryBuilder();
                $qb->selectAlias($qb->func()->count('playlist_id'), 'cnt')
                    ->from('audioplayer_playlist_tracks')
                    ->where($qb->expr()->eq('playlist_id', $qb->createNamedParameter($playlistId)));
                $result = $qb->execute();
                $countRow = $result->fetch();
                $result->closeCursor();
                if ((int)$countRow['cnt'] === 1) {
                    $qb = $this->db->getQueryBuilder();
                    $qb->delete('audioplayer_playlists')
                        ->where($qb->expr()->eq('id', $qb->createNamedParameter($playlistId)))
                        ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
                        ->execute();
                }
            }
            $qb = $this->db->getQueryBuilder();
            $qb->delete('audioplayer_playlist_tracks')
                ->where($qb->expr()->eq('track_id', $qb->createNamedParameter($trackId)))
                ->execute();
        }

        return true;
    }

    public function writeCoverToAlbum($userId, int $albumId, string $image): bool
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update('audioplayer_albums')
            ->set('cover', $qb->createNamedParameter($image))
            ->set('bgcolor', $qb->createNamedParameter(''))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($albumId)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->execute();
        return true;
    }
    public function writeAlbumToDB($userId, $album, $year, $artistId, $parentId): array
    {
        $album = $this->truncate($album, '256');
        $year = $this->normalizeInteger($year);
        $albumCount = 0;

        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'artist_id')
            ->from('audioplayer_albums')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($album)))
            ->andWhere($qb->expr()->eq('folder_id', $qb->createNamedParameter($parentId)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        if ($row) {
            if ((int)$row['artist_id'] !== $artistId) {
                $variousId = $this->writeArtistToDB($userId, $this->l10n->t('Various Artists'));
                $qb = $this->db->getQueryBuilder();
                $qb->update('audioplayer_albums')
                    ->set('artist_id', $qb->createNamedParameter($variousId))
                    ->where($qb->expr()->eq('id', $qb->createNamedParameter($row['id'])))
                    ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
                    ->execute();
            }
            $insertId = (int)$row['id'];
        } else {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('audioplayer_albums')
                ->setValue('user_id', $qb->createNamedParameter($userId))
                ->setValue('name', $qb->createNamedParameter($album))
                ->setValue('folder_id', $qb->createNamedParameter($parentId))
                ->execute();
            $insertId = (int)$this->db->lastInsertId('*PREFIX*audioplayer_albums');
            if ($artistId) {
                $qb = $this->db->getQueryBuilder();
                $qb->update('audioplayer_albums')
                    ->set('year', $qb->createNamedParameter($year))
                    ->set('artist_id', $qb->createNamedParameter($artistId))
                    ->where($qb->expr()->eq('id', $qb->createNamedParameter($insertId)))
                    ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
                    ->execute();
            } else {
                $qb = $this->db->getQueryBuilder();
                $qb->update('audioplayer_albums')
                    ->set('year', $qb->createNamedParameter($year))
                    ->where($qb->expr()->eq('id', $qb->createNamedParameter($insertId)))
                    ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
                    ->execute();
            }
            $albumCount = 1;
        }

        return [
            'id' => $insertId,
            'state' => true,
            'albumcount' => $albumCount,
        ];
    }

    private function truncate(string $string, string $length, string $dots = '...'): string
    {
        return (strlen($string) > (int)$length) ? mb_strcut($string, 0, (int)$length - strlen($dots)) . $dots : $string;
    }

    private function normalizeInteger(string $value): int
    {
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

    public function writeArtistToDB($userId, string $artist): int
    {
        $artist = $this->truncate($artist, '256');

        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('audioplayer_artists')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($artist)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        if ($row) {
            return (int)$row['id'];
        }
        $qb = $this->db->getQueryBuilder();
        $qb->insert('audioplayer_artists')
            ->setValue('user_id', $qb->createNamedParameter($userId))
            ->setValue('name', $qb->createNamedParameter($artist))
            ->execute();
        return (int)$this->db->lastInsertId('*PREFIX*audioplayer_artists');
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }

    public function writeGenreToDB($userId, string $genre): int
    {
        $genre = $this->truncate($genre, '256');

        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('audioplayer_genre')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($genre)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        if ($row) {
            return (int)$row['id'];
        }
        $qb = $this->db->getQueryBuilder();
        $qb->insert('audioplayer_genre')
            ->setValue('user_id', $qb->createNamedParameter($userId))
            ->setValue('name', $qb->createNamedParameter($genre))
            ->execute();
        return (int)$this->db->lastInsertId('*PREFIX*audioplayer_genre');
    }

    public function writeTrackToDB($userId, array $track): array
    {
        $duplicate = 0;
        $insertId = 0;
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('title', $qb->createNamedParameter($track['title'])))
            ->andWhere($qb->expr()->eq('number', $qb->createNamedParameter($track['number'])))
            ->andWhere($qb->expr()->eq('artist_id', $qb->createNamedParameter($track['artist_id'])))
            ->andWhere($qb->expr()->eq('album_id', $qb->createNamedParameter($track['album_id'])))
            ->andWhere($qb->expr()->eq('length', $qb->createNamedParameter($track['length'])))
            ->andWhere($qb->expr()->eq('bitrate', $qb->createNamedParameter($track['bitrate'])))
            ->andWhere($qb->expr()->eq('mimetype', $qb->createNamedParameter($track['mimetype'])))
            ->andWhere($qb->expr()->eq('genre_id', $qb->createNamedParameter($track['genre'])))
            ->andWhere($qb->expr()->eq('year', $qb->createNamedParameter($track['year'])))
            ->andWhere($qb->expr()->eq('disc', $qb->createNamedParameter($track['disc'])))
            ->andWhere($qb->expr()->eq('composer', $qb->createNamedParameter($track['composer'])))
            ->andWhere($qb->expr()->eq('subtitle', $qb->createNamedParameter($track['subtitle'])))
            ->andWhere($qb->expr()->eq('comment', $qb->createNamedParameter($track['comment'])));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        if ($row) {
            $duplicate = 1;
        } else {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('audioplayer_tracks')
                ->setValue('user_id', $qb->createNamedParameter($userId))
                ->setValue('title', $qb->createNamedParameter($track['title']))
                ->setValue('number', $qb->createNamedParameter($track['number']))
                ->setValue('artist_id', $qb->createNamedParameter($track['artist_id']))
                ->setValue('album_id', $qb->createNamedParameter($track['album_id']))
                ->setValue('length', $qb->createNamedParameter($track['length']))
                ->setValue('file_id', $qb->createNamedParameter($track['file_id']))
                ->setValue('bitrate', $qb->createNamedParameter($track['bitrate']))
                ->setValue('mimetype', $qb->createNamedParameter($track['mimetype']))
                ->setValue('genre_id', $qb->createNamedParameter($track['genre']))
                ->setValue('year', $qb->createNamedParameter($track['year']))
                ->setValue('folder_id', $qb->createNamedParameter($track['folder_id']))
                ->setValue('disc', $qb->createNamedParameter($track['disc']))
                ->setValue('composer', $qb->createNamedParameter($track['composer']))
                ->setValue('subtitle', $qb->createNamedParameter($track['subtitle']))
                ->setValue('comment', $qb->createNamedParameter($track['comment']))
                ->setValue('isrc', $qb->createNamedParameter($track['isrc']))
                ->setValue('copyright', $qb->createNamedParameter($track['copyright']))
                ->execute();
            $insertId = (int)$this->db->lastInsertId('*PREFIX*audioplayer_tracks');
        }
        return [
            'id' => $insertId,
            'state' => true,
            'dublicate' => $duplicate,
        ];
    }

    public function getTrackInfo(?int $trackId = null, ?int $fileId = null): array
    {
        $qb = $this->db->getQueryBuilder();
        $bitrateFunction = $qb->createFunction('ROUND((' . $qb->getColumnName('AT.bitrate') . ' / 1000),0)');
        $qb->select('AT.title AS Title')
            ->addSelect('AT.subtitle AS Subtitle')
            ->addSelect('AA.name AS Artist')
            ->addSelect('AB.artist_id AS AlbumArtist')
            ->addSelect('AT.composer AS Composer')
            ->addSelect('AB.name AS Album')
            ->addSelect('AG.name AS Genre')
            ->addSelect('AT.year AS Year')
            ->addSelect('AT.disc AS Disc')
            ->addSelect('AT.number AS Track')
            ->addSelect('AT.length AS Length')
            ->addSelect('AT.mimetype AS `MIME type`')
            ->addSelect('AT.comment AS Comment')
            ->addSelect('AT.isrc AS ISRC')
            ->addSelect('AT.copyright AS Copyright')
            ->addSelect('AT.file_id')
            ->addSelect('AB.id AS album_id')
            ->addSelect('AT.id')
            ->addSelectAlias($bitrateFunction, 'Bitrate')
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_artists', 'AA', $qb->expr()->eq('AT.artist_id', 'AA.id'))
            ->leftJoin('AT', 'audioplayer_genre', 'AG', $qb->expr()->eq('AT.genre_id', 'AG.id'))
            ->leftJoin('AT', 'audioplayer_albums', 'AB', $qb->expr()->eq('AT.album_id', 'AB.id'))
            ->where($qb->expr()->eq('AT.user_id', $qb->createNamedParameter($this->userId)));
        if ($trackId !== null) {
            $qb->andWhere($qb->expr()->eq('AT.id', $qb->createNamedParameter($trackId)));
        } else {
            $qb->andWhere($qb->expr()->eq('AT.file_id', $qb->createNamedParameter($fileId)));
        }
        $qb->orderBy('AT.album_id', 'ASC')
            ->addOrderBy('AT.number', 'ASC');
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        $favorites = $this->tagManager->load('files')->getFavorites();
        if ($row && in_array($row['file_id'], $favorites)) {
            $row['fav'] = 't';
        } else {
            $row['fav'] = 'f';
        }
        return $row ?: [];
    }

    public function getFileId(string $userId, int $trackId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('file_id')
            ->from('audioplayer_tracks')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($trackId)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        return (int)($row['file_id'] ?? 0);
    }

    public function updateTrack($userId, int $trackId, string $key, string $value): bool
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update('audioplayer_tracks')
            ->set($key, $qb->createNamedParameter($value))
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($trackId)))
            ->execute();
        return true;
    }

    public function writeStreamToDB($userId, array $stream): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('audioplayer_streams')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('file_id', $qb->createNamedParameter($stream['file_id'])));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        $duplicate = 0;
        $insertId = 0;
        if ($row) {
            $duplicate = 1;
        } else {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('audioplayer_streams')
                ->setValue('user_id', $qb->createNamedParameter($userId))
                ->setValue('title', $qb->createNamedParameter($stream['title']))
                ->setValue('file_id', $qb->createNamedParameter($stream['file_id']))
                ->setValue('mimetype', $qb->createNamedParameter($stream['mimetype']))
                ->execute();
            $insertId = (int)$this->db->lastInsertId('*PREFIX*audioplayer_streams');
        }
        return [
            'id' => $insertId,
            'state' => true,
            'dublicate' => $duplicate,
        ];
    }

    public function getPlaylistsForTrack(string $userId, int $trackId): array
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

    public function setSessionValue(string $type, string $value, ?string $userId): string
    {
        if ($userId) {
            $this->userId = $userId;
        }
        $qb = $this->db->getQueryBuilder();
        $qb->select('configvalue')
            ->from('preferences')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($this->userId)))
            ->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter('audioplayer')))
            ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($type)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        if (isset($row['configvalue'])) {
            $qb = $this->db->getQueryBuilder();
            $qb->update('preferences')
                ->set('configvalue', $qb->createNamedParameter($value))
                ->where($qb->expr()->eq('userid', $qb->createNamedParameter($this->userId)))
                ->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter('audioplayer')))
                ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($type)))
                ->execute();
            return 'update';
        }
        $qb = $this->db->getQueryBuilder();
        $qb->insert('preferences')
            ->setValue('userid', $qb->createNamedParameter($this->userId))
            ->setValue('appid', $qb->createNamedParameter('audioplayer'))
            ->setValue('configkey', $qb->createNamedParameter($type))
            ->setValue('configvalue', $qb->createNamedParameter($value))
            ->execute();
        return 'insert';
    }

    public function getSessionValue(string $type): string
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('configvalue')
            ->from('preferences')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($this->userId)))
            ->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter('audioplayer')))
            ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($type)));
        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();
        return $row['configvalue'] ?? '';
    }
}
