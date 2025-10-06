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

namespace Vvveb\Controller;

use function Vvveb\__;
use function Vvveb\humanReadable;
use Vvveb\System\Traits\Crud as CrudTrait;

class Crud extends Base {
	use CrudTrait {
		CrudTrait::index as get;
	}

	function index() {
		$result = $this->get();

		$this->view->{$this->type}  = $this->data;

		if ($this->data_id && (! $this->data || (! isset($this->data[$this->type . '_id'])))) {
			return $this->notFound(sprintf(__('%s not found!'), humanReadable(__($this->type))));
		}

		$admin_path          = \Vvveb\adminPath();
		$controllerPath      = $admin_path . 'index.php?module=media/media';
		$this->view->scanUrl = "$controllerPath&action=scan";
		$this->uploadUrl     = "$controllerPath&action=upload";

		$this->view->status = [0 => __('Inactive'), 1 => __('Active')];
	}
}
