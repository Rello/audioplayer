<?php
namespace OCA\audioplayer\Service;


use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

class TagManager
{
    /**
     * @var \OCP\SystemTag\ISystemTagManager
     */
    private $tagManager;
    /**
     * @var \OCP\SystemTag\ISystemTagObjectMapper
     */
    private $objectMapper;

    public function __construct(ISystemTagManager $systemTagManager, ISystemTagObjectMapper $objectMapper)
    {
        $this->tagManager = $systemTagManager;
        $this->objectMapper = $objectMapper;
    }

    public function getAllTags() : array
    {
        return $this->tagManager->getAllTags(true);
    }

    public function getObjectIdsForTags($tags): array
    {
        return $this->objectMapper->getObjectIdsForTags($tags, 'files');
    }
}