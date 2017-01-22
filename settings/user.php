<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */

namespace OCA\audioplayer\AppInfo;
use \OCA\audioplayer\AppInfo\Application;

$app = new Application();
$c = $app->getContainer();

$c->query('API')->addScript('settings-user');

$tmpl = new \OCP\Template($c->query('AppName'), 'settings-user');
$tmpl->assign('category', $c->query('Config')->getUserValue($c->query('UserId'), $c->query('AppName'), 'category'));
$tmpl->assign('cyrillic', $c->query('Config')->getUserValue($c->query('UserId'), $c->query('AppName'), 'cyrillic'));
$tmpl->assign('path', $c->query('Config')->getUserValue($c->query('UserId'), $c->query('AppName'), 'path'));

return $tmpl->fetchPage();
