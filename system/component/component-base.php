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

namespace Vvveb\System\Component;

use function Vvveb\session as sess;
use function Vvveb\siteSettings;
use Vvveb\System\Cache;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Locale;
use Vvveb\System\Session;
use Vvveb\System\User\User;

#[\AllowDynamicProperties]
class ComponentBase {
	/** @var string|null Cache key for this component instance. */
	public ?string $cacheKey = null;

	/** @var int Cache expiry time in seconds. */
	public int $cacheExpire = 3600;

	/** @var array<string, mixed> Shared global context available to all component instances. */
	public static array $global = [];

	/** @var array<array-key, mixed> Resolved options for this component instance. */
	protected array $options = [];

	/** @var string|null Hash used to uniquely identify this component configuration. */
	protected ?string $_hash = null;

	/** @var array<array-key, mixed> Default options merged with global context; subclasses may override. */
	public static array $defaultOptions = [];

	/**
	 * Initialize the component, resolving options against global defaults and request data.
	 *
	 * @param array<array-key, mixed> $options Component-specific options (e.g. from data-v-* attributes).
	 */
	public function __construct(array $options = []) {
		$request = Request :: getInstance();

		if (!self :: $global) {
			$user                                  = User::current();
			$site                                  = siteSettings();
			self :: $global['start']               = 0;
			self :: $global['site_id']             = (APP === 'admin') ? sess('site_id') ?? SITE_ID : (defined('SITE_ID') ? SITE_ID : 0);
			//self :: $global['site']                = siteSettings();
			self :: $global['user_id']             = $user['user_id'] ?? null;
			self :: $global['user_group_id']       = $user['user_group_id'] ?? 1;
			self :: $global['languages']           = $site['languages'] ?? [];
			self :: $global['default_language']    = $site['language'] ?? 'en';
			self :: $global['default_language_id'] = $site['language_id'] ?? 1;
			self :: $global['default_lang_slug']   = $site['default_lang_slug'] ?? false;
			self :: $global['language']            = ($site['language'] ?? '') ?: 'en';
			self :: $global['language_id']         = (int)($site['language_id'] ?? '') ?: 1;
			self :: $global['currency_id']         = (int)(sess('currency_id') ?? $site['currency_id'] ?? 1);
			self :: $global['currency']            = sess('currency') ?? $site['currency'] ?? 'usd';
			self :: $global['currencies']          = $site['currencies'] ?? [];

			//don't set user session language for default language homepage
			if (!isset($request->get['module']) || $request->get['module'] !== 'index/index') {
				self :: $global['language']            = (sess('language') ?? self :: $global['language']);
				self :: $global['language_id']         = (sess('language_id') ?? self :: $global['language_id']);
			}

			if ((isset($request->post['language']) && is_string($request->post['language']) && ($lang = $request->post['language'])) ||
				(isset($request->get['language']) && is_string($request->get['language']) && ($lang = $request->get['language']))) {
				$languages = Locale::availableLanguages();
				if (isset($languages[$lang])) {
					self :: $global['language']    = $lang;
					self :: $global['language_id'] = $languages[$lang]['language_id'];
				}
			}

			if (isset($request->post['language_id']) && is_string($request->post['language_id'])) {
				self :: $global['language_id'] = $request->post['language_id'];
			}
		}

		static :: $defaultOptions += self :: $global;

		foreach (['site_id', 'language_id', 'currency_id', 'user_id', 'user_group_id'] as $key) {
			if (!isset(static :: $defaultOptions[$key]) || empty(static :: $defaultOptions[$key])) {
				static :: $defaultOptions[$key] = self :: $global[$key];
			}
		}

		$this->options = $this->filter($options);

		foreach ($this->options as $key => &$value) {
			if (is_array($value)) {
				foreach ($value as $val) {
					if (is_string($val) && str_starts_with($val, 'url')) {
						//check if url parameter is specified ex: url.slug
						$lookupKey = $key;

						if ($dot = strrpos($val, '.')) {
							$lookupKey = substr($val, $dot + 1);
						}

						if (isset($request->request[$lookupKey])) {
							$value = $request->request[$lookupKey];

							break;
						} else {
							if (isset($value[1])) {
								$value = $value[1];
							} else {
								$value = null;
							}
						}
					}
				}
			}

			if ($value && is_string($value) && str_starts_with($value, 'url')) {
				//check if url parameter is specified ex: url.slug
				$lookupKey = $key;

				if ($dot = strrpos($value, '.')) {
					$lookupKey = substr($value, $dot + 1);
				}

				$value = ($request->request[$lookupKey] ?? ($request->get[$lookupKey] ?? null));
			}

			if ($value === null) {
				unset($this->options[$key]);
			}
		}

		[$this->options] = Event :: trigger(static::class, __FUNCTION__, $this->options);

		$this->di($this);
	}

	/**
	 * Inject standard service dependencies into a component instance.
	 *
	 * @param self &$component The component to inject dependencies into.
	 *
	 * @return void
	 */
	public static function di(self &$component): void {
		$component->request = Request::getInstance();
		$component->view    = View::getInstance();
		$component->session = Session::getInstance();
	}

	/**
	 * Invalidate cached data for this component (stub for subclasses).
	 *
	 * @return void
	 */
	public function invalidateCache(): void {}

	/**
	 * Generate or retrieve the cache key for this component.
	 *
	 * @return string|null The cache key, or null if not yet generated.
	 */
	public function cacheKey(): ?string {
		if ($this->cacheKey !== null) {
			return $this->cacheKey;
		}

		$className      = strtolower(str_replace(['Vvveb\Plugins\\', 'Vvveb\Component\\', '\Component\\'], '', static::class));
		$this->cacheKey = str_replace('\\', '-', $className) . '.' . md5(serialize($this->options));

		return $this->cacheKey;
	}

	/**
	 * Filter and merge component options against the declared default options.
	 *
	 * @param array<array-key, mixed> &$options Raw options to filter (may contain _hash).
	 *
	 * @return array<array-key, mixed> Filtered options merged with defaults.
	 */
	public function filter(array &$options): array {
		//remove fields not declared in the class
		if (isset($options['_hash'])) {
			$this->_hash = $options['_hash'];
			unset($options['_hash']);
		}

		return $options + static :: $defaultOptions;
	}

	/**
	 * Retrieve cached results for this component.
	 *
	 * @return mixed Cached data or null if not cached.
	 */
	public function results(): mixed {
		//check cache
		$cache = Cache :: getInstance();

		return $cache->get('component', $this->cacheKey);
	}
}
