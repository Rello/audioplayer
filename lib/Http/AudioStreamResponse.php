<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the LICENSE.md file.
 */

namespace OCA\audioplayer\Http;

use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http;
use OC\Files\View;

/**
 * Stream audio files using a Response based approach
 */
class AudioStreamResponse extends Response
{
    private string $path;
    private $stream;
    private ?View $view;
    private string $mimeType;
    private int $size;
    private int $mtime;
    private int $buffer = 8192;

    public function __construct(string $path, string $user, ?View $view = null)
    {
        $user = $user ?: \OC::$server->getUserSession()->getUser()->getUID();
        $this->view = $view ?: new View('/' . $user . '/files/');
        $this->path = $path;
        $info = $this->view->getFileInfo($path);
        $this->mimeType = $info['mimetype'];
        $this->size = $info['size'];
        $this->mtime = $info['mtime'];

        $this->setStatus(Http::STATUS_OK);
        $this->addHeader('Content-Type', $this->mimeType . '; charset=utf-8');
        $this->addHeader('Cache-Control', 'max-age=2592000, public');
        $this->addHeader('Expires', gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        $this->addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $this->mtime) . ' GMT');
    }

    private function openStream(): void
    {
        if (!($this->stream = $this->view->fopen($this->path, 'rb'))) {
            throw new \RuntimeException('Could not open stream for reading');
        }
    }

    public function render(): string
    {
        $this->openStream();
        $start = 0;
        $end = $this->size - 1;

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = (int)$matches[1];
            if ($matches[2] !== '') {
                $end = (int)$matches[2];
            }

            $length = $end - $start + 1;
            $this->addHeader('Accept-Ranges', 'bytes');
            $this->addHeader('Content-Length', (string)$length);
            $this->setStatus(Http::STATUS_PARTIAL_CONTENT);
            $this->addHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $this->size);
            fseek($this->stream, $start);
        } else {
            $this->addHeader('Content-Length', (string)$this->size);
        }

        $curPos = $start;
        $data = '';
        set_time_limit(0);
        while (!feof($this->stream) && $curPos <= $end) {
            $bytesToRead = $this->buffer;
            if (($curPos + $bytesToRead) > ($end + 1)) {
                $bytesToRead = $end - $curPos + 1;
            }
            $chunk = fread($this->stream, $bytesToRead);
            $data .= $chunk;
            $curPos += strlen($chunk);
        }
        fclose($this->stream);
        return $data;
    }
}
