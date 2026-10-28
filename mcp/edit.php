<?php

declare(strict_types=1);

namespace CodingAgent\Tools;

require_once __DIR__ . '/edit-diff.php';

interface EditOperations {
	/** @throws \RuntimeException if not readable/writable */
	public function access(string $absolutePath): void;

	/** @return string raw file bytes */
	public function readFile(string $absolutePath): string;

	public function writeFile(string $absolutePath, string $content): void;
}

final class DefaultEditOperations implements EditOperations {
	public function access(string $absolutePath): void {
		if (!is_readable($absolutePath) || !is_writable($absolutePath)) {
			// Match Node's fs.access error code style
			$code = !file_exists($absolutePath) ? 'ENOENT' : 'EACCES';

			throw new \RuntimeException("Could not access file: {$absolutePath}. Error code: {$code}");
		}
	}

	public function readFile(string $absolutePath): string {
		$data = @file_get_contents($absolutePath);
		if ($data === false) {
			throw new \RuntimeException("Failed to read file: {$absolutePath}");
		}

		return $data;
	}

	public function writeFile(string $absolutePath, string $content): void {
		$result = @file_put_contents($absolutePath, $content);
		if ($result === false) {
			throw new \RuntimeException("Failed to write file: {$absolutePath}");
		}
	}
}

/**
 * Expand ~ and resolve relative to $cwd.
 *
 * @param string $filePath
 * @param string $cwd
 */
function resolveToCwd(string $filePath, string $cwd): string {
	// Expand leading ~/
	if (str_starts_with($filePath, '~/') || $filePath === '~') {
		$home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
		if ($home !== '') {
			$filePath = $home . substr($filePath, 1);
		}
	}
	// Strip @ prefix used by some tools
	if (str_starts_with($filePath, '@')) {
		$filePath = substr($filePath, 1);
	}
	// Already absolute
	if (str_starts_with($filePath, '/')) {
		return $filePath;
	}

	return rtrim($cwd, '/') . '/' . $filePath;
}

/**
 * Serialize file mutation operations targeting the same file.
 * Uses per-file flock; different files run without blocking each other.
 *
 * @template T
 *
 * @param string        $filePath
 * @param callable(): T $fn
 *
 * @return T
 */
function withFileMutationQueue(string $filePath, callable $fn): mixed {
	$resolved = realpath($filePath) ?: $filePath;
	$lockFile = sys_get_temp_dir() . '/edit-mutex-' . md5($resolved) . '.lock';
	$handle   = fopen($lockFile, 'c');
	if ($handle === false) {
		// Fallback: run without lock
		return $fn();
	}

	try {
		flock($handle, LOCK_EX);

		return $fn();
	} finally {
		flock($handle, LOCK_UN);
		fclose($handle);
	}
}

/**
 * @param mixed $value
 *
 * @phpstan-assert-if-true array{oldText: string, newText: string} $value
 */
function isSingleEditInput(mixed $value): bool {
	if (!is_array($value)) {
		return false;
	}

	return isset($value['oldText'], $value['newText'])
		&& is_string($value['oldText'])
		&& is_string($value['newText']);
}

/**
 * Normalise relaxed model output:
 * - edits may be JSON string
 * - edits may be single object instead of array
 * - legacy top-level oldText/newText
 *
 * @param mixed $input
 *
 * @return array{path?: string, edits?: mixed, oldText?: mixed, newText?: mixed}
 */
function prepareEditArguments(mixed $input): array {
	if (!is_array($input)) {
		return [];
	}

	$args = $input;

	if (isset($args['edits']) && is_string($args['edits'])) {
		$parsed = json_decode($args['edits'], true);
		if (json_last_error() === JSON_ERROR_NONE) {
			if (is_array($parsed) && array_is_list($parsed)) {
				$args['edits'] = $parsed;
			} elseif (isSingleEditInput($parsed)) {
				$args['edits'] = [$parsed];
			}
		}
	} elseif (isset($args['edits']) && isSingleEditInput($args['edits'])) {
		$args['edits'] = [$args['edits']];
	}

	// Legacy top-level oldText / newText
	if (isset($args['oldText'], $args['newText']) && is_string($args['oldText']) && is_string($args['newText'])) {
		$edits   = isset($args['edits']) && is_array($args['edits']) ? $args['edits'] : [];
		$edits[] = ['oldText' => $args['oldText'], 'newText' => $args['newText']];
		unset($args['oldText'], $args['newText']);
		$args['edits'] = $edits;
	}

	return $args;
}

/**
 * @param array<array-key, mixed> $input
 *
 * @return array{path: string, edits: list<array{oldText: string, newText: string}>}
 */
