<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2020 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\AppInfo;

use OCP\AppFramework\App;
use OCP\IContainer;
use OCP\Util;

class Application extends App {

    public function __construct(array $urlParams = [])
    {

		parent::__construct('audioplayer', $urlParams);
        $container = $this->getContainer();

        $container->registerService(
            'L10N', function (IContainer $c) {
            return $c->query('ServerContainer')
                ->getL10N($c->query('AppName'));
        }
        );
    }

	public function registerFileHooks() {
		Util::connectHook(
			'OC_Filesystem', 'delete', '\OCA\audioplayer\Hooks\FileHooks', 'deleteTrack'
		);
	}

	public function registerUserHooks() {
		Util::connectHook(
			'OC_User', 'post_deleteUser', '\OCA\audioplayer\Hooks\UserHooks', 'deleteUser'
		);
	}
}