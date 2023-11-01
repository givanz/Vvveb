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

namespace Vvveb\Component\Content;

use function Vvveb\getLanguage;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Archives extends ComponentBase {
	public static $defaultOptions = [
		'start'                    => 0,
		'language_id'              => 1,
		'site_id'                  => 1,
		'count'                    => ['url', 4],
		'id_manufacturer'          => NULL,
		'order'                    => ['url', 'price asc'],
		'id_category'              => NULL,
		'limit'                    => 0,
		'start'                    => 1,
		'type'                 	   => 'post',
		'interval'                 => 'month', //year, month, day
	];

	function results() {
		$post    = new \Vvveb\Sql\PostSQL();
		$results = $post->getArchives($this->options) ?? [];

		$df	= false;

		if (class_exists('\IntlDateFormatter')) {
			$dt = new \DateTime();
			$df = new \IntlDateFormatter(getLanguage(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, NULL, NULL, 'MMMM');
		}

		foreach ($results['archives'] as $index => &$archive) {
			if (isset($archive['month'])) {
				$monthNum              = $archive['month'];
				//$dateObj               = \DateTime::createFromFormat('!m', $monthNum);
				//$monthName             = $dateObj->format('F');

				$archive['month_text'] = $monthNum;

				if ($df) {
					$archive['month_text'] = ucfirst(datefmt_format($df, $dt));
					$dt->setDate(0, $archive['month'], 0);
				} else {
					$archive['month_text'] = date('F',mktime(0,0,0,$monthNum,1,$archive['year']));
				}
			}

			$archive['name'] =
				(isset($archive['day']) ? $archive['day'] . ' ' : '') .
				(isset($archive['month']) ? $archive['month_text'] . ' ' : '') .
				(isset($archive['year']) ? $archive['year'] . ' ' : '');

			$archive['month'] = sprintf('%02d', $archive['month']);

			$archive['url'] = htmlentities(\Vvveb\url('content/archive/index', $archive));
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
