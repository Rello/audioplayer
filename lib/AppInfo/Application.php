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
use OCP\Util;

$version = Util::getVersion()[0];
if ($version >= 20) {
    class Application extends Application20
    {
    }
} else {
    class Application extends Application19
    {
    }
}