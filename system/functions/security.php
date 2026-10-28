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
 * Escape a URL for HTML output.
 *
 * @param string $url URL to escape
 *
 * @return string Escaped URL
 */
function escUrl(string $url): string {
	return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * Escape an attribute value for HTML output.
 *
 * @param string $attr Attribute value to escape
 *
 * @return string Escaped attribute value
 */
function escAttr(string $attr): string {
	return htmlspecialchars($attr, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * Escape a string for HTML output.
 *
 * @param string $string String to escape
 *
 * @return string Escaped string
 */
function escHtml(string $string): string {
	return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize filename by removing dangerous characters.
 *
 * @param string $file          The filename to sanitize.
 * @param bool   $normalizePath Whether to normalize directory separators to the OS separator.
 *
 * @return string The sanitized filename.
 */
function sanitizeFileName(string $file, bool $normalizePath = true): string {
	if (!$file) {
		return $file;
	}

	//remove null bytes
	$file = str_replace(chr(0), '', $file);
	//sanitize, remove double dot .. and remove get parameters if any
	$prev = '';
	while ($prev != $file) {
		$prev = $file;
		$file = preg_replace('@\?.*$|\.{2,}|[^\w\-\./ \\\]@', '', $file);
	}
	//replace directory separators with OS specific separator
	if ($normalizePath) {
		$file = str_replace(['\\', '/'], DS, $file);
	}

	return $file;
}

/**
 * Sanitizes HTML by removing dangerous elements and attributes.
 *
 * @param mixed $string The string to sanitize. Non-string values are returned as-is.
 *
 * @return mixed The sanitized string, or the original value if not a string.
 */
function sanitizeHTML(mixed $string): mixed {
	if (!is_string($string)) {
		return $string;
	}

	// Fix &entity\n;
	//add <> before tag entities to allow regex matching
	$string = str_replace(['&lt;', '&gt;'], ['&lt;<', '>&gt;'], $string);
	$string = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $string);
	$string = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $string);
	$string = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $string);
	$string = html_entity_decode($string, ENT_COMPAT | ENT_HTML5, 'UTF-8');

	// Remove any attribute starting with "on" or xmlns
	$string = preg_replace('#(<[^>]+?[\x00-\x20"\'/])(?:on|xmlns)[^>]*+>#iu', '$1>', $string);
	$string = preg_replace('#(<\s*\w+[^>]*?[\x00-\x20"\'/])(?:on\w+|xmlns)[\x00-\x20]*=[^>]*+\z#iu', '$1>', $string);
	$string = preg_replace('#[\x00-\x20"\'/](on|xmlns)[:\w]+[\x00-\x20"\'/]*=[\x00-\x20"\'/]*([\'"]+).+?\2#iu', '', $string);

	// Remove javascript: and vbscript: protocols
	$string = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $string);
	$string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $string);
	$string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $string);

	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
	$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
	$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $string);

	// Remove namespaced elements (we do not need them)
	$string = preg_replace('#</*\w+:\w[^>]*+>#i', '', $string);

	do {
		// Remove really unwanted tags
		$old_data = $string;
		//$string   = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $string);
		$string   = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $string) ?? $string;
	} while ($old_data !== $string);

	//restore entities
	return str_replace(['&lt;<', '>&gt;'], ['&lt;', '&gt;'], $string);
}

/**
 * Removes specified HTML tags from a string.
 *
 * @param string                  $string The string to process.
 * @param array<array-key, mixed> $tags   The tag names to strip (default ['script', 'iframe', 'applet']).
 *
 * @return string The string with tags removed.
 */
function stripTags(string $string, array $tags = ['script', 'iframe', 'applet']): string {
	if ($string && $tags) {
		foreach ($tags as $tag) {
			$string = preg_replace('@<\s*' . $tag . '.*?>.*?</' . $tag . '\s*>@im', '', $string) ?? $string;
		}
	}

	return $string;
}

