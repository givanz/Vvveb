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
 * Get a setting value by namespace and key.
 *
 * @param string                              $namespace Setting namespace
 * @param string|array<array-key, mixed>|null $key       Setting key or array of keys
 * @param mixed                               $default   Default value if key is not found
 * @param int                                 $site_id   Site ID
 *
 * @return mixed Setting value
 */
function getSetting(string $namespace, string|array|null $key = null, mixed $default = null, int $site_id = SITE_ID): mixed {
	return System\Setting::getInstance()->get($namespace, $key, $default, $site_id);
}

/**
 * Set a setting value.
 *
 * @param string      $namespace Setting namespace
 * @param string|null $key       Setting key
 * @param mixed       $value     Value to set
 * @param int         $site_id   Site ID
 *
 * @return mixed Result of the set operation
 */
function setSetting(string $namespace, ?string $key = null, mixed $value = null, int $site_id = SITE_ID): mixed {
	return System\Setting::getInstance()->set($namespace, $key, $value, $site_id);
}

/**
 * Delete a setting.
 *
 * @param string      $namespace Setting namespace
 * @param string|null $key       Setting key
 * @param int         $site_id   Site ID
 *
 * @return mixed Result of the delete operation
 */
function deleteSetting(string $namespace, ?string $key = null, int $site_id = SITE_ID): mixed {
	return System\Setting::getInstance()->delete($namespace, $key, $site_id);
}

/**
 * Set multiple settings at once.
 *
 * @param string                  $namespace Setting namespace
 * @param array<array-key, mixed> $settings  Array of key-value pairs to set
 * @param int                     $site_id   Site ID
 *
 * @return mixed Result of the set operation
 */
function setMultiSetting(string $namespace, array $settings, int $site_id = SITE_ID): mixed {
	return System\Setting::getInstance()->setMulti($namespace, $settings, $site_id);
}

/**
 * Encrypt sensitive values in a settings array for database storage.
 *
 * Walks flat settings and nested method arrays (omnipay/payum store one
 * JSON row per method) and encrypts every non-empty scalar whose key looks
 * secret. Pass an explicit allowlist to override the `isSensitiveSettingKey()`
 * heuristic, pass an empty array to encrypt nothing. Already-encrypted values
 * are left untouched so saves stay idempotent. Encryption failures fall back
 * to plaintext so a missing `app.key` never loses data.
 *
 * @param array<string, mixed>    $settings   Settings to encrypt.
 * @param array<int, string>|null $secretKeys Explicit secret keys, null for auto-detect.
 * @param string|null             $key        Explicit app key override.
 *
 * @return array<string, mixed> Settings with secrets encrypted.
 */
function encryptSettings(array $settings, ?array $secretKeys = null, ?string $key = null): array {
	foreach ($settings as $name => &$value) {
		if (is_array($value)) {
			$value = encryptSettings($value, $secretKeys, $key);
			continue;
		}

		if (!is_string($value) || $value === '' || isEncryptedSecret($value)) {
			continue;
		}

		$secret = (($secretKeys !== null) ? in_array($name, $secretKeys, true) : isSensitiveSettingKey($name));

		if ($secret) {
			$encrypted = encryptSecret($value, $key);

			if (is_string($encrypted) && $encrypted !== '') {
				$value = $encrypted;
			}
		}
	}
	unset($value);

	return $settings;
}

/**
 * Decrypt any secret envelopes in settings data.
 *
 * Key-agnostic: every `enc:v1:` string is decrypted wherever it sits, so
 * renames and heuristic changes stay backward compatible. Legacy plaintext
 * passes through, undecryptable envelopes (missing/rotated key) are kept as
 * stored so admins can re-save instead of losing data.
 *
 * @param mixed       $data Settings array or scalar from storage.
 * @param string|null $key  Explicit app key override.
 *
 * @return mixed Data with secrets decrypted.
 */
