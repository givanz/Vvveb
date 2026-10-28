<?php

declare(strict_types=1);

if (!class_exists('McpToolError')) {
	require_once __DIR__ . '/tool_error.php';
}
if (file_exists(__DIR__ . '/edit.php')) {
	require_once __DIR__ . '/edit.php';
}
if (file_exists(__DIR__ . '/edit-diff.php')) {
	require_once __DIR__ . '/edit-diff.php';
}

const TRUNCATED_MESSAGE = '<response clipped><NOTE>To save on context only part of this file has been shown to you. You should retry this tool after you have searched inside the file with `grep -n` in order to find the line numbers of what you are looking for.</NOTE>';

const DEFAULT_DESCRIPTION = <<<'DESC'
	Custom editing tool for viewing, creating and editing files
	* State is persistent across command calls and discussions with the user
	* If `path` is a file, `view` displays the result of applying `cat -n`. If `path` is a directory, `view` lists non-hidden files and directories up to 2 levels deep
	* The `create` command cannot be used if the specified `path` already exists as a file
	* The `edit` command applies multiple disjoint exact replacements via `edits[].oldText`/`newText` — each oldText must match a unique non-overlapping region
	* If a `command` generates a long output, it will be truncated and marked with `<response clipped>`
	DESC;

class FsError extends McpToolError {
	public function __construct(
		string $message,
		public readonly string $fsCode = 'FS_ERROR',
		?Throwable $previous = null,
	) {
		parent::__construct($message, 0, $previous);
	}
}

/**
 * Filesystem target value object.
 */
final class FsTarget {
	public function __construct(
		public readonly string $absolutePath,
		public readonly string $displayPath,
	) {}
}

/**
 * Filesystem info value object.
 */
final class FsInfo {
	public function __construct(
		/** @var 'file'|'directory'|'other' */
		public readonly string $type,
		public readonly ?string $version = null, // mtime hash placeholder
	) {}
}

function maybeTruncate(string $content, int $maxOutputChars): string {
	return mb_strlen($content) <= $maxOutputChars
		? $content
		: mb_substr($content, 0, $maxOutputChars) . TRUNCATED_MESSAGE;
}

function resolveTarget(string $path): FsTarget {
	if (trim($path) === '') {
		throw new InvalidArgumentException('path must be a non-empty string');
	}
	if (!str_starts_with($path, '/')) {
		throw new InvalidArgumentException("The path {$path} is not an absolute path, it should start with `/`. Maybe you meant /{$path}?");
	}

	// No symlink resolution enforced here; displayPath equals absolutePath.
	return new FsTarget($path, $path);
}

/**
 * @param FsTarget $target
 * @param string   $command
 *
 * @throws FsError
 */
function statExisting(FsTarget $target, string $command): FsInfo {
	$path = $target->absolutePath;

	if (!file_exists($path)) {
		throw new FsError("The path {$target->displayPath} does not exist. Please provide a valid path.", 'FS_NOT_FOUND');
	}

	$isDir  = is_dir($path);
	$isFile = is_file($path);

	if ($isDir && $command !== 'view') {
		throw new FsError("The path {$target->displayPath} is a directory and only the `view` command can be used on directories", 'FS_NOT_REGULAR_FILE');
	}

	if ($isDir) {
		return new FsInfo('directory');
	}
	if ($isFile) {
		// Use mtime as a version token
		$version = (string)@filemtime($path);

		return new FsInfo('file', $version);
	}

	return new FsInfo('other');
}

function requiredForCommand(?string $value, string $parameter, string $command, bool $allowEmpty = true): string {
	if ($value === null) {
		throw new InvalidArgumentException("Parameter `{$parameter}` is required for command: {$command}");
	}
	if (!$allowEmpty && $value === '') {
		throw new InvalidArgumentException("Parameter `{$parameter}` is empty for command: {$command}");
	}

	return $value;
}

/**
 * @param string                       $path
 * @param string                       $content
 * @param int                          $maxOutputChars
 * @param array<array-key, mixed>|null $viewRange
 */
