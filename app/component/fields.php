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
	public static array $defaultOptions = [
		'start'            => 0,
		'limit'            => 100,
		'site_id'          => null,
		'language_id'      => null,
		'field_group_id'   => null,
		'type'             => null,
		'subtype'          => null,
		'post_type'        => null,
		'product_id'       => 'url',
		'post_id'          => 'url',
		'user_id'          => 'url',
		'order_id'         => 'url',
		'taxonomy_item_id' => 'url',
		'field_id'         => null,
	];

	public int $cacheExpire = 0;

	/**
	 * Get custom field values for the specified entity type with decoded settings.
	 *
	 * @return array<string, mixed> Fields data with field list containing decoded settings and values.
	 */
	public function results(): array {
		$model      = false;
		$field_type = trim($this->options['type'] ?? '');

		if (in_array($field_type, ['post', 'product', 'user', 'order', 'taxonomy_item'])) {
			$modelName = $field_type . '_field_value';
			$model     = model($field_type . '_field_value');
		}

		if (!$model) {
			return [];
		}

		if (!$this->options['subtype']) {
			unset($this->options['subtype']);
		}

		$results = $model->getAll($this->options);

		if (isset($results[$modelName])) {
			foreach ($results[$modelName] as $field_id => &$field) {
				$field['settings'] = json_decode($field['settings'], true);
				$field['name']     = $field['settings']['name'];
				$field['type']     = $field['settings']['type'];

				if ($field['value'] && $field['value'][0] === '{') {
					$field['value'] = json_decode($field['value'], true);
				}
			}
		}

		$results['field'] = $results[$modelName] ?? [];
		unset($results[$modelName]);

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Filter results by field_id array if specified.
	 *
	 * @param array<string, mixed> &$results Component results to filter.
	 * @param int                  $index    Request index.
	 *
	 * @return array<string, mixed> Filtered results.
	 */
	public function request(array &$results, int $index = 0): array {
		if (isset($this->options['field_id']) && is_array($this->options['field_id'])) {
			$results = array_intersect_key($results, $this->options['field_id']);
		}

		return $results;
	}
}
