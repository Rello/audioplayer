<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2020 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Search;
use OCA\audioplayer\AppInfo\Application;

/**
 * Provide search results from the 'audioplayer' app
 */
class Provider extends \OCP\Search\Provider {

    private $DBController;
	private $l10n;
	private $app;
	
    public function __construct() {
		$app = new Application();
		$container = $app->getContainer();
		$this->app = $app;
        $this->DBController = $container->query(\OCA\audioplayer\Controller\DbController::class);
		$this->l10n = $container->query('L10N');
	}

    /**
     *
     * @param string $query
     * @return array
     */
	function search($query) {
		$searchresults = array();
        $results = $this->DBController->search($query);
		
		foreach($results as $result) {
			$returnData = array();
			$returnData['id'] = $result['id'];
			$returnData['description'] = $this->l10n->t('Audio Player').' - '.$result['name'];
			$returnData['link'] = '../audioplayer/#' . $result['id'];
			$returnData['icon'] = '../audioplayer/img/app.svg';

			$searchresults[] = new \OCA\audioplayer\Search\Result($returnData);
		}
		return $searchresults;
	}
}
