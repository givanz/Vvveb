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

namespace Vvveb\Controller\Content;

use Vvveb\Controller\Base;
use function Vvveb\getLanguage;

class Archive extends Base {
	function index() {
		if (isset($this->request->get['month'])) {
			$month             = $this->request->get['month'];
			$this->view->month = $month;

			$month_name = $month;

			if (class_exists('\IntlDateFormatter')) {
				$dt = new \DateTime();
				$dt->setDate(0, $month, 0);
				$df         = new \IntlDateFormatter(getLanguage(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, NULL, NULL, 'MMMM');
				$month_name = ucfirst(datefmt_format($df, $dt));
			}

			$this->view->month_name = $month_name;
		}

		if (isset($this->request->get['year'])) {
			$this->view->year = $this->request->get['year'];
		}
	}
}
