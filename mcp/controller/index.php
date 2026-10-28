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

use Throwable;
use function Vvveb\config;
use function Vvveb\humanReadable;
use function Vvveb\rrmdir;
use function Vvveb\sanitizeFileName;
use function Vvveb\slugify;
use Vvveb\System\Cache;
use Vvveb\System\Db;
use Vvveb\System\Event;
use Vvveb\System\MCP;
use Vvveb\System\Session;
use Vvveb\System\Sites;
use Vvveb\System\Sqlp\Sqlp;
use Vvveb\System\Traits\Media as MediaLibraryTrait;
use function Vvveb\url;

// Routine-failure taxonomy (must load before the editor so FsError can extend it)
require_once __DIR__ . '/../tool_error.php';
// Integrated str_replace_editor filesystem tool
require_once __DIR__ . '/../str_replace_editor.php';

#[\AllowDynamicProperties]
class Index extends Base {
	use MediaLibraryTrait;

	/** @var string Media library root for the file cores below. */
	protected string $dirMedia = DIR_MEDIA;

	/** @var string MCP protocol version */
	private string $version = '2025-11-25';

	/** @var int Maximum bytes returned for a single file read */
	private const READ_MAX_BYTES = 5242880; // 5MB

	/** @var int Maximum bytes written in a single write call */
	private const WRITE_MAX_BYTES = 4194304; // 4MB

	/** @var int Maximum combined stdout+stderr bytes returned by bash */
	private const BASH_MAX_OUTPUT = 262144; // 256KB

	/** @var int Maximum decoded bytes accepted by media upload (also capped by the server upload_max_filesize) */
	private const MEDIA_UPLOAD_MAX_BYTES = 4194304; // 4MB

	/** Directories never traversed by grep/glob */
	private const SKIP_DIRS = ['.git', '.svn', 'node_modules'];

	/**
	 * Map of coding harness tool names to controller methods.
	 * Tools defined in tools.php that are not listed here fall back to
	 * their execute_callback (if callable).
	 *
	 * @var array<string, string>
	 */
	private array $toolHandlers = [
		'crud'               => 'toolCrud',
		'crud_models'        => 'toolCrudModels',
		'admin'              => 'toolAdmin',
		'media'              => 'toolMedia',
		'read'               => 'toolRead', //fallback for str_replace_editor
		'write'              => 'toolWrite', //fallback for str_replace_editor
		'glob'               => 'toolGlob', //fallback for disabled bash
		'grep'               => 'toolGrep', //fallback for disabled bash
		'bash'               => 'toolBash', //if shell exec available
		'bash_sub'           => 'toolBashSub', //falback if shell exec not available
		'powershell'         => 'toolPowershell', //only available on windows
		'str_replace_editor' => 'toolStrReplaceEditor',
		'database_query'     => 'toolDatabaseRunQuery',
	];

	/**
	 * Initializes session from MCP session header.
	 */
	public function __construct() {
		$headers   = getallheaders();
		$sessionId = $headers['Mcp-Session-Id'] ?? $_SERVER['Mcp-Session-Id'] ?? null;

		if ($sessionId) {
			$this->session  = Session::getInstance(['id' => $sessionId, 'use_cookies' => 0]);
		}

		$disable            = MCP :: disabledTools();
		$this->toolHandlers = array_diff_key($this->toolHandlers, array_flip($disable));
	}

	/**
	 * List available tools.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return array{tools: mixed}
	 */
	public function tools_list(array $params): array {
		$cache = Cache::getInstance();
		$self  = $this;
		//version the key on the registry sources: a schema or handler deploy
		//must surface immediately instead of lingering for the 3-day TTL. A
		//stale tools-list previously hid a newly added tool for days while
		//the files were already correct.
		$toolsKey = 'tools-list-' . md5((string)@filemtime(__DIR__ . '/../tools.php') . "\n" . implode(',', array_keys($this->toolHandlers)) . "\n" . (config('mcp.tools.db_write', false) === true ? 'w1' : 'w0'));
		$tools    = $cache->cache(APP, $toolsKey, function() use ($self) {
			$tools = \Vvveb\System\MCP::annotateTools(include (__DIR__ . '/../tools.php'));

			$tools = array_values(array_filter(
				$tools,
				fn ($tool) => isset($this->toolHandlers[$tool['name'] ?? ''])
			));

			//hide the coding harness tools entirely when disabled
			if (config('mcp.tools.enabled', true) !== true) {
				$tools = array_values(array_filter(
					$tools,
					fn ($tool) => !isset($this->toolHandlers[$tool['name'] ?? ''])
				));
			}

			[$tools] = Event :: trigger('MCP', 'tools/list', $tools);

			return $tools;
		}, 259200);

		return ['tools' => $tools];
	}

	/**
	 * Execute a tool call.
	 *
	 * Dispatches to the matching coding harness tool handler (read, write,
	 * bash, glob, grep, database_run_query, str_replace_editor ...) or falls back to the
	 * tool's execute_callback when one is registered. Results are wrapped in
	 * an MCP tool result envelope; thrown errors are converted to isError
	 * results instead of protocol errors.
	 *
	 * @param string               $method JSON-RPC method (always "tools/call")
	 * @param array<string, mixed> $params {name: string, arguments: array}
	 *
	 * @return array{content: array<int, mixed>, isError: bool, structuredContent?: mixed}
	 */
	public function tools_call(string $method, array $params): array {
		$name      = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if ($name === '') {
			return $this->errorResult('Missing tool name');
		}

		//master switch for the coding harness tool set
		if (config('mcp.tools.enabled', true) !== true) {
			return $this->errorResult('Coding tools are disabled (config mcp.tools.enabled)');
		}

		$arguments = is_array($arguments) ? $arguments : [];

		try {
			//resolve tool definition from the registry
			$tool = null;

			foreach ($this->tools_list([])['tools'] as $toolDef) {
				if (($toolDef['name'] ?? null) === $name) {
					$tool = $toolDef;
					break;
				}
			}

			if ($tool === null) {
				return $this->errorResult("Unknown tool: $name");
			}

			$this->validateRequiredArgs($name, $arguments, $tool);

			//dispatch to dedicated handler or registered callback
			if (isset($this->toolHandlers[$name]) && method_exists($this, $this->toolHandlers[$name])) {
				$result = $this->{$this->toolHandlers[$name]}($arguments);
			} elseif (!empty($tool['execute_callback']) && is_callable($tool['execute_callback'])) {
				$result = $tool['execute_callback']($arguments);
			} else {
				return $this->errorResult("No handler registered for tool: $name");
			}

			return $this->textResult($result);
		} catch (Throwable $e) {
			//Routine tool failures (missing file, bad args, unknown model)
			//are answers, not crashes: the model gets the message alone and
			//nothing is logged, so agent trial-and-error doesn't flood the
			//server log. Only unexpected failures (IO/DB/environment/coding
			//bugs) log with traces under DEBUG. (This also drops a literal
			//'\n' separator bug - single quotes never escape.)
			$routine = $e instanceof \McpToolError || $e instanceof \InvalidArgumentException;

			if (DEBUG && !$routine) {
				error_log('mcp tools/call ' . $name . ' failed: ' . $e::class . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
			}

			return $this->errorResult(self::trimToolError($e->getMessage() ?: $e::class));
		}
	}

	/* -----------------------------------------------------------------
	 * Coding harness tools
	 * ----------------------------------------------------------------- */

	/**
	 * read tool: return file contents with line numbers, optionally sliced.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{content: string, totalLines: int}
	 */
	public function toolRead(array $args): array {
		$path    = $this->resolvePath((string)($args['path'] ?? ''));
		$offset  = max(1, (int)($args['offset'] ?? 1));
		$limit   = max(1, min(10000, (int)($args['limit'] ?? 2000)));

		if (!is_file($path)) {
			throw new \McpToolError("File not found: {$args['path']}");
		}

		$size = filesize($path);

		if ($size > self::READ_MAX_BYTES) {
			throw new \McpToolError('File exceeds max readable size (' . self::READ_MAX_BYTES . ' bytes): ' . $size);
		}

		$content = file_get_contents($path);

		if ($content === false) {
			throw new \RuntimeException("Failed reading file: {$args['path']}");
		}

		if (str_contains($content, "\0")) {
			throw new \McpToolError('File appears to be binary and cannot be displayed as text');
		}

		$lines = preg_split('/(\r\n|\n|\r)/', $content);
		$total = count($lines);
		$slice = array_slice($lines, $offset - 1, $limit);

		$output = '';

		foreach ($slice as $i => $line) {
			$output .= sprintf("%6d\t%s\n", $offset + $i, $line);
		}

		if ($total > $offset - 1 + count($slice)) {
			$output .= "\n[Output truncated to $limit lines starting at line $offset. Total lines: $total. "
				. 'Use offset/limit parameters to read more.]';
		}

		return ['content' => $output, 'totalLines' => $total];
	}

	/**
	 * write tool: create or overwrite a file, creating parent directories.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{success: bool, bytes: int, created: bool}
	 */
	public function toolWrite(array $args): array {
		//target may not exist yet, only its parent directory has to
		$path    = $this->resolvePath((string)($args['path'] ?? ''), false);
		$content = (string)($args['content'] ?? '');

		if (strlen($content) > self::WRITE_MAX_BYTES) {
			throw new \RuntimeException('Content exceeds max write size (' . self::WRITE_MAX_BYTES . ' bytes)');
		}

		$created = !is_file($path);
		$dir     = dirname($path);

		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			throw new \RuntimeException("Failed creating directory: $dir");
		}

		if (@file_put_contents($path, $content) === false) {
			throw new \RuntimeException("Failed writing file: {$args['path']}");
		}

		@chmod($path, 0664 & ~umask());

		return ['success' => true, 'bytes' => strlen($content), 'created' => $created];
	}

	/**
	 * bash tool: execute a shell command with timeout and output caps.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{stdout: string, stderr: string, exitCode: int, timedOut: bool}
	 */
	public function toolBash(array $args): array {
		$command = trim((string)($args['command'] ?? ''));

		if ($command === '') {
			throw new \InvalidArgumentException('command must not be empty');
		}

		// bash is the native tool (proc_open). If shell_exec/proc_open are disabled,
		// transparently fall back to the pure-PHP bash_sub (BashBox) so callers
		// still get a result. bash_sub remains a distinct pure-PHP tool.
		if (!MCP :: isShellAvailable()) {
			$fallback = $this->toolBashSub($args);
			// keep native bash response shape but indicate fallback
			$fallback['engine'] .= ' (fallback from bash)';

			return $fallback;
		}

		$timeout = max(1, min(120, (int)($args['timeout'] ?? 30)));
		$cwd     = isset($args['cwd']) && $args['cwd'] !== ''
			? $this->resolvePath((string)$args['cwd'], true)
			: $this->toolRoot();

		$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
		$process     = proc_open('exec /bin/bash -lc ' . escapeshellarg($command), $descriptors, $pipes, $cwd);

		if (!is_resource($process)) {
			throw new \RuntimeException('proc_open() failed — check that bash exists and open_basedir allows execution');
		}

		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);
		fclose($pipes[0]);

		$deadline  = microtime(true) + $timeout;
		$stdout    = '';
		$stderr    = '';
		$timedOut  = false;
		$truncated = false;

		while (true) {
			$read   = [$pipes[1], $pipes[2]];
			$write  = null;
			$except = null;

			if (stream_select($read, $write, $except, 0, 200000) === false) {
				break;
			}

			foreach ($read as $stream) {
				$data = fread($stream, 65536);

				if ($data === false || $data === '') {
					continue;
				}

				if ($stream === $pipes[1]) {
					$stdout .= $data;
				} else {
					$stderr .= $data;
				}
			}

			$status = proc_get_status($process);

			if (strlen($stdout) + strlen($stderr) > self::BASH_MAX_OUTPUT) {
				$truncated = true;
				proc_terminate($process, 9);
				break;
			}

			if (!$status['running']) {
				break;
			}

			if (microtime(true) > $deadline) {
				$timedOut = true;
				proc_terminate($process, 9);
				break;
			}
		}

		//drain what's left in the pipes after the process ended
		while (($line = fgets($pipes[1])) !== false) {
			$stdout .= $line;
		}

		while (($line = fgets($pipes[2])) !== false) {
			$stderr .= $line;
		}

		fclose($pipes[1]);
		fclose($pipes[2]);

		$exitCode = proc_close($process);

		if ($timedOut) {
			$exitCode = $exitCode ?: 124; // 124 = timeout convention
		}

		if ($truncated) {
			$stdout = mb_strcut($stdout, 0, self::BASH_MAX_OUTPUT / 2);
			$stderr = mb_strcut($stderr, 0, self::BASH_MAX_OUTPUT / 2);
			$stdout .= '\n[output truncated]';
		}

