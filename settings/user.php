<?php
/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @copyright Morris Jobke 2013, 2014
 */
namespace OCA\audioplayer\AppInfo;
use \OCA\audioplayer\AppInfo\Application;


$app = new Application();
$c = $app->getContainer();

#$c->query('API')->addScript('public/settings-user');
#$c->query('API')->addStyle('settings-user');

$tmpl = new \OCP\Template($c->query('AppName'), 'settings-user');
$tmpl->assign('category', $c->query('Config')->getUserValue($c->query('UserId'), $c->query('AppName'), 'category'));

#$tmpl->assign('ampacheKeys', $c->query('AmpacheUserMapper')->getAll($c->query('UserId')));
#$tmpl->assign('URLGenerator', $c->query('URLGenerator'));
return $tmpl->fetchPage();