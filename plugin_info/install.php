<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function sabnzbd_install() {
	$cron = cron::byClassAndFunction('sabnzbd', 'pull');
	log::add('sabnzbd','debug',"cron = ".$cron);

	if ( ! is_object($cron)) {
        $cron = new cron();
        $cron->setClass('sabnzbd');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('* * * * *');
        $cron->save();
	}
}

function sabnzbd_update() {
	foreach (eqLogic::byType('sabnzbd') as $eqLogic) {
		$eqLogic->save();
	}
    $cron = cron::byClassAndFunction('sabnzbd', 'pull');
	log::add('sabnzbd','debug',"cron = ".$cron);
	if ( ! is_object($cron)) {
        $cron = new cron();
        $cron->setClass('sabnzbd');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('* * * * *');
        $cron->save();
	}
}

function sabnzbd_remove() {
    $cron = cron::byClassAndFunction('sabnzbd', 'pull');
	log::add('sabnzbd','debug',"cron = ".$cron);
    if (is_object($cron)) {
		$cron->stop();
        $cron->remove();
    }
}
?>
