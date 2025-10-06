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
use function Vvveb\postTypes;

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

	function index() {
		$types = postTypes($this->type);

		$this->view->type  = $types;
		$this->view->count = count($types);
	}
}
