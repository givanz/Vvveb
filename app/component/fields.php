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

namespace Vvveb\Component;

use function Vvveb\model;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Fields extends ComponentBase {
	public static $defaultOptions = [
		'start'             => 0,
		'limit'             => 100,
		'site_id'           => NULL,
		'language_id'       => NULL,
		'field_group_id'    => NULL,
		'type'              => NULL,
		'subtype'           => NULL,
		'post_type'         => NULL,
		'product_id'        => 'url',
		'post_id'           => 'url',
		'user_id'           => 'url',
		'order_id'          => 'url',
		'taxonomy_item_id'  => 'url',
		'field_id'          => NULL, //array with filed_id's to filter
	];

	public $cacheExpire = 0; //no cache

	function results() {
		$model      = false;
		$field_type = trim($this->options['type'] ?? '');

		if (in_array($field_type, ['post', 'product', 'user', 'order', 'taxonomy_item'])) {
			$modelName = $field_type . '_field_value';
			$model     = model($field_type . '_field_value');
		}

		if (! $model) {
			return [];
		}

		if (! $this->options['subtype']) {
			unset($this->options['subtype']);
		}

		$results = $model->getAll($this->options);

		if (isset($results[$modelName])) {
			foreach ($results[$modelName] as $field_id => &$field) {
				$field['settings'] = json_decode($field['settings'], true);
				$field['name']     = $field['settings']['name'];
				$field['type']     = $field['settings']['type'];

				if ($field['value'] && $field['value'][0] == '{') {
					$field['value'] = json_decode($field['value'], true);
				}
			}
		}

		$results['field'] = $results[$modelName] ?? [];
		unset($results[$modelName]);

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called on each request
	function request(&$results, $index = 0) {
		if (isset($this->options['field_id']) && is_array($this->options['field_id'])) {
			$results = array_intersect_key($results, $this->options['field_id']);
		}

		return $results;
	}
}
