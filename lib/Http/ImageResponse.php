<?php
declare(strict_types=1);
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Olivier Paroz <galleryapps@oparoz.com>
 * @copyright 2016-2021 Marcel Scherello
 */

namespace OCA\audioplayer\Http;

use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http;

/**
 * A renderer for cover arts
 */
class ImageResponse extends Response
{

    private string $preview;

    /**
     * @param array $image image meta data
     * @param int $statusCode the Http status code, defaults to 200
     */
    public function __construct(array $image, int $statusCode = Http::STATUS_OK)
    {
        $this->preview = is_string($image['content'] ?? null) ? $image['content'] : '';
        $mimetype = $image['mimetype'] ?? 'application/octet-stream';

        $this->setStatus($statusCode);
        $this->addHeader('Content-Type', $mimetype);
        $this->addHeader('Content-Length', (string)strlen($this->preview));
        $this->cacheFor(365 * 24 * 60 * 60);
        $this->setETag(sha1($this->preview));
    }

    /**
     * Returns the rendered image
     *
     * @return string the file
     */
    public function render()
    {
        if ($this->preview === '' || $this->preview === null) {
            return '';
        }
        return $this->preview;
    }
}
