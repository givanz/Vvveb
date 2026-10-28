<?php

declare(strict_types=1);

namespace CodingAgent\Tools;

/**
 * @phpstan-type EditDiffResult array{diff: string, firstChangedLine?: int|null}
 * @phpstan-type EditDiffError array{error: string}
 * @phpstan-type EditPreview EditDiffResult|EditDiffError
 *
 * @param string $content
 */

/**
 * Split leading UTF-8 BOM (\xEF\xBB\xBF / \uFEFF).
 *
 * @return array{bom: string, text: string}
 */
function splitBom(string $content): array {
	$bom = "\xEF\xBB\xBF";
	if (str_starts_with($content, $bom)) {
		return ['bom' => $bom, 'text' => substr($content, 3)];
	}
	// Also handle single-codepoint form if already decoded as UTF-8 string with \uFEFF
	if (str_starts_with($content, "\u{FEFF}")) {
		return ['bom' => "\u{FEFF}", 'text' => mb_substr($content, 1)];
	}

	return ['bom' => '', 'text' => $content];
}

function detectLineEnding(string $content): string {
	$crlfIdx = strpos($content, "\r\n");
	$lfIdx   = strpos($content, "\n");
	if ($lfIdx === false) {
		return "\n";
	}
	if ($crlfIdx === false) {
		return "\n";
	}

	return $crlfIdx < $lfIdx ? "\r\n" : "\n";
}

function normalizeToLF(string $text): string {
	$text = str_replace("\r\n", "\n", $text);

	return str_replace("\r", "\n", $text);
}

function restoreLineEndings(string $text, string $ending): string {
	return $ending === "\r\n" ? str_replace("\n", "\r\n", $text) : $text;
}

/**
 * Normalize text for fuzzy matching.
 * - NFKC normalization
 * - Strip trailing whitespace per line
 * - Smart quotes → ASCII
 * - Dashes → hyphen
 * - Special spaces → regular space
 *
 * @param string $text
 */
function normalizeForFuzzyMatch(string $text): string {
	// NFKC
	if (class_exists(\Normalizer::class)) {
		$normalized = \Normalizer::normalize($text, \Normalizer::FORM_KC);
		if ($normalized !== false) {
			$text = $normalized;
		}
	}

	$lines = explode("\n", $text);
	$lines = array_map(fn (string $line): string => rtrim($line, " \t"), $lines);
	$text  = implode("\n", $lines);

	// Smart single quotes → '
	$text = preg_replace('/[\x{2018}\x{2019}\x{201A}\x{201B}]/u', "'", $text) ?? $text;
	// Smart double quotes → "
	$text = preg_replace('/[\x{201C}\x{201D}\x{201E}\x{201F}]/u', '"', $text) ?? $text;
	// Various dashes/hyphens → -
	$text = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2015}\x{2212}]/u', '-', $text) ?? $text;

	// Special spaces → regular space
	return preg_replace('/[\x{00A0}\x{2002}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $text) ?? $text;
}

/** @return string[] each entry retains its trailing \n if present */
function splitLinesWithEndings(string $content): array {
	if ($content === '') {
		return [];
	}
	preg_match_all('/[^\n]*\n|[^\n]+/', $content, $m);

	return $m[0];
}

/** @return array<int, array{start: int, end: int}> */
function getLineSpans(string $content): array {
	$offset = 0;
	$spans  = [];
	foreach (splitLinesWithEndings($content) as $line) {
		$len     = strlen($line);
		$spans[] = ['start' => $offset, 'end' => $offset + $len];
		$offset += $len;
	}

	return $spans;
}

/**
 * @param array<int, array{start: int, end: int}>  $lines
 * @param array{matchIndex: int, matchLength: int} $replacement
 *
 * @return array{startLine: int, endLine: int}
 */
