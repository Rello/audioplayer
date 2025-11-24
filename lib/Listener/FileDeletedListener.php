<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2025 Marcel Scherello
 */

declare(strict_types=1);

namespace OCA\audioplayer\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCA\audioplayer\Db\DbMapper;
use OCP\Files\FileInfo;

class FileDeletedListener implements IEventListener {

	/** @var DbMapper */
	private $db;

	public function __construct(DbMapper $db) {
		$this->db = $db;
	}

	public function handle(Event $event): void {
		if (!$event instanceof NodeDeletedEvent) {
			return;
		}

		$node = $event->getNode();
		if ($node->getType() === FileInfo::TYPE_FILE) {
			$this->db->deleteFromDB($node->getId());
		}
	}
}