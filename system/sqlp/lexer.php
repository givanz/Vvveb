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

namespace Vvveb\System\Sqlp;

class Lexer {
	/** @var string Compiled regex built from the token-map keys. */
	protected string $regex;

	/** @var list<string> Token names indexed by capture-group offset. */
	protected array $offsetToToken;

	/** @var array<string, string|array{0: string, 1: string}> Maps token names to their PHP code or [regex, template] pairs. */
	protected array $macroMap;

	/**
	 * @param array<string, string>                             $tokenMap Regex pattern → token-name mapping.
	 * @param array<string, string|array{0: string, 1: string}> $macroMap Token-name → PHP-code mapping.
	 */
	public function __construct(array $tokenMap, array $macroMap) {
		$this->regex         = '/(' . implode(')|(', array_keys($tokenMap)) . ')/As';
		$this->offsetToToken = array_values($tokenMap);
		$this->macroMap      = $macroMap;
	}

	/**
	 * Tokenise an SQL string into a flat list of `[value, token-type, offset]` tuples.
	 *
	 * Consecutive `T_SQL` tokens are merged into a single entry.
	 *
	 * @param string $string Raw SQL-P source.
	 *
	 * @throws \Exception When an unexpected character is encountered.
	 *
	 * @return list<array{0: string, 1: string, 2: int}>
	 */
	public function lex(string $string): array {
		$tokens = [];

		$offset = 0;
		$sql    = '';

		while (isset($string[$offset])) {
			if (!preg_match($this->regex, $string, $matches, 0, $offset)) {
				throw new \Exception(sprintf('Unexpected character "%s" at offset %d', $string[$offset], $offset));
			}

			// find the first non-empty element (but skipping $matches[0]) using a quick for loop
			for ($i = 1; '' === $matches[$i]; $i++);
			$token = $this->offsetToToken[$i - 1];
			//gather all sql tokens into one continuous string
			if ($token === 'T_SQL') {
				$sql .= $matches[0];
			} else {
				if ($sql) {
					$tokens[] = [$sql, 'T_SQL', $offset];
					$sql      = '';
				}
				$tokens[] = [$matches[0], $token, $offset];
			}

			$offset += strlen($matches[0]);
		}
		//add remaining sql if any left
		if ($sql) {
			$tokens[] = [$sql, 'T_SQL', $offset];
		}

		return $tokens;
	}

	/**
	 * Recursively build a simple AST from the flat token list (pre-macro expansion).
	 *
	 * Recognises `T_IF_START` / `T_IF_END` blocks and attaches child nodes.
	 *
	 * @param list<array{0: string, 1: string, 2: int}> $structure Flat token list (modified in-place).
	 * @param int                                       $k         Starting index.
	 *
	 * @return array{0: list<array<array-key, mixed>>, 1: int} [nodes, last-consumed-index].
	 */
	public function tree(array &$structure, int $k = 0): array {
		$output = [];
		$count  = count($structure);

		for ($i = $k; $i < $count; $i++) {
			[$element, $type, $offset] = $structure[$i];

			$node = $structure[$i];

			if ($type === 'T_IF_START') {
				$ret              = $this->tree($structure, $i + 1);
				$node['children'] = $ret[0];
				$i                = $ret[1];
			} else {
				if ($type === 'T_IF_END' || $type === 'T_EACH_END') {
					$output[] = $node;

					return [$output, $i];
				} else {
					if ($type === 'T_SQL') {
					}
				}
			}

			$output[] = $node;
		}

		return [$output, $i];
	}

	/**
	 * Throw a parse error for an unclosed tag.
	 *
	 * @param list<array{0: string, 1: string, 2: int}> $structure Token tree.
	 * @param int                                       $node_idx  Index of the unclosed node.
	 *
	 * @return never
	 */
	public function parseError(array $structure, int $node_idx): never {
		$node = $structure[$node_idx];

		throw (new \Exception(sprintf('sqlP parse error, expecting close tag for `%s` at offset %d at `%s`', $node[0], $node[2], $this->treeToSql($structure))));
	}

