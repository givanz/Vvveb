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
 * Reads the last N lines from a file.
 *
 * @param string $filename The path to the file.
 * @param int    $lines    The number of lines to read from the end.
 *
 * @return string|false The last lines of the file, or false on failure.
 */
function tail(string $filename, int $lines = 1000): string|false {
	$file = @fopen($filename, 'r');

	if ($file === false) {
		return false;
	}

	$buffer = 4096;
	$output = '';
	$chunk  = '';

	@fseek($file, -1, SEEK_END);

	if (fread($file, 1) != "\n") {
		$lines--;
	}

	while (ftell($file) > 0 && $lines >= 0) {
		$seek = min(ftell($file), $buffer);
		fseek($file, -$seek, SEEK_CUR);
		$output = ($chunk = fread($file, $seek)) . $output;
		//fseek($file, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		fseek($file, strlen($chunk), SEEK_CUR);
		$lines -= substr_count($chunk, "\n");
	}

	while ($lines++ < 0) {
		$output = substr($output, strpos($output, "\n") + 1);
	}

	fclose($file);

	return trim($output);
}

/**
 * Recursively removes a directory.
 *
 * @param string                  $src        The directory path to remove.
 * @param array<array-key, mixed> $skip       Files or directories (full paths) to skip.
 * @param mixed                   $keepParent Don't delete parent folder
 * @param callable                $condition  Provide callback function to condition file delete
 *
 * @return bool True on success, false on failure.
 */
function rrmdir(string $src, array $skip = [], $keepParent = false, ?callable $condition = null): bool {
	//never follow a symlink, otherwise a link inside the removed tree can
	//point outside it and its target contents would be deleted
	if (is_link($src)) {
		if (in_array($src, $skip)) {
			return true;
		}

		return @unlink($src);
	}

	if (!is_dir($src)) {
		return false;
	}

	$dir    = @opendir($src);
	$result = true;

	if ($dir) {
		while (false !== ($file = readdir($dir))) {
			$full = $src . DS . $file;

			if (($file != '.') &&
				($file != '..') &&
				(!in_array($full, $skip))) {
				if (is_link($full)) {
					if ($condition === null || $condition($full)) {
						$result = @unlink($full);
					}
				} elseif (is_dir($full)) {
					$result = rrmdir($full, $skip, false);
				} else {
					if ($condition === null || $condition($full)) {
						$result = @unlink($full);
					}
				}

				if (!$result) {
					break;
				}
			}
		}

		closedir($dir);

		if ($result && !$keepParent) {
			$result = @rmdir($src);
		}
	}

	return $result;
}

/**
 * Recursively copies a directory.
 *
 * @param string                  $src       The source directory path.
 * @param string                  $dst       The destination directory path.
 * @param array<array-key, mixed> $skip      Files or directories to skip.
 * @param bool                    $overwrite Whether to overwrite existing destination (default true).
 *
 * @return bool True on success, false on failure.
 */
function rcopy(string $src, string $dst, array $skip = [], bool $overwrite = true): bool {
	$result = true;

	//copy symlinks as links instead of following them outside the source tree
	if (is_link($src)) {
		$target = @readlink($src);

		if ($target === false) {
			return false;
		}

		@unlink($dst);

		return @symlink($target, $dst);
	}

	if (is_dir($src)) {
		if (!file_exists($dst)) {
			$result = @mkdir($dst);

			if (!$result) {
				return false;
			}
		}

		@chmod($dst, 0755);

		$files = scandir($src);

		foreach ($files as $file) {
			$full = $src . DS . $file;

			if ($file != '.' &&
				$file != '..' &&
				!in_array($file, $skip)) {
				//is_link() first in rcopy() so links are recreated, not followed
				if (is_dir($full) || is_link($full)) {
					$result = rcopy($full, $dst . DS . $file);
				} else {
					$result = @copy($full, $dst . DS . $file);

					if (!$result) {
						if (@chmod($dst . DS . $file, 0644)) {
							$result = @copy($full, $dst . DS . $file);
						}
					}
				}

				if (!$result) {
					return false;
				}
			}
		}
	} else {
		if (file_exists($src)) {
			$result = @copy($src, $dst);

			if (!$result) {
				if (@chmod($dst, 0644)) {
					$result = copy($src, $dst);
				}
			}

			if (!$result) {
				return false;
			}
		}
	}

	return $result;
}

