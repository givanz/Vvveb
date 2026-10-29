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

namespace Vvveb\Controller\User;

use function Vvveb\__;
use function Vvveb\pregMatch;
use Vvveb\Sql\Admin_failed_loginSQL;
use Vvveb\System\Event;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Functions\Str;
use Vvveb\System\Locale;
use Vvveb\System\User\Admin;
use Vvveb\System\Validator;

#[\AllowDynamicProperties]
class Login {
	/**
	 * Time interval format for grouping failed login attempts.
	 *
	 * @var string
	 */
	protected string $failedTimeInterval = 'Y-m-d H:00:00';

	/**
	 * Maximum number of failed login attempts before the account is locked.
	 *
	 * @var int
	 */
	protected int $failedCount = 10;

	/**
	 * Initialize the default language and RTL settings for the login page.
	 *
	 * @return void
	 */
	protected function language(): void {
		$default_rtl = false;
		if (!($default_code = $this->session->get('code'))) {
			$languages = Locale::availableLanguages();

			foreach ($languages as $code => $lang) {
				//set global default language
				if ($lang['default']) {
					$default_language    = $code;
					$default_language_id = $lang['language_id'];
					$default_locale      = $lang['locale'];
					$default_code        = $lang['code'];
					$default_rtl         = $lang['rtl'] ?? false;

					break;
				}
			}
		}

		Locale::setLanguage($default_code);
		$this->global['code'] = $default_code;
		$this->global['rtl']  = $default_rtl;
	}

	/**
	 * Redirect the user to the specified URL.
	 *
	 * @param string                   $url        The URL to redirect to.
	 * @param array<int|string, mixed> $parameters Additional URL parameters (unused).
	 *
	 * @return void
	 */
	protected function redirect(string $url = '/', array $parameters = []): void {
		header("Location: $url");

		exit();
	}

	/**
	 * Handle the login page display and form submission.
	 *
	 * @return void
	 */
	public function index(): void {
		$view            = $this->view;
		$adminPath       = \Vvveb\adminPath();
		$view->adminPath = $adminPath;

		if (isset($this->request->post['logout'])) {
			Admin::logout();

			return;
		}

		$this->language();
		$admin      = Admin::current();

		if ($admin) {
			$this->redirect($adminPath);

			return;
		}

		if (!$this->session->get('csrf')) {
			$this->session->set('csrf', Str::random());
		}

		//don't load plugins at all if safemode get parameter
		if (!isset($this->request->get['safemode'])) {
			$failedPlugins = [];

			try {
				Plugins::loadPlugins();
			} catch (\Throwable $e) {
				$file            = $e->getFile();
				$plugin          = pregMatch('@.*[\/]plugins[\/]([^\/]+)[$\/]@', $file, 1) ?: $file;
				$failedPlugins[] = $plugin;
				$view->errors[]  = sprintf(__('Plugin `%s` failed to load!'), $plugin);
			}

			if ($failedPlugins) {
				$view->warning[] = __('Some plugins throw errors when loading, enable safe mode to disable them!');
				$view->safemode  = true;
			}
		}

		$view->action    = $adminPath . 'index.php?module=user/login';
		$view->modal     = $this->request->get['modal'] ?? false;

		if (isset($this->request->get['success'])) {
			$view->success['get'] = htmlspecialchars($this->request->get['success']);
		}

		if (isset($this->request->get['errors'])) {
			$view->errors['get'] = htmlspecialchars($this->request->get['errors']);
		}

		if ($errors = $this->session->get('errors')) {
			if (is_array($errors)) {
				$view->errors = ($view->errors ?? []) + $errors;
			} else {
				$view->errors['session'] = $errors;
			}
			$this->session->delete('errors');
		}

		if ($success = $this->session->get('success')) {
			if (is_array($success)) {
				$view->success = ($view->success ?? []) + $success;
			} else {
				$view->success['session'] = $success;
			}
			$this->session->delete('success');
		}

		$validator = new Validator(['login']);

		if (isset($this->request->get['module']) && $this->request->get['module'] != 'user/login') {
			$view->redir = $this->request->get['module'];
		}

		$method = $this->request->getMethod();

		if ($method == 'post') {
			if (!($csrf = $this->session->get('csrf')) || !($postCsrf = ($this->request->post['csrf'] ?? false)) || ($csrf !== $postCsrf)) {
				$view->errors['get'] = __('Invalid csrf!');

				return;
			}

			if (($view->errors = $validator->validate($this->request->post)) === true) {
				$user	    = $this->request->post['user'];

				$safemode  = $this->request->post['safemode'] ?? false;
				$flags     = [];
				$loginData = [];

				if (strpos($user, '@')) {
					$loginData['email'] = $user;
				} else {
					$loginData['username'] = $user;
				}

				$failedLogin   = new Admin_failed_loginSQL();
				$date          = date($this->failedTimeInterval);
				$lastIp        = $_SERVER['REMOTE_ADDR'] ?? '';
				$failedAttemps = $failedLogin->get(['updated_at' => $date, 'status' => 1] + $loginData);

				if ($failedAttemps && ($failedAttemps['count'] > $this->failedCount)) {
					$view->errors = [__('Too many login attempts, try again in one hour!')];
					$failedLogin->logFailed(['last_ip' => $lastIp, 'updated_at' => $date] + $loginData);

					return;
				}

				$loginData['password'] = $this->request->post['password'];

				if ($safemode) {
					$flags['safemode'] = true;
				}

				[$loginData] = Event::trigger(__CLASS__, __FUNCTION__, $loginData);

				if ($loginData) {
					if ($userInfo = Admin::login($loginData, $flags)) {
						$view->success[] = __('Login successful!');

						if (isset($this->request->post['redir']) && $this->request->post['redir'] && $_SERVER['REQUEST_URI'] != $this->request->post['redir']) {
							$url = parse_url($this->request->post['redir']);
							$this->redirect($url['path'] . '?' . ($url['query'] ?? '') . '#' . ($url['fragment'] ?? ''));
						} else {
							$this->redirect($adminPath);
						}
					} else {
						//user not found or wrong password
						$view->errors   = [__('Authentication failed, wrong email or password!')];
						$view->safemode = $safemode;
						$this->session->set('csrf', Str::random());
						//increment failed attempts
						$failedLogin->logFailed(['last_ip' => $lastIp, 'updated_at' => $date] + $loginData);
					}
				}
			} else {
				$this->session->set('csrf', Str::random());
			}
		}
	}
}