function decryptSettings(mixed $data, ?string $key = null): mixed {
	if (is_array($data)) {
		foreach ($data as &$value) {
			$value = decryptSettings($value, $key);
		}
		unset($value);

		return $data;
	}

	if (isEncryptedSecret($data)) {
		$plain = decryptSecret($data, $key);

		return $plain === false ? $data : $plain;
	}

	return $data;
}

/**
 * Keep stored secrets when the admin leaves a password field blank.
 *
 * Lets secret inputs render empty (instead of echoing plaintext into HTML)
 * without wiping credentials on save.
 *
 * @param array<string, mixed>    $posted     Submitted settings (possibly nested methods).
 * @param array<string, mixed>    $stored     Currently stored settings (decrypted).
 * @param array<int, string>|null $secretKeys Explicit secret keys, null for auto-detect.
 *
 * @return array<string, mixed> Posted settings with blanks filled from storage.
 */
function preserveSecretSettings(array $posted, array $stored, ?array $secretKeys = null): array {
	foreach ($posted as $name => &$value) {
		if (is_array($value)) {
			$storedChild = (isset($stored[$name]) && is_array($stored[$name])) ? $stored[$name] : [];
			$value       = preserveSecretSettings($value, $storedChild, $secretKeys);
			continue;
		}

		$secret = ($secretKeys !== null) ? in_array($name, $secretKeys, true) : isSensitiveSettingKey($name);

		if ($secret && ($value === '' || $value === null) && isset($stored[$name]) && is_string($stored[$name]) && $stored[$name] !== '') {
			$value = $stored[$name];
		}
	}
	unset($value);

	return $posted;
}

/**
 * Get a single secret setting, decrypted.
 *
 * Thin wrapper over getSetting() for intent-clarity; reads already decrypt
 * transparently via System\Setting.
 *
 * @param string      $namespace Setting namespace
 * @param string|null $key       Setting key
 * @param mixed       $default   Default when missing/undecryptable
 * @param int         $site_id   Site ID
 *
 * @return mixed Decrypted value.
 */
function getSecretSetting(string $namespace, ?string $key = null, mixed $default = null, int $site_id = SITE_ID): mixed {
	return getSetting($namespace, $key, $default, $site_id);
}

/**
 * Set a single secret setting, encrypted at rest.
 *
 * @param string      $namespace Setting namespace
 * @param string|null $key       Setting key
 * @param mixed       $value     Value to encrypt and store
 * @param int         $site_id   Site ID
 *
 * @return mixed Result of the set operation
 */
function setSecretSetting(string $namespace, ?string $key = null, mixed $value = null, int $site_id = SITE_ID): mixed {
	if (is_string($value) && $value !== '' && !isEncryptedSecret($value)) {
		$encrypted = encryptSecret($value);

		if (is_string($encrypted) && $encrypted !== '') {
			$value = $encrypted;
		}
	}

	if (is_array($value)) {
		$value = encryptSettings($value);
	}

	return System\Setting::getInstance()->set($namespace, $key, $value, $site_id);
}

/**
 * Set multiple settings, encrypting secrets at rest.
 *
 * Drop-in for setMultiSetting() for payment/plugin settings. Uses the
 * auto-detect heuristic unless $secretKeys is given.
 *
 * @param string                  $namespace  Setting namespace
 * @param array<string, mixed>    $settings   Settings to store
 * @param array<int, string>|null $secretKeys Explicit secret keys, null for auto-detect
 * @param int                     $site_id    Site ID
 *
 * @return mixed Result of the set operation
 */
function setMultiSecretSetting(string $namespace, array $settings, ?array $secretKeys = null, int $site_id = SITE_ID): mixed {
	return System\Setting::getInstance()->setMulti($namespace, encryptSettings($settings, $secretKeys), $site_id);
}

/**
 * Masked hint for a configured secret without exposing it.
 *
 * For admin placeholders like `Leave blank to keep ••••abcd`. Returns an
 * empty string when nothing is configured. Only the last 4 characters ever
 * leave the server — never use this for runtime auth, only for display.
 *
 * @param string $namespace Setting namespace
 * @param string $key       Setting key
 * @param int    $site_id   Site ID
 *
 * @return string Masked hint (`••••last4`) or empty string.
 */