function formatFileView(string $path, string $content, int $maxOutputChars, ?array $viewRange = null): string {
	$allLines    = explode("\n", $content);
	$lines       = $allLines;
	$initialLine = 1;
	$finalLine   = null;
	$prompt      = "Here's the content of {$path} with line numbers (which has a total of " . count($allLines) . ' lines)';

	if ($viewRange !== null) {
		if (
			count($viewRange) !== 2
			|| !isset($viewRange[0], $viewRange[1])
			|| !is_int($viewRange[0])
			|| !is_int($viewRange[1])
		) {
			throw new InvalidArgumentException('Invalid `view_range`. It should be a list of two integers.');
		}
		[$requestedInitialLine, $requestedFinalLine] = $viewRange;
		$initialLine                                 = $requestedInitialLine;
		$finalLine                                   = $requestedFinalLine;

		if ($initialLine < 1 || $initialLine > count($allLines)) {
			throw new InvalidArgumentException('Invalid `view_range`: [' . implode(', ', $viewRange) . "]. Its first element `{$initialLine}` should be within the range of lines of the file: [1, " . count($allLines) . ']');
		}
		if ($finalLine > count($allLines)) {
			throw new InvalidArgumentException('Invalid `view_range`: [' . implode(', ', $viewRange) . "]. Its second element `{$finalLine}` should be smaller than the number of lines in the file: `" . count($allLines) . '`');
		}
		if ($finalLine !== -1 && $finalLine < $initialLine) {
			throw new InvalidArgumentException('Invalid `view_range`: [' . implode(', ', $viewRange) . "]. Its second element `{$finalLine}` should be larger or equal than its first `{$initialLine}`");
		}

		$lines = $finalLine === -1
			? array_slice($allLines, $initialLine - 1)
			: array_slice($allLines, $initialLine - 1, $finalLine - $initialLine + 1);

		$prompt .= " with view_range=[{$initialLine}, {$finalLine}]";
	}

	$numbered = implode("\n", array_map(
		fn (string $line, int $index): string => sprintf('%6d  %s', $initialLine + $index, $line),
		$lines,
		array_keys($lines)
	));

	return maybeTruncate("{$prompt}:\n{$numbered}\n", $maxOutputChars);
}

/**
 * List directory up to 2 levels deep, excluding hidden / node_modules / __pycache__.
 *
 * @param FsTarget $target
 * @param int      $maxOutputChars
 */
function listDirectory(FsTarget $target, int $maxOutputChars): string {
	/** @return string[] rows like "d\t/path" */
	$visit = function(FsTarget $dir, int $depth) use (&$visit): array {
		$entries = @scandir($dir->absolutePath);
		if ($entries === false) {
			throw new FsError("cannot list directory {$dir->displayPath}", 'FS_IO_ERROR');
		}
		$rows = [];
		foreach ($entries as $name) {
			if ($name === '.' || $name === '..') {
				continue;
			}
			if (str_starts_with($name, '.') || $name === 'node_modules' || $name === '__pycache__') {
				continue;
			}
			$full    = rtrim($dir->absolutePath, '/') . '/' . $name;
			$display = rtrim($dir->displayPath, '/') . '/' . $name;
			$isDir   = is_dir($full);
			$isFile  = is_file($full);
			$type    = $isDir ? 'd' : ($isFile ? 'f' : '?');
			$rows[]  = "{$type}\t{$display}";
			if ($isDir && $depth < 2) {
				$child = new FsTarget($full, $display);
				$rows  = array_merge($rows, $visit($child, $depth + 1));
			}
		}

		return $rows;
	};

	$rows = array_merge(["d\t{$target->displayPath}"], $visit($target, 1));

	// Binary-safe codepoint order on the display path.
	usort($rows, static fn (string $left, string $right): int => substr($left, (int)strpos($left, "\t") + 1) <=> substr($right, (int)strpos($right, "\t") + 1));

	$listing = maybeTruncate(implode("\n", $rows) . "\n", $maxOutputChars);

	return "Here're the files and directories up to 2 levels deep in {$target->displayPath}, excluding hidden items, node_modules, and Python cache directories:\n{$listing}\n";
}

/**
 * @param string                       $path
 * @param array<array-key, mixed>|null $viewRange
 * @param int                          $maxOutputChars
 */
function viewPath(string $path, ?array $viewRange, int $maxOutputChars): string {
	$target = resolveTarget($path);
	$info   = statExisting($target, 'view');

	if ($info->type === 'directory') {
		if ($viewRange !== null) {
			throw new InvalidArgumentException('The `view_range` parameter is not allowed when `path` points to a directory.');
		}

		return listDirectory($target, $maxOutputChars);
	}

	if ($info->type !== 'file') {
		throw new FsError("cannot view \"{$target->displayPath}\": not a regular file or directory", 'FS_NOT_REGULAR_FILE');
	}

	$content = @file_get_contents($target->absolutePath);
	if ($content === false) {
		throw new FsError("cannot read file {$target->displayPath}", 'FS_IO_ERROR');
	}

	return formatFileView($target->displayPath, $content, $maxOutputChars, $viewRange);
}

function createFile(string $path, ?string $fileText): string {
	$content = requiredForCommand($fileText, 'file_text', 'create');
	$target  = resolveTarget($path);

	if (file_exists($target->absolutePath)) {
		throw new InvalidArgumentException("File already exists at: {$target->displayPath}. Cannot overwrite files using command `create`.");
	}

	// Ensure parent directory exists
	$dir = dirname($target->absolutePath);
	if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
		throw new FsError("cannot create parent directory for {$target->displayPath}", 'FS_IO_ERROR');
	}

	$written = @file_put_contents($target->absolutePath, $content, LOCK_EX);
	if ($written === false) {
		throw new FsError("failed to write {$target->displayPath}", 'FS_IO_ERROR');
	}

	return "New file created successfully at: {$target->displayPath}";
}

