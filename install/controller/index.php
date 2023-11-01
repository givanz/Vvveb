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
use function Vvveb\installedLanguages;
use function Vvveb\session;
use function Vvveb\setLanguage;
use Vvveb\Sql\LanguageSQL;
use Vvveb\Sql\RoleSQL;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Core\View;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Extensions\Themes as ThemesList;
use Vvveb\System\Functions\Str;
use Vvveb\System\User\Admin;
use function Vvveb\userPreferedLanguage;

define('REQUIRED_EXTENSIONS', ['mysqli', 'mysqlnd', 'xml', 'libxml', 'pcre',  'zip', 'dom', 'curl', 'gettext']);
define('WRITABLE_FOLDERS', ['storage', 'storage/cache', 'storage/model', 'storage/compiled-templates', 'config', 'config/sites.php', 'public/media/', 'public/themes', 'public/image-cache']);
define('MIN_PHP_VERSION', '7.4.0');
define('DEFAULT_LANG', 'en_US');

#[\AllowDynamicProperties]
class Index extends Base {
	function __construct() {
		if (! ($lang = session('language'))) {
			$lang = userPreferedLanguage();

			if ($lang) {
				session(['language' => $lang]);
			}
		}

		if ($lang) {
			setLanguage($lang);
		}

		if (\Vvveb\is_installed()) {
			$admin = false;

			try {
				$admin = Admin::get(['role_id' => 1]);
			} catch (\Exception $e) {
				$this->view         = View :: getInstance();
				$message            = __('Missing admin table or data, remove config/db.php to reinstall!') . "\n\n" . $e->getMessage();
				$this->view->info[] = $message;

				//die($message);
			}

			if ($admin && $admin['status'] == '1') {
				header('Location: /');

				die(__('Already installed! To reinstall remove config/db.php') . "\n");
			} else {
				if (! $admin || ! isset($admin['role_id']) || $admin['role_id'] != '1') {
					$message            = __('Invalid installation. No user with "super admin" role found!');
					$this->view         = View :: getInstance();
					$this->view->info[] = $message;
				}
			}
		}
	}

	function checkRequirements() {
		$notMet = [];

		if (version_compare(PHP_VERSION, MIN_PHP_VERSION) < 0) {
			$notMet[] = sprintf(__('You need at least PHP %s , your current version %s'), MIN_PHP_VERSION, PHP_VERSION);
		}

		foreach (REQUIRED_EXTENSIONS as $extension) {
			if (! extension_loaded($extension)) {
				$notMet[] = sprintf(__('PHP extension %s is not installed'), $extension);
			}
		}

		foreach (WRITABLE_FOLDERS as $folder) {
			$path = DIR_ROOT . $folder;

			if (! is_writable($path) && ! @chmod($path, 0750)) {
				$notMet[] = sprintf(__('"%s" is not writable'), $folder);
			}
		}

		return $notMet;
	}

	function writeConfig($data) {
		return \Vvveb\set_config('db', $data);
		$configFile = DIR_ROOT . 'config/db.php';
		file_put_contents($configFile, "<?php\n return " . var_export($data, true) . ';');
		clearstatcache(true, $configFile);
	}

