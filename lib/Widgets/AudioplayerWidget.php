<?php

/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2018 Marcel Scherello
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
        return $this->l10n->t('Audio Player (beta)');
    }


    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->l10n->t('Access your recently played or favorite titles from within the dashboard. Player is not yet working.');
    }


    /**
     * @return array
     */
    public function getTemplate(): array {
        return [
            'app'      => 'audioplayer',
            'icon'     => 'icon-audioplayer',
            'css'      => ['widgets/audioplayer','bar-ui-min','style-min'],
            'js'       => 'widgets/audioplayer',
            'content'  => 'widgets/audioplayer',
            'function' => 'OCA.DashBoard.widget.init'
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
            'settings' => [
                [
                    'name'    => 'Favorites',
                    'title'   => 'Favorites',
                    'type'    => 'checkbox',
                    'default' => true
                ],
                [
                    'name'    => 'Recently',
                    'title'   => 'Recently Played',
                    'type'    => 'checkbox',
                    'default' => false
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