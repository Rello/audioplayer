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

namespace OCA\audioplayer\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;

class Personal implements ISettings
{

    private $userId;
    private $configManager;

    public function __construct(
        $userId,
        IConfig $configManager
    )
    {
        $this->userId = $userId;
        $this->configManager = $configManager;
    }

    /**
     * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
     * @since 9.1
     */
    public function getForm()
    {

        $parameters = [
            'audioplayer_cyrillic' => $this->configManager->getUserValue($this->userId, 'audioplayer', 'cyrillic'),
            'audioplayer_path' => $this->configManager->getUserValue($this->userId, 'audioplayer', 'path'),
        ];
        return new TemplateResponse('audioplayer', 'settings/personal', $parameters, '');
    }

    /**
     * Print config section (ownCloud 10)
     *
     * @return TemplateResponse
     */
    public function getPanel()
    {
        return $this->getForm();
    }

    /**
     * @return string the section ID, e.g. 'sharing'
     * @since 9.1
     */
    public function getSection()
    {
        return 'audioplayer';
    }

    /**
     * Get section ID (ownCloud 10)
     *
     * @return string
     */
    public function getSectionID()
    {
        return 'audioplayer';
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     *
     * E.g.: 70
     * @since 9.1
     */
    public function getPriority()
    {
        return 10;
    }
}