		return ['stdout' => $stdout, 'stderr' => $stderr, 'exitCode' => (int)$exitCode, 'timedOut' => $timedOut];
	}

	/**
	 * bash_sub tool: sandboxed pure-PHP bash via BashBox (shipfastlabs/bashbox).
	 *
	 * Used when php shell_exec/proc_open are disabled. Every command is a PHP class,
	 * filesystem is virtualised via ReadWriteFs sandboxed to toolRoot(). No proc_open/exec/shell_exec
	 * is invoked. Falls back to native bash if BashBox is not installed but shell is available.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{stdout: string, stderr: string, exitCode: int, timedOut: bool, engine: string, truncated?: bool}
	 */
	public function toolBashSub(array $args): array {
		$command = trim((string)($args['command'] ?? ''));
		if ($command === '') {
			throw new \InvalidArgumentException('command must not be empty');
		}
		$timeout = max(1, min(120, (int)($args['timeout'] ?? 30)));
		$cwdHost = isset($args['cwd']) && $args['cwd'] !== '' ? $this->resolvePath((string)$args['cwd'], true) : $this->toolRoot();
		$root    = $this->toolRoot();
		// virtual cwd is sandbox-relative, ReadWriteFs maps "/" -> $root
		$virtualCwd = '/' . ltrim(substr($cwdHost, strlen($root)), '/');
		if ($virtualCwd === '/') {
			$virtualCwd = '/';
		}
		$shellAvailable = MCP :: isShellAvailable();
		// If BashBox not available, gracefully fall back to native bash when possible
		if (!class_exists('\\BashBox\\Bash')) {
			if ($shellAvailable) {
				$fallback           = $this->toolBash($args);
				$fallback['engine'] = 'native (bashbox missing)';

				return $fallback;
			}

			throw new \RuntimeException('BashBox not installed (shipfastlabs/bashbox) and native shell is disabled. Run: composer require shipfastlabs/bashbox');
		}

		try {
			$fs = new \BashBox\Filesystem\ReadWriteFs($root);
		} catch (\Throwable $e) {
			throw new \RuntimeException('Failed to init BashBox filesystem: ' . $e->getMessage(), 0, $e);
		}
		$limits = new \BashBox\Limits(
			maxOutputSize: self::BASH_MAX_OUTPUT,
			maxCommandCount: 10000,
			maxLoopIterations: 10000,
			maxStringLength: self::BASH_MAX_OUTPUT,
		);
		$bashOptions = new \BashBox\BashOptions(
			fs: $fs,
			cwd: $virtualCwd,
			limits: $limits,
		);
		$bash        = new \BashBox\Bash($bashOptions);
		$execOptions = new \BashBox\ExecOptions(cwd: $virtualCwd);

		// BashBox has no timeout; enforce via maxCommandCount/limits. We still respect $timeout for API parity.
		try {
			$result = $bash->exec($command, $execOptions);
		} catch (\Throwable $e) {
			return [
				'stdout'   => '',
				'stderr'   => 'bashbox error: ' . $e->getMessage(),
				'exitCode' => 127,
				'timedOut' => false,
				'engine'   => 'bashbox',
			];
		}
		$stdout    = $result->stdout;
		$stderr    = $result->stderr;
		$exitCode  = $result->exitCode;
		$truncated = false;
		if (strlen($stdout) + strlen($stderr) > self::BASH_MAX_OUTPUT) {
			$stdout = mb_strcut($stdout, 0, (int)(self::BASH_MAX_OUTPUT / 2));
			$stderr = mb_strcut($stderr, 0, (int)(self::BASH_MAX_OUTPUT / 2));
			$stdout .= "\n[output truncated]";
			$truncated = true;
		}

		return [
			'stdout'    => $stdout,
			'stderr'    => $stderr,
			'exitCode'  => $exitCode,
			'timedOut'  => false,
			'engine'    => $shellAvailable ? 'bashbox (shell available, pure PHP)' : 'bashbox',
			'truncated' => $truncated,
		];
	}

	/**
	 * powershell tool: execute a PowerShell command (Windows-only).
	 *
	 * Native counterpart to bash for Windows servers where /bin/bash is
	 * unavailable. Prefers pwsh.exe (PowerShell 7+) and falls back to the
	 * inbox powershell.exe (Windows PowerShell 5.1). Uses a polling read
	 * loop instead of stream_select() which is socket-only on Windows.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{stdout: string, stderr: string, exitCode: int, timedOut: bool, engine: string}
	 */
	public function toolPowershell(array $args): array {
		$command = trim((string)($args['command'] ?? ''));

		if ($command === '') {
			throw new \InvalidArgumentException('command must not be empty');
		}

		if (!MCP :: isShellAvailable()) {
			throw new \RuntimeException('Native shell execution is disabled (proc_open/shell_exec disabled) — powershell tool requires proc_open');
		}

		$powershell = $this->findPowershell();

		if ($powershell === null) {
			throw new \RuntimeException('No PowerShell executable found (looked for pwsh/powershell). On Windows PowerShell is built in; on Linux/macOS install PowerShell 7 (pwsh) to use this tool.');
		}

		$timeout = max(1, min(120, (int)($args['timeout'] ?? 30)));
		$cwd     = isset($args['cwd']) && $args['cwd'] !== ''
			? $this->resolvePath((string)$args['cwd'], true)
			: $this->toolRoot();

		$shellCommand = '"' . $powershell . '" -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command ' . escapeshellarg($command);

		$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
		$process     = proc_open($shellCommand, $descriptors, $pipes, $cwd);

		if (!is_resource($process)) {
			throw new \RuntimeException('proc_open() failed — check that PowerShell exists and open_basedir allows execution');
		}

		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);
		fclose($pipes[0]);

		$deadline  = microtime(true) + $timeout;
		$stdout    = '';
		$stderr    = '';
		$timedOut  = false;
		$truncated = false;

		//polling loop (no stream_select — on Windows it only supports sockets).
		while (true) {
			$outChunk = fread($pipes[1], 65536);
			$errChunk = fread($pipes[2], 65536);

			if ($outChunk !== false && $outChunk !== '') {
				$stdout .= $outChunk;
			}

			if ($errChunk !== false && $errChunk !== '') {
				$stderr .= $errChunk;
			}

			$status = proc_get_status($process);

			if (strlen($stdout) + strlen($stderr) > self::BASH_MAX_OUTPUT) {
				$truncated = true;
				proc_terminate($process, 9);
				break;
			}

			if (!$status['running']) {
				break;
			}

			if (microtime(true) > $deadline) {
				$timedOut = true;
				proc_terminate($process, 9);
				break;
			}

			usleep(100000); // 100ms poll interval
		}

		//drain what's left in the pipes after the process ended
		while (($line = fgets($pipes[1])) !== false) {
			$stdout .= $line;
		}

		while (($line = fgets($pipes[2])) !== false) {
			$stderr .= $line;
		}

		fclose($pipes[1]);
		fclose($pipes[2]);

		$exitCode = proc_close($process);

		if ($timedOut) {
			$exitCode = $exitCode ?: 124; // 124 = timeout convention
		}

		if ($truncated) {
			$stdout = mb_strcut($stdout, 0, self::BASH_MAX_OUTPUT / 2);
			$stderr = mb_strcut($stderr, 0, self::BASH_MAX_OUTPUT / 2);
			$stdout .= '\n[output truncated]';
		}

		return [
			'stdout'   => $stdout,
			'stderr'   => $stderr,
			'exitCode' => (int)$exitCode,
			'timedOut' => $timedOut,
			'engine'   => basename(str_replace('\\', '/', $powershell)),
		];
	}

	/**
	 * Whether the server OS is Windows.
	 */
	private function isWindows(): bool {
		return PHP_OS_FAMILY === 'Windows' || DIRECTORY_SEPARATOR === '\\';
	}

	/**
	 * Locate a PowerShell executable.
	 *
	 * On Windows prefers pwsh.exe (PowerShell 7+) then falls back to the
	 * inbox powershell.exe (5.1). On other systems looks for pwsh/powershell
	 * (PowerShell Core). Returns the full path when resolvable via where/command -v,
	 * otherwise the bare executable name on Windows (resolved via PATH by proc_open)
	 * or null when nothing is available.
	 */
	private function findPowershell(): ?string {
		$candidates = $this->isWindows()
			? ['pwsh.exe', 'powershell.exe']
			: ['pwsh', 'powershell'];

		$disabled     = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
		$disabled     = array_map('strtolower', $disabled);
		$canShellExec = function_exists('shell_exec') && !in_array('shell_exec', $disabled, true);

		if ($canShellExec) {
			$finder  = $this->isWindows() ? 'where' : 'command -v';
			$devNull = $this->isWindows() ? 'NUL' : '/dev/null';

			foreach ($candidates as $bin) {
				$out   = trim((string)@$this->shellExecQuiet("$finder $bin 2>$devNull"));
				$first = strtok($out, "\r\n");

				if ($first !== false && trim($first) !== '') {
					return trim($first);
				}
			}
		}

		//shell_exec unavailable or lookup failed: on Windows fall back to the
		//inbox executable name and let proc_open resolve it via PATH.
		if ($this->isWindows()) {
			return 'powershell.exe';
		}

		return null;
	}

	/**
	 * Quiet shell_exec wrapper (suppressed errors, always returns string).
	 *
	 * @param string $command
	 */
	private function shellExecQuiet(string $command): string {
		$result = @shell_exec($command);

		return is_string($result) ? $result : '';
	}

	/**
	 * glob tool: find files by pattern relative to the site root.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{matches: list<string>, count: int}
	 */
	public function toolGlob(array $args): array {
		$pattern = (string)($args['pattern'] ?? '');
		$limit   = max(1, min(5000, (int)($args['limit'] ?? 500)));

		if ($pattern === '') {
			throw new \InvalidArgumentException('pattern must not be empty');
		}

		$root = $this->toolRoot();

		//normalize to a root-relative body. Absolute patterns must already
		//sit inside the sandbox (rejected, never remapped); anything else is
		//relative to the site root.
		if ($pattern[0] === '/') {
			if ($pattern !== $root && !str_starts_with($pattern, $root . '/')) {
				throw new \McpToolError('Pattern resolves outside the site root');
			}
			$body = ltrim(substr($pattern, strlen($root)), '/');
		} else {
			$body = ltrim(str_replace('\\', '/', $pattern), '/');
		}

		//leading ./ segments are noise for matching (relative hits never
		//carry them) but would break the recursive regex anchor
		$body = preg_replace('#^(?:\./)+#', '', $body);

		//only the static leading segments (no wildcard chars) locate the
		//search base - the old realpath(dirname($target)) check broke on any
		//wildcard (dirname('public/**/*') is not a real path) and rejected
		//every relative pattern. '..' climbs above the root are rejected;
		//deeper '..' collapse lexically exactly like resolvePath().
		$segments  = $body === '' ? [] : explode('/', $body);
		$canonical = [];

		foreach ($segments as $seg) {
			if (strpbrk($seg, '*?[{') !== false) {
				break;
			}
			if ($seg === '' || $seg === '.') {
				continue;
			}
			if ($seg === '..') {
				if (!$canonical) {
					throw new \McpToolError('Pattern resolves outside the site root');
				}
				array_pop($canonical);
				continue;
			}
			$canonical[] = $seg;
		}

		$baseAbs = $root . ($canonical ? '/' . implode('/', $canonical) : '');

		//existing bases must really live inside the sandbox (symlink check)
		$realBase = realpath($baseAbs);

		if ($realBase !== false && $realBase !== $root && !str_starts_with($realBase, $root . '/')) {
			throw new \McpToolError('Pattern resolves outside the site root');
		}

		$results = [];

		//native glob() treats a double star as a single-level star, so
		//double-star patterns walk recursively and match per file instead.
		if (str_contains($body, '**')) {
			$results = $this->globRecursive($realBase !== false ? $realBase : $baseAbs, $root, $body, $limit);
		} else {
			$matches = is_dir($baseAbs) ? (glob($root . '/' . $body, GLOB_BRACE | GLOB_NOSORT) ?: []) : [];
			sort($matches);

			foreach ($matches as $match) {
				if (count($results) >= $limit) {
					break;
				}
				//per-match containment: a symlinked match must not escape
				$real = realpath($match);

				if ($real === false || ($real !== $root && !str_starts_with($real, $root . '/'))) {
					continue;
				}
				$results[] = ltrim(substr($real, strlen($root)), '/');
			}
		}

		return ['matches' => $results, 'count' => count($results)];
	}

	/**
	 * Recursive double-star glob: walk $walkRoot and match each file's
	 * root-relative path against the glob body (a double star spans zero or
	 * more directories).
	 *
	 * @param string $walkRoot existing search base, already sandbox-checked
	 * @param string $root
	 * @param string $body
	 * @param int    $limit
	 *
	 * @return list<string> root-relative matches, sorted, capped at $limit
	 */
	private function globRecursive(string $walkRoot, string $root, string $body, int $limit): array {
		$results = [];

		if (!is_dir($walkRoot)) {
			return $results;
		}

		$regex     = $this->globPatternToRegex($body);
		$prefixLen = strlen($root) + 1;
		$iterator  = new \RecursiveIteratorIterator(
			new \RecursiveCallbackFilterIterator(
				new \RecursiveDirectoryIterator($walkRoot, \FilesystemIterator::SKIP_DOTS),
				fn ($current) => !$current->isDir() || !in_array($current->getFilename(), self::SKIP_DIRS, true)
			),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);
		$scanned = 0;

		foreach ($iterator as $file) {
			if (!$file instanceof \SplFileInfo || !$file->isFile()) {
				continue;
			}
			if (++$scanned > 50000) {
				break; // runaway guard on huge trees; limit already caps output
			}
			$rel = str_replace('\\', '/', substr($file->getPathname(), $prefixLen));

			if (!preg_match($regex, $rel)) {
				continue;
			}
			$results[] = $rel;

			if (count($results) >= $limit) {
				break;
			}
		}
		sort($results);

		return $results;
	}

	/**
	 * Translate a glob body to a PCRE pattern over /-separated relative
	 * paths: a double star plus slash spans zero or more directories, a
	 * single star never crosses /.
	 *
	 * @param string $glob
	 */
	private function globPatternToRegex(string $glob): string {
		$out = '';
		$len = strlen($glob);
		$i   = 0;

		while ($i < $len) {
			$c = $glob[$i];

			if ($c === '*') {
				$run = 0;

				while (($glob[$i] ?? '') === '*') {
					$run++;
					$i++;
				}
				if ($run >= 2 && ($glob[$i] ?? '') === '/') {
					$i++;
					$out .= '(.*/)?';
				} elseif ($run >= 2) {
					$out .= '.*';
				} else {
					$out .= '[^/]*';
				}
			} elseif ($c === '?') {
				$out .= '[^/]';
				$i++;
			} elseif ($c === '[') {
				//copy the character class through, translating [!...] to [^...]
				$cls = '[';
				$i++;

				if (($glob[$i] ?? '') === '!') {
					$cls .= '^';
					$i++;
				}
				while ($i < $len && $glob[$i] !== ']') {
					$cls .= $glob[$i] === '\\' ? '\\\\' : $glob[$i];
					$i++;
				}
				$cls .= ']';
				$i++;
				$out .= $cls;
			} elseif ($c === '{') {
				//{a,b} alternation: split top-level commas, recurse per branch
				$depth = 1;
				$j     = $i + 1;
				$alts  = [''];

				while ($j < $len && $depth > 0) {
					$ch = $glob[$j];

					if ($ch === '{') {
						$depth++;
						$alts[count($alts) - 1] .= $ch;
					} elseif ($ch === '}') {
						$depth--;

						if ($depth > 0) {
							$alts[count($alts) - 1] .= $ch;
						}
					} elseif ($ch === ',' && $depth === 1) {
						$alts[] = '';
					} else {
						$alts[count($alts) - 1] .= $ch;
					}
					$j++;
				}
				if ($depth === 0) {
					$parts = [];

					foreach ($alts as $alt) {
						$parts[] = substr($this->globPatternToRegex($alt), 2, -2);
					}
					$out .= '(?:' . implode('|', $parts) . ')';
					$i = $j;
				} else {
					$out .= preg_quote($c, '@');
					$i++;
				}
			} else {
				$out .= preg_quote($c, '@');
				$i++;
			}
		}

		return '@^' . $out . '$@';
	}

	/**
	 * grep tool: recursive regex search over text files.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{matches: list<array{file: string, line: int, text: string}>, count: int, truncated: bool}
	 */
	public function toolGrep(array $args): array {
		$pattern    = (string)($args['pattern'] ?? '');
		$searchPath = (string)($args['path'] ?? '.');
		$include    = (string)($args['include'] ?? '*');
		$ignoreCase = !empty($args['ignoreCase']);
		$maxResults = max(1, min(1000, (int)($args['maxResults'] ?? 200)));

		if ($pattern === '') {
			throw new \InvalidArgumentException('pattern must not be empty');
		}

		$regex = '@' . str_replace('@', '\\@', $pattern) . '@' . ($ignoreCase ? 'i' : '');

		if (@preg_match($regex, '') === false) {
			throw new \InvalidArgumentException("Invalid PCRE pattern: $pattern");
		}

		$path      = $this->resolvePath($searchPath);
		$root      = $this->toolRoot();
		$matches   = [];
		$count     = 0;
		$truncated = false;

		$iterator = is_dir($path)
			? new \RecursiveIteratorIterator(
				new \RecursiveCallbackFilterIterator(
					new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
					fn ($current) => !$current->isDir() || !in_array($current->getFilename(), self::SKIP_DIRS, true)
				),
				\RecursiveIteratorIterator::LEAVES_ONLY
			)
			: [$spl = new \SplFileInfo($path)];

		foreach ($iterator as $file) {
			if (!$file instanceof \SplFileInfo || !$file->isFile()) {
				continue;
			}

			if ($include !== '*' && !fnmatch($include, $file->getFilename())) {
				continue;
			}

			if ($file->getSize() > 524288) { // skip files over 512KB
				continue;
			}

			$contents = @file_get_contents($file->getPathname());

			if ($contents === false || str_contains($contents, "\0")) {
				continue;
			}

			$lines = preg_split('/\r\n|\n|\r/', $contents);

			foreach ($lines as $i => $line) {
				if (@preg_match($regex, $line)) {
					$count++;

					if ($count <= $maxResults) {
						$matches[] = [
							'file' => ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/'),
							'line' => $i + 1,
							'text' => mb_strcut($line, 0, 500),
						];
					}
				}
			}

			if ($count >= $maxResults * 10) { // hard stop once far past the cap
				$truncated = true;
				break;
			}
		}

		return ['matches' => $matches, 'count' => $count, 'truncated' => $count > $maxResults];
	}

	/**
	 * database_run_query tool: run SQL with prepared params against the site DB.
	 *
	 * Read-only statements are always allowed; write statements require config
	 * key mcp.tools.db_write. SELECTs without LIMIT are capped automatically.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public function toolDatabaseRunQuery(array $args): array {
		$query   = trim((string)($args['query'] ?? ''));
		$params  = is_array($args['params'] ?? null) ? $args['params'] : [];
		$maxRows = max(1, min(10000, (int)($args['maxRows'] ?? 1000)));

		if ($query === '') {
			throw new \InvalidArgumentException('query must not be empty');
		}

		//the driver binds :named placeholders; translate positional '?' marks
		//(outside quoted strings) into :p1, :p2 ... so users can pass a plain
		//array of values
		if ($params) {
			$out      = '';
			$inSingle = false;
			$inDouble = false;
			$index    = 0;

			foreach (str_split($query) as $char) {
				if ($char === "'" && !$inDouble) {
					$inSingle = !$inSingle;
				} elseif ($char === '"' && !$inSingle) {
					$inDouble = !$inDouble;
				}

				if ($char === '?' && !$inSingle && !$inDouble) {
					$index++;
					$out .= ':p' . $index;
					continue;
				}

				$out .= $char;
			}

			$query  = $out;
			$bind   = [];

			foreach ($params as $i => $value) {
				$bind['p' . ($i + 1)] = $value;
			}

			$params = $bind;
		}

		//strip comments before checking statement type
		$stmtType = self::statementType($query);

		$readOnlyTypes = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'];
		$isReadOnly    = in_array($stmtType, $readOnlyTypes, true)
			|| ($stmtType === 'WITH' && !self::containsWriteKeyword($query));

		if (!$isReadOnly && config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $stmtType queries.");
		}

		$db = Db::getInstance();

		if (!$db) {
			throw new \RuntimeException('Database not available');
		}

		if ($stmtType === 'SELECT' && !stripos($query, ' LIMIT ')) {
			//cap unbounded selects so a missing LIMIT cannot exhaust memory;
			//an explicit LIMIT set by the caller is respected as-is
			$query = rtrim($query, "; \t\r\n") . ' LIMIT ' . $maxRows;
		}

		$stmt = $db->execute($query, $params);

		if ($stmt === null) {
			throw new \RuntimeException('Query failed');
		}

		if (in_array($stmtType, $readOnlyTypes, true) || $stmtType === 'WITH') {
			$rows       = $db->fetchAll($stmt);
			$rowCount   = count($rows);
			$truncated  = $rowCount > $maxRows;

			if ($truncated) {
				$rows = array_slice($rows, 0, $maxRows);
			}

			return ['rows' => $rows, 'rowCount' => $rowCount, 'truncated' => $truncated];
		}

		return [
			'affectedRows' => (int)($db->affected_rows ?? 0),
			'insertId'     => (int)($db->insert_id ?? 0),
			'success'      => true,
		];
	}

	/**
	 * crud tool: map CRUD operations to admin SQL models.
	 *
	 * The model name maps to admin/sql/<engine>/<model>.sql (e.g. product,
	 * user, post, order) and the operation maps to one of its procedures
	 * (get, getAll, add, edit, delete). Reads are always allowed; writes
	 * require config key mcp.tools.db_write, mirroring the database_query tool.
	 *
	 * @param mixed                $result
	 * @param mixed                $columns
	 * @param array<string, mixed> $args
	 *
	 * @return array{model: string, operation: string, result: mixed}
	 */
	/**
	 * Project result rows down to the requested columns (PHP-side: the
	 * procedures always SELECT wide). Applies recursively to nested row
	 * lists (e.g. post_content) so bodies can be dropped while names stay;
	 * scalars like counts pass through untouched. Unknown columns yield an
	 * explicit error naming real keys instead of silent empty rows.
	 * Pure, tested.
	 *
	 * @param mixed $result  Model result.
	 * @param mixed $columns Wanted column names (validated as list of strings inside).
	 *
	 * @throws \InvalidArgumentException On unknown columns.
	 *
	 * @return mixed Projected result.
	 */
	private static function crudSelectColumns(mixed $result, mixed $columns): mixed {
		if (!is_array($columns) || !array_is_list($columns)) {
			throw new \InvalidArgumentException("Invalid columns for tool 'crud': must be a list of column names.");
		}
		if ($columns === [] || !is_array($result)) {
			return $result;
		}
		foreach ($columns as $column) {
			if (!is_string($column) || !preg_match('/^[a-z][a-z0-9_]*$/', $column)) {
				throw new \InvalidArgumentException("Invalid columns entry for tool 'crud': each must match [a-z][a-z0-9_]*.");
			}
		}
		//top-level scalars (count, totals) always pass: they describe the
		//query, not the rows. Nested levels project strictly. $matched
		//records any column hit so unknown names error instead of silently
		//emptying (scalar passthrough alone must not count as a hit).
		$matched = false;
		$project = function($rows, bool $top = false) use (&$project, $columns, &$matched) {
			if (!is_array($rows)) {
				return $rows;
			}
			if (array_is_list($rows)) {
				return array_map(fn ($row) => $project($row, false), $rows);
			}
			//associative row: project known keys, recurse into nested lists
			//(empty projected rows drop out so unknown keys vanish)
			$out = [];
			foreach ($rows as $key => $value) {
				if (is_array($value)) {
					$nested = $project($value, false);
					if (array_is_list($nested)) {
						$nested = array_values(array_filter($nested, fn ($row) => $row !== [] && $row !== null));
					}
					if ($nested !== [] || in_array($key, $columns, true)) {
						$out[$key] = $nested;
					}
				} elseif ($top || in_array($key, $columns, true)) {
					if (in_array($key, $columns, true)) {
						$matched = true;
					}
					$out[$key] = $value;
				}
			}

			return $out;
		};
		$projected = $project($result, true);
		//no column hit anywhere, yet row data existed: unknown names
		$hasRows = function($rows) use (&$hasRows): bool {
			if (!is_array($rows)) {
				return false;
			}
			if (array_is_list($rows)) {
				foreach ($rows as $row) {
					if ($hasRows($row)) {
						return true;
					}
				}

				return false;
			}

			return true;
		};
		$flatten = function($rows) use (&$flatten): array {
			if (!is_array($rows)) {
				return [];
			}
			if (array_is_list($rows)) {
				$keys = [];
				foreach ($rows as $row) {
					$keys = array_merge($keys, $flatten($row));
				}

				return array_values(array_unique($keys));
			}
			$keys = [];
			foreach ($rows as $key => $value) {
				$keys[] = $key;
				$keys   = array_merge($keys, $flatten($value));
			}

			return array_values(array_unique($keys));
		};
		if (!$matched && $hasRows($result)) {
			throw new \InvalidArgumentException("Unknown columns for tool 'crud' (" . implode(', ', $columns) . '). Available keys include: ' . implode(', ', array_slice($flatten($result), 0, 12)) . '. Check table columns via database_query DESCRIBE.');
		}

		return $projected;
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @return array{model: string, operation: string, result: mixed}
	 */
	public function toolCrud(array $args): array {
		$operation = trim((string)($args['operation'] ?? ''));
		$params    = $args['params'] ?? [];
		$model     = self::crudNormalizeModel((string)($args['model'] ?? ''));
		$columns   = $args['columns'] ?? [];

		if ($columns !== [] && (!is_array($columns) || !array_is_list($columns))) {
			throw new \InvalidArgumentException("Invalid columns for tool 'crud': must be a list of column names.");
		}

		$allowedOps = ['get', 'getAll', 'add', 'edit', 'delete'];

		if (!in_array($operation, $allowedOps, true)) {
			throw new \InvalidArgumentException("Invalid operation '$operation' for tool 'crud'. Allowed: " . implode(', ', $allowedOps));
		}

		if (!is_array($params)) {
			throw new \InvalidArgumentException("Invalid params for tool 'crud'. params must be an object with key/value pairs.");
		}

		$sqlFile = self::crudModelFile($model);

		if ($sqlFile === null) {
			throw new \McpToolError("Unknown model: '$model'. Available models: " . implode(', ', self::crudModels()));
		}

		$procedures = array_keys(self::crudProcedures($sqlFile, false));

		if ($procedures === []) {
			throw new \McpToolError("No procedures found in model '$model'.");
		}

		if (!in_array($operation, $procedures, true)) {
			throw new \McpToolError("Operation '$operation' not available for model '$model'. Available operations: " . implode(', ', $procedures));
		}

		//normalize wrong formats
		if (isset($params[$model . '_content'])) {
			if (!array_is_list($params[$model . '_content'])) {
				if (isset($params[$model . '_content']['content'])) {
					$params[$model . '_content'] = [$params[$model . '_content']];
				} else {
					//some models add extra keys like item
					$first = array_first($params[$model . '_content']);
					if (isset($first['content'])) {
						$params[$model . '_content'] = [$first];
					}
				}
			}
			foreach ($params[$model . '_content'] as &$content) {
				if (!isset($content['slug']) && isset($content['name'])) {
					$content['slug'] = slugify($content['name']);
				}
				if (is_array($content)) {
					$content += ['language_id' => $this->global['language_id']];
				}
			}
		}

		if (($model === 'post' || $model === 'product') && !isset($params['site_id'])) {
			$params['site_id'] = [$this->global['site_id']];
		}

		if ($operation == 'getAll') {
			$params += ['start' => 0, 'limit' => 5];
		}

		if ($operation == 'add') {
			foreach ($params as $param => &$value) {
				//content expects a multi language array if only one language is provided add to list
				if (str_ends_with($param, '_content')) {
					if (!array_is_list($value)) {
						$value = [$value];
					}

					foreach ($value as &$row) {
						if (is_array($row) && !isset($row['slug']) && isset($row['name'])) {
							$row['slug'] = slugify($row['name']);
						}
					}
					unset($row);
				}
			}
		}
		//normalize wrong formats - end

		$procParams = self::crudProcedures($sqlFile, false)[$operation] ?? [];
		$params     = self::crudCoerceArrayParams($procParams, $params);

		//fail fast with the calling convention: add/edit nest the record
		//under the first ARRAY parameter (usually the model name), never as
		//flat fields. Without this a flat call dies deep in SQL with a
		//cryptic error; the message below carries a working example instead.
		//Runs before the db_write gate so agents get the shape fix first.
		$shapeError = self::crudCheckRecordParam($model, $operation, $procParams, $params);

		if ($shapeError !== null) {
			throw new \InvalidArgumentException($shapeError);
		}

		//fail fast with guidance: missing must-supply params, misshapen
		//rows and unknown row keys are rejected here, before the db_write
		//gate, so agents get actionable errors instead of deep SQL failures.
		$detailParams  = self::crudProcedures($sqlFile)[$operation] ?? [];
		$validateError = self::crudValidateParams($model, $operation, $detailParams, $params);

		if ($validateError !== null) {
			throw new \InvalidArgumentException($validateError);
		}

		if (in_array($operation, ['add', 'edit', 'delete'], true) && config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $operation on model $model.");
		}

		$modelObj = \Vvveb\model($model);

		if (!method_exists($modelObj, $operation)) {
			throw new \McpToolError("Operation '$operation' not available for model '$model'.");
		}

		if (isset($params[$model])) {
			$params[$model] += $this->global;
		}

		$params += $this->global;

		$result = $modelObj->{$operation}($params);

		if ($columns !== [] && in_array($operation, ['get', 'getAll'], true)) {
			$result = self::crudSelectColumns($result, $columns);
		}

		return ['model' => $model, 'operation' => $operation, 'result' => $result ?? []];
	}

	/**
	 * crud_models tool: discover available CRUD models and their procedures.
	 *
	 * Without a model argument lists every model with its procedure names and
	 * which of them are callable through the crud tool. With a model argument
	 * returns each procedure with its IN parameters and crud-callability, so
	 * agents know exactly what params an operation accepts. Read-only.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public function toolCrudModels(array $args): array {
		$rawModel = trim((string)($args['model'] ?? ''));

		if ($rawModel === '') {
			$models = [];

			foreach (self::crudModels() as $name) {
				$sqlFile    = self::crudModelFile($name);
				$procedures = $sqlFile ? array_keys(self::crudProcedures($sqlFile, false)) : [];
				$models[]   = [
					'model'      => $name,
					'crud'       => array_values(array_intersect(['get', 'getAll', 'add', 'edit', 'delete'], $procedures)),
					'procedures' => $procedures,
				];
			}

			return ['models' => $models, 'count' => count($models)];
		}

		$model   = self::crudNormalizeModel($rawModel);
		$sqlFile = self::crudModelFile($model);

		if ($sqlFile === null) {
			throw new \McpToolError("Unknown model: '$model'. Available models: " . implode(', ', self::crudModels()));
		}

		$crudOps    = ['get', 'getAll', 'add', 'edit', 'delete'];
		$procedures = [];

		foreach (self::crudProcedures($sqlFile) as $name => $params) {
			foreach ($params as &$entry) {
				//internal validation aid, never part of the tool schema
				unset($entry['columns']);
			}
			unset($entry);

			$procedures[] = ['name' => $name, 'crud' => in_array($name, $crudOps, true), 'params' => $params];
		}

		return ['model' => $model, 'procedures' => $procedures];
	}

	/**
	 * Normalize a user supplied model name (trim, CamelCase to snake_case).
	 *
	 * @param string $rawModel
	 *
	 * @throws \InvalidArgumentException on empty or malformed names
	 */
	private static function crudNormalizeModel(string $rawModel): string {
		//accept CamelCase too (ProductOption -> product_option)
		$model = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', trim($rawModel)));

		if ($model === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $model)) {
			throw new \InvalidArgumentException("Invalid model name: '$rawModel'");
		}

		return $model;
	}

	/**
	 * Resolve the .sql definition file for a normalized model name.
	 *
	 * Uses the current DB engine directory with fallback to the canonical
	 * mysqli definitions. Returns null when the model does not exist.
	 *
	 * @param string $model
	 */
	private static function crudModelFile(string $model): ?string {
		$engine  = defined('DB_ENGINE') ? DB_ENGINE : 'mysqli';
		$sqlFile = DIR_ROOT . '/admin/sql/' . $engine . '/' . $model . '.sql';

		if (is_file($sqlFile)) {
			return $sqlFile;
		}

		$sqlFile = DIR_ROOT . '/admin/sql/mysqli/' . $model . '.sql';

		return is_file($sqlFile) ? $sqlFile : null;
	}

	/**
	 * Parse procedure names and IN params from a .sql definition file.
	 *
	 * With $detail, each IN param also reports whether it is must-supply
	 * (`required`) and, for ROW/ROWS record params, the accepted field names
	 * (`fields`). required is derived from the procedure body: a param used
	 * in unconditional SQL must be supplied, while @IF-guarded, loop-tolerant
	 * (@EACH) or unreferenced params are safely omittable. fields are the
	 *
	 * @FILTER table columns minus auto-increment keys and keys the procedure
	 * injects itself (e.g. post_id via @result.post).
	 *
	 * @param string $sqlFile
	 * @param bool   $detail
	 *
	 * @return array<string, list<array{name: string, type: string, required?: bool, fields?: list<string>, required_fields?: list<string>, columns?: list<string>}>> procedure name to IN params
	 */
	private static function crudProcedures(string $sqlFile, bool $detail = true): array {
		static $cache = [];

		$cacheKey = $sqlFile . ($detail ? '#detail' : '');

		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}

		$procedures = [];
		$content    = (string)@file_get_contents($sqlFile);

		//SqlP strips -- comments before parsing; do the same so comment
		//text never counts as a parameter reference.
		$stripped = (string)preg_replace('/(--.*)\s+/', '', $content);

		//declaration headers contain no nested parens, and the trailing
		//\s*BEGIN anchor disambiguates the header's closing paren.
		//The body runs to the first END not followed by @ (same as SqlP).
		if (preg_match_all('/(CREATE\s+)?PROCEDURE\s+([A-Za-z0-9_]+)\s*\((.*?)\)\s*BEGIN\s*(.*?)\s*END(?!\s*@)/is', $stripped, $blocks, PREG_SET_ORDER)) {
			foreach ($blocks as $block) {
				$params = [];

				if (preg_match_all('/\bIN\s+([A-Za-z0-9_]+)\s+([A-Za-z0-9_]+)/i', $block[3], $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						$entry = ['name' => $match[1], 'type' => strtoupper($match[2])];

						if ($detail) {
							$entry['required'] = self::crudParamRequired($match[1], $block[4]);
							$fieldInfo         = self::crudParamFields($match[1], $entry['type'], $block[4]);

							if ($fieldInfo !== null) {
								$entry['fields'] = $fieldInfo['fields'];

								if ($fieldInfo['required_fields'] !== []) {
									$entry['required_fields'] = $fieldInfo['required_fields'];
								}

								//internal only: lenient edit checks need every table
								//column, never emitted (stripped in toolCrudModels)
								$entry['columns'] = $fieldInfo['columns'];
							}
						}

						$params[] = $entry;
					}
				}

				$procedures[$block[2]] = $params;
			}
		}

		return $cache[$sqlFile . ($detail ? '#detail' : '')] = $procedures;
	}

	/**
	 * Whether an IN param must be supplied: referenced in unconditional SQL.
	 *
	 * Params used only inside @IF guards, tolerant @EACH loops or @FILTER
	 * preprocessing (or not referenced at all) are safely omittable.
	 * :ret aliases from `:ret = @FILTER(:param, ...)` count as the param.
	 *
	 * @param string $param
	 * @param string $body
	 */
	private static function crudParamRequired(string $param, string $body): bool {
		$flow = [$param];

		if (preg_match_all('/:([A-Za-z0-9_\.]+)\s*=\s*@FILTER\s*\(\s*:([A-Za-z0-9_\.]+)\b/', $body, $m)) {
			foreach ($m[2] as $i => $data) {
				if ($data === $param) {
					$flow[] = $m[1][$i];
				}
			}
		}

		$probe = $body;

		//peel nested @IF...END @IF blocks (innermost first)
		do {
			$probe = (string)preg_replace('/@IF\b(?:(?!@IF).)*?END @IF/s', '', $probe, -1, $count);
		} while ($count > 0);

		/**
		 * @EACH headers are tolerant (isset + is_array guard), @FILTER
		 * calls are preprocessing - neither proves the param is needed
		 */
		$probe = (string)preg_replace('/@EACH\s*\(\s*:[^)]+\)/', '', $probe);
		$probe = (string)preg_replace('/:[A-Za-z0-9_\.]+\s*=\s*@FILTER\s*\([^()]*\)/', '', $probe);
		$probe = (string)preg_replace('/@FILTER\s*\([^()]*\)/', '', $probe);

		foreach ($flow as $var) {
			if (preg_match('/:' . preg_quote($var, '/') . '(?!\w)/', $probe)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Accepted field names for a ROW/ROWS record param, or null.
	 *
	 * Tables come from @FILTER calls on the param (following renames) or,
	 * failing that, the INSERT target of its @EACH loop. Fields are table
	 * columns minus auto-increment keys and keys the procedure injects
	 * itself (siblings of @KEYS in INSERT column lists, e.g. post_id).
	 * Emitted only when the data is row-shaped (direct @KEYS/@LIST or a
	 * row-valued @EACH loop), so flat id lists never get fields.
	 *
	 * @param string $param
	 * @param string $type
	 * @param string $body
	 *
	 * @return array{fields: list<string>, required_fields: list<string>, columns: list<string>}|null
	 *                                                                                                null when no table resolves; required_fields lists fields with
	 *                                                                                                no usable default, i.e. omitting them fails the INSERT.
	 */
	private static function crudParamFields(string $param, string $type, string $body): ?array {
		$flow = [$param];

		if (preg_match_all('/:([A-Za-z0-9_\.]+)\s*=\s*@FILTER\s*\(\s*:([A-Za-z0-9_\.]+)\b/', $body, $m)) {
			foreach ($m[2] as $i => $data) {
				if ($data === $param) {
					$flow[] = $m[1][$i];
				}
			}
		}

		$tables = [];

		if (preg_match_all('/@FILTER\s*\(\s*:([A-Za-z0-9_\.]+)\s*,\s*([A-Za-z_]+)/', $body, $fm, PREG_SET_ORDER)) {
			foreach ($fm as $f) {
				//dotted children (:order.products) belong to sub-lists, not this param
				if (in_array($f[1], $flow, true) && !in_array($f[2], $tables, true)) {
					$tables[] = $f[2];
				}
			}
		}

		$rowShaped  = false;
		$loopTables = [];

		foreach ($flow as $var) {
			if (preg_match('/@(KEYS|LIST)\s*\(\s*:' . preg_quote($var, '/') . '(?!\w)/', $body)) {
				$rowShaped = true;

				break;
			}
		}

		foreach (explode(';', $body) as $stmt) {
			if (!preg_match('/@EACH\s*\(\s*:([A-Za-z0-9_\.]+)\s*\)/', $stmt, $em) || !in_array($em[1], $flow, true)) {
				continue;
			}

			$hasKeys = (bool)preg_match('/@KEYS\s*\(\s*:each(?!\w)/', $stmt);
			$hasList = (bool)preg_match('/@LIST\s*\(\s*:each(?!\w)/', $stmt) && (bool)preg_match('/:each\.\w+/', $stmt);

			if ($hasKeys || $hasList) {
				$rowShaped = true;

				if (preg_match('/INSERT\s+INTO\s+["`]?([A-Za-z_][\w]*)["`]?/i', $stmt, $im) && !in_array($im[1], $loopTables, true)) {
					$loopTables[] = $im[1];
				}
			}
		}

		if (!$rowShaped) {
			return null;
		}

		if (!$tables && $loopTables) {
			$tables = $loopTables;
		}

		if (!$tables) {
			return null;
		}

		//columns the procedure supplies itself: siblings of @KEYS(...)
		//in INSERT column lists - rows must not (re)supply them
		$extras = [];

		if (preg_match_all('/\(\s*@KEYS\s*\([^)]+\)([^()]*)\)/', $body, $km, PREG_SET_ORDER)) {
			foreach ($km as $k) {
				if (preg_match_all('/[`"]?([A-Za-z_][\w]*)[`"]?/', $k[1], $cm)) {
					foreach ($cm[1] as $col) {
						$extras[$col] = true;
					}
				}
			}
		}

		$fields         = [];
		$requiredFields = [];
		$columns        = [];

		foreach ($tables as $table) {
			foreach (self::crudTableColumns($table) as $name => $col) {
				if (!in_array($name, $columns, true)) {
					$columns[] = $name;
				}

				if (isset($extras[$name]) || self::crudIsAutoColumn($col)) {
					continue;
				}

				if (!in_array($name, $fields, true)) {
					$fields[] = $name;

					if (self::crudIsRequiredColumn($col)) {
						$requiredFields[] = $name;
					}
				}
			}
		}

		return $fields === [] ? null : ['fields' => $fields, 'required_fields' => $requiredFields, 'columns' => $columns];
	}

	/**
	 * Live column metadata for a table, keyed by column name.
	 *
	 * Same source @FILTER enforces against, so listed fields can never
	 * disagree with filtering. Empty when the table is unavailable.
	 *
	 * @param string $table
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function crudTableColumns(string $table): array {
		static $cache = [];

		if (array_key_exists($table, $cache)) {
			return $cache[$table];
		}

		$columns = [];

		try {
			$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'vvveb_';
			$meta   = Db::getInstance()->getColumnsMeta($prefix . $table);

			if (is_array($meta)) {
				foreach ($meta as $key => $col) {
					if (is_array($col)) {
						$columns[$col['name'] ?? $key] = $col;
					}
				}
			}
		} catch (\Throwable) {
			//uninstalled plugin tables and the like: no fields
		}

		return $cache[$table] = $columns;
	}

	/**
	 * Whether a metadata column is auto-assigned on insert (and agents
	 * must therefore not supply it): mysqli EXTRA flag, pgsql serial
	 * nextval() defaults, sqlite INTEGER PRIMARY KEY rowid aliases
	 * (nullable, default-less INT columns).
	 *
	 * @param array<string, mixed> $col
	 */
	private static function crudIsAutoColumn(array $col): bool {
		//mysqli INFORMATION_SCHEMA EXTRA flag
		if (($col['e'] ?? '') === 'auto_increment') {
			return true;
		}

		//pgsql serial columns default to nextval(...)
		if (str_starts_with((string)($col['d'] ?? ''), 'nextval')) {
			return true;
		}

		//sqlite getColumnsMeta() exposes no pk/extra flags: an INTEGER
		//PRIMARY KEY is a rowid alias (auto-assigned, pragma notnull = 0).
		//Nullable default-less INTs also match, which is harmless - omitting
		//a nullable column is always safe.
		if (defined('DB_ENGINE') && DB_ENGINE === 'sqlite') {
			$type     = strtoupper((string)($col['t'] ?? ''));
			$default  = $col['d'] ?? null;

			return str_contains($type, 'INT') && ($col['n'] ?? 1) == 0 && ($default === null || $default === '');
		}

		return false;
	}

	/**
	 * Whether omitting the column fails the INSERT: NOT NULL with no
	 * usable default (any non-null default, including '' or 0, fills the
	 * column safely). Nullability flags differ per driver (YES/NO strings
	 * vs sqlite pragma 0/1), normalized here.
	 *
	 * @param array<string, mixed> $col
	 */
	private static function crudIsRequiredColumn(array $col): bool {
		$n = $col['n'] ?? null;

		if (defined('DB_ENGINE') && DB_ENGINE === 'sqlite') {
			$nullable = ($n == 0);
		} else {
			$nullable = in_array($n, ['YES', 'yes', true, 1, '1'], true);
		}

		if ($nullable) {
			return false;
		}

		return !array_key_exists('d', $col) || $col['d'] === null;
	}

	/**
	 * Wrap scalar values for declared ARRAY IN params in a one-item list, so
	 * delete with {"post_id": 5} or a single taxonomy id just works instead
	 * of failing inside SQL iteration. A single row object for a ROWS list
	 * param wraps the same way (one assoc becomes a one-row list). Arrays
	 * and objects pass through untouched; only real scalars are wrapped.
	 *
	 * @param list<array{name: string, type: string}> $procParams
	 * @param array<array-key, mixed>                 $params
	 *
	 * @return array<string, mixed>
	 */
	private static function crudCoerceArrayParams(array $procParams, array $params): array {
		foreach ($procParams as $p) {
			$name = $p['name'];

			if (!isset($params[$name])) {
				continue;
			}

			$type = strtoupper($p['type']);

			if ($type === 'ARRAY' && is_scalar($params[$name])) {
				$params[$name] = [$params[$name]];
			} elseif ($type === 'ROWS' && is_array($params[$name]) && !array_is_list($params[$name])) {
				$params[$name] = [$params[$name]];
			}
		}

		return $params;
	}

	/**
	 * Validate params against the procedure contract before any write.
	 *
	 * Missing must-supply params, misshapen rows and unknown row keys are
	 * rejected here with guidance instead of dying deep in SQL (silent
	 *
	 * @FILTER drops, NULL-bound filters) or corrupting data (stale ids in
	 * auto-managed keys). Unknown keys on edit rows are checked leniently
	 * against all table columns, since echoing keys back is harmless there;
	 * on add only accepted fields pass. Row required_fields apply to add
	 * only - edits merge, so partial rows are fine. Returns a guidance
	 * message when the call must not proceed, null otherwise.
	 *
	 * @param string                                                                                                                                  $model
	 * @param string                                                                                                                                  $operation
	 * @param list<array{name: string, type: string, required?: bool, fields?: list<string>, required_fields?: list<string>, columns?: list<string>}> $procParams
	 * @param array<array-key, mixed>                                                                                                                 $params
	 */
	private static function crudValidateParams(string $model, string $operation, array $procParams, array $params): ?string {
		foreach ($procParams as $p) {
			$name = $p['name'];

			if ($name === '' || empty($p['required'])) {
				continue;
			}

			if (!array_key_exists($name, $params) || $params[$name] === null || $params[$name] === '') {
				return "Missing required param '$name' for crud $operation on model '$model'. " .
					'Omit optional params you have no value for instead of sending empty strings; ' .
					"call crud_models with {\"model\":\"$model\"} for the full contract.";
			}
		}

		foreach ($procParams as $p) {
			$name = $p['name'];
			$type = strtoupper($p['type']);

			if ($name === '' || ($type !== 'ROW' && $type !== 'ROWS') || !array_key_exists($name, $params)) {
				continue;
			}

			$value = $params[$name];

			if (!is_array($value)) {
				$want = $type === 'ROWS' ? 'a list of row objects' : 'a single row object';

				return "Invalid '$name' for crud $operation on model '$model': must be $want, got " . gettype($value) . '. ' .
					"Call crud_models with {\"model\":\"$model\"} for the full contract.";
			}

			if ($type === 'ROW' && array_is_list($value)) {
				return "Invalid '$name' for crud $operation on model '$model': must be a single row object, not a list. " .
					"Call crud_models with {\"model\":\"$model\"} for the full contract.";
			}

			$rows   = $type === 'ROWS' ? array_values($value) : [$value];
			$fields = $p['fields'] ?? null;
			$strict = $operation === 'add';
			$known  = $strict ? $fields : ($p['columns'] ?? $fields);

			foreach ($rows as $row) {
				if (!is_array($row)) {
					return "Invalid '$name' for crud $operation on model '$model': each row must be an object. " .
						"Call crud_models with {\"model\":\"$model\"} for the full contract.";
				}

				if (is_array($known)) {
					foreach (array_keys($row) as $key) {
						if (is_string($key) && !in_array($key, $known, true)) {
							return self::crudUnknownFieldMessage($model, $operation, $name, (string)$key, $known, $fields, $p['columns'] ?? $fields);
						}
					}
				}

				if ($strict && !empty($p['required_fields'])) {
					$absent = array_diff($p['required_fields'], array_keys($row));

					//the tool slugifies name into slug on add, so a
					//missing slug is satisfied by a present name
					if ($absent !== [] && in_array('slug', $absent, true) && !empty($row['name'])) {
						$absent = array_diff($absent, ['slug']);
					}

					if ($absent !== []) {
						return "Missing required field(s) '" . implode(', ', $absent) . "' in '$name' rows for crud $operation on model '$model'. " .
							'Accepted fields: ' . implode(', ', (array)$fields) . '. ' .
							"Call crud_models with {\"model\":\"$model\"} for the full contract.";
					}
				}
			}
		}

		return null;
	}

	/**
	 * Guidance message for an unknown row key: auto-managed keys must be
	 * omitted, otherwise suggest the closest accepted field.
	 *
	 * @param string            $model
	 * @param string            $operation
	 * @param string            $param
	 * @param string            $key
	 * @param list<string>|null $known
	 * @param list<string>|null $fields
	 * @param list<string>|null $columns
	 */
	private static function crudUnknownFieldMessage(string $model, string $operation, string $param, string $key, ?array $known, ?array $fields, ?array $columns = null): string {
		if (is_array($fields) && !in_array($key, $fields, true) && in_array($key, $columns ?? [], true)) {
			return "Invalid field '$key' in '$param' rows for crud $operation on model '$model': auto-managed keys must be omitted, the procedure supplies them. " .
				'Accepted fields: ' . implode(', ', $fields) . '. ' .
				"Call crud_models with {\"model\":\"$model\"} for the full contract.";
		}

		$best     = null;
		$bestDist = 4;

		if (is_array($known)) {
			foreach ($known as $candidate) {
				$dist = levenshtein($key, (string)$candidate);

				if ($dist < $bestDist) {
					$bestDist = $dist;
					$best     = $candidate;
				}
			}
		}

		$hint = ($best !== null) ? " Did you mean '$best'?" : '';
		$list = is_array($known) ? (' Accepted fields: ' . implode(', ', $known) . '.') : '';

		return "Unknown field '$key' in '$param' rows for crud $operation on model '$model'." . $hint . $list . ' ' .
			"Call crud_models with {\"model\":\"$model\"} for the full contract.";
	}

	/**
	 * Verify the add/edit record shape: the record must sit under the first
	 * ARRAY IN parameter (conventionally the model name: post, product,
	 * currency, ...; exceptions like filter->attribute or
	 * order_shipment->shipment follow the same rule via their own first
	 * ARRAY param). Returns a guidance message with a working example when
	 * the shape is wrong, null when the call may proceed. Read operations
	 * and procedures without ARRAY params are never rejected here.
	 *
	 * @param string                                  $model
	 * @param string                                  $operation
	 * @param list<array{name: string, type: string}> $procParams
	 * @param array<array-key, mixed>                 $params
	 */
	private static function crudCheckRecordParam(string $model, string $operation, array $procParams, array $params): ?string {
		if (!in_array($operation, ['add', 'edit'], true)) {
			return null;
		}

		//record params are typed ROW (single) or ROWS (list); some older
		//models type the record ARRAY, in which case scalar id lists
		//(taxonomy_item_id, site_id, ...) must not match - they end in _id
		$recordKey = null;

		foreach ($procParams as $p) {
			$type = strtoupper($p['type']);
			$name = $p['name'];

			if ($name === '' || ($type !== 'ROW' && $type !== 'ROWS' && $type !== 'ARRAY')) {
				continue;
			}

			if ($type === 'ARRAY' && preg_match('/_id$/', $name)) {
				continue;
			}

			$recordKey = $name;

			break;
		}

		if ($recordKey === null || (isset($params[$recordKey]) && is_array($params[$recordKey]))) {
			return null;
		}

		$example = ['model' => $model, 'operation' => $operation, 'params' => [$recordKey => ['slug' => 'hello', 'status' => 1]]];

		foreach ($procParams as $p) {
			if ($p['name'] === $model . '_content') {
				$example['params'][$model . '_content'] = [['name' => 'Hello', 'content' => '...', 'language_id' => 1]];

				break;
			}
		}

		return "Invalid params for crud $operation on model '$model': the record must be nested under the '$recordKey' key, not passed as flat fields. " .
			'Example: ' . json_encode($example) . '. ' .
				"Use crud_models with {\"model\":\"$model\"} for every accepted parameter (language_id/site_id default to 1); " .
				"record field names are table columns - inspect them read-only via database_query, e.g. {\"query\":\"DESCRIBE $model\"}.";
	}

	/**
	 * List available CRUD model names (basenames of admin/sql/mysqli/*.sql).
	 *
	 * @return list<string>
	 */
	private static function crudModels(): array {
		$models = [];

		foreach (glob(DIR_ROOT . '/admin/sql/mysqli/*.sql') ?: [] as $file) {
			$models[] = basename($file, '.sql');
		}

		sort($models);

		return $models;
	}

	/**
	 * admin tool: unified dispatcher for subsystem CRUD stubs.
	 *
	 * Routes to the themes/plugins/media/cron/cache stubs by target, passing
	 * all other arguments through. Keeps the tool registry small while each
	 * stub keeps its own per-operation validation.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public function toolAdmin(array $args): array {
		$target = trim((string)($args['target'] ?? ''));

		//media stays callable here as a deprecated alias (top-level media
		//tool is advertised instead); the admin schema no longer lists it.
		$stubs = [
			'themes'  => 'toolThemes',
			'plugins' => 'toolPlugins',
			'media'   => 'toolMedia',
			'cron'    => 'toolCron',
			'cache'   => 'toolCache',
		];

		if (!isset($stubs[$target])) {
			throw new \InvalidArgumentException("Invalid target '$target' for tool 'admin'. Allowed: " . implode(', ', array_keys($stubs)));
		}

		$method = $stubs[$target];
		$result = $this->$method($args);

		return ['target' => $target] + $result;
	}

	/**
	 * Search the marketplace feed for themes or plugins.
	 *
	 * @param class-string         $class     Themes/Plugins extension class
	 * @param string               $kind      theme|plugin (selects slug argument and result key)
	 * @param array<string, mixed> $args
	 * @param array<string, mixed> $installed locally installed items keyed by slug
	 *
	 * @return array<string, mixed>
	 */
	private function marketSearch(string $class, string $kind, array $args, array $installed): array {
		$listKey = $kind . 's'; // themes|plugins
		$query   = [];

		foreach (['search', 'category'] as $key) {
			$value = trim((string)($args[$key] ?? ''));

			if ($value !== '') {
				$query[$key] = $value;
			}
		}

		$slug = trim((string)($args[$kind] ?? ''));

		if ($slug !== '') {
			$query['slug'] = $slug;
		}

		$query['start'] = max(1, (int)($args['start'] ?? 1));
		$query['limit'] = max(1, min(50, (int)($args['limit'] ?? 10)));

		$result = $class::getMarketList($query);

		if ($result === []) {
			throw new \RuntimeException('Error contacting the marketplace feed');
		}

		$items = $result[$listKey] ?? [];

		foreach ($items as &$item) {
			$item['installed'] = isset($installed[$item['slug'] ?? '']);
		}
		unset($item);

		return ['operation' => 'market', $listKey => $items, 'count' => $result['count'] ?? count($items)];
	}

	/**
	 * Install a theme or plugin from the marketplace by slug.
	 *
	 * Mirrors the admin Market controllers: look the slug up in the market
	 * feed, download the zip, install it and clear the list cache. Requires
	 * config key mcp.tools.db_write.
	 *
	 * @param class-string $class  Themes/Plugins extension class
	 * @param string       $kind   theme|plugin
	 * @param string       $slug
	 * @param int          $siteId
	 *
	 * @return array<string, mixed>
	 */
	private function marketInstall(string $class, string $kind, string $slug, int $siteId): array {
		if (config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow install on $kind $slug.");
		}

		$listKey = $kind . 's'; // themes|plugins
		$list    = $class::getMarketList(['slug' => $slug]);

		if ($list === []) {
			throw new \RuntimeException('Error contacting the marketplace feed');
		}

		$items = $list[$listKey] ?? [];

		if (!$items || !isset($items[0])) {
			throw new \RuntimeException("Extension '$slug' not found in the marketplace");
		}

		$info = $items[0];

		if (empty($info['download_link'])) {
			throw new \RuntimeException("Extension '$slug' has no download link in the marketplace");
		}

		$downloadLink = $class::marketUrl() . $info['download_link'];
		$tempFile     = $class::download($downloadLink);

		if (!$tempFile) {
			throw new \RuntimeException("Error downloading '$slug' from $downloadLink");
		}

		try {
			//themes skip zip validation like the admin theme market controller
			$installed = $kind === 'theme' ? $class::install($tempFile, $slug, false) : $class::install($tempFile, $slug);

			if (!$installed) {
				throw new \RuntimeException("Error installing '$slug'");
			}

			\Vvveb\System\CacheManager::clearObjectCache('vvveb', "{$listKey}_list_$siteId");
		} finally {
			@unlink($tempFile);
		}

		return ['operation' => 'install', $kind => $slug, 'name' => $info['name'] ?? $slug, 'success' => true];
	}

	/**
	 * Slim extension rows for list operations: slug, name and description
	 * only. Full headers (author, license, version, paths) stay available
	 * through the get operation. Map keys preserved. Pure, tested.
	 *
	 * @param array<array-key, mixed> $items
	 *
	 * @return array<string, array{slug: string, name: string, description: string, active?: bool}>
	 */
	private static function slimExtensionList(array $items): array {
		$out = [];
		foreach ($items as $slug => $item) {
			if (!is_array($item)) {
				continue;
			}
			$slug = (string)($item['slug'] ?? $slug);
			$row  = [
				'slug'        => $slug,
				'name'        => (string)($item['name'] ?? $slug),
				'description' => (string)($item['description'] ?? ''),
			];
			if (!empty($item['active'])) {
				$row['active'] = true;
			}
			$out[$slug] = $row;
		}

		return $out;
	}

	/**
	 * Themes stub (via admin tool): CRUD for CMS themes (public/themes/<slug>).
	 *
	 * Thin wrappers around Themes::getList and Sites::setTheme: list/get are
	 * always allowed; activate/delete require config key mcp.tools.db_write.
	 * Delete refuses the active theme.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	private function toolThemes(array $args): array {
		$operation = trim((string)($args['operation'] ?? ''));
		$rawTheme  = trim((string)($args['theme'] ?? ''));
		$siteId    = (int)($args['site_id'] ?? $this->global['site_id'] ?? 1);

		$allowedOps = ['list', 'get', 'market', 'activate', 'install', 'delete'];

		if (!in_array($operation, $allowedOps, true)) {
			throw new \InvalidArgumentException("Invalid operation '$operation' for tool 'themes'. Allowed: " . implode(', ', $allowedOps));
		}

		if (in_array($operation, ['get', 'activate', 'install', 'delete'], true) && $rawTheme === '') {
			throw new \InvalidArgumentException("Missing required argument 'theme' for tool 'themes' operation '$operation'");
		}

		if ($rawTheme !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $rawTheme)) {
			throw new \InvalidArgumentException("Invalid theme name: '$rawTheme'");
		}

		$theme   = \Vvveb\sanitizeFileName(basename($rawTheme));
		$themes  = \Vvveb\System\Extensions\Themes::getList($siteId, false);
		$active  = \Vvveb\System\Sites::getTheme($siteId);

		if ($operation === 'list') {
			$slim = self::slimExtensionList($themes);

			return ['operation' => $operation, 'themes' => $slim, 'active' => $active, 'count' => count($slim)];
		}

		if (in_array($operation, ['get', 'activate', 'delete'], true) && !isset($themes[$theme])) {
			throw new \McpToolError("Unknown theme: '$theme'. Available themes: " . implode(', ', array_keys($themes)));
		}

		if ($operation === 'get') {
			return ['operation' => $operation, 'theme' => $theme, 'info' => $themes[$theme], 'active' => $active];
		}

		if ($operation === 'market') {
			return $this->marketSearch(\Vvveb\System\Extensions\Themes::class, 'theme', $args, $themes);
		}

		if (config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $operation on theme $theme.");
		}

		if ($operation === 'activate') {
			if (!\Vvveb\System\Sites::setTheme($siteId, $theme, 'index.html')) {
				throw new \RuntimeException("Error activating theme '$theme'");
			}

			\Vvveb\System\CacheManager::clearObjectCache('site');
			\Vvveb\System\CacheManager::clearFrontend();
			\Vvveb\System\CacheManager::clearCompiledFiles();
			\Vvveb\System\CacheManager::clearPageCache();

			return ['operation' => $operation, 'theme' => $theme, 'active' => $theme, 'success' => true];
		}

		if ($operation === 'install') {
			return $this->marketInstall(\Vvveb\System\Extensions\Themes::class, 'theme', $theme, $siteId);
		}

		if ($theme === $active) {
			throw new \McpToolError("Refusing to delete the active theme '$theme'. Activate another theme first.");
		}

		$dir = DIR_THEMES . $theme;

		if (!is_dir($dir) || !rrmdir($dir)) {
			throw new \RuntimeException("Error removing theme '$theme'");
		}

		\Vvveb\System\Extensions\Themes::clearThemesCache($siteId);

		return ['operation' => $operation, 'theme' => $theme, 'success' => true];
	}

	/**
	 * Plugins stub (via admin tool): CRUD for CMS plugins (plugins/<slug>).
	 *
	 * Thin wrappers around Plugins::getList/activate/deactivate/uninstall:
	 * list/get are always allowed; activate/deactivate/uninstall require
	 * config key mcp.tools.db_write.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	private function toolPlugins(array $args): array {
		$operation = trim((string)($args['operation'] ?? ''));
		$plugin    = trim((string)($args['plugin'] ?? ''));
		$category  = trim((string)($args['category'] ?? ''));
		$siteId    = (int)($args['site_id'] ?? $this->global['site_id'] ?? 1);

		$allowedOps = ['list', 'get', 'market', 'activate', 'deactivate', 'install', 'uninstall'];

		if (!in_array($operation, $allowedOps, true)) {
			throw new \InvalidArgumentException("Invalid operation '$operation' for tool 'plugins'. Allowed: " . implode(', ', $allowedOps));
		}

		if (!in_array($operation, ['list', 'market'], true) && $plugin === '') {
			throw new \InvalidArgumentException("Missing required argument 'plugin' for tool 'plugins' operation '$operation'");
		}

		if ($plugin !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $plugin)) {
			throw new \InvalidArgumentException("Invalid plugin name: '$plugin'");
		}

		$plugins = \Vvveb\System\Extensions\Plugins::getList($siteId, $category !== '' ? $category : false, false);

		if ($operation === 'list') {
			$slim = self::slimExtensionList($plugins);

			return ['operation' => $operation, 'plugins' => $slim, 'count' => count($slim)];
		}

		if (!in_array($operation, ['market', 'install'], true) && !isset($plugins[$plugin])) {
			throw new \McpToolError("Unknown plugin: '$plugin'. Available plugins: " . implode(', ', array_keys($plugins)));
		}

		if ($operation === 'get') {
			return ['operation' => $operation, 'plugin' => $plugin, 'info' => $plugins[$plugin]];
		}

		if ($operation === 'market') {
			return $this->marketSearch(\Vvveb\System\Extensions\Plugins::class, 'plugin', $args, $plugins);
		}

		if (config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $operation on plugin $plugin.");
		}

		if ($operation === 'install') {
			return $this->marketInstall(\Vvveb\System\Extensions\Plugins::class, 'plugin', $plugin, $siteId);
		}

		if ($operation === 'activate') {
			if (!\Vvveb\System\Extensions\Plugins::activate($plugin, $siteId)) {
				throw new \RuntimeException("Error activating plugin '$plugin'");
			}

			\Vvveb\System\CacheManager::clearObjectCache('admin-menu');
			\Vvveb\System\CacheManager::clearCompiledFiles();
			\Vvveb\System\CacheManager::clearPageCache();

			return ['operation' => $operation, 'plugin' => $plugin, 'success' => true];
		}

		if ($operation === 'deactivate') {
			if (!\Vvveb\System\Extensions\Plugins::deactivate($plugin, $siteId)) {
				throw new \RuntimeException("Error deactivating plugin '$plugin'");
			}

			\Vvveb\System\CacheManager::clearObjectCache('admin-menu');
			\Vvveb\System\CacheManager::clearCompiledFiles();
			\Vvveb\System\CacheManager::clearPageCache();

			return ['operation' => $operation, 'plugin' => $plugin, 'success' => true];
		}

		if (!\Vvveb\System\Extensions\Plugins::uninstall($plugin, $siteId)) {
			throw new \RuntimeException("Error removing plugin '$plugin'");
		}

		return ['operation' => $operation, 'plugin' => $plugin, 'success' => true];
	}

	/**
	 * Normalize caller-supplied media metadata into model rows.
	 *
	 * Accepts a single flat row (e.g. `['caption' => 'x']`), a list of
	 * rows, or a language-keyed map (e.g. `[1 => ['caption' => 'x']]`)
	 * and returns a list of rows each carrying a language_id and a name
	 * default derived from the file base name via humanReadable.
	 *
	 * @param string                  $file    Media-relative file path.
	 * @param array<array-key, mixed> $content Raw metadata.
	 *
	 * @return list<array<string, mixed>> Normalized rows.
	 */
	private function normalizeMediaContentRows(string $file, array $content): array {
		$languageId  = $this->global['language_id'] ?? $this->global['default_language_id'] ?? 1;
		$defaultName = humanReadable((string)pathinfo(str_replace('\\', '/', $file), PATHINFO_FILENAME));

		$isRowList = false;

		foreach ($content as $value) {
			if (is_array($value)) {
				$isRowList = true;

				break;
			}
		}

		if ($isRowList) {
			$rows = [];

			foreach ($content as $key => $row) {
				if (!is_array($row)) {
					continue;
				}

				if (!isset($row['language_id'])) {
					$row['language_id'] = (is_int($key) && $key > 0 && $key < 10000) ? $key : $languageId;
				}

				if (!isset($row['name']) || $row['name'] === '') {
					$row['name'] = $defaultName;
				}

				$rows[] = $row;
			}

			if ($rows !== []) {
				return $rows;
			}
		}

		if (!$isRowList) {
			$row = $content;

			if (!isset($row['language_id'])) {
				$row['language_id'] = $languageId;
			}

			if (!isset($row['name']) || $row['name'] === '') {
				$row['name'] = $defaultName;
			}

			return [$row];
		}

		return [['name' => $defaultName, 'language_id' => $languageId]];
	}

	/**
	 * Upsert a media library metadata record by file path.
	 *
	 * Shared by the save operation and uploads carrying metadata. New
	 * records fall back to the file base name run through humanReadable
	 * when the caller supplies no explicit name.
	 *
	 * @param string               $file    Media-relative file path.
	 * @param array<string, mixed> $content Metadata key/value pairs.
	 * @param string|null          $type    MIME type for new records.
	 *
	 * @return mixed[] Model result.
	 */
	private function upsertMediaContentRecord(string $file, array $content, ?string $type = null): array {
		$media    = \Vvveb\model('media_content');
		$existing = $media->get(['file' => $file] + $this->global);
		$rows     = $this->normalizeMediaContentRows($file, $content);

		if ($existing) {
			$result = $media->edit(['media_id' => $existing['media_id'], 'media_content' => $rows, 'media' => []]);
		} else {
			$mediaRow = ['file' => $file];

			if ($type !== null && $type !== '') {
				$mediaRow['type'] = $type;
			}

			$result = $media->add(['media' => $mediaRow, 'media_content' => $rows]);
		}

		return $result ?? [];
	}

	/**
	 * Cap for decoded upload bytes: the lower of the MCP cap and the server
	 * upload_max_filesize, checked on the base64 length before allocating.
	 */
	private function mediaUploadByteCap(): int {
		try {
			$serverMax = \Vvveb\parseQuantity((string)ini_get('upload_max_filesize'));
		} catch (\Throwable $e) {
			$serverMax = 0;
		}

		if ($serverMax > 0) {
			return min(self::MEDIA_UPLOAD_MAX_BYTES, (int)$serverMax);
		}

		return self::MEDIA_UPLOAD_MAX_BYTES;
	}

	/**
	 * Store a base64 upload: decode to temp, run the shared file core for
	 * the full validation chain, then register the library metadata record
	 * (file base name via humanReadable, caller content merged on top).
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 *
	 * @return mixed[] Upload result plus metadata record result.
	 */
	private function toolMediaUpload(array $args): array {
		$fileName = trim((string)($args['file_name'] ?? ''));
		$data     = (string)($args['data'] ?? '');
		$path     = trim((string)($args['path'] ?? ''));

		if ($fileName === '') {
			throw new \InvalidArgumentException("Missing required argument 'file_name' for tool 'media' operation 'upload'");
		}

		if ($data === '') {
			throw new \InvalidArgumentException("Missing required argument 'data' (base64 file bytes) for tool 'media' operation 'upload'");
		}

		$cap = $this->mediaUploadByteCap();

		if ((int)(strlen($data) * 3 / 4) > $cap) {
			throw new \McpToolError('Upload exceeds the media tool cap, use a smaller file or url import.');
		}

		$bytes = base64_decode($data, true);

		if ($bytes === false) {
			throw new \InvalidArgumentException("Invalid argument 'data' for tool 'media' operation 'upload': not valid base64");
		}

		if (strlen($bytes) > $cap) {
			throw new \McpToolError('Upload exceeds the media tool cap, use a smaller file or url import.');
		}

		$tmp = tempnam(sys_get_temp_dir(), 'mcp-upload-');

		if ($tmp === false || file_put_contents($tmp, $bytes) === false) {
			@unlink((string)$tmp);

			throw new \RuntimeException('Could not stage upload for processing.');
		}

		$byteLen = strlen($bytes);
		unset($bytes, $data);

		try {
			$stored = $this->storeMediaFile($tmp, $fileName, $path, [
				'overwrite' => (bool)($args['overwrite'] ?? false),
				'size'      => $byteLen,
			]);
		} finally {
			@unlink($tmp);
		}

		if (!$stored['success']) {
			return $stored;
		}

		$content = $args['media_content'] ?? null;

		if (is_array($content) && $content !== []) {
			$stored['record'] = $this->upsertMediaContentRecord($stored['file'], $content);
		} else {
			$stored['record'] = $this->createMediaRecord($stored['file']);
		}

		return $stored;
	}

	/**
	 * Fetch a remote file by URL into the media library: download with
	 * redirect/SSRF/size/type guards to temp, then run the exact same tail
	 * as upload (storeMediaFile validation chain plus metadata record).
	 * Sits behind the media db_write gate like upload.
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 *
	 * @return mixed[] Stored-file result plus source info and optional record.
	 */
	private function toolMediaFetch(array $args): array {
		$url      = trim((string)($args['url'] ?? ''));
		$path     = trim((string)($args['path'] ?? ''));
		$given    = trim((string)($args['file_name'] ?? ''));

		if ($url === '') {
			throw new \InvalidArgumentException("Missing required argument 'url' for tool 'media' operation 'fetch'");
		}

		$storedCap   = $this->mediaUploadByteCap();
		$transferCap = $storedCap * 4;
		$dl          = $this->mediaFetchDownload($url, $transferCap, $storedCap);

		$fileName = self::mediaFetchFileName($given !== '' ? $given : null, $dl['url'], $dl['contentType']);

		//a missing sub-path used to die later in storeMediaFile with a bare
		//'Invalid upload!': create it here so fetch names its own folder
		$this->ensureMediaPath($path);

		try {
			$stored = $this->storeMediaFile($dl['tmp'], $fileName, $path, [
				'overwrite' => (bool)($args['overwrite'] ?? false),
				'size'      => $dl['size'],
			]);
		} finally {
			@unlink($dl['tmp']);
		}

		if (!$stored['success']) {
			return $stored + ['source_url' => $dl['url']];
		}

		$stored['source_url']   = $dl['url'];
		$stored['content_type'] = $dl['contentType'];

		if (!empty($dl['note'])) {
			$stored['note'] = $dl['note'];
		}

		$content = $args['media_content'] ?? null;

		if (is_array($content) && $content !== []) {
			$stored['record'] = $this->upsertMediaContentRecord($stored['file'], $content, $dl['contentType']);
		} else {
			$stored['record'] = $this->createMediaRecord($stored['file'], $dl['contentType']);
		}

		return $stored;
	}

	/**
	 * Create a missing media sub-path for fetch (nested, sandboxed).
	 * storeMediaFile only realpaths existing dirs, so without this a
	 * first-time folder fails with a bare 'Invalid upload!'. Throws
	 * McpToolError with the actual reason. Pure filesystem, tested.
	 *
	 * @param string $path
	 */
	private function ensureMediaPath(string $path): void {
		$path = trim(str_replace('\\', '/', $path), '/');

		if ($path === '') {
			return;
		}

		$mediaDir = rtrim($this->dirMedia, DS) . DS;
		$current  = $mediaDir;

		foreach (explode('/', $path) as $segment) {
			$segment = sanitizeFileName($segment);

			if ($segment === '' || $segment === '.' || $segment === '..') {
				throw new \McpToolError("Media fetch refused: invalid path '$path'.");
			}

			$current .= $segment . DS;

			if (!is_dir($current) && !@mkdir($current, 0755)) {
				throw new \McpToolError("Media fetch cannot create folder '$path'.");
			}
		}

		$real = realpath($mediaDir . str_replace('/', DS, $path));

		if ($real === false || !str_starts_with(rtrim($real, DS) . DS, $mediaDir)) {
			throw new \McpToolError("Media fetch refused: path '$path' escapes the media library.");
		}
	}

	/**
	 * Derive the library file name for a fetch: explicit file_name wins,
	 * else the URL path basename, with the extension mapped from the
	 * response Content-Type when missing. Pure (no IO) for testability.
	 *
	 * @param ?string $given
	 * @param string  $url
	 * @param string  $contentType
	 */
	private static function mediaFetchFileName(?string $given, string $url, string $contentType): string {
		if ($given !== null && $given !== '') {
			return $given;
		}

		$base = (string)(basename((string)parse_url($url, PHP_URL_PATH)) ?: '');
		$base = trim(urldecode($base));

		if ($base === '' || $base === '.' || str_contains($base, "\0")) {
			$base = 'fetch';
		}

		if (!str_contains(pathinfo($base, PATHINFO_BASENAME), '.')) {
			$map = [
				'image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif',
				'image/webp' => '.webp', 'image/svg+xml' => '.svg', 'image/avif' => '.avif',
				'image/bmp'  => '.bmp', 'image/x-icon' => '.ico',
			];
			$base .= $map[strtolower(trim($contentType))] ?? '';
		}

		return $base;
	}

	/**
	 * Downscale an oversized raster image to fit the stored cap instead
	 * of refusing it. Returns [tmp, size, note]. GIFs refuse (resizing
	 * flattens animation, silently destroying intent); vectors and
	 * non-images never reach here. Pure filesystem + resizer, tested.
	 *
	 * @param string $tmp
	 * @param string $type
	 * @param int    $cap
	 * @param string $url
	 *
	 * @return array{0: string, 1: int, 2: string|null}
	 */
	private function mediaFetchFitImage(string $tmp, string $type, int $cap, string $url): array {
		if ($type === 'image/gif') {
			@unlink($tmp);

			throw new \McpToolError("Media fetch of '$url' exceeds the media tool cap and is animated GIF - resize would flatten it, use a smaller file.");
		}

		$maxDim = 2048;

		try {
			$configured = config('mcp.tools.image_max_dim', 2048);
			if (is_numeric($configured) && (int)$configured >= 256) {
				$maxDim = min(4096, (int)$configured);
			}
		} catch (\Throwable $e) {
		}

		$info = @getimagesize($tmp);
		if (!is_array($info) || $info[0] <= 0 || $info[1] <= 0) {
			@unlink($tmp);

			throw new \McpToolError("Media fetch of '$url' exceeds the media tool cap, use a smaller file.");
		}

		[$w, $h] = [(int)$info[0], (int)$info[1]];

		//already small enough in pixels: same-dim quality pass instead of
		//a pointless upscale (tiny noisy files can still exceed the cap)
		$small = max($w, $h) <= $maxDim;

		if ($w * $h > 50000000) {
			@unlink($tmp);

			throw new \McpToolError("Media fetch of '$url' is {$w}x{$h}px, too many pixels to process safely - use a smaller file.");
		}

		$out = $tmp . '.fit';

		try {
			if ($small) {
				$tw      = $w;
				$th      = $h;
				$quality = 70;
			} else {
				$tw      = $w >= $h ? $maxDim : 0;
				$th      = $w >= $h ? 0 : $maxDim;
				$quality = 82;
			}
			if (!\Vvveb\System\Images::resize($tmp, $out, $tw, $th, 'r', $quality)) {
				@unlink($tmp);

				throw new \McpToolError("Media fetch of '$url' exceeds the media tool cap, use a smaller file.");
			}
			$newSize = filesize($out) ?: 0;
			if ($newSize <= 0 || $newSize > $cap) {
				@unlink($tmp);
				@unlink($out);

				throw new \McpToolError("Media fetch of '$url' still exceeds the media tool cap after resize, use a smaller file.");
			}
		} catch (\McpToolError $e) {
			throw $e;
		} catch (\Throwable $e) {
			@unlink($tmp);
			@unlink($out);

			throw new \McpToolError("Media fetch of '$url' exceeds the media tool cap, use a smaller file.");
		}

		@unlink($tmp);
		$note = "Resized {$w}x{$h} to fit the media tool cap.";

		try {
			$fit = @getimagesize($out);
			if (is_array($fit)) {
				$note = "Resized {$w}x{$h} to {$fit[0]}x{$fit[1]} to fit the media tool cap.";
			}
		} catch (\Throwable $e) {
		}

		return [$out, $newSize, $note];
	}

	/**
	 * Download a URL to temp for media fetch, with the guards upload gets
	 * plus web ones: per-hop SSRF validation (DNS-rebinding safe via
	 * CURLOPT_RESOLVE pinning, like Vvveb\download), manual redirect chain
	 * (max 4, each hop re-validated), Content-Length pre-check and an
	 * in-transfer cap, connect/total timeouts, and an image Content-Type
	 * allowlist. All routine failures throw McpToolError (no log flood).
	 *
	 * A test seam: config mcp.tools.fetch_allow_private (default false)
	 * skips the private-IP refusal so the suite can run a local fixture
	 * server. Never enable outside tests.
	 *
	 * @param string $url
	 * @param int    $transferCap
	 * @param int    $storedCap
	 *
	 * @return array{tmp: string, url: string, contentType: string, size: int, note: string|null}
	 */
	private function mediaFetchDownload(string $url, int $transferCap, int $storedCap): array {
		if (!function_exists('curl_init')) {
			throw new \McpToolError("Media fetch needs the curl extension to download '$url'.");
		}

		$allowPrivate = config('mcp.tools.fetch_allow_private', false) === true;
		$current      = $url;

		for ($hop = 0; $hop <= 4; $hop++) {
			$ips = \Vvveb\validateUrl($current);

			if (!$ips && !$allowPrivate) {
				throw new \McpToolError("Media fetch refused '$current': URL must be public http(s) on port 80/443 with no private or reserved IP.");
			}

			$tmp = tempnam(sys_get_temp_dir(), 'mcp-fetch-');

			if ($tmp === false) {
				throw new \RuntimeException('Could not stage fetch for processing.');
			}

			$fh = fopen($tmp, 'w');

			if ($fh === false) {
				@unlink($tmp);

				throw new \RuntimeException('Could not stage fetch for processing.');
			}

			$headers = [];
			$ch      = curl_init($current);
			curl_setopt($ch, CURLOPT_FILE, $fh);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			//no FAILONERROR: error bodies are inspected below (bot-check
			//detection needs the bytes); status is checked explicitly
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.9']);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $line) use (&$headers) {
				$parts = explode(':', $line, 2);

				if (count($parts) === 2) {
					$headers[strtolower(trim($parts[0]))] = trim($parts[1]);
				}

				return strlen($line);
			});
			//transfer cap is 4x the stored cap: oversized images finish
			//downloading so they can be resized below; absurd transfers
			//still abort here before eating memory
			curl_setopt($ch, CURLOPT_NOPROGRESS, false);
			curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, fn ($ch, $dlTotal, $dlNow) => $dlNow > $transferCap ? 1 : 0);

			if ($ips) {
				curl_setopt($ch, CURLOPT_RESOLVE, $ips);
			}

			$ok    = curl_exec($ch);
			$code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$errno = curl_errno($ch);
			fclose($fh);

			//aborted by the progress cap (CURLE_ABORTED_BY_CALLBACK): the
			//transfer itself was fine, the file is just too big
			if (!$ok && $errno === 42) {
				@unlink($tmp);

				throw new \McpToolError('Fetched file exceeds the media tool cap, use a smaller file.');
			}

			//manual redirect: re-validate the next hop from scratch
			if ($ok && in_array($code, [301, 302, 303, 307, 308], true) && isset($headers['location'])) {
				@unlink($tmp);
				$next = trim($headers['location']);

				if ($next === '') {
					throw new \McpToolError("Media fetch of '$url' got an empty redirect.");
				}

				//resolve relative Location against the current URL (core,
				//RFC 3986: plain string-append gets directory resolution wrong)
				if (!preg_match('#^https?://#i', $next)) {
					$next = \Vvveb\resolveUrl($current, $next);
				}

				$current = $next;

				continue;
			}

			if (!$ok || $code !== 200) {
				$head = (string)@file_get_contents($tmp, false, null, 0, 4096);
				@unlink($tmp);
				if (\Vvveb\isChallengePage($head)) {
					throw new \McpToolError("Media fetch of '$url' is blocked by a bot check (HTTP $code) no script can pass - open the URL in your browser and upload the file, or choose another source.");
				}

				throw new \McpToolError("Media fetch of '$url' failed (HTTP $code).");
			}

			$size = filesize($tmp) ?: 0;

			if ($size <= 0) {
				@unlink($tmp);

				throw new \McpToolError('Fetched file is empty, use another source.');
			}

			$type    = strtolower(trim(explode(';', $headers['content-type'] ?? '')[0]));
			$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/avif', 'image/bmp', 'image/x-icon'];

			if (!in_array($type, $allowed, true)) {
				@unlink($tmp);

				throw new \McpToolError("Media fetch of '$url' refused: Content-Type '$type' is not a supported image type.");
			}

			$fitNote = null;

			if ($size > $storedCap) {
				[$tmp, $size, $fitNote] = $this->mediaFetchFitImage($tmp, $type, $storedCap, $url);
			}

			return ['tmp' => $tmp, 'url' => $current, 'contentType' => $type, 'size' => $size, 'note' => $fitNote];
		}

		@unlink($tmp);

		throw new \McpToolError("Media fetch of '$url' gave up after too many redirects.");
	}

	/**
	 * media tool: media library metadata and file operations.
	 *
	 * Top-level since its parameter set dwarfs the other admin stubs; also
	 * reachable via the admin tool (target media) as a deprecated alias.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{operation: string, result: mixed}
	 */
	public function toolMedia(array $args): array {
		$operation = trim((string)($args['operation'] ?? ''));
		$file      = trim((string)($args['file'] ?? ''));
		$content   = $args['content'] ?? null;
		$params    = $args['params'] ?? [];

		//scan/search report root-relative paths with a leading slash: strip
		//it back here so file args round-trip into DB lookups and stored
		//rows verbatim (stored format has no leading slash)
		foreach (['file', 'path', 'newfile'] as $slashKey) {
			if (isset($args[$slashKey]) && is_string($args[$slashKey])) {
				$args[$slashKey] = ltrim(trim($args[$slashKey]), '/');
			}
		}

		$file = (string)($args['file'] ?? '');

		$allowedOps = ['list', 'get', 'save', 'delete', 'upload', 'fetch', 'mkdir', 'rename', 'scan'];

		if (!in_array($operation, $allowedOps, true)) {
			throw new \InvalidArgumentException("Invalid operation '$operation' for tool 'media'. Allowed: " . implode(', ', $allowedOps));
		}

		if ($operation === 'scan') {
			$scanPath   = trim((string)($args['path'] ?? ''));
			$scanLevels = isset($args['levels']) ? max(1, min(3, (int)$args['levels'])) : 1;
			$scanSearch = trim((string)($args['search'] ?? ''));
			$scanLimit  = isset($args['limit']) ? max(1, min(200, (int)$args['limit'])) : 200;
			$scanStart  = isset($args['start']) ? max(0, (int)$args['start']) : 0;

			return ['operation' => $operation, 'result' => $this->listMedia($scanPath, $scanLevels, $scanSearch, '', $scanLimit, $scanStart)];
		}

		if (!is_array($params)) {
			throw new \InvalidArgumentException("Invalid params for tool 'media'. params must be an object with key/value pairs.");
		}

		$media = \Vvveb\model('media_content');

		if ($operation === 'list') {
			$result = $media->getAll($params + $this->global);

			return ['operation' => $operation, 'result' => $result ?? []];
		}

		if (in_array($operation, ['get', 'save'], true) && $file === '') {
			throw new \InvalidArgumentException("Missing required argument 'file' for tool 'media' operation '$operation'");
		}

		if ($operation === 'get') {
			$result = $media->getContent(['file' => $file] + $this->global);

			return ['operation' => $operation, 'result' => $result ?? []];
		}

		if (config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $operation on media.");
		}

		if ($operation === 'upload') {
			return ['operation' => $operation, 'result' => $this->toolMediaUpload($args)];
		}

		if ($operation === 'fetch') {
			return ['operation' => $operation, 'result' => $this->toolMediaFetch($args)];
		}

		if ($operation === 'mkdir') {
			$folder = trim((string)($args['folder'] ?? ''));
			$path   = trim((string)($args['path'] ?? ''));

			if ($folder === '') {
				throw new \InvalidArgumentException("Missing required argument 'folder' for tool 'media' operation 'mkdir'");
			}

			return ['operation' => $operation, 'result' => $this->createMediaFolder($path, $folder)];
		}

		if ($operation === 'rename') {
			$newfile = trim((string)($args['newfile'] ?? ''));
			$newname = trim((string)($args['newname'] ?? ''));

			if ($file === '') {
				throw new \InvalidArgumentException("Missing required argument 'file' for tool 'media' operation 'rename'");
			}

			if ($newfile === '' && $newname === '') {
				throw new \InvalidArgumentException("Missing required argument 'newfile' or 'newname' for tool 'media' operation 'rename'");
			}

			return ['operation' => $operation, 'result' => $this->renameMediaFile($file, $newfile, $newname, (bool)($args['duplicate'] ?? false))];
		}

		if ($operation === 'save') {
			if (!is_array($content) || $content === []) {
				throw new \InvalidArgumentException("Missing required argument 'content' for tool 'media' operation 'save'");
			}

			return ['operation' => $operation, 'result' => $this->upsertMediaContentRecord($file, $content)];
		}

		$mediaId = $args['media_id'] ?? null;

		if ($mediaId === null && $file === '') {
			throw new \InvalidArgumentException("Missing required argument 'media_id' or 'file' for tool 'media' operation 'delete'");
		}

		$options = [];

		if ($mediaId !== null) {
			$options['media_id'] = [(int)$mediaId];
		}

		if ($file !== '') {
			$options['file'] = [$file];
		}

		$result = $media->delete($options + $this->global);

		return ['operation' => $operation, 'result' => $result ?? []];
	}

	/**
	 * Cron stub (via admin tool): CRUD for CMS cron jobs.
	 *
	 * list is always allowed; run/run_all execute job callbacks and require
	 * config key mcp.tools.db_write.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	private function toolCron(array $args): array {
		$operation = trim((string)($args['operation'] ?? ''));
		$job       = trim((string)($args['job'] ?? ''));

		$allowedOps = ['list', 'run', 'run_all'];

		if (!in_array($operation, $allowedOps, true)) {
			throw new \InvalidArgumentException("Invalid operation '$operation' for tool 'cron'. Allowed: " . implode(', ', $allowedOps));
		}

		$jobs = [];

		foreach (\Vvveb\System\Cron::getCrons() as $priorities) {
			foreach ($priorities as $group) {
				foreach (array_keys($group) as $name) {
					$jobs[] = $name;
				}
			}
		}

		if ($operation === 'list') {
			return ['operation' => $operation, 'jobs' => $jobs, 'count' => count($jobs)];
		}

		if (config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $operation on cron.");
		}

		if ($operation === 'run') {
			if ($job === '') {
				throw new \InvalidArgumentException("Missing required argument 'job' for tool 'cron' operation 'run'");
			}

			if (!preg_match('/^[A-Za-z0-9_.-]+$/', $job)) {
				throw new \InvalidArgumentException("Invalid cron job name: '$job'");
			}

			if (!in_array($job, $jobs, true)) {
				throw new \McpToolError("Unknown cron job: '$job'. Available jobs: " . implode(', ', $jobs));
			}

			return ['operation' => $operation, 'job' => $job, 'result' => \Vvveb\System\Cron::run($job)];
		}

		return ['operation' => $operation, 'result' => \Vvveb\System\Cron::runAll()];
	}

	/**
	 * Cache stub (via admin tool): CRUD for CMS caches (mirrors admin Tools/Cache).
	 *
	 * Single clear operation with a target selector; requires config key
	 * mcp.tools.db_write.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array{operation: string, scope: string, success: bool}
	 */
	private function toolCache(array $args): array {
		$operation = trim((string)($args['operation'] ?? ''));
		$scope     = trim((string)($args['scope'] ?? 'all'));

		if ($operation !== 'clear') {
			throw new \InvalidArgumentException("Invalid operation '$operation' for tool 'cache'. Allowed: clear");
		}

		$scopes = ['all', 'template', 'page', 'database', 'frontend', 'image', 'model'];

		if (!in_array($scope, $scopes, true)) {
			throw new \InvalidArgumentException("Invalid scope '$scope' for cache. Allowed: " . implode(', ', $scopes));
		}

		if (config('mcp.tools.db_write', false) !== true) {
			throw new \McpToolError("Writes are disabled - enable mcp.tools.db_write to allow $operation on cache $scope.");
		}

		$host = trim((string)($args['host'] ?? ''));

		$success = match ($scope) {
			'all'      => \Vvveb\System\CacheManager::delete(),
			'template' => \Vvveb\System\CacheManager::clearCompiledFiles(),
			'page'     => \Vvveb\System\CacheManager::clearPageCache($host !== '' ? $host : null),
			'database' => \Vvveb\System\CacheManager::clearObjectCache(),
			'frontend' => \Vvveb\System\CacheManager::clearFrontend(),
			'image'    => \Vvveb\System\CacheManager::clearImageCache(),
			'model'    => \Vvveb\System\CacheManager::clearModelCache(),
		};

		return ['operation' => $operation, 'scope' => $scope, 'success' => (bool)$success];
	}

	/**
	 * str_replace_editor tool: unified filesystem editor (view/create/edit — multi-edit only).
	 *
	 * Wraps the native PHP StrReplaceEditor (mcp/str_replace_editor.php) with sandbox-aware
	 * path resolution via resolvePath()/toolRoot(). All paths are sandboxed inside the
	 * installation root. Only view, create and edit (multi-edit via edits[]) are exposed;
	 * single str_replace/insert are removed in favor of the unified multi-edit.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return string|array<string, mixed>
	 */
	public function toolStrReplaceEditor(array $args): mixed {
		$command = (string)($args['command'] ?? '');
		$rawPath = (string)($args['path'] ?? '');

		if ($command === '') {
			throw new \InvalidArgumentException("Missing required argument 'command' for tool 'str_replace_editor'");
		}
		if ($rawPath === '') {
			throw new \InvalidArgumentException("Missing required argument 'path' for tool 'str_replace_editor'");
		}
		if (!in_array($command, ['view', 'create', 'edit'], true)) {
			throw new \InvalidArgumentException("Invalid command '$command' for str_replace_editor. Allowed: view, create, edit");
		}

		// Resolve path inside sandbox. `create` may target a non-existing file, so mustExist=false.
		// `view` may target a directory; others require a file. resolvePath validates sandbox escape.
		$mustExist = $command !== 'create';
		$resolved  = $this->resolvePath($rawPath, $mustExist);

		// StrReplaceEditor expects an absolute path that passes its own resolveTarget check.
		// $resolved is already absolute (toolRoot + normalized relative) and sandbox-validated.
		$cwd    = $this->toolRoot();
		$editor = new \StrReplaceEditor(null, $cwd);

		$execArgs         = $args;
		$execArgs['path'] = $resolved;

		// Delegate to the native editor. It returns a formatted string (file view or success+diff).
		return $editor->execute($execArgs);
	}

	/* -----------------------------------------------------------------
	 * Tool helpers
	 * ----------------------------------------------------------------- */

	/**
	 * Sandbox root for file tools (defaults to the installation root).
	 */
	private function toolRoot(): string {
		$root = config('mcp.tools.root', DIR_ROOT);
		$real = realpath($root);

		return $real !== false ? rtrim($real, '/') : rtrim(DIR_ROOT, '/');
	}

	/**
	 * Resolve a user supplied path inside the sandbox root.
	 *
	 * @param string $path      Relative or absolute path
	 * @param bool   $mustExist Require the resolved path to exist
	 *
	 * @return string Absolute real path inside the sandbox
	 */
	private function resolvePath(string $path, bool $mustExist = true): string {
		if ($path === '') {
			throw new \McpToolError('Empty path');
		}

		$root = $this->toolRoot();
		if ($path[0] === '/' && ($path === $root || str_starts_with($path, $root . '/'))) {
			$path = substr($path, strlen($root));
		}

		//paths are always interpreted relative to the sandbox root; absolute
		//looking paths (/etc/passwd) simply map to <root>/etc/passwd which is
		//safer than honoring filesystem absolute paths
		$relative = ltrim(str_replace('\\', '/', $path), '/');

		//lexically normalize the path against the sandbox root; resolves '..'
		//and '.' segments without requiring any segment to exist
		$canonical = [];

		foreach (explode('/', trim($relative, '/')) as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}

			if ($segment === '..') {
				array_pop($canonical);
				continue;
			}

			$canonical[] = $segment;
		}

		$resolved = $root . '/' . implode('/', $canonical);

		//existing files must really live inside the sandbox (symlink check)
		if (file_exists($resolved)) {
			$real = realpath($resolved);

			if ($real !== false && !str_starts_with($real, $root)) {
				throw new \McpToolError("Path escapes the site root sandbox: $path");
			}
		} elseif ($mustExist) {
			throw new \McpToolError("File or directory not found: $path");
		}

		return $resolved;
	}

	/**
	 * Validate arguments against the tool inputSchema required list.
	 *
	 * @param string               $name
	 * @param array<string, mixed> $arguments
	 * @param array<string, mixed> $tool
	 *
	 * @return void
	 */
	private function validateRequiredArgs(string $name, array $arguments, array $tool): void {
		$required = $tool['inputSchema']['required'] ?? [];

		foreach ((array)$required as $field) {
			if (!isset($arguments[$field]) || $arguments[$field] === '') {
				throw new \InvalidArgumentException("Missing required argument '$field' for tool '$name'");
			}
		}
	}

	/**
	 * Wrap a handler result into an MCP tool result envelope.
	 *
	 * @param mixed $result
	 *
	 * @return array{content: array<int, mixed>, isError: false, structuredContent?: mixed}
	 */
	private function textResult($result): array {
		//compact encoding: pretty whitespace costs ~25% on every result
		//with zero information (humans format on read, agents never do)
		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		if (is_string($result)) {
			$text = $result;
		} elseif ($this->isAckShape($result)) {
			//pure acknowledgements (mkdir/rename): one message line for
			//content, full detail stays in structuredContent
			$text = (string)($result['message'] ?? '');
		} else {
			$text = (string)json_encode($result, $flags);
		}

		$out = [
			'content' => [['type' => 'text', 'text' => $text]],
			'isError' => false,
		];

		if (!is_string($result)) {
			$out['structuredContent'] = $result;
		}

		return $out;
	}

	/**
	 * Pure-ack shape: exactly success + message, nothing the caller needs
	 * verbatim (mkdir/rename confirmations). Anything richer stays whole.
	 *
	 * @param mixed $result
	 */
	private function isAckShape(mixed $result): bool {
		if (!is_array($result)) {
			return false;
		}

		$keys = array_keys($result);

		return count($keys) === 2 && in_array('success', $keys, true) && in_array('message', $keys, true);
	}

	/**
	 * Cap an error message for the model: some failures (DB driver dumps)
	 * run to many kilobytes, and a long error never helps the caller more
	 * than its head does. Full detail stays in the server log via DEBUG.
	 *
	 * @param string $message
	 * @param int    $max
	 */
	private static function trimToolError(string $message, int $max = 2000): string {
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($message) > $max) {
				return mb_substr($message, 0, $max) . '… [error truncated, see server log]';
			}

			return $message;
		}

		return strlen($message) > $max ? substr($message, 0, $max) . '… [error truncated, see server log]' : $message;
	}

	/**
	 * Build an MCP error tool result.
	 *
	 * @param string $message
	 *
	 * @return array{content: array<int, mixed>, isError: true}
	 */
	private function errorResult(string $message): array {
		return [
			'content' => [['type' => 'text', 'text' => $message]],
			'isError' => true,
		];
	}

	/**
	 * Detect the leading SQL statement type ignoring comments.
	 *
	 * @param string $query
	 */
	private static function statementType(string $query): string {
		$clean = preg_replace('/^\s*(--[^\n]*|#[^\n]*|\/\*.*?\*\/)+/s', '', $query) ?? $query;

		if (preg_match('/^\s*([a-zA-Z]+)/', $clean, $m)) {
			return strtoupper($m[1]);
		}

		return '';
	}

	/**
	 * Check whether a WITH (CTE) query contains write statements.
	 *
	 * @param string $query
	 */
	private static function containsWriteKeyword(string $query): bool {
		return (bool)preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|MERGE|ALTER|CREATE|DROP|TRUNCATE|RENAME|GRANT|REVOKE|CALL|LOAD|HANDLER|DO|SET)\b/i', $query);
	}

	/**
	 * Execute a registered admin action (admin-execute-action tool).
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return array{success: bool, data?: mixed, error?: string}
	 */
	public function admin_execute_action(array $params): array {
		$name = $params['action_name'] ?? '';
		$args = $params['parameters'] ?? [];

		if ($name === '') {
			return ['success' => false, 'error' => 'Missing action_name'];
		}

		try {
			[$result] = \Vvveb\System\Event::trigger('MCP', 'admin/execute', $args, $name);

			return ['success' => true, 'data' => $result];
		} catch (Throwable $e) {
			return ['success' => false, 'error' => $e->getMessage()];
		}
	}

	/**
	 * List available prompts.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return array{prompts: array<int, array<string, mixed>>, nextCursor: string}
	 */
	public function prompts_list(array $params): array {
		return [
			'prompts' => [
				[
					'name'        => 'code_review',
					'title'       => 'Request Code Review',
					'description' => 'Asks the LLM to analyze code quality and suggest improvements',
					'arguments'   => [
						[
							'name'        => 'code',
							'description' => 'The code to review',
							'required'    => true
						]
					],
					'icons' => [
						[
							'src'      => 'https://example.com/review-icon.svg',
							'mimeType' => 'image/svg+xml',
							'sizes'    => [
								'any'
							]
						]
					]
				]
			],
			'nextCursor' => 'next-page-cursor'
		];
	}

	/**
	 * Get a specific prompt.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return array{description: string, messages: array<int, array<string, mixed>>}
	 */
	public function prompts_get(array $params): array {
		return [
			'description' => 'Code review prompt',
			'messages'    => [
				[
					'role'    => 'user',
					'content' => [
						'type' => 'text',
						'text' => 'Please review this Python code:
		def hello():
			print(\'world\')'
					]
				]
			]
		];
	}

	/**
	 * Handle notifications/initialized event.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return void
	 */
	public function notifications_initialized(array $params): void {}

	/**
	 * Handle delete event.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return void
	 */
	public function delete(array $params): void {}

	/**
	 * List available resources.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return array{resources: array<int, mixed>}
	 */
	public function resources_list(array $params): array {
		return ['resources' => []];
	}

	/**
	 * Read a resource.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return void
	 */
	public function resources_read(array $params): void {}

	/**
	 * Server discovery endpoint.
	 *
	 * @param array<string, mixed> $params
	 *
	 * @return void
	 */
	public function server_discover(array $params): void {}

	/**
	 * Initialize the MCP session and return capabilities.
	 *
	 * @return array{protocolVersion: string, capabilities: array<string, mixed>, serverInfo: array{name: string, version: string}}
	 */
	public function initialize(): array {
		$this->session  = Session::getInstance();
		$sessionId      = $this->session->sessionId();
		header("Mcp-Session-Id: $sessionId");

		return [
			'protocolVersion' => $this->version,
			'capabilities'    => [
				'tools' => [
					'listChanged' => false
				],
				'resources' => [
					'subscribe'   => false,
					'listChanged' => false
				],
				'prompts' => [
					'listChanged' => false
				]
			],
			'serverInfo' => [
				'name'    => 'Vvveb',
				'version' => V_VERSION
			]
		];
	}

	/**
	 * Main MCP index action: parse JSON-RPC request and dispatch to handler.
	 *
	 * @return void
	 */
	public function index(): void {
		$rawInput = file_get_contents('php://input');
		$input    = [];

		if (($input = json_decode($rawInput, true)) === null) {
			$this->notFound('Parse error', -32700);
		}

		$this->response->output = false;

		$result = [];
		if (isset($input['method']) && ($method = $input['method'])) {
			$params = $input['params'] ?? [];

			switch ($method) {
				case 'initialize':
					$result = $this->initialize();
					break;
				case 'tools/list':
					$result = $this->tools_list($params);
					break;
				case 'notifications/initialized':
					$this->notifications_initialized($params);
					$result = null;
					break;
				case 'resources/list':
					$result = $this->resources_list($params);
					break;
				case 'prompts/list':
					$result = $this->prompts_list($params);
					break;
				case 'tools/call':
					$result = $this->tools_call($method, $params);
					break;
				case 'server/discover':
					$this->server_discover($params);
					$result = null;
					break;
				default:
					if (!function_exists($method)) {
						$this->notFound('Method not found', -32601);
					}
			}
		} else {
			$this->notFound('Invalid Request', -32600);
		}

		$id = (int)($this->session->get('id') ?? 0);

		$output = [
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result
		];

		$this->session->set('id', ++$id);

		echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		/*
		$this->response->setType('json');
		$this->response->output = true;
		$this->response->output($output);
		 */
	}
}