function validateEditInput(array $input): array {
	if (!isset($input['edits']) || !is_array($input['edits']) || count($input['edits']) === 0) {
		throw new \InvalidArgumentException('Edit tool input is invalid. edits must contain at least one replacement.');
	}
	if (!isset($input['path']) || !is_string($input['path']) || $input['path'] === '') {
		throw new \InvalidArgumentException('Edit tool input is invalid. path must be a non-empty string.');
	}

	$edits = [];

	foreach ($input['edits'] as $edit) {
		if (!is_array($edit) || !isset($edit['oldText'], $edit['newText']) || !is_string($edit['oldText']) || !is_string($edit['newText'])) {
			throw new \InvalidArgumentException('Edit tool input is invalid. Each edit must have string oldText and newText.');
		}

		$edits[] = ['oldText' => $edit['oldText'], 'newText' => $edit['newText']];
	}

	return ['path' => $input['path'], 'edits' => $edits];
}

/**
 * Compute preview diff without writing the file.
 *
 * @param string                                        $path
 * @param list<array{oldText: string, newText: string}> $edits
 * @param string                                        $cwd
 * @param ?EditOperations                               $ops
 *
 * @return array{diff: string, firstChangedLine: int|null}|array{error: string}
 */
function computeEditsDiff(string $path, array $edits, string $cwd, ?EditOperations $ops = null): array {
	$ops ??= new DefaultEditOperations();
	$absolutePath = resolveToCwd($path, $cwd);

	try {
		try {
			$ops->access($absolutePath);
		} catch (\Throwable $e) {
			// For preview allow readable-only check fallback
			if (!is_readable($absolutePath)) {
				return ['error' => "Could not edit file: {$path}. Error code: ENOENT."];
			}
		}
		$rawContent          = $ops->readFile($absolutePath);
		['text' => $content] = splitBom($rawContent);
		$normalizedContent   = normalizeToLF($content);
		$result              = applyEditsToNormalizedContent($normalizedContent, $edits, $path);

		return generateDiffString($result['baseContent'], $result['newContent']);
	} catch (\Throwable $e) {
		return ['error' => $e->getMessage()];
	}
}

final class EditToolDefinition {
	public const NAME        = 'edit';
	public const LABEL       = 'edit';
	public const DESCRIPTION = 'Edit a single file using exact text replacement. Every edits[].oldText must match a unique, non-overlapping region of the original file. If two changes affect the same block or nearby lines, merge them into one edit instead of emitting overlapping edits. Do not include large unchanged regions just to connect distant changes.';

	public function __construct(
		private readonly string $cwd,
		private readonly EditOperations $ops,
	) {}

	/**
	 * Prepare / normalise raw model arguments.
	 *
	 * @param mixed $input
	 *
	 * @return array{path?: string, edits?: mixed, oldText?: mixed, newText?: mixed}
	 */
	public function prepareArguments(mixed $input): array {
		return prepareEditArguments($input);
	}

	/**
	 * Execute the edit.
	 *
	 * @param array{path: string, edits: list<array{oldText: string, newText: string}>} $input
	 *
	 * @return array{content: array<int, array{type: string, text: string}>, details: array{diff: string, patch: string, firstChangedLine?: int|null}}
	 */
	public function execute(array $input): array {
		$validated    = validateEditInput($this->prepareArguments($input));
		$path         = $validated['path'];
		$edits        = $validated['edits'];
		$absolutePath = resolveToCwd($path, $this->cwd);

		return withFileMutationQueue($absolutePath, function() use ($path, $edits, $absolutePath): array {
			// Access check
			try {
				$this->ops->access($absolutePath);
			} catch (\Throwable $e) {
				throw new \RuntimeException("Could not edit file: {$path}. {$e->getMessage()}.");
			}

			$buffer                             = $this->ops->readFile($absolutePath);
			['bom' => $bom, 'text' => $content] = splitBom($buffer);
			$originalEnding                     = detectLineEnding($content);
			$normalizedContent                  = normalizeToLF($content);

			$result      = applyEditsToNormalizedContent($normalizedContent, $edits, $path);
			$baseContent = $result['baseContent'];
			$newContent  = $result['newContent'];

			$finalContent = $bom . restoreLineEndings($newContent, $originalEnding);
			$this->ops->writeFile($absolutePath, $finalContent);

			$diffResult = generateDiffString($baseContent, $newContent);
			$patch      = generateUnifiedPatch($path, $baseContent, $newContent);

			return [
				'content' => [
					['type' => 'text', 'text' => 'Successfully replaced ' . count($edits) . " block(s) in {$path}."],
				],
				'details' => [
					'diff'             => $diffResult['diff'],
					'patch'            => $patch,
					'firstChangedLine' => $diffResult['firstChangedLine'] ?? null,
				],
			];
		});
	}
}

/**
 * Factory — mirrors createEditToolDefinition(cwd, options).
 *
 * @param string          $cwd
 * @param ?EditOperations $operations
 */
function createEditToolDefinition(string $cwd, ?EditOperations $operations = null): EditToolDefinition {
	return new EditToolDefinition($cwd, $operations ?? new DefaultEditOperations());
}
