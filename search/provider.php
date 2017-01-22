<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2017 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Search;
use OCA\audioplayer\AppInfo\Application;

/**
 * Provide search results from the 'audioplayer' app
 */
class Provider extends \OCP\Search\Provider {

    #private $playlistController;
	private $musicController;
	
	private $l10N;
	
    public function __construct() {
		$app = new Application();
		$container = $app->getContainer();
		$this->app = $app;
		#$this->playlistController = $container->query('PlaylistController');
		$this->musicController = $container->query(\OCA\audioplayer\Controller\MusicController::class);
		$this->l10N = $container->query('L10N');
	}
	
	/**
	 * 
	 * @param string $query
	 * @return \OCP\Search\Result
	 */
	function search($query) {
		$unescape = function($value) {
			return strtr($value, array('\,' => ',', '\;' => ';'));
		};

		$searchresults = array();
		$results = $this->musicController->searchProperties($query);
		
		
		foreach($results as $result) {
				
			$returnData['id'] = $result['id'];
			$returnData['description'] = $result['name'];
			$returnData['link'] = '../audioplayer/#show-album-' . $result['id'];
			$returnData['icon'] = '../audioplayer/img/app.svg';
			
		    $searchresults[] = new \OCA\audioplayer\Search\Result($returnData);
		}
		return $searchresults;
	}
}
