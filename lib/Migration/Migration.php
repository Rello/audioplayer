<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */

namespace OCA\audioplayer\Migration;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Migration implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	/**
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Cleanup of old Audio Player tables';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @since 9.1.0
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output) {
		$version = $this->config->getAppValue('audioplayer', 'installed_version', '0.0.0');
		if (version_compare($version, '2.1.0', '>')) {

			if ($this->connection->tableExists('audioplayer_statistics')) {
				$this->connection->dropTable('audioplayer_statistics');
				\OCP\Util::writeLog('audioplayer', 'Table -audioplayer_statistics- deleted', \OCP\Util::DEBUG);
			}
			if ($this->connection->tableExists('audioplayer_album_artists')) {
				$this->connection->dropTable('audioplayer_album_artists');
				\OCP\Util::writeLog('audioplayer', 'Table -audioplayer_album_artists- deleted', \OCP\Util::DEBUG);
			}
			$output->finishProgress();
		}
	}
}
