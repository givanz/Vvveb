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

namespace Vvveb;

/**
 * Returns site settings with caching and optional language filtering.
 *
 * @param int       $site_id     The site ID (default SITE_ID).
 * @param int|false $language_id The language ID to filter by, or false to skip filtering.
 *
 * @return array<array-key, mixed> The site settings array.
 */
function siteSettings(int $site_id = SITE_ID, int|false $language_id = false): array {
	$cache     = System\Cache::getInstance();
	$site      = $cache->cache(APP, 'site.' . $site_id, function() use ($site_id) {
		$siteSql             = new Sql\SiteSQL();
		$site                = $siteSql->get(['site_id' => $site_id]);

		if ($site && isset($site['settings'])) {
			$settings = json_decode($site['settings'], true);
			$siteData = $siteSql->getSiteData(['site' => $settings]);

			foreach (['favicon', 'logo', 'logo-sticky', 'logo-dark', 'logo-dark-sticky', 'webbanner'] as $img) {
				if (isset($settings[$img])) {
					$settings["$img-src"] = $settings[$img];
					$settings[$img]       = System\Images::image($settings[$img], '');
				}
			}

			return $settings + $siteData;
		}

		return [];
	}, 259200);

	if ($language_id && $site && isset($site['description']) && is_array($site['description'])) {
		$site['description'] = $site['description'][$language_id] ?? $site['description'][1] ?? '';
	}

	return $site;
}

/**
 * Send email using template.
 *
 * @param string|array<array-key, mixed> $to
 * @param string                         $subject
 * @param string|array<array-key, mixed> $template
 * @param array<array-key, mixed>        $data
 * @param array<array-key, mixed>        $config
 * @param string|null                    $app
 * @param int                            $site_id
 * @param array<array-key, mixed>        $additionalOptions
 *
 * @return bool
 */
function email(string|array $to, string $subject, string|array $template, array $data = [], array $config = [], ?string $app = null, int $site_id = SITE_ID, array $additionalOptions = []): bool {
	$email = System\Email::getInstance($additionalOptions);

	if (is_array($template)) {
		$html = $template['html'] ?? '';
		$txt  = $template['txt'] ?? '';
	} else {
		$htmlView  = new System\Core\View($app);
		$htmlView->setTheme();
		$htmlView->set($data);
		//get email html template
		$htmlView->template("email/$template.html");
		$html = $htmlView->render(true, false, true);

		//get email text template
		$txtView  = new System\Core\View($app);
		$txtView->setTheme();
		$txtView->set($data);
		$txtView->template("email/$template.txt.html");
		$txt = $txtView->render(true, false, true);
		$txt = htmlToText($txt);
	}

	//get site contact email for sender and reply to
	$site   = siteSettings($site_id, session('language_id') ?? 1);
	$sender = $config['sender'] ?? (is_array($site['description'] ?? null) ? ($site['description']['title'] ?? '') : ($site['description'] ?? ''));
	$from   = $config['from'] ?? $site['contact-email'] ?? '';
	$reply  = $config['reply'] ?? $site['contact-email'] ?? '';

	$email->setHtml($html);
	$email->setText($txt);
	$email->setTo($to);
	$email->setSender($sender);

	if ($from) {
		$email->setFrom($from);
	}

	if ($reply) {
		$email->setReplyTo($reply);
	}

	$email->setSubject($subject);

	return $email->send();
}

/**
 * Glob with brace expansion support (fallback for systems without GLOB_BRACE).
 *
 * @param string                  $path     Directory path
 * @param array<array-key, mixed> $glob     Array of glob patterns
 * @param string                  $filename Optional filename suffix
 *
 * @return array<array-key, mixed> Array of matching file paths
 */
function globBrace(string $path, array $glob, string $filename = ''): array {
	$files = [];

	if (defined('GLOB_BRACE')) {
		$path .= '{' . implode(',', $glob) . '}' . $filename;
		$files = glob($path, GLOB_BRACE) ?: [];
	} else {
		foreach ($glob as $pattern) {
			//$path already ends with a directory separator (all callers append it)
			$files = array_merge($files, glob($path . $pattern . $filename) ?: []);
		}
	}

	return $files;
}

/**
 * Checks if the current request is over HTTPS.
 *
 * @return bool True if request is secure, false otherwise
 */
function isSecure(): bool {
	return
	(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
	|| ($_SERVER['SERVER_PORT'] ?? 80) == 443;
}