/**
 * Recursively strips tags from all string values in an array.
 *
 * @param array<array-key, mixed> $array The array to process.
 * @param array<array-key, mixed> $tags  The tag names to strip.
 *
 * @return array<array-key, mixed> The array with tags stripped from string values.
 */
function stripTagsArray(array $array, array $tags): array {
	if ($tags) {
		foreach ($array as $key => &$value) {
			if (is_string($value)) {
				$value = stripTags($value, $tags);
			} elseif (is_array($value)) {
				$value = stripTagsArray($value, $tags);
			}
		}
		unset($value);
	}

	return $array;
}

/**
 * Removes specified characters from a string.
 *
 * @param string                  $string The string to process.
 * @param array<array-key, mixed> $chars  The characters to remove.
 *
 * @return string The string with characters removed.
 */
function stripChars(string $string, array $chars = ['(', ')', '{', '}', ':', '<', '>']): string {
	return str_replace($chars, '', $string);
}

/**
 * Validates URL and resolves hostname to prevent SSRF attacks.
 *
 * @param string $url The URL to validate.
 *
 * @return list<string> The cURL resolve entries, or empty array for invalid URLs or private/reserved IPs.
 */
function validateUrl(string $url): array {
	$p      = parse_url($url);
	$scheme = $p['scheme'] ?? 'https';
	$port   = (string)($p['port'] ?? ($scheme === 'http' ? '80' : '443'));

	if (!$p || !in_array($scheme, ['http', 'https'], true) || !in_array($port, ['80', '443'], true) || empty($p['host'])) {
		return [];
	}

	if (($scheme === 'http' && $port !== '80') || ($scheme === 'https' && $port !== '443')) {
		return [];
	}

	$ips = gethostbynamel($p['host']) ?: [];

	$results = [];
	foreach ($ips as $ip) {
		$results[] = $p['host'] . ':' . $port . ':[' . $ip . ']';

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return []; // private / reserved / loopback / link-local
		}
	}

	return $results;
}

/**
 * Detects bot-challenge pages (Cloudflare "Just a moment", Turnstile)
 * from a response head sample. Conservative on purpose: the generic word
 * "cloudflare" alone never matches (articles about Cloudflare exist),
 * only challenge chrome does. Pure, tested.
 *
 * @param string $sample First bytes of the response body.
 *
 * @return bool True when the sample looks like a bot challenge page.
 */
function isChallengePage(string $sample): bool {
	$head = mb_substr($sample, 0, 4096);

	if (preg_match('/<title>\s*Just a moment/i', $head)) {
		return true;
	}

	return (bool)preg_match('/cf-challenge|cf_challenge|__cf_chl_|turnstile/i', $head);
}

/**
 * Encrypts a string using OpenSSL with specified cipher and digest.
 *
 * @param string      $key        The encryption key.
 * @param string      $value      The plaintext string to encrypt.
 * @param string      $cipher     The cipher method (default 'aes256').
 * @param string      $digest     The digest method for key derivation (default 'sha256').
 * @param string|null &$tag       The authentication tag output.
 * @param string      $aad        Additional authenticated data.
 * @param int         $tag_length The authentication tag length (default 16).
 *
 * @return string The base64-encoded encrypted string.
 */
function encrypt(string $key, string $value, string $cipher = 'aes256', string $digest = 'sha256', ?string &$tag = null, string $aad = '', int $tag_length = 16): string {
	$key       = openssl_digest($key, $digest, true);
	$iv_length = openssl_cipher_iv_length($cipher);
	$iv        = openssl_random_pseudo_bytes($iv_length);

	return base64_encode($iv . openssl_encrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, $tag_length));
}

/**
 * Decrypts an OpenSSL-encrypted string.
 *
 * @param string      $key    The decryption key.
 * @param string      $value  The base64-encoded encrypted string.
 * @param string      $cipher The cipher method (default 'aes256').
 * @param string      $digest The digest method for key derivation (default 'sha256').
 * @param string|null &$tag   The authentication tag output.
 * @param string      $aad    Additional authenticated data.
 *
 * @return string|false The decrypted string, or false on failure.
 */
