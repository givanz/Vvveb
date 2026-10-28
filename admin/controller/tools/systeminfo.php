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
use Vvveb\System\Event;
use Vvveb\System\Media\Image;

class SystemInfo extends Base {
	/**
	 * Display system information including PHP, server, database, and extension details.
	 *
	 * @return void
	 */
	public function index(): void {
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

		$imageLibrary = 'mockup (Imagick or GD not enabled!)';

		if (extension_loaded('imagick')) {
			$imageLibrary = 'imagick';
		} elseif (extension_loaded('gd')) {
			$imageLibrary = 'gd';
		}

		$objectcache = \Vvveb\getConfig('app.cache.driver');
		$objectcache .= ($objectcache != 'file' && !extension_loaded($objectcache)) ? ' extension not available!' : '';

		$info = [
			'general' => [
				__('Vvveb version')             => V_VERSION,
				__('Admin path')                => \Vvveb\adminPath(),
				__('PHP version')               => PHP_VERSION . ' | ' . PHP_SAPI,
				__('Server')                    => $_SERVER['SERVER_SOFTWARE'] ?? '',
				__('OS version')                => php_uname(),
				__('Database driver & version') => $database,
				__('PHP time limit')            => ini_get('max_execution_time'),
				__('PHP memory limit')          => ini_get('memory_limit'),
				__('Max input time')            => ini_get('max_input_time'),
				__('Upload max filesize')       => ini_get('upload_max_filesize'),
				__('PHP post max size')         => ini_get('post_max_size'),
				__('Extensions')                => implode(' ', get_loaded_extensions()),
				__('Page cache')                => (defined('PAGE_CACHE') && PAGE_CACHE) ? __('enabled') : __('disabled'),
				__('Object cache')              => $objectcache,
				__('Email Driver')              => \Vvveb\getConfig('app.email.driver'),
				__('Session Driver')            => \Vvveb\getConfig('app.session.driver'),
				__('Debug')                     => DEBUG ? __('enabled') : __('disabled'),
				__('Sql changes check')         => SQL_CHECK ? __('enabled') : __('disabled'),
				__('Image library')             => $imageLibrary,
				__('Image formats')             => implode(' ', Image::formats()),
				'Rest API'                      => REST ? __('enabled') : __('disabled'),
				'GraphQL'                       => GRAPHQL ? __('enabled') : __('disabled'),
			],
			'server' => [
				__('Document root') => $_SERVER['DOCUMENT_ROOT'] ?? '',
				__('Public path')   => PUBLIC_PATH,
			],
		];

		foreach ($_SERVER as $key => $value) {
			$key                  = __(\Vvveb\humanReadable(strtolower($key)));
			$info['server'][$key] = $value;
		}

		[$info] = Event::trigger(__CLASS__, __FUNCTION__, $info);

		$this->view->info = $info;

		if (isset($this->request->get['phpinfo'])) {
			ob_start();
			phpinfo();
			$php = ob_get_contents();
			ob_end_clean();
			$php = preg_replace('@^.+?<body><div class="center">|</div></body></html>@ms', '', $php);
			$php = preg_replace('@<table>@ms', '<table class="table table-bordered">', $php);
			$php = preg_replace('@<h2><a.+?>(.+?)</a></h2>@ms', '<h3>\1</h3>', $php);

			$this->view->phpinfo = $php;
		}
	}
}
