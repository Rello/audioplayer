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
 
namespace OCA\audioplayer;
use \OC\Files\View;
 
class AudioStream {
	private $path = "";
	private $stream;
	private $iStart = -1;
	private $iEnd = -1;
	private $iSize = 0;
	private $mimeType = 0;
	private $buffer = 8192;
	private $mTime = 0;
    private $userView ;
	private $isStream = false;
	
	function __construct($filePath,$user=null) {
		
		if(is_null($user) || $user === ''){
			$user = \OC::$server->getUserSession()->getUser()->getUID();
		}	
		$this->userView = new View('/' . $user . '/files');	
		
		$this -> path = $filePath;
		$fileInfo = $this->userView -> getFileInfo($filePath);
		$this -> mimeType = $fileInfo['mimetype'];
		$this -> mTime = $fileInfo['mtime'];
		$this -> iSize = $fileInfo['size'];
		
	}

	/**
	 * Open stream
	 */
	private function openStream() {
		if (!($this -> stream = $this->userView->fopen($this -> path, 'rb'))) {
			die('Could not open stream for reading');
		}
	}

	/**
	 * Set proper header to serve the video content
	 */
	private function setHeader() {
		@ob_get_clean();
		header("Content-Type: ".$this -> mimeType);
		header("Cache-Control: max-age=2592000, public");
		header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
		header("Last-Modified: " . gmdate('D, d M Y H:i:s', $this -> mTime) . ' GMT');

		$this -> iStart = 0;
		$this -> iEnd = $this -> iSize - 1;
		
		header("Accept-Ranges: bytes");
		
		if (isset($_SERVER['HTTP_RANGE'])) {
			$c_start = $this -> iStart;
			$c_end = $this -> iEnd;
			$this->isStream = true;
		
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			
			if (strpos($range, ',') !== false) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes ".$this->iStart."-".$this->iEnd."/".$this->iSize);
				exit ;
			}
			if ($range === '-') {
				$c_start = $this -> iSize - substr($range, 1);
			} else {
				$range = explode('-', $range);
				$c_start = $range[0];
				$c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
			}
			$c_end = ($c_end > $this -> iEnd) ? $this -> iEnd : $c_end;
			if ($c_start > $c_end || $c_start > $this -> iSize - 1 || $c_end >= $this -> iSize) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes ".$this->iStart."-".$this->iEnd."/".$this->iSize);
				exit ;
			}
			$this -> iStart = $c_start;
			$this -> iEnd = $c_end;
			$length = $this -> iEnd - $this -> iStart + 1;
			if($this -> iStart > 0){
				fseek($this -> stream, $this -> iStart);
			}
			header('HTTP/1.1 206 Partial Content');
//			header("Content-Length: " . $length);
			header("Content-Range: bytes ".$this->iStart."-".$this->iEnd."/".$this->iSize);
			//\OCP\Util::writeLog('audioplayer','SEQ:'.$this->iStart."-".$this->iEnd."/".$this->iSize.'length:'.$length,\OCP\Util::DEBUG);
		} else {
			header("Content-Length: " . $this -> iSize);
			$this->isStream = false;
			
		}
	}

	/**
	 * close opened stream
	 */
	private function closeStream() {
		fclose($this -> stream);
		exit ;
	}

	/**
	 * perform the streaming
	 */
	private function stream() {
		if($this->isStream){
			//$data = stream_get_contents($this -> stream);
			//echo $data;
			
			$curPos = $this->iStart;
	        set_time_limit(0);
	        while(!feof($this->stream) && $curPos <= $this->iEnd) {
	           if( connection_aborted() || connection_status() !== 0 ) {
	           	\OCP\Util::writeLog('audioplayer','Connection aborted',\OCP\Util::DEBUG);
				   $this->closeStream();
			  	}
			    $bytesToRead = $this->buffer;
	            if(($curPos+$bytesToRead) > ($this->iEnd + 1)) {
	                $bytesToRead = $this->iEnd - $curPos + 1;
	            }
	            $data = fread($this->stream, $bytesToRead);
	            echo $data;
	            flush();
	            $curPos += strlen($data);
	        }
		}else{
			 \OC\Files\Filesystem::readfile($this -> path);
		}
		
		
	}

	/**
	 * Start streaming video 
	 */
	public function start() {
		
		$this -> openStream();
		$this -> setHeader();
		$this -> stream();
		$this -> closeStream();
		
		
	}
}
