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

namespace Vvveb\Controller\Settings;

use Vvveb\Controller\Listing;
use Vvveb\System\Event;

class PostTypes extends Listing {
	protected $type = 'post';

	function delete() {
		$userType = $this->request->get['type'] ?? false;

		if ($userType) {
			$userPostTypes = \Vvveb\getSetting($this->type, 'types', []);

			if ($userPostTypes) {
				if (! is_array($userType)) {
					$userType = [$userType];
				}

				foreach ($userType as $type) {
					if (isset($userPostTypes[$type])) {
						unset($userPostTypes[$type]);
					}
				}
				$userPostTypes = \Vvveb\setSetting($this->type, 'types', $userPostTypes);
			}
		}

		return $this->index();
	}

	protected $defaultTypes = 	[
		'post' => [
			'name'    => 'Post',
			'source'  => 'default',
			'site_id' => '0',
			'type'    => 'post',
			'plural'  => 'posts',
			'icon'    => 'icon-document-text-outline',
			'comments'=> true,
		],
		'page' => [
			'name'    => 'Page',
			'source'  => 'default',
			'site_id' => '0',
			'type'    => 'page',
			'plural'  => 'pages',
			'icon'    => 'icon-document-outline',
			'comments'=> false,
		],
	];

	function index() {
		$type              = ucfirst($this->type);
		list($pluginTypes) = Event::trigger('Vvveb\Controller\Base', "custom$type", []);
		array_walk($pluginTypes, function (&$type,$key) {$type['source'] = 'plugin'; $type['name'] = ucfirst($key); });

		$userTypes = \Vvveb\getSetting($this->type, 'types', []);

		$params            = ['module' => "settings/{$this->type}-type"];
		$paramsList        = ['module' => "settings/{$this->type}-types"];

		array_walk($userTypes, function (&$type,$key) use ($params, $paramsList) {
			$type['source'] = 'user';
			$type['name'] = ucfirst($key);
			$type['url']        = \Vvveb\url($params + ['type' => $type['type']]);
			$type['delete-url'] = \Vvveb\url($paramsList + ['action' => 'delete', 'type[]' => $type['type']]);
		});

		$types = $this->defaultTypes + $pluginTypes + $userTypes;

		$this->view->type  = $types;
		$this->view->count = count($types);
	}
}