function getReplacementLineRange(array $lines, array $replacement): array {
	$start = $replacement['matchIndex'];
	$end   = $replacement['matchIndex'] + $replacement['matchLength'];

	$startLine = -1;
	foreach ($lines as $i => $line) {
		if ($start >= $line['start'] && $start < $line['end']) {
			$startLine = $i;
			break;
		}
	}
	if ($startLine === -1) {
		throw new \RuntimeException('Replacement range is outside the base content.');
	}
	$endLine = $startLine;
	while ($endLine < count($lines) && $lines[$endLine]['end'] < $end) {
		$endLine++;
	}
	if ($endLine >= count($lines)) {
		throw new \RuntimeException('Replacement range is outside the base content.');
	}

	return ['startLine' => $startLine, 'endLine' => $endLine + 1];
}

/**
 * @param string                                                                $content
 * @param array<int, array{matchIndex: int, matchLength: int, newText: string}> $replacements
 * @param int                                                                   $offset
 */
function applyReplacements(string $content, array $replacements, int $offset = 0): string {
	$result = $content;
	for ($i = count($replacements) - 1; $i >= 0; $i--) {
		$r      = $replacements[$i];
		$idx    = $r['matchIndex'] - $offset;
		$result = substr($result, 0, $idx) . $r['newText'] . substr($result, $idx + $r['matchLength']);
	}

	return $result;
}

/**
 * Apply replacements matched against baseContent to originalContent while
 * preserving unchanged line blocks from the original.
 *
 * @param string                                                                $originalContent
 * @param string                                                                $baseContent
 * @param array<int, array{matchIndex: int, matchLength: int, newText: string}> $replacements
 */
function applyReplacementsPreservingUnchangedLines(string $originalContent, string $baseContent, array $replacements): string {
	$originalLines = splitLinesWithEndings($originalContent);
	$baseLines     = getLineSpans($baseContent);

	if (count($originalLines) !== count($baseLines)) {
		throw new \RuntimeException('Cannot preserve unchanged lines because the base content has a different line count.');
	}

	$groups = [];
	$sorted = $replacements;
	usort($sorted, fn ($a, $b) => $a['matchIndex'] <=> $b['matchIndex']);

	foreach ($sorted as $replacement) {
		$range   = getReplacementLineRange($baseLines, $replacement);
		$current = !empty($groups) ? $groups[count($groups) - 1] : null;
		if ($current !== null && $range['startLine'] < $current['endLine']) {
			$idx                            = count($groups) - 1;
			$groups[$idx]['endLine']        = max($current['endLine'], $range['endLine']);
			$groups[$idx]['replacements'][] = $replacement;
			continue;
		}
		$groups[] = ['startLine' => $range['startLine'], 'endLine' => $range['endLine'], 'replacements' => [$replacement]];
	}

	$originalLineIndex = 0;
	$result            = '';
	foreach ($groups as $group) {
		$result .= implode('', array_slice($originalLines, $originalLineIndex, $group['startLine'] - $originalLineIndex));
		$groupStartOffset = $baseLines[$group['startLine']]['start'];
		$groupEndOffset   = $baseLines[$group['endLine'] - 1]['end'];
		$result .= applyReplacements(
			substr($baseContent, $groupStartOffset, $groupEndOffset - $groupStartOffset),
			$group['replacements'],
			$groupStartOffset
		);
		$originalLineIndex = $group['endLine'];
	}
	$result .= implode('', array_slice($originalLines, $originalLineIndex));

	return $result;
}

/**
 * @param string $content
 * @param string $oldText
 *
 * @return array{found: bool, index: int, matchLength: int, usedFuzzyMatch: bool, contentForReplacement: string}
 */