final class StrReplaceEditorConfig {
	public function __construct(
		public readonly int $maxOutputChars = 16_000,
		public readonly string $description = DEFAULT_DESCRIPTION,
	) {
		if ($maxOutputChars <= 0) {
			throw new InvalidArgumentException('tool-str-replace-editor: maxOutputChars must be a positive safe integer');
		}
		if (trim($description) === '') {
			throw new InvalidArgumentException('tool-str-replace-editor: description must be non-empty');
		}
	}
}

final class StrReplaceEditor {
	private StrReplaceEditorConfig $config;
	private mixed $editTool = null; // lazy CodingAgent\Tools\EditToolDefinition
	private string $cwd;

	public function __construct(?StrReplaceEditorConfig $config = null, ?string $cwd = null) {
		$this->config = $config ?? new StrReplaceEditorConfig();
		$this->cwd    = $cwd ?? getcwd() ?: '/tmp';
	}

	private function getEditTool(): mixed {
		if ($this->editTool !== null) {
			return $this->editTool;
		}
		if (!class_exists('\\CodingAgent\\Tools\\EditToolDefinition')) {
			throw new \RuntimeException('Edit tool not available: edit.php not loaded. Ensure edit.php and edit-diff.php are in the same directory.');
		}
		$class = '\\CodingAgent\\Tools\\EditToolDefinition';
		// EditToolDefinition expects cwd + EditOperations; use DefaultEditOperations if available
		$opsClass       = '\\CodingAgent\\Tools\\DefaultEditOperations';
		$ops            = class_exists($opsClass) ? new $opsClass() : null;
		$this->editTool = new $class($this->cwd, $ops);

		return $this->editTool;
	}

	/**
	 * Direct multi-edit via CodingAgent\Tools engine (fuzzy, BOM, line-ending aware).
	 *
	 * @param array{path:string, edits:array<int, array{oldText:string,newText:string}>} $input
	 *
	 * @return array{content: array<int, array{type: string, text: string}>, details: array{diff: string, patch: string, firstChangedLine?: int|null}}
	 */
	public function edit(array $input): array {
		return $this->getEditTool()->execute($input);
	}

	/**
	 * Execute a multi-edit call and flatten the structured engine result
	 * to the string contract (message + diff).
	 *
	 * @param string $path
	 * @param mixed  $edits raw `edits` argument (array, JSON string or single object)
	 */
	private function executeEdit(string $path, mixed $edits): string {
		// The engine normalises relaxed input itself (JSON string, single
		// object, legacy oldText/newText) and validates `edits`.
		$result = $this->edit(['path' => $path, 'edits' => $edits]);
		$diff   = $result['details']['diff'];
		$msg    = $result['content'][0]['text'] ?? "Edited {$path}";

		return $diff !== '' ? "{$msg}\n{$diff}" : $msg;
	}

	/**
	 * Execute a tool call.
	 *
	 * @param array<array-key, mixed> $args Unvalidated tool arguments from JSON-RPC input.
	 */
	public function execute(array $args): string {
		$command = $args['command'] ?? throw new InvalidArgumentException('Missing required parameter: command');
		$path    = $args['path'] ?? throw new InvalidArgumentException('Missing required parameter: path');

		if (!is_string($command) || !is_string($path)) {
			throw new InvalidArgumentException('Invalid required parameters: command and path must be strings');
		}

		$viewRange = $args['view_range'] ?? null;

		if ($viewRange !== null && !is_array($viewRange)) {
			throw new InvalidArgumentException('Invalid `view_range`. It should be a list of two integers.');
		}

		$fileText = $args['file_text'] ?? null;

		if ($fileText !== null && !is_string($fileText)) {
			throw new InvalidArgumentException('Invalid `file_text`. It should be a string.');
		}

		return match ($command) {
			'view'   => viewPath($path, $viewRange, $this->config->maxOutputChars),
			'create' => createFile($path, $fileText),
			'edit'   => $this->executeEdit($path, $args['edits'] ?? null),
			default  => throw new InvalidArgumentException("Unknown command: {$command}"),
		};
	}
}

/*
Example usage (uncomment to test):

$editor = new StrReplaceEditor();
echo $editor->execute(['command' => 'view', 'path' => '/tmp']);
echo $editor->execute(['command' => 'create', 'path' => '/tmp/hello.txt', 'file_text' => "hello\nworld\n"]);
echo $editor->execute(['command' => 'view', 'path' => '/tmp/hello.txt', 'view_range' => [1, 1]]);
// integrated edit (multi-edit, fuzzy, BOM-aware via edit.php)
echo $editor->execute(['command' => 'edit', 'path' => '/tmp/hello.txt', 'edits' => [['oldText' => "hello\n", 'newText' => "hey\n"], ['oldText' => "world", 'newText' => "earth"]]]);
print_r($editor->edit(['path' => '/tmp/hello.txt', 'edits' => [['oldText' => 'hey', 'newText' => 'hello']]])); // structured result with diff/patch
*/
