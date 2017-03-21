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

/**
 * A found file
 */
class Result extends \OCP\Search\Result {

	/**
	 * Type name; translated in templates
	 * @var string 
	 */
	public $type = 'audioplayer';

	/**
	 * Create a new file search result
	 * @param array $data file data given by provider
	 */
	public function __construct(array $data = null) {
		if($data !== null){
			$this->id = $data['id'];
			$this->name = $data['description'];
			$this->link = $data['link'];
			$this->icon = $data['icon'];
		}
	}
}
