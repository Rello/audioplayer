<?php

declare(strict_types=1);

/**
 * Analytics
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2020 Marcel Scherello
 */

namespace OCA\audioplayer\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadAdditionalScripts implements IEventListener
{
    public function handle(Event $event): void
    {
        if ($event instanceof LoadAdditionalScriptsEvent) {
            Util::addScript('audioplayer', 'viewer/viewer');
            Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');
        } elseif ($event instanceof BeforeTemplateRenderedEvent) {
            Util::addScript('audioplayer', 'viewer/viewer');
            Util::addScript('audioplayer', 'sharing/sharing');
            Util::addStyle('audioplayer', '3rdparty/fontello/css/fontello');
        } else {
            return;
        }
    }
}