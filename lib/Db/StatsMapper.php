<?php
namespace OCA\audioplayer\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class StatsMapper extends QBMapper
{
    public const TABLE_NAME = 'audioplayer_stats';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLE_NAME);
    }

    public function findByUserAndTrack(string $userId, int $trackId): ?array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('track_id', $qb->createNamedParameter($trackId)));
        $result = $qb->execute();
        $data = $result->fetch();
        $result->closeCursor();

        return $data ?: null;
    }

    public function updatePlayCount(int $id, int $playcount, int $playtime): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update(self::TABLE_NAME)
            ->set('playcount', $qb->createNamedParameter($playcount))
            ->set('playtime', $qb->createNamedParameter($playtime))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        $qb->execute();
    }

    public function insertStat(string $userId, int $trackId, int $playtime, int $playcount): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::TABLE_NAME)
            ->setValue('user_id', $qb->createNamedParameter($userId))
            ->setValue('track_id', $qb->createNamedParameter($trackId))
            ->setValue('playtime', $qb->createNamedParameter($playtime))
            ->setValue('playcount', $qb->createNamedParameter($playcount));
        $qb->execute();

        return (int)$this->db->lastInsertId(self::TABLE_NAME);
    }
}
