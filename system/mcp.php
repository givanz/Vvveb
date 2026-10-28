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

namespace Vvveb\System;

use Vvveb\System\Extensions\Extensions;

class MCP {
	/**
	 * Check whether native shell execution is available.
	 * Inspects disable_functions and function_exists for shell_exec/proc_open/exec.
	 */
	public static function isShellAvailable(): bool {
		$disabled = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
		$disabled = array_map('strtolower', $disabled);
		if (in_array('shell_exec', $disabled, true) && in_array('proc_open', $disabled, true) && in_array('exec', $disabled, true)) {
			return false;
		}
		// proc_open is the primary mechanism used by toolBash
		if (!function_exists('proc_open') || !function_exists('shell_exec')) {
			return false;
		}

		return !(in_array('proc_open', $disabled, true) || in_array('shell_exec', $disabled, true));
	}

	/**
	 * Check whether native shell execution is available.
	 * Inspects disable_functions and function_exists for shell_exec/proc_open/exec.
	 *
	 * @return list<string>
	 */
	public static function disabledTools(): array {
		$tools = [];
		if (self::isShellAvailable()) {
			array_push($tools, 'glob', 'grep', 'bash_sub');
			if (PHP_OS_FAMILY == 'Windows' || DIRECTORY_SEPARATOR === '\\') {
				//no bash on windows
				$tools[] = 'bash';
			} else {
				//no powershell on linux
				$tools[] = 'powershell';
			}
		} else {
			$tools = ['bash', 'powershell'];
		}

		return $tools;
	}

	/**
	 * Whether mutating tool operations are currently allowed.
	 */
	public static function writesEnabled(): bool {
		try {
			return \Vvveb\config('mcp.tools.db_write', false) === true;
		} catch (\Throwable $e) {
			return false;
		}
	}

	public const WRITES_DISABLED_NOTE = ' Writes are disabled (enable mcp.tools.db_write to allow them).';

	/**
	 * Append the writes-disabled note to descriptions of tools that can
	 * mutate (readOnlyHint === false) when writes are off. No-op when
	 * writes are enabled, so enabled agents never see the warning.
	 *
	 * @param array<array-key, mixed> $tools
	 *
	 * @return array<array-key, mixed>
	 */
	public static function annotateTools(array $tools): array {
		if (self::writesEnabled()) {
			return $tools;
		}

		foreach ($tools as &$tool) {
			if (($tool['annotations']['readOnlyHint'] ?? null) === false && isset($tool['description'])) {
				$tool['description'] .= self::WRITES_DISABLED_NOTE;
			}
		}
		unset($tool);

		return $tools;
	}

