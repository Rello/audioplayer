<?php

declare(strict_types=1);

/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2021 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */
namespace OCA\audioplayer\Event;

use OCP\EventDispatcher\Event;

class LoadAdditionalScriptsEvent extends Event {
    private $hiddenFields = [];

    public function addHiddenField(string $name, string $value): void {
        $this->hiddenFields[$name] = $value;
    }

    public function getHiddenFields(): array {
        return $this->hiddenFields;
    }
}