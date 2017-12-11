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
use OCP\ILogger;

class Migration implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;
    private $logger;
	/**
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(
	        IDBConnection $connection,
            IConfig $config,
            ILogger $logger
    ) {
		$this->connection = $connection;
		$this->config = $config;
        $this->logger = $logger;
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
            $this->logger->info('Migration of Audio Player started', array('app' => 'audioplayer'));
            if ($this->connection->tableExists('audioplayer_statistics')) {
				$this->connection->dropTable('audioplayer_statistics');
                $this->logger->info('Table -audioplayer_statistics- deleted', array('app' => 'audioplayer'));
			}
			if ($this->connection->tableExists('audioplayer_album_artists')) {
				$this->connection->dropTable('audioplayer_album_artists');
                $this->logger->info('Table -audioplayer_album_artists- deleted', array('app' => 'audioplayer'));
			}
			$output->finishProgress();
		}
	}
}
