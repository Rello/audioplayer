<?php
/**
 * ownCloud
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
