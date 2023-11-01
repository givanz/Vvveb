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

use function Vvveb\__;
use Vvveb\Controller\Base;

class SystemInfo extends Base {
	function index() {
		$database = DB_ENGINE;

		if (DB_ENGINE == 'mysqli') {
			$database .= ' | ' . sprintf(__('Client library version: %s'), mysqli_get_client_info());
			$database .= ' | ' . sprintf(__('Server version: %s'), \Vvveb\System\Db::getInstance()->info());
		} else {
			$info = \Vvveb\System\Db::getInstance()->info();

			if (is_array($info)) {
				$str = '';

				foreach ($info as $key => $value) {
					$str .= " $key = $value | ";
				}
				$info = $str;
			}
			$database .= ' | ' . sprintf(__('Server version: %s'), $info);
		}

		$info = [
			'general' => [
				__('Vvveb version')                    => V_VERSION,
				__('Admin path')                       => \Vvveb\adminPath(),
				__('PHP version')                      => phpversion() . ' | ' . php_sapi_name(),
				__('Server')                           => $_SERVER['SERVER_SOFTWARE'] ?? '',
				__('OS version')                       => php_uname(),
				__('Database driver & version')        => $database,
				__('PHP time limit')                   => ini_get('max_execution_time'),
				__('PHP memory limit')                 => ini_get('memory_limit'),
				__('Max input time')                   => ini_get('max_input_time'),
				__('Upload max filesize')              => ini_get('upload_max_filesize'),
				__('PHP post max size')                => ini_get('post_max_size'),
				__('Extensions')                       => implode(' ', get_loaded_extensions()),
				__('Page cache')                       => (defined('PAGE_CACHE') && PAGE_CACHE) ? __('enabled') : __('disabled'),
				__('Debug')                            => DEBUG ? __('enabled') : __('disabled'),
				__('Sql changes check')                => SQL_CHECK ? __('enabled') : __('disabled'),
			],
			'server' => [
				__('Document root')               	   => $_SERVER['DOCUMENT_ROOT'] ?? '',
				__('Public path')               	     => PUBLIC_PATH ?? '',
			],
		];

		foreach ($_SERVER as $key => $value) {
			$key                  = __(\Vvveb\humanReadable(strtolower($key)));
			$info['server'][$key] = $value;
		}

		$this->view->info = $info;
		/*
		ob_start();
		phpinfo();
		$php = ob_get_contents();
		ob_end_clean();
		$php = preg_replace('@^.+?<body><div class="center">|</div></body></html>@ms', '', $php);
		$php = preg_replace('@<table>@ms', '<table class="table table-bordered">', $php);
		$php = preg_replace('@<h2><a.+?>(.+?)</a></h2>@ms', '<h3>\1</h3>', $php);

		$this->view->phpinfo = $php;*/
	}
}
