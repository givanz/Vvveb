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

/**
 * Escape a character in a string by prepending a backslash.
 *
 * Ex: @@macro Escape("Escape ' this","'")@@
 *
 * @param Vtpl    $vtpl   The Vtpl instance.
 * @param DOMNode $node   The current DOM node.
 * @param string  $string The string to escape.
 * @param string  $char   The character to escape.
 *
 * @return string The escaped string.
 */
function vtplEscape(Vtpl $vtpl, DOMNode $node, string $string, string $char = '\''): string {
	return str_replace($char, '\\' . $char, $string);
}

/**
 * Convert a form field name with array notation to PHP array key notation.
 *
 * Ex: @@macro vtplpostNameToArrayKey("name[key]")@@
 *
 * @param Vtpl    $vtpl         The Vtpl instance.
 * @param DOMNode $node         The current DOM node.
 * @param string  $keyAttribute The form field name to convert.
 *
 * @return string The converted array<array-key, mixed> key string.
 */
function vtplpostNameToArrayKey(Vtpl $vtpl, DOMNode $node, string $keyAttribute): string {
	return Vvveb\postNameToArrayKey($keyAttribute);
}

/**
 * Get attributes as a key => value array for elements specified by a CSS selector.
 *
 * Ex: @@macro KeyValue(".settings input","data-v-option","data-v-option")@@
 *
 * @param Vtpl    $vtpl           The Vtpl instance.
 * @param DOMNode $node           The current DOM node.
 * @param string  $selector       CSS selector to match elements.
 * @param string  $keyAttribute   Attribute name to use as the array key.
 * @param string  $valueAttribute Attribute name to use as the array value.
 *
 * @return string A var_export'd array<array-key, mixed> of key => value pairs.
 */
function vtplKeyValue(Vtpl $vtpl, DOMNode $node, string $selector, string $keyAttribute, string $valueAttribute): string {
	$values = [];

	$elements = $vtpl->query($selector);

	if ($elements && $elements->length) {
		foreach ($elements as $element) {
			$key = $value = false;

			foreach ($element->attributes as $attribute) {
				if ($attribute->name === $keyAttribute) {
					$key = $attribute->value;
				}

				if ($attribute->name === $valueAttribute) {
					$value = $attribute->value;
				}
			}

			if ($key && $value) {
				$values[$key] = $value;
			}
		}
	}

	return var_export($values, true);
}

/**
 * Compile a conditional expression into a PHP if-condition string.
 *
 * Transforms variable dot-notation to PHP array syntax, handles operators,
 * and wraps in isset() checks where needed.
 *
 * @param string $condition The conditional expression to compile.
 *
 * @return string The compiled PHP condition string.
 */
function ifCondition(string $condition = ''): string {
	//remove php functions
	$condition = preg_replace('/[a-zA-Z_][\w\\]*\([^\)]*\)/', '', $condition);

	//transform variables to php variables
	$array_keys = '';
	$condition  = preg_replace_callback(
		'/[\'"]?[a-zA-Z_][\w\.-]*[\'"]?/',
		function($matches) use (&$array_keys) {
			$value = $matches[0];
			$len   = strlen($value) - 1;

			if ($value === 'null' || $value === 'NULL'
				|| $value === 'true' || $value === 'TRUE'
				|| $value === 'false' || $value === 'FALSE'
				|| $value === 'and' || $value === 'or' || $value === 'xor'
				|| $value[0] === '\'' || $value[$len] === '\''
				|| $value[0] === '"' || $value[$len] === '"') {
				return $value;
			}

			if ($value === 'this') {
				return '$this';
			}

			if (str_starts_with($value, 'this')) {
				$value = str_replace('this.', 'this->', $value);
			}

			if (str_contains($value, '.')) {
				$value = Vvveb\dotToArrayKey($value);
				$value = '$' . $value;

				if ($array_keys) {
					$array_keys .= ' && ';
				}
				$array_keys .= "isset($value)";
			} else {
				$value = '$' . $value;

				if ($array_keys) {
					$array_keys .= ' && ';
				}
				$array_keys .= "isset($value)";
			}

			return $value;
		},
		$condition
	);

	//double ==
	$condition = preg_replace('/(?<![<>\!])=+/', ' == ', $condition);

	if ($array_keys) {
		$array_keys = "($array_keys) && ";
	}

	return $array_keys . "($condition)";
}

/**
 * Macro wrapper for ifCondition() used in Vtpl templates.
 *
 * @param Vtpl    $vtpl   The Vtpl instance.
 * @param DOMNode $node   The current DOM node.
 * @param string  $string The conditional expression.
 *
 * @return string The compiled PHP condition string.
 */