function secretHint(string $namespace, string $key, int $site_id = SITE_ID): string {
	$value = getSetting($namespace, $key, null, $site_id);

	if (!is_string($value) || $value === '') {
		return '';
	}

	//never reveal more than the tail for display purposes
	return '••••' . substr($value, -4);
}

/**
 * Blank sensitive values for admin display (`methods_params` JSON, etc.).
 *
 * Prevents decrypted secrets from being embedded in page HTML/JSON while
 * keeping saves safe: blanked fields are restored from storage by
 * `preserveSecretSettings()` on submit.
 *
 * @param array<string, mixed>    $data       Settings or method rows.
 * @param array<int, string>|null $secretKeys Explicit secret keys, null for auto-detect.
 *
 * @return array<string, mixed> Copy with secrets replaced by empty strings.
 */
function scrubSensitiveSettings(array $data, ?array $secretKeys = null): array {
	foreach ($data as $name => &$value) {
		if (is_array($value)) {
			$value = scrubSensitiveSettings($value, $secretKeys);
			continue;
		}

		if (!is_string($value) || $value === '') {
			continue;
		}

		$secret = ($secretKeys !== null) ? in_array($name, $secretKeys, true) : isSensitiveSettingKey($name);

		if ($secret) {
			$value = '';
		}
	}
	unset($value);

	return $data;
}

/*
 * File-backed global application config (System\Config), persisted to
 * `config/$namespace.php` files at runtime — as opposed to the DB-backed,
 * per-site settings above. Admins edit settings; code edits config.
 */

/**
 * Get a configuration value or the Config instance.
 *
 * @param string|null $key     Config key to retrieve, or null for the Config instance
 * @param mixed       $default Default value if key is not found
 *
 * @return mixed Configuration value or Config instance
 */
function config(?string $key = null, mixed $default = null): mixed {
	if (is_null($key)) {
		return System\Config::getInstance();
	}

	return System\Config::getInstance()->get($key, $default);
}

/**
 * Get a configuration value.
 *
 * @param string|null $key     Config key to retrieve, or null for all config
 * @param mixed       $default Default value if key is not found
 *
 * @return mixed Configuration value
 */
function getConfig(?string $key = null, mixed $default = null): mixed {
	return System\Config::getInstance()->get($key, $default);
}

/**
 * Set a configuration value.
 *
 * @param string $key   Config key to set
 * @param mixed  $value Value to set
 *
 * @return bool True on success
 */
function setConfig(string $key, mixed $value = null): bool {
	return System\Config::getInstance()->set($key, $value);
}

/**
 * Remove a configuration value.
 *
 * @param string $key Config key to remove
 *
 * @return mixed Result of the unset operation
 */
function unsetConfig(string $key): mixed {
	return System\Config::getInstance()->unset($key);
}

/**
 * Get or set session data.
 *
 * When a string is passed, retrieves the session value for that key. When an array is passed, sets all key-value pairs.
 *
 * @param string|array<array-key, mixed> $data    Session key to get, or array of key-value pairs to set
 * @param mixed                          $default Default value to return if key is not found
 *
 * @return mixed Session value when getting, or default value
 */
function session(string|array $data, mixed $default = null): mixed {
	$session = System\Session::getInstance();

	if (!$session) {
		return $default;
	}

	if (is_array($data)) {
		foreach ($data as $key => $value) {
			$session->set($key, $value);
		}
	} else {
		$value = $session->get($data);

		if ($value === null) {
			return $default;
		}

		return $value;
	}

	return $default;
}

/**
 * Get an environment variable value.
 *
 * @param string $key     Environment variable name
 * @param mixed  $default Default value if variable is not set
 *
 * @return mixed Environment variable value or default
 */
function env(string $key, mixed $default = null): mixed {
	$env = getenv($key);

	return $env === false ? $default : $env;
}
