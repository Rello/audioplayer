<?php
/**
 * ownCloud - Audios
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
namespace OCA\Audios\AppInfo;

use \OCP\AppFramework\App;
use OCP\IContainer;
use OCP\AppFramework\IAppContainer;

use \OCA\Audios\Controller\PageController;
use \OCA\Audios\Controller\PlaylistController;
use \OCA\Audios\Controller\ScannerController;
use \OCA\Audios\Controller\MusicController;

class Application extends App {
	
	public function __construct (array $urlParams=array()) {
		
		parent::__construct('audios', $urlParams);
        $container = $this->getContainer();
	
	
		$container->registerService('PageController', function(IContainer $c) {
			return new PageController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N')
			);
		});
		
		$container->registerService('PlaylistController', function(IContainer $c) {
			return new PlaylistController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N')
			);
		});
		
		$container->registerService('MusicController', function(IContainer $c) {
			return new MusicController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N')
			);
		});
		
		$container->registerService('ScannerController', function(IContainer $c) {
			return new ScannerController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N')
			);
		});
		
		
        /**
		 * Core
		 */
		 
		  $container->registerService('URLGenerator', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return $server->getURLGenerator();
		});
		
		$container -> registerService('UserId', function(IContainer $c) {
			return \OCP\User::getUser();
		});
		
		$container -> registerService('L10N', function(IContainer $c) {
			return $c -> query('ServerContainer') -> getL10N($c -> query('AppName'));
		});
		
		
	}
  
    
}