	/**
	 * Recursively build a macro-aware AST from the flat token list.
	 *
	 * Handles `T_IF_START` / `T_ELSE` / `T_IF_END` and `T_EACH` / `T_EACH_END` blocks.
	 *
	 * @param list<array{0: string, 1: string, 2: int}> $structure Flat token list (modified in-place).
	 * @param int                                       $k         Starting index.
	 * @param int                                       $level     Nesting depth.
	 * @param string|false                              $startTag  The opening tag type of the current block.
	 *
	 * @return array{0: list<array<string, mixed>>, 1: int}|list<array<string, mixed>> [nodes, last-index] or plain nodes.
	 */
	public function treeMacro(array &$structure, int $k = 0, int $level = 0, string|false $startTag = false): array {
		$output = [];
		$count  = count($structure);

		for ($i = $k; $i < $count; $i++) {
			[$element, $type, $offset] = $structure[$i];

			$node = $structure[$i];

			if ($type === 'T_EACH') {
				$ret  = $this->treeMacro($structure, $i + 1, $level + 1, $type);

				if (count($ret) === 2) {
					$node['children'] = $ret[0];
					$i                = $ret[1];
				} else {
					$this->parseError($structure, $i);
				}
			} else {
				if ($type === 'T_ELSE') {
					$ret  = $this->treeMacro($structure, $i + 1, $level + 1, $type);

					if (count($ret) === 2) {
						$node['children'] = $ret[0];
						$i                = $ret[1];
					} else {
						$this->parseError($structure, $i);
					}
				}
			}

			if ($type === 'T_IF_START') {
				$ret  = $this->treeMacro($structure, $i + 1, $level + 1, $type);

				if (count($ret) === 2) {
					$node['children'] = $ret[0];
					$i                = $ret[1];
				} else {
					$this->parseError($structure, $i);
				}
			} else {
				if ($level > 0 && (
					($type === 'T_ELSE' && $startTag === 'T_IF_START') ||
					($type === 'T_IF_END' && $startTag === 'T_ELSE') ||
					($type === 'T_IF_END' && $startTag === 'T_IF_START') ||
					($type === 'T_EACH_END' && $startTag === 'T_EACH')
				)) {
					$output[] = $node;

					return [$output, $i];
				} else {
					if ($type === 'T_SQL') {
					}
				}
			}

			$output[] = $node;
		}

		return $output;
	}

	/**
	 * Convert a token tree back into a plain SQL string.
	 *
	 * @param list<array<array-key, mixed>> $tree Token tree.
	 *
	 * @return string Reconstructed SQL.
	 */
	public function treeToSql(array &$tree): string {
		$sql = '';

		foreach ($tree as $token) {
			$sql .= $token[0];

			if (isset($token['children'])) {
				$sql .= $this->treeToSql($token['children']);
			}
		}

		return $sql;
	}

	/**
	 * Expand a single macro by replacing its regex-captured placeholders in a template.
	 *
	 * @param string $statement SQL text containing the macro.
	 * @param string $regex     Regex that captures the macro arguments.
	 * @param string $template  PHP code template with `%placeholder%` and `$%varname%` tokens.
	 *
	 * @return string Expanded SQL.
	 */
	public function parseMacro(string $statement, string $regex, string $template): string {
		if (preg_match_all($regex, $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$macro = $template;

				//replace macro template variables %$variable
				$macro = preg_replace_callback(
					'@\$%(\w+)@',
					function($varMatch) use ($match) {
						return
						preg_replace_callback(
							'/:([\w\.]+)/ms',
							function($matches) {
								return '$' . \Vvveb\dotToArrayKey('params.' . $matches[1]);
								//return '$params[\'' . $matches[1] . '\']';
							},
							$match[$varMatch[1]]
						);
					},
					$macro
				);

				/**
				 * @result
				 * replace result variables
				 */
				$macro =
					preg_replace_callback(
						'/@result\.([\w\.]+)/',
						fn ($matches) => \Vvveb\dotToArrayKey('$results.' . $matches[1]),
						$macro
					);

				//replace macro template placeholders %placeholder
				$macro = preg_replace_callback(
					'@\%(\w+)@',
					fn ($varMatch) => $match[$varMatch[1]],
					$macro
				);

				$statement = str_replace($match[0], $macro, $statement);
			}
		}

		return $statement;
	}

	/**
	 * Convert a token tree into a PHP code string by expanding macros.
	 *
	 * @param list<array<array-key, mixed>>                     $tree     Token tree.
	 * @param array<string, string|array{0: string, 1: string}> $macroMap Token-name → code mapping.
	 *
	 * @return string Generated PHP code.
	 */
	public function treeToPhp(array &$tree, array &$macroMap): string {
		$sql = '';

		foreach ($tree as $token) {
			$name = $token[1];
			$code = $token[0];

			if ($name === 'T_SQL') {
				$sql .= $code;
			} elseif (isset($macroMap[$name])) {
				$macro = $macroMap[$name];

				if (is_array($macro)) {
					$regex  =  $macro[0];
					$string =  $macro[1];

					$sql .= $this->parseMacro($code, $regex, $string);
				} else {
					$sql .= $macro;
				}
			}

			if (isset($token['children'])) {
				$sql .= $this->treeToPhp($token['children'], $macroMap);
			}
		}

		return $sql;
	}
}
