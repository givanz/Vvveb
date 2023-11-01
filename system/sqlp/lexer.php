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
	protected $regex;

	protected $offsetToToken;

	protected $macroMap;

	public function __construct($tokenMap,  $macroMap) {
		$this->regex         = '/(' . implode(')|(', array_keys($tokenMap)) . ')/As';
		$this->offsetToToken = array_values($tokenMap);
		$this->macroMap      = $macroMap;
	}

	public function lex($string) {
		$tokens = [];

		$offset = 0;
		$sql    = '';

		while (isset($string[$offset])) {
			if (! preg_match($this->regex, $string, $matches, 0, $offset)) {
				throw new Exception(sprintf('Unexpected character "%s" at offset %d', $string[$offset], $offset));
			}

			// find the first non-empty element (but skipping $matches[0]) using a quick for loop
			for ($i = 1; '' === $matches[$i]; ++$i);
			$token = $this->offsetToToken[$i - 1];
			//gather all sql tokens into one continuous string
			if ($token == 'T_SQL') {
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

	// a recursive function to build the ast structure
	function tree(&$structure, $k=0) {
		$output = [];
		$count  = count($structure);

		for ($i= $k; $i < $count; $i++) {
			list($element, $type, $offset) = $structure[$i];

			$node = $structure[$i];

			if ($type == 'T_IF_START') {
				$ret              = $this->tree($structure, $i + 1);
				$node['children'] = $ret[0];
				$i                = $ret[1];
			} else {
				if ($type == 'T_IF_END' || $type == 'T_EACH_END') {
					$output[] = $node;

					return [$output, $i];
				} else {
					if ($type == 'T_SQL') {
					}
				}
			}

			$output[] = $node;
		}

		return $output;
	}

	function parseError($structure, $node_idx) {
		$node = $structure[$node_idx];

		throw(
				new \Exception(
				sprintf('sqlP parse error, expecting close tag for `%s` at offset %d at `%s`',
				$node[0] ,  $node[2], $this->treeToSql($structure))
			));
	}

	// a recursive function to build the ast structure
	function treeMacro(&$structure, $k=0, $level = 0, $startTag = false) {
		$output = [];
		$count  = count($structure);

		for ($i = $k; $i < $count; $i++) {
			list($element, $type, $offset) = $structure[$i];

			$node = $structure[$i];

			if ($type == 'T_EACH') {
				$ret  = $this->treeMacro($structure, $i + 1, $level + 1, $type);

				if (is_array($ret) && count($ret) == 2) {
					$node['children'] = $ret[0];
					$i                = $ret[1];
				} else {
					$this->parseError($structure, $i);
				}
			} else {
				if ($type == 'T_ELSE') {
					$ret  = $this->treeMacro($structure, $i + 1, $level + 1, $type);

					if (is_array($ret) && count($ret) == 2) {
						$node['children'] = $ret[0];
						$i                = $ret[1];
					} else {
						$this->parseError($structure, $i);
					}
				}
			}

			if ($type == 'T_IF_START') {
				$ret  = $this->treeMacro($structure, $i + 1, $level + 1, $type);

				if (is_array($ret) && count($ret) == 2) {
					$node['children'] = $ret[0];
					$i                = $ret[1];
				} else {
					$this->parseError($structure, $i);
				}
			} else {
				if ($level > 0 && (
						($type == 'T_ELSE' && $startTag == 'T_IF_START') ||
						($type == 'T_IF_END' && $startTag == 'T_ELSE') ||
						($type == 'T_IF_END' && $startTag == 'T_IF_START') ||
						($type == 'T_EACH_END' && $startTag == 'T_EACH')
					)) {
					$output[] = $node;

					return [$output, $i];
				} else {
					if ($type == 'T_SQL') {
					}
				}
			}

			$output[] = $node;
		}

		return $output;
	}

	function treeToSql(&$tree) {
		$sql = '';

		foreach ($tree as $token) {
			$sql .= $token[0];

			if (isset($token['children'])) {
				$sql .= $this->treeToSql($token['children']);
			}
		}

		return $sql;
	}

	function parseMacro($statement, $regex, $template) {
		$macro = $template;

		if (preg_match_all($regex, $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$macro = $template;

				//replace macro template variables %$variable
				$macro = preg_replace_callback(
				'@\$%(\w+)@',
				function ($varMatch) use ($match) {
					return
					preg_replace_callback(
						'/:(\w+)/ms',
						function ($matches) {
							return '$params[\'' . $matches[1] . '\']';
						},
					$match[$varMatch[1]]);
				},
				$macro);

				//replace macro template placeholders %placeholder
				$macro = preg_replace_callback(
				'@\%(\w+)@',
					function ($varMatch) use ($match) {
						return $match[$varMatch[1]];
					},
				$macro);

				$statement = str_replace($match[0], $macro, $statement);
			}
		}

		return $statement;
	}

	function treeToPhp(&$tree, &$macroMap) {
		$sql = '';

		foreach ($tree as $token) {
			$name = $token[1];
			$code = $token[0];

			if ($name == 'T_SQL') {
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
