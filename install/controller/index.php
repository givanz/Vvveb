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
use function Vvveb\session as sess;
use function Vvveb\setLanguage;
use Vvveb\Sql\LanguageSQL;
use Vvveb\Sql\menuSQL;
use Vvveb\Sql\RoleSQL;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Core\View;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Extensions\Themes as ThemesList;
use Vvveb\System\Functions\Str;
use Vvveb\System\Media\Image;
use Vvveb\System\User\Admin;
use function Vvveb\userPreferedLanguage;

define('REQUIRED_EXTENSIONS', ['mysqli', 'mysqlnd', 'xml', 'libxml', 'pcre',  'zip', 'dom', 'curl', 'gettext']);
define('WRITABLE_FOLDERS', ['storage', 'storage/cache', 'storage/model', 'storage/compiled-templates', 'config', 'config/sites.php', 'public/media/', 'public/themes', 'public/image-cache', 'plugins']);
define('MIN_PHP_VERSION', '7.4.0');
define('DEFAULT_LANG', 'en_US');

#[\AllowDynamicProperties]
class Index extends Base {
	private $config = ['engine' => 'mysqli', 'host' => '127.0.0.1', 'database'  => 'vvveb', 'user'  => 'root', 'password'  => '', 'port'  => null, 'prefix'  => ''];
	
	private $subdir = '';

	function __construct() {
		if (! ($lang = sess('language'))) {
			$lang = userPreferedLanguage();

			if ($lang) {
				sess(['language' => $lang, 'language_id' => 1]);
			}
		}

		if ($lang) {
			setLanguage($lang);
		}
		
		$this->subdir = (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : \Vvveb\detectSubDir());

		if (\Vvveb\is_installed()) {
			$admin   = false;
			$message = '';

			try {
				//check if super admin user is available
				$admin = Admin::get(['role_id' => 1]);
			} catch (\Exception $e) {
				$this->view         = View :: getInstance();
				$message            = __('Missing admin table or data, remove config/db.php to reinstall!') . "\n\n" . $e->getMessage();
				$this->view->info[] = $message;

				//die($message);
			}

			if ($admin && isset($admin['status']) && $admin['status'] == '1') {
				header('Location: ' . $this->subdir. '/');

				die(__('Already installed! To reinstall remove config/db.php') . "\n");
			} else {
				if (! $admin || ! isset($admin['role_id']) || $admin['role_id'] != '1') {
					$message .= __('Invalid installation. No user with "super admin" role found!');
					$this->view         = View :: getInstance();
					$this->view->info[] = $message;
				}
			}
		}
	}

	private function checkRequirements() {
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

	function replaceInFile($file, $search, $replace) {
		$content = file_get_contents($file);

		if ($content) {
			$content = str_replace($search, $replace, $content);

			return file_put_contents($file, $content);
		}
	}

	private function writeConfig($data) {
		return \Vvveb\setConfig('db', $data);
		$configFile = DIR_ROOT . 'config/db.php';
		file_put_contents($configFile, "<?php\n return " . var_export($data, true) . ';');
		clearstatcache(true, $configFile);
	}

	private function import($noimport = false) {
		$config = $this->config;

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
				define('DB_HOST',   $host);
				define('DB_USER',   $user);
				define('DB_PASS',   $password);
				define('DB_NAME',   $database);
				define('DB_PREFIX', $prefix);
				define('DB_PORT',   $port);
				define('DIR_SQL',   DIR_APP . 'sql/' . DB_ENGINE . '/');
			}

			$import = new \Vvveb\System\Import\Sql($engine, $host, $database, $user, $password, $port, $prefix);
			//$import->createDb($database);
			if ($engine == 'mysqli') {
				$import->createDb($database);
			}
			$import->setPath(DIR_ROOT . "install/sql/$engine/schema/");
			$import->createTables();

			if ($noimport) {
				$exclude = ['post*', 'product*', 'vendor*', 'manufacturer*', 'taxonomy_*', 'attribute*', 'option*', 'user.sql', 'comment*', 'digital_asset*'];
			} else {
				$exclude = [];
			}

			$import->setPath(DIR_ROOT . 'install/sql/insert/');
			$import->insertData([], $exclude);
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

		//get defaults from get parameters if passed or from env if available
		foreach ($this->config as $key => &$value) {
			$env = 'DB_' . strtoupper($key);

			if (isset($_ENV[$env])) {
				$value = $_ENV[$env];
			}

			if (isset($this->request->get[$key])) {
				$value = $this->request->get[$key];
			}
		}

		if ($this->request->post) {
			if (isset($this->request->post['language'])) {
				$lang = $this->request->post['language'];
				sess(['language' => $lang, 'language_id' => 1]);
				setLanguage($lang);
			} else {
				$this->import($noimport);
				//if user data is provided (by CLI) run also step2
				if (isset($this->request->post['admin'])) {
					$this->install();
				}
			}
		}

		$installedLanguages = [DEFAULT_LANG => '0'] + array_flip(installedLanguages());
		$languagesList      = include DIR_SYSTEM . 'data/languages-list.php';
		$languages          = array_intersect_key($languagesList, $installedLanguages);
		$this->view->config = $this->config;

		if (! defined('CLI')) {
			$this->view->languagesList    = $languages;
			$this->view->currentLanguage  = sess('language') ?? DEFAULT_LANG;
		}
	}