function fuzzyFindText(string $content, string $oldText): array {
	$exactIndex = strpos($content, $oldText);
	if ($exactIndex !== false) {
		return [
			'found'                 => true,
			'index'                 => $exactIndex,
			'matchLength'           => strlen($oldText),
			'usedFuzzyMatch'        => false,
			'contentForReplacement' => $content,
		];
	}

	$fuzzyContent = normalizeForFuzzyMatch($content);
	$fuzzyOldText = normalizeForFuzzyMatch($oldText);
	$fuzzyIndex   = strpos($fuzzyContent, $fuzzyOldText);

	if ($fuzzyIndex === false) {
		return [
			'found'                 => false,
			'index'                 => -1,
			'matchLength'           => 0,
			'usedFuzzyMatch'        => false,
			'contentForReplacement' => $content,
		];
	}

	return [
		'found'                 => true,
		'index'                 => $fuzzyIndex,
		'matchLength'           => strlen($fuzzyOldText),
		'usedFuzzyMatch'        => true,
		'contentForReplacement' => $fuzzyContent,
	];
}

function countOccurrences(string $content, string $oldText): int {
	$fuzzyContent = normalizeForFuzzyMatch($content);
	$fuzzyOldText = normalizeForFuzzyMatch($oldText);
	if ($fuzzyOldText === '') {
		return 0;
	}

	return substr_count($fuzzyContent, $fuzzyOldText);
}

function getNotFoundError(string $path, int $editIndex, int $totalEdits): \RuntimeException {
	if ($totalEdits === 1) {
		return new \RuntimeException(
			"Could not find the exact text in {$path}. The old text must match exactly including all whitespace and newlines."
		);
	}

	return new \RuntimeException(
		"Could not find edits[{$editIndex}] in {$path}. The oldText must match exactly including all whitespace and newlines."
	);
}

function getDuplicateError(string $path, int $editIndex, int $totalEdits, int $occurrences): \RuntimeException {
	if ($totalEdits === 1) {
		return new \RuntimeException(
			"Found {$occurrences} occurrences of the text in {$path}. The text must be unique. Please provide more context to make it unique."
		);
	}

	return new \RuntimeException(
		"Found {$occurrences} occurrences of edits[{$editIndex}] in {$path}. Each oldText must be unique. Please provide more context to make it unique."
	);
}

function getEmptyOldTextError(string $path, int $editIndex, int $totalEdits): \RuntimeException {
	if ($totalEdits === 1) {
		return new \RuntimeException("oldText must not be empty in {$path}.");
	}

	return new \RuntimeException("edits[{$editIndex}].oldText must not be empty in {$path}.");
}

function getNoChangeError(string $path, int $totalEdits): \RuntimeException {
	if ($totalEdits === 1) {
		return new \RuntimeException(
			"No changes made to {$path}. The replacement produced identical content. This might indicate an issue with special characters or the text not existing as expected."
		);
	}

	return new \RuntimeException("No changes made to {$path}. The replacements produced identical content.");
}

/**
 * Apply one or more exact-text replacements to LF-normalized content.
 *
 * Every edit is first probed against the original content; when at least one
 * edit only matches after fuzzy normalization (unicode quotes/dashes/spaces,
 * trailing whitespace) all edits are matched against the fuzzy-normalized
 * base so every offset shares one coordinate space. Uniqueness is always
 * checked on the fuzzy-normalized base. The file content itself is normalized
 * once up front instead of once per edit.
 *
 * @param string                                              $normalizedContent
 * @param array<int, array{oldText: string, newText: string}> $edits
 * @param string                                              $path
 *
 * @return array{baseContent: string, newContent: string}
 */
