<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Olivier Paroz <galleryapps@oparoz.com>
 * @copyright 2017 Marcel Scherello
 */

namespace OCA\audioplayer\Http;

use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http;

/**
 * A renderer for cover arts
 */
class ImageResponse extends Response {

	private $preview;

	/**
	 * @param array $image image meta data
	 * @param int $statusCode the Http status code, defaults to 200
	 */

	public function __construct(array $image, $statusCode = Http::STATUS_OK) {
		$this->preview = $image['content'];
		$this->setStatus($statusCode);
		$this->addHeader('Content-type', $image['mimetype'] . '; charset=utf-8');
		$etag = md5($image['content']);
		$this->setETag($etag);		
		//$this->addHeader('Cache-Control ', 'public');
	}

	/**
	 * Returns the rendered image
	 *
	 * @return string the file
	 */
	public function render() {
		if ($this->preview instanceof \OC_Image) {
			// Uses imagepng() to output the image
			return $this->preview->data();
		} else {
			return $this->preview;
		}
	}
}
