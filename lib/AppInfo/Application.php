<?php
/**
 * Audioplayer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2020 Marcel Scherello
 */

namespace OCA\audioplayer\AppInfo;

use OCA\audioplayer\Listener\LoadAdditionalScripts;
use OCA\audioplayer\Search\Provider;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Util;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'audioplayer';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScripts::class);
        $context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadAdditionalScripts::class);
        $context->registerSearchProvider(Provider::class);
        $this->registerNavigationEntry();
        $this->registerFileHooks();
        $this->registerUserHooks();
    }

    protected function registerNavigationEntry(): void
    {
        $navigationEntry = function () {
            return [
                'id' => 'audioplayer',
                'order' => 6,
                'name' => \OC::$server->getL10N('audioplayer')->t('Audio Player'),
                'href' => \OC::$server->getURLGenerator()->linkToRoute('audioplayer.page.index'),
                'icon' => \OC::$server->getURLGenerator()->imagePath('audioplayer', 'app.svg'),
            ];
        };
        \OC::$server->getNavigationManager()->add($navigationEntry);
    }

    protected function registerFileHooks()
    {
        Util::connectHook(
            'OC_Filesystem', 'delete', '\OCA\audioplayer\Hooks\FileHooks', 'deleteTrack'
        );
    }

    protected function registerUserHooks()
    {
        Util::connectHook(
            'OC_User', 'post_deleteUser', '\OCA\audioplayer\Hooks\UserHooks', 'deleteUser'
        );
    }

    public function boot(IBootContext $context): void
    {
    }

}