	function import($noimport = false) {
		$config = ['engine' => '', 'host' => '', 'database'  => '', 'user'  => '', 'password'  => '', 'prefix'  => ''];

		array_walk($this->request->post, function ($value, $key) use (&$config) {
			if (isset($config[$key])) {
				$config[$key] = $value;
			}
		});

		$config['engine'] = $config['engine'] ? $config['engine'] : 'mysqli';

		if ($config['engine'] == 'sqlite') {
			$config['host'] = DIR_STORAGE . 'sqlite/vvveb.db';
		}

		extract($config);

		$prefix                       = $prefix ?? '';
		$data['default']              = $config['engine'];
		$data['connections'][$engine] = $config;

		try {
			if (! defined('DB_ENGINE')) {
				define('DB_ENGINE', $engine);
				define('DB_HOST', $host);
				define('DB_USER', $user);
				define('DB_PASS', $password);
				define('DB_NAME', $database);
				define('DB_PREFIX', $prefix);
				define('DB_CHARSET', 'utf8mb4');
				define('DIR_SQL', DIR_APP . 'sql/' . DB_ENGINE . '/');
			}

			$import = new \Vvveb\System\Import\Sql($engine, $host, $database, $user, $password, $prefix);
			//$import->createDb($database);
			if ($engine == 'mysqli') {
				$import->createDb($database);
			}
			$import->setPath(DIR_ROOT . "install/sql/$engine/schema/");
			$import->createTables();

			if ($noimport) {
				$filter = ['taxonomy.insert', 'admin.insert', 'role.insert', 'country.insert', 'region.insert', 'site.insert', 'menu.insert', 'length_type.insert'];
			} else {
				$filter = [];
			}

			$import->setPath(DIR_ROOT . 'install/sql/insert/');
			$import->insertData($filter);
			//$import->db->close();
			$this->writeConfig($data);

			header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'localhost') . '?action=install');
		} catch (\Exception $e) {
			$this->view->errors[] = sprintf(__('Db error: "%s" Error code: "%s"'), $e->getMessage(), $e->getCode());
		}
	}

	function index() {
		$requirements   = $this->checkRequirements();
		$noimport 	     = $this->request->post['noimport'] ?? false;

		if ($requirements) {
			$this->view->requirements     = $requirements;
		}

		\Vvveb\System\CacheManager::clearFrontend();

		if ($this->request->post) {
			if (isset($this->request->post['language'])) {
				$lang = $this->request->post['language'];
				session(['language' => $lang]);
				setLanguage($lang);
			} else {
				$this->import($noimport);
				//if user data is provided (by CLI) run also step2
				if (isset($this->request->post['admin'])) {
					$this->install();
				}
			}
		}

		$installedLanguages                = [DEFAULT_LANG => '0'] + array_flip(installedLanguages());
		$languagesList                     = include DIR_SYSTEM . 'data/languages-list.php';
		$languages                         = array_intersect_key($languagesList, $installedLanguages);

		if (! defined('CLI')) {
			$this->view->languagesList    = $languages;
			$this->view->currentLanguage  = session('language') ?? DEFAULT_LANG;
		}
	}

	function install() {
		if (! defined('CLI')) {
			$themes             =  ThemesList :: getList();

			$this->view->themes = $themes;
			$this->view->count  = count($themes);
		}

		if ($this->request->post) {
			//set admin password
			$user        = $this->request->post['admin'] ?? [];
			$settings    = $this->request->post['settings'] ?? [];
			$theme       = $this->request->post['theme'] ?? 'landing';
			$noecommerce = $this->request->post['noecommerce'] ?? false;

			$user['status'] = 1;
			$result         = Admin::update($user, ['username' => 'admin']);
			$sites          = new SiteSQL();
			//$result         = \Vvveb\set_settings('site',$settings);

			$site             = [];
			$site['site_id']  = 1;
			$site['settings'] = json_encode($settings);

			if ($theme) {
				@\Vvveb\set_config('sites.* * *.theme', $theme);
			}

			if ($noecommerce) {
				Plugins::activate('hide-ecommerce', 1);
			}

			//if (isset($_SERVER['HTTP_HOST'])) {
			//set default website url
			$sites           = new SiteSQL();
			$siteSettings    = $sites->get(['site_id' => 1]);
			$hasSiteSettings = false;

			if (is_array($settings) && is_array($siteSettings)) {
				$hasSiteSettings = true;
				$data            = json_decode($siteSettings['settings'], true) ?? [];
				$settings        = $settings + $data;
			}

			$settings['admin-email']   = $user['email'];
			$settings['contact-email'] = $user['email'];

			$site = [
				'host'     => $_SERVER['HTTP_HOST'] ?? '*.*.*',
				'theme'    => $theme,
				'settings' => json_encode($settings),
			];

			if ($hasSiteSettings) {
				$sites->edit(['site' => $site, 'site_id' => 1]);
			} else {
				//empty installation
				$site['site_id'] = 1;
				$site['key']     = '* * *';
				$site['name']    = 'default';
				$sites->add(['site' => $site]);

				$role = new RoleSQL();
				$role->add(['role' => [
					'role_id'      => 1,
					'name'         => 'super_admin',
					'display_name' => 'Super Administrator',
					'permissions'  => '{"allow":["*"], "deny":[]}',
				]]);

				$languageModel = new LanguageSQL();
				$languageModel->add(['language' => [
					'language_id' => 1,
					'name'        => 'English',
					'code'        => 'en_US',
					'locale'      => 'en-us',
					'status'      => 1,
					'default'     => 1,
				]]);
			}

			$lang = \Vvveb\session('language');

			if ($lang) {
				$languageModel          = new LanguageSQL();
				$installed              = $languageModel->get(['code' => $lang]);

				if ($installed) {
					$result = $languageModel->edit(['language' => ['status' => 1], 'language_id' => $installed['language_id']]);
				} else {
					$languagesList                     = include DIR_SYSTEM . 'data/languages-list.php';
					$language                          = $languagesList[$lang];
					$language['locale']                = $language['code'];
					$language['code']                  = $lang;
					$language['status']                = 1;

					$result = $languageModel->add(['language' => $language]);
				}
			}

			@\Vvveb\set_config('app.cronkey', Str::random(32));
			@\Vvveb\set_config('app.key', Str::random(32));

			$success               = __('Installation succesful!');
			$this->view->success[] = $success;
			$admin_path            = \Vvveb\adminPath();
			$location              = preg_replace('@/install.*$@', $admin_path . "?success=$success", ($_SERVER['REQUEST_URI'] ?? ''));

			header("Location: $location");
		}

		$this->view->template('install.html');
	}
}
