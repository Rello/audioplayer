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
use OCP\Dashboard\Model\IWidgetConfig;
use OCP\Dashboard\Model\IWidgetRequest;
use OCP\Dashboard\Model\WidgetSetup;
use OCP\Dashboard\Model\WidgetTemplate;
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
        return $this->l10n->t('Audio Player (alpha - no playback yet)');
	}


	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->l10n->t(
			'Access your recently played or favorite titles from within the dashboard. Player is not yet working.'
		);
	}


//    /**
//     * @return array
//     */
//    public function getTemplate(): array {
//        return [
//            'app'      => 'audioplayer',
//            'icon'     => 'icon-audioplayer',
//            'css'      => ['widgets/audioplayer','bar-ui-min','style-min'],
//            'js'       => 'widgets/audioplayer',
//            'content'  => 'widgets/audioplayer',
//            'function' => 'OCA.DashBoard.widget.init'
//        ];
//    }
//
//
//    /**
//     * @return array
//     */
//    public function widgetSetup(): array {
//        return [
//            'size' => [
//                'min'     => [
//                    'width'  => 4,
//                    'height' => 2
//                ],
//                'default' => [
//                    'width'  => 4,
//                    'height' => 2
//                ],
//                'max'     => [
//                    'width'  => 6,
//                    'height' => 4
//                ]
//            ],
//            'settings' => [
//                [
//                    'name'    => 'Favorites',
//                    'title'   => 'Favorites',
//                    'type'    => 'checkbox',
//                    'default' => true
//                ],
//                [
//                    'name'    => 'Recently',
//                    'title'   => 'Recently Played',
//                    'type'    => 'checkbox',
//                    'default' => false
//                ]
//
//            ]
//        ];
//    }


	/**
	 * Must generate and return a WidgetTemplate that define important stuff
	 * about the Widget: icon, content, css or javascript.
	 *
	 * @see WidgetTemplate
	 *
	 * @since 15.0.0
	 *
	 * @return WidgetTemplate
	 */
	public function getWidgetTemplate(): WidgetTemplate {
		$template = new WidgetTemplate();
        $template->setCss(['widgets/audioplayer', 'bar-ui', 'style'])
				 ->addJs('widgets/audioplayer')
				 ->setIcon('icon-audioplayer')
				 ->setContent('widgets/audioplayer')
				 ->setInitFunction('OCA.DashBoard.widget.init');

		return $template;
	}

	/**
	 * Must create and return a WidgetSetup containing the general setup of
	 * the widget
	 *
	 * @see WidgetSetup
	 *
	 * @since 15.0.0
	 *
	 * @return WidgetSetup
	 */
	public function getWidgetSetup(): WidgetSetup {
		$setup = new WidgetSetup();
		$setup->addSize(WidgetSetup::SIZE_TYPE_MIN, 4, 2)
			  ->addSize(WidgetSetup::SIZE_TYPE_MAX, 6, 4)
			  ->addSize(WidgetSetup::SIZE_TYPE_DEFAULT, 4, 2);

		/**
		 * Fill and uncomment those lines if you want to
		 *  - add a menu entry,
		 *  - add some delayed job (every n seconds)
		 *  - add a method to be called on the front-end by the back-end (push)
		 */
//		$setup->addMenuEntry('OCA.DashBoard.fortunes.getFortune', 'icon-fortunes', 'New Fortune');
//		$setup->addDelayedJob('OCA.DashBoard.fortunes.getFortune', 300);
//		$setup->setPush('OCA.DashBoard.fortunes.push');
		return $setup;
//            'settings' => [
//                [
//                    'name'    => 'Favorites',
//                    'title'   => 'Favorites',
//                    'type'    => 'checkbox',
//                    'default' => true
//                ],
//                [
//                    'name'    => 'Recently',
//                    'title'   => 'Recently Played',
//                    'type'    => 'checkbox',
//                    'default' => false
//                ]
//
//            ]
//        ];

	}

	/**
	 * This method is called when a widget is loaded on the dashboard.
	 * A widget is 'loaded on the dashboard' when one of these conditions
	 * occurs:
	 *
	 * - the user is adding the widget on his dashboard,
	 * - the user already added the widget on his dashboard and he is opening
	 *   the dashboard app.
	 *
	 * @see IWidgetConfig
	 *
	 * @since 15.0.0
	 *
	 * @param IWidgetConfig $settings
	 */
	public function loadWidget(IWidgetConfig $settings) {
	}

	/**
	 * This method s executed when the widget call the net.requestWidget()
	 * from the Javascript API.
	 *
	 * This is used by the frontend to communicate with the backend.
	 *
	 * @see IWidgetRequest
	 *
	 * @since 15.0.0
	 *
	 * @param IWidgetRequest $request
	 */
	public function requestWidget(IWidgetRequest $request) {
	}
}
