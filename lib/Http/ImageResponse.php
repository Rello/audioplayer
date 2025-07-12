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
use OC_Image;

/**
 * A renderer for cover arts
 */
class ImageResponse extends Response
{

    /** @var OC_Image|string */
    private OC_Image|string $preview;

    /**
     * @param array $image image meta data
     * @param int $statusCode the Http status code, defaults to 200
     */
    public function __construct(array $image, int $statusCode = Http::STATUS_OK)
    {
        $this->preview = $image['content'] ?? '';
        $mimetype = $image['mimetype'] ?? 'application/octet-stream';

        $this->setStatus($statusCode);
        $this->addHeader('Content-Type', $mimetype);
        if (!($this->preview instanceof OC_Image)) {
            $this->addHeader('Content-Length', (string)strlen($this->preview));
        }
        $this->cacheFor(365 * 24 * 60 * 60);
        $etag = sha1(is_string($this->preview) ? $this->preview : $this->preview->data());
        $this->setETag($etag);
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
        if ($this->preview instanceof OC_Image) {
            return $this->preview->data();
        }

        return $this->preview;
    }
}
