<?php

namespace OCA\audioplayer\Service;

use OCA\audioplayer\Categories\Tag;
use OCA\audioplayer\DB\CategoryMapper;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class CategoryService {
	private string $userId;
	private IL10N $l10n;
	private $logger;
	private CategoryMapper $mapper;
	private Tag $categoriesTag;

	public function __construct(
		string          $userId,
		IL10N           $l10n,
		CategoryMapper  $mapper,
		Tag             $categoriesTag,
		LoggerInterface $logger
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->mapper = $mapper;
		$this->categoriesTag = $categoriesTag;
		$this->logger = $logger;
	}

	public function getCategoryItems(string $category): array {
		$items = [];
		switch ($category) {
			case 'Artist':
				$items = $this->mapper->getArtists($this->userId);
				break;
			case 'Genre':
				$items = $this->mapper->getGenres($this->userId);
				break;
			case 'Year':
				$items = $this->mapper->getYears($this->userId);
				break;
			case 'Title':
				$items[] = ['id' => '0', 'name' => $this->l10n->t('All Titles'), 'lower' => ''];
				break;
			case 'Playlist':
				$items[] = ['id' => 'X1', 'name' => $this->l10n->t('Favorites')];
				$items[] = ['id' => 'X2', 'name' => $this->l10n->t('Recently Added')];
				$items[] = ['id' => 'X3', 'name' => $this->l10n->t('Recently Played')];
				$items[] = ['id' => 'X4', 'name' => $this->l10n->t('Most Played')];
				$items[] = ['id' => 'X5', 'name' => $this->l10n->t('50 Random Tracks')];
				$items[] = ['id' => '', 'name' => ''];
				foreach ($this->mapper->getStreams($this->userId) as $row) {
					unset($row['lower']);
					$row['id'] = 'S' . $row['id'];
					$items[] = $row;
				}
				$items[] = ['id' => '', 'name' => ''];
				$items = array_merge($items, $this->mapper->getPlaylists($this->userId));
				break;
			case 'Folder':
				$items = $this->mapper->getFolders($this->userId);
				break;
			case 'Album':
				$items = $this->mapper->getAlbums($this->userId);
				break;
			case 'Album Artist':
				$items = $this->mapper->getAlbumArtists($this->userId);
				break;
			case 'Tags':
				$items = $this->categoriesTag->getCategoryItems();
				// already includes count
				return $items;
		}

		foreach ($items as &$row) {
			if (($row['name'] === '0' || $row['name'] === '') && $category !== 'Title') {
				$row['name'] = $this->l10n->t('Unknown');
			}
			$row['cnt'] = $this->mapper->getTrackCount($category, $row['id'], $this->userId);
			unset($row['lower']);
		}
		return $items;
	}

	public function getCategoryItemCovers(string $category, ?string $categoryId): array {
		if ($category === 'Tags') {
			return $this->categoriesTag->getCategoryItemCovers($categoryId);
		}
		$rows = $this->mapper->getAlbumCovers($this->userId, $category, $categoryId);
		foreach ($rows as &$row) {
			$row['art'] = $this->mapper->loadArtistsToAlbum((int)$row['id'], (int)$row['art']);
			if ($row['name'] === '0' || $row['name'] === '') {
				$row['name'] = $this->l10n->t('Unknown');
			}
			unset($row['lower']);
		}
		return $rows;
	}

	public function getTrackCount(string $category, string $categoryId): int {
		return $this->mapper->getTrackCount($category, $categoryId, $this->userId);
	}
}
