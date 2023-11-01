<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Vvveb\Controller\Tools;

use Vvveb\Controller\Base;
use Vvveb\System\Core\View;
use Vvveb\System\Cron as CronList;
use Vvveb\System\Sites;

class Cron extends Base {
	function index() {
		$view           = View :: getInstance();
		$site           = Sites::getSiteById($this->global['site_id']);

		$view->cron     = CronList::getCrons();
		$view->cronkey  = \Vvveb\get_config('app.cronkey');
		$view->cron_url = $site['href'] . '/run-cron/' . $view->cronkey;
		$view->cron_cli = 'php ' . DIR_ROOT . 'cli.php app module=cron/index';
	}
}
