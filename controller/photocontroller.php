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
 
namespace OCA\audioplayer\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;

class PhotoController extends Controller {
	
	private $l10n;
	private $helperController;
	public function __construct(
			$appName, 
			IRequest $request, 
			IL10N $l10n 
			) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;		
	}
	
	/**
	 * @NoAdminRequired
	 */
	
	public function cropPhoto(){
		
		$id = $this -> params('id');	
		$tmpkey = $this -> params('tmpkey');	
		
		$params=array(
		 'tmpkey' => $tmpkey,
		 'id' => $id,
		);	
		$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$csp->addAllowedImageDomain('data:');
		
		$response = new TemplateResponse('audioplayer', 'part.cropphoto', $params, '');
	 	$response->setContentSecurityPolicy($csp);
	  
	   return $response;
	}
	
	/**
	 * @NoAdminRequired
	 */
	 
	public function clearPhotoCache(){
		//$id = $this -> params('id');
		$tmpkey = $this -> params('tmpkey');		
		$data = \OC::$server->getCache()->get($tmpkey);
		//\OCP\Util::writeLog('pinit','cleared.'.$tmpkey,\OCP\Util::DEBUG);		
		if($data) {
			
			\OC::$server->getCache()->remove($tmpkey);
		}
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function saveCropPhoto(){
		$id = $this -> params('id');
		$tmpkey = $this -> params('tmpkey');			
		$x = $this -> params('x1', 0);	
		$y = $this -> params('y1', 0);	
		$w = $this -> params('w', -1);	
		$h = $this -> params('h', -1);	
		
		$image = null;
		
		//\OCP\Util::writeLog('audioplayer','MIMI'.$tmpkey,\OCP\Util::DEBUG);	
		$data = \OC::$server->getCache()->get($tmpkey);
		if($data) {
			
			$image = new \OCP\Image();
			if($image->loadFromdata($data)) {
				$w = ($w !== -1 ? $w : $image->width());
				$h = ($h !== -1 ? $h : $image->height());
				
				if($image->crop($x, $y, $w, $h)) {
					if(($image->width() <= 300 && $image->height() <= 300) || $image->resize(300)) {
					
					$imgString = $image->__toString();
						
						$resultData=array(
							'id' => $id,
							'width' => $image->width(),
							'height' => $image->height(),
							'dataimg' =>$imgString,
							'mimetype' =>$image->mimeType()
						);
						
						 \OC::$server->getCache()->remove($tmpkey);
						 \OC::$server->getCache()->set($tmpkey, $image->data(), 600);
						 $response = new JSONResponse();
						  $response -> setData($resultData);
						  
						return $response;
					}
				}
			}
		}
		
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function getImageFromCloud(){
		$id = $this -> params('id');	
		$path = $this -> params('path');	
		
		$localpath = \OC\Files\Filesystem::getLocalFile($path);
		$tmpkey = 'audioplayer-photo-' . $id;
		$size = getimagesize($localpath, $info);
		$exif = @exif_read_data($localpath);
		$image = new \OCP\Image();
		$image -> loadFromFile($localpath);
		if ($image -> width() > 350 || $image -> height() > 350) {
			$image -> resize(350);
		}
		$image -> fixOrientation();
		
		$imgString = $image -> __toString();
		$imgMimeType = $image -> mimeType();
		if (\OC::$server->getCache()->set($tmpkey, $image -> data(), 600)) {
	 	
		
			
	    $resultData = array(
		     'id' =>$id,
		     'tmp' => $tmpkey,
		     'imgdata' => $imgString,
		     'mimetype' => $imgMimeType,
	      );
		  
		  $response = new JSONResponse();
		  $response -> setData($resultData);
		  
		return $response;
	
} 
		
	}
/**
	 * @NoAdminRequired
	 */
	public function uploadPhoto(){
		//$type = $this->request->getHeader('Content-Type');
		$id = $this -> params('id');
		$file = $this->request->getUploadedFile('imagefile');
		
		$error = $file['error'];
		if($error !== UPLOAD_ERR_OK) {
			$errors = array(
				0=>$this->l10n->t("There is no error, the file uploaded with success"),
				1=>$this->l10n->t("The uploaded file exceeds the upload_max_filesize directive in php.ini").ini_get('upload_max_filesize'),
				2=>$this->l10n->t("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"),
				3=>$this->l10n->t("The uploaded file was only partially uploaded"),
				4=>$this->l10n->t("No file was uploaded"),
				6=>$this->l10n->t("Missing a temporary folder")
			);
			\OCP\Util::writeLog('audioplayer','Uploaderror: '.$errors[$error],\OCP\Util::DEBUG);	
		}

		if(file_exists($file['tmp_name'])) {
			$tmpkey = 'audioplayer-photo-'.md5(basename($file['tmp_name']));
			$size = getimagesize($file['tmp_name'], $info);
		    $exif = @exif_read_data($file['tmp_name']);
			$image = new \OCP\Image();
			if($image->loadFromFile($file['tmp_name'])) {
				
				if($image->width() > 350 || $image->height() > 350) {
					$image->resize(350); // Prettier resizing than with browser and saves bandwidth.
				}
				if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
					\OCP\Util::writeLog('audioplayer','Couldn\'t save correct image orientation: '.$tmpkey,\OCP\Util::DEBUG);
				}
				
					if(\OC::$server->getCache()->set($tmpkey, $image->data(), 600)) {
					$imgString=$image->__toString();
					
					

                      $resultData=array(
							'mime'=>$file['type'],
							'size'=>$file['size'],
							'name'=>$file['name'],
							'id'=>$id,
							'tmp'=>$tmpkey,
							'imgdata' =>$imgString,
					);
					
					 $response = new JSONResponse();
					  $response -> setData($resultData);
					  
					return $response;
					
				}
				
				
			}
			
			
			
		}
		
		
	}

	
}