function vtplIfCondition(Vtpl $vtpl, DOMNode $node, string $string = ''): string {
	return ifCondition($string);
}

/**
 * Remove a CSS class from a DOM node.
 *
 * Used by data-v-class-if to strip classes before conditional re-application.
 *
 * @param Vtpl    $vtpl      The Vtpl instance.
 * @param DOMNode $node      The current DOM node.
 * @param string  $className The class name to remove.
 *
 * @return void
 */
function vtplRemoveClass(Vtpl $vtpl, DOMNode $node, string $className = ''): void {
	if (!$className) {
		return;
	}
	$value = $node->getAttribute('class');

	if (str_contains($value, $className)) {
		$node->setAttribute('class', str_replace($className, '', $value));
	}
}

/**
 * Process data-v-class-if-* and data-v-class-if-not-* attributes on a node.
 *
 * Generates PHP code that conditionally adds CSS classes based on expressions.
 *
 * @param Vtpl    $vtpl The Vtpl instance.
 * @param DOMNode $node The current DOM node.
 *
 * @return string PHP code snippet that echoes conditional class names.
 */
function vtplIfClass(Vtpl $vtpl, DOMNode $node): string {
	$if      = [];
	$ifnot   = [];
	$length  = $node->attributes->length;

	for ($i = 0; $i < $length; $i++) {
		if ($item = $node->attributes->item($i)) {
			$name = $item->name;

			if (str_contains($name, 'data-v-class-if-not-')) {
				$name           = str_replace('data-v-class-if-not-', '', $name);
				$ifnot[$name]   = $item->value;
			} else {
				if (str_contains($name, 'data-v-class-if-')) {
					$name           = str_replace('data-v-class-if-', '', $name);
					$if[$name]      = $item->value;
				}
			}
		}
	}

	$return = '';
	$value  = $node->getAttribute('class');

	foreach ($ifnot as $class => $cond) {
		$value = str_replace($class, '', $value);
		//$node->removeAttribute("data-v-class-if-not-$class");
		$condition = ifCondition($cond);
		$return .= "if  (!($condition)) {echo ' $class';}";
	}

	foreach ($if as $class => $cond) {
		$value = str_replace($class, '', $value);
		//$node->removeAttribute("data-v-class-if-$class");
		$condition = ifCondition($cond);
		$return .= "if  ($condition) {echo ' $class';}";
	}

	$value = preg_replace('/\s+/', ' ', $value);

	$node->setAttribute('class', $value);

	return $return;
}

/**
 * Process data-v-attr-if-* and data-v-attr-if-not-* attributes on a node.
 *
 * Generates PHP code that conditionally outputs HTML attributes based on expressions.
 *
 * @param Vtpl    $vtpl The Vtpl instance.
 * @param DOMNode $node The current DOM node.
 *
 * @return void
 */
function vtplIfAttr(Vtpl $vtpl, DOMNode $node): void {
	$if      = [];
	$ifnot   = [];
	$length  = $node->attributes->length;

	for ($i = 0; $i < $length; $i++) {
		if ($item = $node->attributes->item($i)) {
			$name = $item->name;

			if (str_contains($name, 'data-v-attr-if-not-')) {
				$name           = str_replace('data-v-attr-if-not-', '', $name);
				$ifnot[$name]   = $item->value;
			} else {
				if (str_contains($name, 'data-v-attr-if-')) {
					$name           = str_replace('data-v-attr-if-', '', $name);
					$if[$name]      = $item->value;
				}
			}
		}
	}

	foreach ($ifnot as $value => $cond) {
		//$node->removeAttribute("data-v-attr-if-not-$value");
		$attr = $value;
		if ($val = $node->getAttribute($value)) {
			$attr = $attr . '=' . '"' . $val . '"';
		}
		$node->removeAttribute($value);

		$condition = ifCondition($cond);
		$ifCode    = "<?php if  (!($condition)) {echo '$attr';}?>";
		$vtpl->addNodeNewAttribute($node, $ifCode);
	}

	foreach ($if as $value => $cond) {
		//$node->removeAttribute("data-v-attr-if-$value");
		$attr = $value;
		if ($val = $node->getAttribute($value)) {
			$attr = $attr . '=' . '"' . $val . '"';
		}
		$node->removeAttribute($value);

		$condition = ifCondition($cond);
		$ifCode    = "<?php if  ($condition) {echo '$attr';}?>";
		$vtpl->addNodeNewAttribute($node, $ifCode);
	}
}
