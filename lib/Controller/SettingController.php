<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Attributes\NoAdminRequired;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\audioplayer\Service\SettingService;

/**
 * Controller class for main page.
 */
class SettingController extends Controller {

    private $userId;
    private $settingService;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        SettingService $settingService
    )
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->userId = $userId;
        $this->settingService = $settingService;
    }

    /**
     * @param $type
     * @param $value
     * @return JSONResponse
     * @throws \OCP\PreConditionNotMetException
     */
    #[NoAdminRequired]
    public function setValue($type, $value) {
        $this->settingService->setValue($this->userId, $type, $value);
        return new JSONResponse(['success' => 'true']);
    }

    /**
     * @param $type
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function getValue($type) {
        $value = $this->settingService->getValue($this->userId, $type);

		//\OCP\Util::writeLog('audioplayer', 'settings load: '.$type.$value, \OCP\Util::DEBUG);

		if ($value !== '') {
			$result = [
					'status' => 'success',
					'value' => $value
				];
		} else {
			$result = [
					'status' => 'false',
					'value' =>'nodata'
				];
		}
        return new JSONResponse($result);
    }

    /**
     * @param $value
     * @return JSONResponse
     * @throws \OCP\PreConditionNotMetException
     */
    #[NoAdminRequired]
    public function userPath($value) {
        $success = $this->settingService->userPath($this->userId, $value);
        return new JSONResponse(['success' => $success]);
    }

    /**
     * @param $trackid
     * @param $isFavorite
     * @return bool
     */
    #[NoAdminRequired]
    public function setFavorite($trackid, $isFavorite)
    {
        return $this->settingService->setFavorite($this->userId, $trackid, $isFavorite);
    }

    /**
     * @param $track_id
     * @return int|string
     * @throws \Exception
     */
    #[NoAdminRequired]
    public function setStatistics($track_id) {
        return $this->settingService->setStatistics($this->userId, $track_id);
    }

}