	function install() {
		if (! defined('CLI')) {
			$themes             =  ThemesList :: getList();

			$this->view->themes = $themes;
			$this->view->count  = count($themes);
		}

		$isRootPublic             = (constant('PUBLIC_PATH') == DIRECTORY_SEPARATOR) ? 'true' : 'false';
		$this->view->isRootPublic = $isRootPublic;

		$languagesList      = include DIR_SYSTEM . 'data/languages-list.php';

		if (! defined('CLI')) {
			$this->view->languagesList    = $languagesList;
			$this->view->currentLanguage  = sess('language') ?? DEFAULT_LANG;
		}

		if ($this->request->post) {
			//set admin password
			$user        = $this->request->post['admin'] ?? [];
			$settings    = $this->request->post['settings'] ?? [];
			$theme       = $this->request->post['theme'] ?? 'landing';
			$noecommerce = $this->request->post['noecommerce'] ?? false;
			$hostname    = $this->request->post['hostname'] ?? null;
			$adminPath   = $this->request->post['admin-path'] ?? false;
			$language    = $this->request->post['language'] ?? 'en_US';

			$user['status'] = 1;
			$result         = Admin::update($user, ['username' => 'admin']);
			$sites          = new SiteSQL();
			//$result         = \Vvveb\setMultiSetting('site',$settings);

			if ($noecommerce) {
				Plugins::activate('hide-ecommerce', 1);
			}

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

			if (Image::formats('webp')) {
				$settings['image_format'] = 'webp';
			}

			$site = [
				'host'     => $hostname ?? '*.*.*', //$_SERVER['HTTP_HOST']
				'site_id'  => 1,
				'id'       => 1,
				'name'     => 'Default',
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

			unset($site['settings']);
			@\Vvveb\setConfig('sites.* * *', $site);

			$lang = $language ?? sess('language') ?? 'en_US';

			//set default language
			if ($lang && $lang != 'en_US') {
				$languageModel      = new LanguageSQL();
				$language           = $languagesList[$lang];
				$language['locale'] = $language['code'];
				$language['code']   = $lang;
				$language['status'] = 1;
				//$installed              = $languageModel->get(['code' => $lang]);
				$result = $languageModel->edit(['language' => $language, 'language_id' => 1]);
				sess(['language' => $lang, 'language_id' => 1]);
				setLanguage($lang);
			}

			$error = '';

			//change admin login path
			if ($isRootPublic && $adminPath &&
				($adminPath != 'admin' && $adminPath != 'vadmin')) {
				$from = DIR_PUBLIC . 'vadmin';
				$to   = DIR_PUBLIC . $adminPath;

				if (@rename($from, $to)) {
					@\Vvveb\setConfig('admin.path', $adminPath);
					//if succesful remove failsafe /admin login option
					@unlink(DIR_PUBLIC . 'admin' . DS . 'index.php');
				} else {
					$error = sprintf(__('Renaming admin login path from %s to %s failed! use /admin/index.php path to login'), 'vadmin', $adminPath);
				}
			}

			@\Vvveb\setConfig('app.cronkey', Str::random(32));
			@\Vvveb\setConfig('app.key', Str::random(32));

			//set APCu memory cache if available instead of default file cache
			$cacheDriver = (function_exists('apcu_cache_info') && ini_get('apc.enabled')) ? 'APCu' : null;

			foreach (['app', 'admin', 'graphql', 'rest'] as $app) {
				if ($cacheDriver) {
					@\Vvveb\setConfig($app . '.cache.driver', $cacheDriver);
				}
				@\Vvveb\setConfig($app . '.cronkey', Str::random(32));
				@\Vvveb\setConfig($app . '.key', Str::random(32));
			}

			$subdir = $this->request->post['subdir'] ?? $this->subdir ?? false;

			if ($subdir) {
				$subdir = '/' . trim($subdir, '/ ');
				//add subdir path to menu links
				$menus  = new menuSQL();

				foreach ([1, 5] as $menu_id) { //main menu and footer menu id's
					$menuItems = $menus->get(['menu_id' => $menu_id, 'language_id' => 1])['menu'] ?? [];

					foreach ($menuItems as $menuItem) {
						$data = ['url' => $this->subdir . $menuItem['url'], 'menu_item_content' => []];
						$menus->editMenuItem(['menu_item' => $data,  'menu_item_id' => $menuItem['menu_item_id']]);
					}
				}
				
				//try to set subdir in env.php and .htaccess
				$this->replaceInFile(DIR_ROOT . 'env.php', "define('V_SUBDIR_INSTALL', false" , "define('V_SUBDIR_INSTALL', '{$subdir}'");
				$this->replaceInFile(DIR_ROOT . '.htaccess', 'RewriteRule ^ index.php [L]' , "RewriteRule ^ {$subdir}/index.php [L]");
			}

			if ($error) {
				$this->view->error[] = $error;
			}

			$success               = __('Installation succesful!');
			$this->view->success[] = $success;
			$admin_path            = \Vvveb\adminPath();
			$admin_path            = str_replace($this->subdir, '', $admin_path);
			$location              = preg_replace('@/install.*$@', $admin_path . "/index.php?success=$success&errors=$error", ($_SERVER['REQUEST_URI'] ?? ''));

			header("Location: $location");
		}

		$this->view->template('install.html');
	}
}