function decrypt(string $key, string $value, string $cipher = 'aes256', string $digest = 'sha256', ?string &$tag = null, string $aad = ''): string|false {
	$result    = false;

	$key       = openssl_digest($key, $digest, true);
	$iv_length = openssl_cipher_iv_length($cipher);
	$value     = base64_decode($value);
	$iv        = substr($value, 0, $iv_length);
	$value     = substr($value, $iv_length);

	if (strlen($iv) == $iv_length) {
		$result = openssl_decrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
	}

	return $result;
}

/**
 * Prefix marking a value encrypted with encryptSecret() (versioned envelope).
 */
const SECRET_ENC_PREFIX = 'enc:v1:';

/**
 * Check if a setting key likely holds a secret.
 *
 * Mirrors the gateway `isSensitive()` heuristic (`key|secret|password|
 * signature|token|private`) plus account logins (`username|login`) so
 * flat payment settings and nested omnipay/payum credentials are covered.
 * Explicit per-plugin `SECRET_KEYS` lists (e.g. payment-stripe) take
 * precedence over this heuristic — see `encryptSettings()`.
 *
 * Public identifiers are never secrets: `publishable_*`, `public_*` and
 * `*_public_key` (Stripe publishable, Braintree public, etc.) stay plaintext
 * so they remain debuggable. Already-encrypted `enc:v1:` envelopes still
 * decrypt regardless of key name (see `decryptSettings()`).
 *
 * @param string $key Setting/parameter name.
 *
 * @return bool True when the value should be encrypted at rest.
 */
function isSensitiveSettingKey(string $key): bool {
	$lower = strtolower($key);

	if (str_starts_with($lower, 'publishable_') || str_starts_with($lower, 'public_') || str_ends_with($lower, '_public_key')) {
		return false;
	}

	//token *counts*, not token secrets (ai max_tokens, usage quotas, etc.)
	if ($lower === 'max_tokens' || str_ends_with($lower, '_tokens') || str_ends_with($lower, '_token_count') || str_ends_with($lower, '_token_limit')) {
		return false;
	}

	return (bool)preg_match('/key|secret|password|signature|token|private|username|login/i', $key);
}

/**
 * Check if a value is an encrypted secret envelope.
 *
 * @param mixed $value Value to check.
 *
 * @return bool True when the value starts with the secret prefix.
 */
function isEncryptedSecret(mixed $value): bool {
	return is_string($value) && str_starts_with($value, SECRET_ENC_PREFIX);
}

/**
 * Resolve the app secret key used for secret encryption.
 *
 * @param string|null $key Explicit key override, defaults to `app.key` config.
 *
 * @return string App key or empty string when not configured.
 */
function appSecretKey(?string $key = null): string {
	if ($key !== null && $key !== '') {
		return $key;
	}

	if (function_exists('Vvveb\\getConfig')) {
		return (string)(getConfig('app.key', '') ?? '');
	}

	return '';
}

/**
 * Derive the 32-byte raw encryption key from the app key.
 *
 * @param string|null $key Explicit app key override.
 *
 * @return string|false Raw 32-byte key or false when no app key is configured.
 */
function secretRawKey(?string $key = null): string|false {
	$appKey = appSecretKey($key);

	if ($appKey === '') {
		return false;
	}

	return hash('sha256', $appKey, true);
}

/**
 * Encrypt a secret for database storage using the install-generated app key.
 *
 * Authenticated encryption only: libsodium secretbox when available,
 * otherwise OpenSSL AES-256-GCM. The envelope is versioned (`enc:v1:` +
 * base64(`0x01|nonce|ciphertext` or `0x02|iv|tag|ciphertext`)) so ciphers
 * can rotate without breaking existing rows. Plaintext DB dumps stay opaque
 * without the filesystem key; a full server compromise (DB + `config/app.php`)
 * is still unrecoverable by design — this protects DB-only disclosure
 * (dumps, SQLi, backups, snapshots, dev clones).
 *
 * @param string      $plaintext Plaintext secret, empty string stays empty.
 * @param string|null $key       Explicit app key override.
 *
 * @return string|false Encrypted envelope, empty string passthrough, or false when no key/crypto is available.
 */
