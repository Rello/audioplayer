<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */

namespace OCA\audioplayer\Categories;

use OCA\audioplayer\Service\TagManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class Tag
{
    private $tagManager;
    private $userId;
    private $logger;
    private $db;

    public function __construct(
        $userId,
        IDBConnection $db,
        LoggerInterface $logger,
        TagManager $tagManager
    )
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->tagManager = $tagManager;
        $this->userId = $userId;
    }

    /**
     * @return array
     */
    public function getCategoryItems()
    {
        $allTags = $this->tagManager->getAllTags();
        $aPlaylists = array();

        foreach ($allTags as $tag) {
            $tagName = $tag->getName();
            $tagId = $tag->getId();
            $trackCount = $this->getTrackCount($tagId);
            if ($trackCount && $trackCount !== 0) {
                $aPlaylists[] = array('id' => $tagId, 'name' => $tagName, 'cnt' => $trackCount);
            }
        }
        return $aPlaylists;
    }

    /**
     * Get the number of tracks for a Tag
     *
     * @param string $category
     * @param integer $categoryId
     * @return integer
     */
    private function getTrackCount($tagId)
    {
        $allFiles = $this->tagManager->getObjectIdsForTags($tagId);

        $result = 0;
        $fileChunks = array_chunk($allFiles, 999);
        foreach ($fileChunks as $fileChunk) {
            $sql = $this->db->getQueryBuilder();
            $sql->selectAlias($sql->func()->count('id'), 'count')
                ->from('audioplayer_tracks')
                ->where($sql->expr()->in('file_id', $sql->createNamedParameter($fileChunk, IQueryBuilder::PARAM_INT_ARRAY)))
                ->andWhere($sql->expr()->eq('user_id', $sql->createNamedParameter($this->userId)));
            $statement = $sql->execute();
            $statementResult = $statement->fetch();
            $result = $result + $statementResult['count'];
            $statement->closeCursor();
        }
        return $result;
    }

    /**
     * Get the tracks for a selected category or album
     *
     * @param string $category
     * @param string $categoryId
     * @return array
     */
    public function getTracksDetails($tagId)
    {
        $allFiles = $this->tagManager->getObjectIdsForTags($tagId);

        $sql = $this->db->getQueryBuilder();
        $function = $sql->createFunction('
			CASE 
				WHEN ' . $sql->getColumnName('AB.cover') . ' IS NOT NULL THEN '. $sql->getColumnName('AB.id') . '
				ELSE NULL 
			END'
        );

        $sql->select('AT.id')
            ->selectAlias('AT.title', 'cl1')
            ->selectAlias('AA.name', 'cl2')
            ->selectAlias('AB.name', 'cl3')
            ->selectAlias('AT.length', 'len')
            ->selectAlias('AT.file_id', 'fid')
            ->selectAlias('AT.mimetype', 'mim')
            ->selectAlias($function, 'cid')
            ->selectAlias($sql->createNamedParameter(mb_strtolower('AB.name')), 'lower')
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_artists', 'AA', $sql->expr()->eq('AT.artist_id', 'AA.id'))
            ->leftJoin('AT', 'audioplayer_albums', 'AB', $sql->expr()->eq('AT.album_id', 'AB.id'))
            ->orderBy($sql->createNamedParameter(mb_strtolower('AB.name')), 'ASC')
            ->addorderBy('AT.disc', 'ASC')
            ->addorderBy('AT.number', 'ASC')
            ->where($sql->expr()->in('AT.file_id', $sql->createNamedParameter($allFiles, IQueryBuilder::PARAM_INT_ARRAY)))
            ->andWhere($sql->expr()->eq('AT.user_id', $sql->createNamedParameter($this->userId)));

        $statement = $sql->execute();
        $result = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * Get the covers for the "Album Covers" view
     *
     * @NoAdminRequired
     * @param $category
     * @param $categoryId
     * @return array
     */
    public function getCategoryItemCovers($tagId)
    {
        $allFiles = $this->tagManager->getObjectIdsForTags($tagId);

        $sql = $this->db->getQueryBuilder();
        $function = $sql->createFunction('
			CASE 
				WHEN ' . $sql->getColumnName('AB.cover') . ' IS NOT NULL THEN '. $sql->getColumnName('AB.id') . '
				ELSE NULL 
			END'
        );

        $sql->select('AB.id')
            ->addSelect('AB.name')
            ->selectAlias($sql->func()->lower(('AB.name')), 'lower')
            ->selectAlias('AA.id', 'art')
            ->selectAlias($function, 'cid')
            ->from('audioplayer_tracks', 'AT')
            ->leftJoin('AT', 'audioplayer_albums', 'AB', $sql->expr()->eq('AT.album_id', 'AB.id'))
            ->leftJoin('AB', 'audioplayer_artists', 'AA', $sql->expr()->eq('AB.artist_id', 'AA.id'))
            ->where($sql->expr()->in('AT.file_id', $sql->createNamedParameter($allFiles, IQueryBuilder::PARAM_INT_ARRAY)))
            ->andWhere($sql->expr()->eq('AT.user_id', $sql->createNamedParameter($this->userId)))
            ->orderBy($sql->createNamedParameter(mb_strtolower('AB.name')), 'ASC')
            ->groupBy('AB.id')
            ->addGroupBy('AA.id')
            ->addGroupBy('AB.name');

        $statement = $sql->execute();
        $result = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }
}