function applyEditsToNormalizedContent(string $normalizedContent, array $edits, string $path): array {
	$total = count($edits);

	$normalizedEdits = array_map(static fn (array $e): array => [
		'oldText' => normalizeToLF($e['oldText']),
		'newText' => normalizeToLF($e['newText']),
	], $edits);

	foreach ($normalizedEdits as $i => $edit) {
		if ($edit['oldText'] === '') {
			throw getEmptyOldTextError($path, $i, $total);
		}
	}

	// Fast-path probe: fall back to the fuzzy base only when an edit does not
	// match the original content exactly.
	$needsFuzzy = false;
	foreach ($normalizedEdits as $edit) {
		if (!str_contains($normalizedContent, $edit['oldText'])) {
			$needsFuzzy = true;
			break;
		}
	}

	$replacementBaseContent = $needsFuzzy ? normalizeForFuzzyMatch($normalizedContent) : $normalizedContent;
	// Uniqueness base, normalized once instead of once per edit.
	$uniquenessBase = normalizeForFuzzyMatch($replacementBaseContent);

	$matchedEdits = [];
	foreach ($normalizedEdits as $i => $edit) {
		$fuzzyOldText = normalizeForFuzzyMatch($edit['oldText']);

		$index = strpos($replacementBaseContent, $edit['oldText']);
		if ($index !== false) {
			$matchIndex  = $index;
			$matchLength = strlen($edit['oldText']);
		} else {
			$fuzzyIndex = $fuzzyOldText === '' ? false : strpos($uniquenessBase, $fuzzyOldText);
			if ($fuzzyIndex === false) {
				throw getNotFoundError($path, $i, $total);
			}
			$matchIndex  = $fuzzyIndex;
			$matchLength = strlen($fuzzyOldText);
		}

		$occurrences = $fuzzyOldText === '' ? 0 : substr_count($uniquenessBase, $fuzzyOldText);
		if ($occurrences > 1) {
			throw getDuplicateError($path, $i, $total, $occurrences);
		}

		$matchedEdits[] = [
			'editIndex'   => $i,
			'matchIndex'  => $matchIndex,
			'matchLength' => $matchLength,
			'newText'     => $edit['newText'],
		];
	}

	usort($matchedEdits, fn ($a, $b) => $a['matchIndex'] <=> $b['matchIndex']);
	for ($i = 1; $i < count($matchedEdits); $i++) {
		$prev = $matchedEdits[$i - 1];
		$curr = $matchedEdits[$i];
		if ($prev['matchIndex'] + $prev['matchLength'] > $curr['matchIndex']) {
			throw new \RuntimeException("edits[{$prev['editIndex']}] and edits[{$curr['editIndex']}] overlap in {$path}. Merge them into one edit or target disjoint regions.");
		}
	}

	$baseContent = $normalizedContent;
	$newContent  = $needsFuzzy
		? applyReplacementsPreservingUnchangedLines($normalizedContent, $replacementBaseContent, $matchedEdits)
		: applyReplacements($replacementBaseContent, $matchedEdits);

	if ($baseContent === $newContent) {
		throw getNoChangeError($path, $total);
	}

	return ['baseContent' => $baseContent, 'newContent' => $newContent];
}

/**
 * Generate a standard unified patch (context lines = 4).
 *
 * @param string $path
 * @param string $oldContent
 * @param string $newContent
 * @param int    $contextLines
 */
function generateUnifiedPatch(string $path, string $oldContent, string $newContent, int $contextLines = 4): string {
	// Use `diff -u` style output via pure PHP when sebastian/diff is unavailable.
	// Fallback: hand-rolled minimal unified patch.

	$oldLines = explode("\n", $oldContent);
	$newLines = explode("\n", $newContent);

	// If sebastian/diff is available, prefer it
	if (class_exists(\SebastianBergmann\Diff\Differ::class)) {
		$builder = new \SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder("--- {$path}\n+++ {$path}\n", false);
		$differ  = new \SebastianBergmann\Diff\Differ($builder);

		return $differ->diff($oldContent, $newContent);
	}

	// Minimal fallback: produce a simple patch header + line-by-line diff
	$out = "--- {$path}\n+++ {$path}\n";
	// Use LCS-based hunk generation if possible, otherwise naive line iteration
	$max = max(count($oldLines), count($newLines));
	$out .= '@@ -1,' . count($oldLines) . ' +1,' . count($newLines) . " @@\n";
	$i = 0;
	$j = 0;
	while ($i < count($oldLines) || $j < count($newLines)) {
		$o = $oldLines[$i] ?? null;
		$n = $newLines[$j] ?? null;
		if ($o === $n) {
			$out .= ' ' . ($o ?? '') . "\n";
			$i++;
			$j++;
		} elseif ($o !== null && $n !== null) {
			$out .= '-' . $o . "\n";
			$out .= '+' . $n . "\n";
			$i++;
			$j++;
		} elseif ($o !== null) {
			$out .= '-' . $o . "\n";
			$i++;
		} else {
			$out .= '+' . $n . "\n";
			$j++;
		}
		if ($i + $j > 20000) {
			break;
		} // safety cap
	}

	return $out;
}