function encryptSecret(string $plaintext, ?string $key = null): string|false {
	if ($plaintext === '') {
		return '';
	}

	$raw = secretRawKey($key);

	if ($raw === false) {
		return false;
	}

	//prefer libsodium secretbox (XSalsa20-Poly1305, nonce-misuse resistant API)
	if (function_exists('sodium_crypto_secretbox')) {
		try {
			$nonce  = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$cipher = sodium_crypto_secretbox($plaintext, $nonce, $raw);

			return SECRET_ENC_PREFIX . base64_encode("\x01" . $nonce . $cipher);
		} catch (\Throwable $e) {
		}
	}

	//fallback to AES-256-GCM with random 96-bit IV and 128-bit tag
	if (function_exists('openssl_encrypt')) {
		try {
			$iv      = random_bytes(12);
			$tag     = '';
			$cipher  = openssl_encrypt($plaintext, 'aes-256-gcm', $raw, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

			if ($cipher !== false && strlen($tag) === 16) {
				return SECRET_ENC_PREFIX . base64_encode("\x02" . $iv . $tag . $cipher);
			}
		} catch (\Throwable $e) {
		}
	}

	return false;
}

/**
 * Decrypt a secret envelope created by encryptSecret().
 *
 * Non-envelopes pass through unchanged so plaintext rows written before
 * encryption migrate transparently on first save.
 *
 * @param mixed       $value Stored value (envelope or legacy plaintext).
 * @param string|null $key   Explicit app key override.
 *
 * @return mixed Decrypted string, the original value when not encrypted, or false on failure.
 */
function decryptSecret(mixed $value, ?string $key = null): mixed {
	if (!is_string($value) || !isEncryptedSecret($value)) {
		return $value;
	}

	$raw = secretRawKey($key);

	if ($raw === false) {
		return false;
	}

	$payload = base64_decode(substr($value, strlen(SECRET_ENC_PREFIX)), true);

	if ($payload === false || strlen($payload) < 2) {
		return false;
	}

	$algo = $payload[0];

	try {
		if ($algo === "\x01" && function_exists('sodium_crypto_secretbox_open')) {
			$nonce  = substr($payload, 1, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$cipher = substr($payload, 1 + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

			if (strlen($nonce) === SODIUM_CRYPTO_SECRETBOX_NONCEBYTES && $cipher !== '') {
				$plain = sodium_crypto_secretbox_open($cipher, $nonce, $raw);

				return $plain === false ? false : $plain;
			}
		}

		if ($algo === "\x02" && function_exists('openssl_decrypt')) {
			$iv      = substr($payload, 1, 12);
			$tag     = substr($payload, 13, 16);
			$cipher  = substr($payload, 29);

			if (strlen($iv) === 12 && strlen($tag) === 16 && $cipher !== '') {
				$plain = openssl_decrypt($cipher, 'aes-256-gcm', $raw, OPENSSL_RAW_DATA, $iv, $tag);

				return $plain === false ? false : $plain;
			}
		}
	} catch (\Throwable $e) {
		return false;
	}

	return false;
}

/**
 * Generates a string of random digits of specified length.
 *
 * @param int $length Number of digits to generate
 *
 * @return string Random digit string
 */
function randomDigits(int $length): string {
	$result = '';

	for ($i = 0; $i < $length; $i++) {
		$result .= random_int(0, 9);
	}

	return $result;
}

/**
 * Check php code for syntax errors, return false on errors.
 *
 * @param string $source
 *
 * @return bool
 */
function checkPhpSyntax(string $source): bool {
	try {
		$tokens = token_get_all($source, TOKEN_PARSE);
	} catch (\ParseError $e) {
		return false;
	}

	return true;
}