	/**
	 * Get system prompt
	 *
	 * $format api, mcp defaults to mcp
	 *
	 * @param int                     $roleId
	 * @param array<array-key, mixed> $append
	 *
	 * @return string System prompt.
	 */
	public static function systemPrompt(int $roleId, array $append = []): string {
		$toolsDesc = [
			'read'               => 'Read file contents',
			'write'              => 'Create or overwrite files',
			'grep'               => 'Search file contents recursively with a regular expression',
			'glob'               => 'Find files by matching a glob pattern',
			'crud'               => 'Add/edit/delete CMS content records (posts, products, ...) via data models',
			'crud_models'        => 'Discover data model names and exact IN parameters per crud operation (call before writing data calls); params flag required vs omittable and record columns',
			'media'              => 'Media library files and metadata (list/get/save/delete/upload/fetch/mkdir/scan)',
			'admin'              => 'Manage themes, plugins, cron jobs and caches',
			'powershell'         => 'Execute a PowerShell command inside the site root directory',
			'database_query'     => 'SQL against the site database (SELECT/SHOW/DESCRIBE preferred)',
			'bash'               => 'Execute bash commands (ls, grep, find, etc.)',
			'bash_sub'           => 'Pure-PHP sandboxed bash fallback when native shell functions are disabled',
			'str_replace_editor' => 'Viewing, creating and editing files',
			//'page'               => 'Run javascript in page builder edited page',
			//'ui'                 => 'Run javascript in user window'
		];

		$disable   = self::disabledTools();
		$toolsText =  '';
		foreach ($toolsDesc as $name => $desc) {
			if (in_array($name, $disable)) {
				continue;
			}
			$toolsText .= "- $name: $desc\n";
		}

		$prompt =  'You are an expert assistant operating inside a CMS'
. (($append['instructions'] ?? '') ? ', ' . $append['instructions'] : '')

. '.

Available tools:
'
. $toolsText
. (($append['tools'] ?? '') ? "\n" . $append['tools'] : '')

. '
In addition to the tools above, you may have access to other custom tools depending on the project.

Guidelines:
- Use crud for editing or adding content
- Use admin for managing themes, plugins, media or cron
- Use media for image and media upload and insert
- Use str_replace_editor view command to examine files instead of cat or sed.
- Use str_replace_editor edit command for precise changes (edits[].oldText must match exactly)
- When changing multiple separate locations in one file, use one edit call with multiple entries in edits[] instead of multiple edit calls
- Each edits[].oldText is matched against the original file, not after earlier edits are applied. Do not emit overlapping or nested edits. Merge nearby changes into one edit.
- Keep edits[].oldText as small as possible while still being unique in the file. Do not pad with large unchanged regions.
- Don\'t save page without asking.
- Use dedicated tools before falling back to general purpose.
- Don\'t execute destructive or bulk operations without user confirmation.
- Be concise in your responses'
. (($append['guidelines'] ?? '') ? "\n" . $append['guidelines'] : '') . "\n";

		$agents  = @file_get_contents(DIR_ROOT . 'AGENTS.md') ?: '';
		$context = "\n<project_context>\nProject-specific instructions and guidelines:\n";
		$context .= "<project_instructions path=\"/AGENTS.md\">\n{$agents}\n</project_instructions>";

		$skills     = self::skills();
		$skillsText = '';
		if ($skills) {
			$skillsText = "The following skills provide specialized instructions for specific tasks.
Use the read tool to load a skill's file when the task matches its description.
When a skill file references a relative path, resolve it against the skill directory (parent of SKILL.md / dirname of the path) and use that absolute path in tool commands.\n";
			$skillsText = "<available_skills>\n";
			foreach ($skills as $file => $desc) {
				$skillsText .= "\t<skill>\n";
				$skillsText .= "\t\t<name>{$desc['name']}</name>\n";
				$skillsText .= "\t\t<description>{$desc['description']}</description>\n";
				$skillsText .= "\t\t<location>{$desc['location']}</location>\n";
				$skillsText .= "\t</skill>\n";
			}
			$skillsText .= "</available_skills>\n";
		}

		$context .= "</project_context>\n";

		$prompt .= $context . $skillsText;
		$prompt .= 'Current working directory: ' . DIR_ROOT;

		return $prompt;
	}

	/**
	 * Get all available tools .
	 *
	 * $format api, mcp defaults to mcp
	 *
	 * @param ?int    $roleId
	 * @param ?string $format
	 *
	 * @return array<array-key, mixed> List of tools.
	 */
	public static function tools(?int $roleId = null, ?string $format = null): array {
		$tools = self::annotateTools(include DIR_ROOT . 'mcp' . DS . 'tools.php');

		$disable = self::disabledTools();

		if ($format === 'api') {
			$api = [];
			foreach ($tools as $tool) {
				if (in_array($tool['name'], $disable)) {
					continue;
				}
				$parameters = [];

				if (isset($tool['inputSchema']['properties'])) {
					$parameters = [
						'type'       => 'object',
						'properties' => $tool['inputSchema']['properties'],
						'required'   => $tool['inputSchema']['required'] ?? []];
				}

				$api[] = [
					'type'     => 'function',
					'function' => [
						'name'        => $tool['name'],
						'description' => $tool['description'],
						'parameters'  => $parameters
					]
				];
			}

			return $api;
		}

		return $tools;
	}

	/** Get all available tools from the database.
	 *
	 * @return array<array-key, mixed> list of tools
	 */
	public static function skills(): array {
		$path   = DIR_ROOT . 'skills';
		$files  = scandir($path);
		$skills = [];
		foreach ($files as $file) {
			if ($file[0] === '.') {
				continue;
			}
			$location = DS . 'skills' . DS . $file . DS . 'SKILL.md';

			$markdown = file_get_contents(DIR_ROOT . $location, false, null, 0, 500);
			$params   = [];
			if ($markdown && strncmp($markdown, '---', 3) === 0) {
				if (preg_match('@^\s*---.+?---\s*@ms', $markdown, $matches)) {
					$params = Extensions::getParams($matches[0]);
				}
			}

			$skills[$file] = ['name' => $params['name'] ?? $file, 'description' => $params['description'] ?? $file, 'location' => $location];
		}

		return $skills;
	}
}
