<?php
namespace OCA\audioplayer\Service;

use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\ITagManager;
use OCA\audioplayer\Db\DbMapper;
use OCA\audioplayer\Db\StatsMapper;

class SettingService
{
    private $appName;
    private $config;
    private $rootFolder;
    private $tagManager;
    private $dbMapper;
    private $statsMapper;

    public function __construct(
        string $appName,
        IConfig $config,
        ITagManager $tagManager,
        IRootFolder $rootFolder,
        DbMapper $dbMapper,
        StatsMapper $statsMapper
    ) {
        $this->appName = $appName;
        $this->config = $config;
        $this->tagManager = $tagManager;
        $this->rootFolder = $rootFolder;
        $this->dbMapper = $dbMapper;
        $this->statsMapper = $statsMapper;
    }

    public function admin(string $type, string $value): void
    {
        $this->config->setAppValue($this->appName, $type, $value);
    }

    public function setValue(string $userId, string $type, string $value): void
    {
        $this->config->setUserValue($userId, $this->appName, $type, $value);
    }

    public function getValue(string $userId, string $type): string
    {
        return $this->config->getUserValue($userId, $this->appName, $type);
    }

    public function userPath(string $userId, string $path): bool
    {
        try {
            $this->rootFolder->getUserFolder($userId)->get($path);
        } catch (\OCP\Files\NotFoundException $e) {
            return false;
        } catch (\OCP\Files\InvalidPathException $e) {
            return false;
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        if ($path[strlen($path) - 1] !== '/') {
            $path .= '/';
        }
        $this->config->setUserValue($userId, $this->appName, 'path', $path);
        return true;
    }

    public function setFavorite(string $userId, int $trackId, string $isFavorite)
    {
        $tagger = $this->tagManager->load('files');
        $fileId = $this->dbMapper->getFileId($this->userId, $trackId);

        if ($isFavorite === 'true') {
            return $tagger->removeFromFavorites($fileId);
        }
        return $tagger->addToFavorites($fileId);
    }

    public function setStatistics(string $userId, int $trackId)
    {
        $date = new \DateTime();
        $playtime = $date->getTimestamp();

        $row = $this->statsMapper->findByUserAndTrack($userId, $trackId);
        if ($row !== null && isset($row['id'])) {
            $playcount = (int)$row['playcount'] + 1;
            $this->statsMapper->updatePlayCount((int)$row['id'], $playcount, $playtime);
            return 'update';
        }
        return $this->statsMapper->insertStat($userId, $trackId, $playtime, 1);
    }
}
