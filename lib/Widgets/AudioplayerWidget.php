<?php

/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2018 Marcel Scherello
 */

namespace OCA\audioplayer\Widgets;

use OCP\Dashboard\IDashboardWidget;
use OCP\Dashboard\Model\IWidgetRequest;
use OCP\Dashboard\Model\IWidgetSettings;
use OCP\IL10N;

class AudioplayerWidget implements IDashboardWidget {

    const WIDGET_ID = 'audioplayer';


    /** @var IL10N */
    private $l10n;


    public function __construct(IL10N $l10n) {
        $this->l10n = $l10n;
    }


    /**
     * @return string
     */
    public function getId(): string {
        return self::WIDGET_ID;
    }


    /**
     * @return string
     */
    public function getName(): string {
        return $this->l10n->t('Audio Player');
    }


    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->l10n->t('Audio Player');
    }


    /**
     * @return array
     */
    public function getTemplate(): array {
        return [
            'app'      => 'audioplayer',
            'icon'     => 'icon-clock',
            'css'      => 'widgets/widget',
            'js'       => 'widgets/widget',
            'content'  => 'widgets/audioplayer',
            'function' => 'OCA.AudioPlayer.widget.init'
        ];
    }


    /**
     * @return array
     */
    public function widgetSetup(): array {
        return [
            'size' => [
                'min'     => [
                    'width'  => 4,
                    'height' => 2
                ],
                'default' => [
                    'width'  => 4,
                    'height' => 2
                ],
                'max'     => [
                    'width'  => 6,
                    'height' => 4
                ]
            ],
            'jobs' => [
                [
                    'delay'    => 1,
                    'function' => 'OCA.AudioPlayer.widget.displayTime'
                ]
            ]
        ];
    }


    /**
     * @param IWidgetSettings $settings
     */
    public function loadWidget(IWidgetSettings $settings) {
    }


    /**
     * @param IWidgetRequest $request
     */
    public function requestWidget(IWidgetRequest $request) {
    }


}