/**
 * Recursively renames/moves a directory.
 *
 * @param string                  $src  The source directory path.
 * @param string                  $dst  The destination directory path.
 * @param array<array-key, mixed> $skip Files or directories to skip.
 *
 * @return void
 */
function rrename(string $src, string $dst, array $skip = []): void {
	if (file_exists($dst)) {
		rrmdir($dst);
	}

	if (is_dir($src)) {
		if (!is_dir($dst)) {
			mkdir($dst, 0755, true);
		}
		$files = scandir($src);

		foreach ($files as $file) {
			if ($file != '.' &&
				$file != '..' &&
				!in_array($file, $skip)) {
				rename("$src/$file", "$dst/$file");
			}
		}
	} else {
		if (file_exists($src)) {
			rename($src, $dst);
		}
	}
}

/**
 * Extracts a zip archive.
 *
 * @param string $file The path to the zip file to extract.
 *
 * @return bool True on success, false on failure.
 */
function unzip(string $file): bool {
	// get the absolute path to $file
	$realPath = realpath($file);

	if ($realPath === false) {
		return false;
	}

	$path = pathinfo($realPath, PATHINFO_DIRNAME);

	$zip = new \ZipArchive();
	$res = $zip->open($file);

	if ($res === true) {
		$zip->extractTo($path);
		$zip->close();

		return $result;
	}

	return false;
}

/**
 * Format byte count to human-readable string (KB, MB, etc.).
 *
 * @param int $bytes The number of bytes.
 *
 * @return string The formatted string with appropriate unit.
 */
function formatBytes(int $bytes): string {
	$i     = 0;
	$units = ['', 'K', 'M', 'G', 'T', 'P', 'E'];
	$bytes = abs($bytes);

	while (($remainder = ($bytes / 1024)) > 1 && $i < count($units) - 1) {
		$bytes = $remainder;
		$i++;
	}

	return round($bytes, 2) . ' ' . $units[$i] . 'B';
}

/**
 * Parse quantity string with K/M/G suffix to integer bytes.
 *
 * @param string $string The quantity string to parse (e.g. "10M", "512K").
 *
 * @return int The parsed integer value in bytes.
 */
function parseQuantity(string $string): int {
	if (function_exists('\ini_parse_quantity')) {
		return \ini_parse_quantity($string);
	}

	$string     = trim($string);

	if ($string === '') {
		return 0;
	}

	$multiplier = lcfirst($string[strlen($string) - 1]);
	$number     = (int)$string;

	return match ($multiplier) {
		'k'     => $number * 1024,
		'm'     => $number * 1048576,
		'g'     => $number * 1073741824,
		default => $number,
	};
}

/**
 * Returns a human-readable error message for a PHP file upload error code.
 *
 * @param int $errorCode One of the UPLOAD_ERR_* constants
 *
 * @return string The translated error message
 */
function fileUploadErrMessage(int $errorCode): string {
	return match ($errorCode) {
		//UPLOAD_ERR_OK => __('Upload ok'),
		UPLOAD_ERR_NO_FILE                        => __('No file sent'),
		UPLOAD_ERR_PARTIAL                        => __('The uploaded file was only partially uploaded'),
		UPLOAD_ERR_NO_TMP_DIR                     => __('Missing a temporary folder'),
		UPLOAD_ERR_CANT_WRITE                     => __('Failed to write file to disk'),
		UPLOAD_ERR_EXTENSION                      => __('A PHP extension stopped the file upload'),
		UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('Exceeded filesize limit'),
		default                                   => __('Unknown errors'),
	};
}
