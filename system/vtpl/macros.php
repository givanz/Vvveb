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

use Vvveb\System\Functions\Str;

/*
 * Get attributes as a key => value array for elements specified by selector
 * Ex: @@macro KeyValue(".settings input","data-v-option","data-v-option")@@
 */
function vtplpostNameToArrayKey($vtpl, $node, $keyAttribute) {
	return Vvveb\postNameToArrayKey($keyAttribute);
}

/*
 * Get attributes as a key => value array for elements specified by selector
 * Ex: @@macro KeyValue(".settings input","data-v-option","data-v-option")@@
 */
function vtplKeyValue($vtpl, $node, $selector, $keyAttribute, $valueAttribute) {
	$values = [];

	$elements = $vtpl->xpath->query($vtpl->cssToXpath($selector));

	if ($elements && $elements->length) {
		foreach ($elements as $element) {
			$key = $value = false;

			foreach ($element->attributes as $attribute) {
				if ($attribute->name == $keyAttribute) {
					$key = $attribute->value;
				}

				if ($attribute->name == $valueAttribute) {
					$value = $attribute->value;
				}
			}

			if ($key && $value) {
				$values[$key] = $value;
			}
		}
	}

	return var_export($values, 1);
}
/*
 If macro enables elements with data-v-if="condition = true" to be visible only if condition is true also data-v-if-not="condition = true"

 Parameter can have the following formats
 variable = variable ex product.price = price this will result in $product['price'] == $price
 variable = 'string' ex this.stock = 'available' this will result in $this->price == $price
 */
function ifCondition($string = '') {
	$logic      = ['&&', '\|\|', 'AND', 'OR'];
	$regex      = '/\s+(' . implode(')\s+|\s+(', $logic) . ')\s+/i';
	$conditions = preg_split($regex, $string, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

	$return = '( ';

	foreach ($conditions as $condition) {
		if (in_array(str_replace('|', '\|', $condition),  $logic)) {
			$return .= " ) $condition ( ";
		} else {
			$operators         = ['>', '<', '<=', '>=', '=', '!='];
			$operatorsMatch    = implode('',array_unique(str_split(implode('', $operators))));
			$condition         = html_entity_decode($condition);

			$key      = $condition;
			$compare  = $condition;
			$operator = false;
			$value    = false;

			if ($key = strpbrk($condition, $operatorsMatch)) {
				$operator = trim(Str::match("/^([ $operatorsMatch]+)/", $key));
				$value    = Str::match("/[ $operatorsMatch]+(.+)$/", $key);
				$compare  = Str::match("/(.+?)[ $operatorsMatch]/", $condition);
			}

			if (strpos($value, 'this') === 0) {
				$value = str_replace('this.', 'this->', $value);
			}

			if (strpos($compare, 'this') === 0) {
				$compare = str_replace('this.', 'this->', $compare);
			}

			if (($compare && $compare[0] != "'") && ! is_numeric($compare)) {
				$compare = '$' . $compare;
			}

			if (($value && $value[0] != "'") && ! is_numeric($value)) {
				$value = '$' . $value;
			}

			if ($operator == '=') {
				$operator = '==';
			}

			$value   = Vvveb\dotToArrayKey($value);
			$compare = Vvveb\dotToArrayKey($compare);

			$return .= "(isset($compare) && ($compare $operator $value))";
		}
	}
	$return .= ' )';

	return $return;
}

function vtplIfCondition($vtpl, $node, $string = '') {
	return ifCondition($string);
}

/*
Remove class from node, used by  data-v-class-if
 */
function vtplRemoveClass($vtpl, $node, $className = '') {
	if (! $className) {
		return;
	}
	$value = $node->getAttribute('class');

	if (strpos($value, $className) !== false) {
		$node->setAttribute('class', str_replace($className, '', $value));
	}
}

function vtplIfClass($vtpl, $node) {
	$if      = [];
	$ifnot   = [];
	$length  = $node->attributes->length;

	for ($i = 0; $i < $length; ++$i) {
		if ($item = $node->attributes->item($i)) {
			$name = $item->name;

			if (strpos($name, 'data-v-class-if-not-') !== false) {
				$name           = str_replace('data-v-class-if-not-', '', $name);
				$ifnot[$name]   = $item->value;
			} else {
				if (strpos($name, 'data-v-class-if-') !== false) {
					$name           = str_replace('data-v-class-if-', '', $name);
					$if[$name]      = $item->value;
				}
			}
		}
	}

	$return = '';
	$value  = $node->getAttribute('class');

	foreach ($ifnot as $class => $cond) {
		$value = str_replace($class, '',$value);
		//$node->removeAttribute("data-v-class-if-not-$class");
		$condition = ifCondition($cond);
		$return .= "if  (!($condition)) {echo ' $class';}";
	}

	foreach ($if as $class => $cond) {
		$value = str_replace($class, '',$value);
		//$node->removeAttribute("data-v-class-if-$class");
		$condition = ifCondition($cond);
		$return .= "if  ($condition) {echo ' $class';}";
	}

	$value = preg_replace('/\s+/', ' ', $value);

	$node->setAttribute('class', $value);

	return $return;
}