/**
 * Generate display-oriented diff with line numbers and context.
 *
 * @param string $oldContent
 * @param string $newContent
 * @param int    $contextLines
 *
 * @return array{diff: string, firstChangedLine: int|null}
 */
function generateDiffString(string $oldContent, string $newContent, int $contextLines = 4): array {
	$oldLines = $oldContent === '' ? [] : explode("\n", $oldContent);
	$newLines = $newContent === '' ? [] : explode("\n", $newContent);

	// Line-level diff parts via LCS.
	$parts = diffLinesLcs($oldLines, $newLines);

	$maxLineNum   = max(count($oldLines), count($newLines), 1);
	$lineNumWidth = strlen((string)$maxLineNum);

	$output           = [];
	$oldLineNum       = 1;
	$newLineNum       = 1;
	$lastWasChange    = false;
	$firstChangedLine = null;

	$totalParts = count($parts);
	for ($pi = 0; $pi < $totalParts; $pi++) {
		$part      = $parts[$pi];
		$raw       = $part['value'];
		$isAdded   = $part['added'];
		$isRemoved = $part['removed'];
		$isContext = !$isAdded && !$isRemoved;

		if ($isAdded || $isRemoved) {
			if ($firstChangedLine === null) {
				$firstChangedLine = $newLineNum;
			}
			foreach ($raw as $line) {
				if ($isAdded) {
					$num      = str_pad((string)$newLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
					$output[] = "+{$num} {$line}";
					$newLineNum++;
				} else {
					$num      = str_pad((string)$oldLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
					$output[] = "-{$num} {$line}";
					$oldLineNum++;
				}
			}
			$lastWasChange = true;
		} else {
			// Context block
			$nextIsChange      = $pi + 1 < $totalParts && ($parts[$pi + 1]['added'] || $parts[$pi + 1]['removed']);
			$hasLeadingChange  = $lastWasChange;
			$hasTrailingChange = $nextIsChange;

			if ($hasLeadingChange && $hasTrailingChange) {
				if (count($raw) <= $contextLines * 2) {
					foreach ($raw as $line) {
						$num      = str_pad((string)$oldLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
						$output[] = " {$num} {$line}";
						$oldLineNum++;
						$newLineNum++;
					}
				} else {
					$leading  = array_slice($raw, 0, $contextLines);
					$trailing = array_slice($raw, -$contextLines);
					$skipped  = count($raw) - count($leading) - count($trailing);
					foreach ($leading as $line) {
						$num      = str_pad((string)$oldLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
						$output[] = " {$num} {$line}";
						$oldLineNum++;
						$newLineNum++;
					}
					$output[] = ' ' . str_pad('', $lineNumWidth, ' ') . ' ...';
					$oldLineNum += $skipped;
					$newLineNum += $skipped;
					foreach ($trailing as $line) {
						$num      = str_pad((string)$oldLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
						$output[] = " {$num} {$line}";
						$oldLineNum++;
						$newLineNum++;
					}
				}
			} elseif ($hasLeadingChange) {
				$shown   = array_slice($raw, 0, $contextLines);
				$skipped = count($raw) - count($shown);
				foreach ($shown as $line) {
					$num      = str_pad((string)$oldLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
					$output[] = " {$num} {$line}";
					$oldLineNum++;
					$newLineNum++;
				}
				if ($skipped > 0) {
					$output[] = ' ' . str_pad('', $lineNumWidth, ' ') . ' ...';
					$oldLineNum += $skipped;
					$newLineNum += $skipped;
				}
			} elseif ($hasTrailingChange) {
				$skipped = max(0, count($raw) - $contextLines);
				if ($skipped > 0) {
					$output[] = ' ' . str_pad('', $lineNumWidth, ' ') . ' ...';
					$oldLineNum += $skipped;
					$newLineNum += $skipped;
				}
				foreach (array_slice($raw, $skipped) as $line) {
					$num      = str_pad((string)$oldLineNum, $lineNumWidth, ' ', STR_PAD_LEFT);
					$output[] = " {$num} {$line}";
					$oldLineNum++;
					$newLineNum++;
				}
			} else {
				$oldLineNum += count($raw);
				$newLineNum += count($raw);
			}
			$lastWasChange = false;
		}
	}

	return ['diff' => implode("\n", $output), 'firstChangedLine' => $firstChangedLine];
}

/**
 * Compute line-level diff parts (added/removed/context) via LCS DP.
 *
 * Parts strictly alternate between change runs and context runs by
 * construction. For very large inputs the O(m*n) table is skipped and the
 * whole files are reported as a single removed + added pair.
 *
 * @param string[] $a
 * @param string[] $b
 *
 * @return array<int, array{value: string[], added: bool, removed: bool}>
 */
function diffLinesLcs(array $a, array $b): array {
	$m = count($a);
	$n = count($b);

	// For large files cap DP to avoid O(m*n) explosion — fall back to simple scan
	if ($m * $n > 4_000_000) {
		// Simple fallback: treat whole files as two parts if not equal
		if ($a === $b) {
			return [['value' => $a, 'added' => false, 'removed' => false]];
		}

		return [
			['value' => $a, 'added' => false, 'removed' => true],
			['value' => $b, 'added' => true, 'removed' => false],
		];
	}

	// Build LCS table
	$dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
	for ($i = $m - 1; $i >= 0; $i--) {
		for ($j = $n - 1; $j >= 0; $j--) {
			if ($a[$i] === $b[$j]) {
				$dp[$i][$j] = $dp[$i + 1][$j + 1] + 1;
			} else {
				$dp[$i][$j] = max($dp[$i + 1][$j], $dp[$i][$j + 1]);
			}
		}
	}

	// Walk to produce edit script, grouping runs by kind
	$parts   = [];
	$i       = 0;
	$j       = 0;
	$buf     = [];
	$bufType = null; // 'context' | 'removed' | 'added'

	/**
	 * @param list<string>                                                 $buf
	 * @param 'context'|'removed'|'added'|null                             $bufType
	 * @param list<array{value: list<string>, added: bool, removed: bool}> $parts
	 *
	 * @return array{0: list<array{value: list<string>, added: bool, removed: bool}>, 1: list<string>}
	 */
	$flush = static function(array $buf, ?string $bufType, array $parts): array {
		if ($buf === []) {
			return [$parts, $buf];
		}
		$parts[] = match ($bufType) {
			'added'   => ['value' => $buf, 'added' => true, 'removed' => false],
			'removed' => ['value' => $buf, 'added' => false, 'removed' => true],
			default   => ['value' => $buf, 'added' => false, 'removed' => false],
		};

		return [$parts, []];
	};

	while ($i < $m || $j < $n) {
		if ($i < $m && $j < $n && $a[$i] === $b[$j]) {
			if ($bufType !== 'context' && $bufType !== null) {
				[$parts, $buf] = $flush($buf, $bufType, $parts);
			}
			$bufType = 'context';
			$buf[]   = $a[$i];
			$i++;
			$j++;
		} elseif ($j < $n && ($i >= $m || $dp[$i][$j + 1] >= $dp[$i + 1][$j])) {
			if ($bufType !== 'added') {
				[$parts, $buf] = $flush($buf, $bufType, $parts);
				$bufType       = 'added';
			}
			$buf[] = $b[$j];
			$j++;
		} else {
			if ($bufType !== 'removed') {
				[$parts, $buf] = $flush($buf, $bufType, $parts);
				$bufType       = 'removed';
			}
			$buf[] = $a[$i];
			$i++;
		}
	}
	[$parts] = $flush($buf, $bufType, $parts);

	return $parts;
}
