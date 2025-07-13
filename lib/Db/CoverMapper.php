<?php
namespace OCA\audioplayer\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class CoverMapper extends QBMapper
{
    public const TABLE_NAME = 'audioplayer_albums';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLE_NAME);
    }

    public function findCoverData(string $userId, int $albumId): ?array
    {
        /** @var IQueryBuilder $qb */
        $qb = $this->db->getQueryBuilder();
        $qb->select('cover', 'name', 'artist_id')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($albumId)));

        $result = $qb->execute();
        $data = $result->fetch();
        $result->closeCursor();

        return $data ?: null;
    }
}
