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

namespace Vvveb\Controller\Product;

use function Vvveb\__;
use Vvveb\Controller\Content\Edit;
use function Vvveb\dashesToCamelCase;
use Vvveb\Sql\Product_Option_ValueSQL;
use Vvveb\Sql\Product_OptionSQL;
use Vvveb\Sql\Product_VariantSQL;
use Vvveb\Sql\ProductSQL;

class Product extends Edit {
	protected $type = 'product';

	protected $object = 'product';

	protected $module = 'product/product';

	protected $list   = 'product/products';

	protected $revisions = true;

	function saveVariants() {
		$post                     = &$this->request->post;
		$post['product_variant']  = $post['product_variant'] ?? [];

		if ($post['product_variant']) {
			$deleteVariant            = $this->request->post['delete']['product_variant_id'] ?? [];
			$variantSql               = new Product_VariantSQL();

			unset($post['product_variant']['#'], $post['product_variant']['0']);

			foreach ($post['product_variant'] as $i => $opt) {
				$opt['product_id'] = $this->product_id;

				if ($opt['product_variant_id']) {
					//existing variant
					$variantSql->edit([
						'product_variant'    => $opt,
						'product_variant_id' => $opt['product_variant_id'],
					] + $this->global);
				} else {
					// new variant
					unset($opt['product_variant_id']);
					$result = $variantSql->add(['product_variant' => $opt] + $this->global);

					if ($result && $result['product_variant']) {
						//set new id to use for variant values
						$opt['product_variant_id'] = $result['product_variant'];
					}
				}
			}

			if ($deleteVariant) {
				$variantSql->delete(['product_variant_id' => $deleteVariant] + $this->global);
			}
		}
	}

	function saveOptions() {
		$post                    = &$this->request->post;
		$post['product_option']  = $post['product_option'] ?? [];

		if ($post['product_option']) {
			$deleteOption		    = $this->request->post['delete']['product_option_id'] ?? [];
			$deleteOptionValue	= $this->request->post['delete']['product_option_value_id'] ?? [];

			$optionSql               = new Product_OptionSQL();
			$productOptionValueSql   = new Product_Option_ValueSQL();

			unset($post['product_option']['#'], $post['product_option']['0']);

			foreach ($post['product_option'] as $i => $opt) {
				$opt['product_id'] = $this->product_id;

				if ($opt['product_option_id']) {
					//existing option
					$optionSql->edit([
						'product_option'	   => $opt,
						'product_option_id'	=> $opt['product_option_id'],
					] + $this->global);
				} else {
					// new option
					unset($opt['product_option_id']);
					$result = $optionSql->add(['product_option' => $opt] + $this->global);

					if ($result && $result['product_option']) {
						//set new id to use for option values
						$opt['product_option_id'] = $result['product_option'];
					}
				}

				if (isset($opt['product_option_value'])) {
					unset($opt['product_option_value']['#']);

					foreach ($opt['product_option_value'] as $j => $value) {
						//set parent option id
						$value['product_option_id'] = $opt['product_option_id'];
						$value['option_id']         = $opt['option_id'];
						$value['product_id']        = $this->product_id;

						if ($value['product_option_value_id']) {
							//existing option value
							$productOptionValueSql->edit([
								'product_option_value'		  => $value,
								'product_option_value_id'	=> $value['product_option_value_id'],
							] + $this->global);
						} else {
							unset($value['product_option_value_id']);
							$result = $productOptionValueSql->add(['product_option_value' => $value] + $this->global);
						}
					}
				}
			}

			if ($deleteOptionValue) {
				$productOptionValueSql->delete(['product_option_value_id' => $deleteOptionValue] + $this->global);
			}

			if ($deleteOption) {
				$optionSql->delete(['product_option_id' => $deleteOption] + $this->global);
			}
		}
	}

	function save() {
		$post                    = &$this->request->post;

		$post['requires_shipping']    = isset($post['requires_shipping']) ? 1 : 0;
		$post['subscription_onetime'] = isset($post['subscription_onetime']) ? 1 : 0;
		$post['manufacturer_id']      = isset($post['manufacturer_id']) ? ($post['manufacturer_id'] ?: 0) : null;
		$post['vendor_id']            = isset($post['vendor_id']) ? ($post['vendor_id'] ?: 0) : null;

		parent::save();

		$this->product_id = $this->request->get[$this->object . '_id'] ?? $post[$this->object . '_id'] ?? false;

		if ($this->product_id) {
			$this->saveOptions();
			$this->saveVariants();

			$product          = new ProductSQL();
			$features         = ['related', /*'variant',*/ 'subscription', 'discount', 'promotion', 'points', 'attribute', 'digital_asset'];

			foreach ($features as $feature) {
				$feature = $this->object . '_' . $feature; //eg: product_related
				$data    = $this->request->post[$feature] ?? [];

				if ($data) {
					$fn = lcfirst(dashesToCamelCase($feature, '_')); //eg: $product->productRelated()
					unset($data['#']);
					$product->$fn([$this->object . '_id' => $this->product_id, $feature => array_unique($data, SORT_REGULAR)]);
				}
			}
		}

		return $this->index();
	}

	private function optionCombinations($arr) {
		$names  = [];
		$values = [];

		foreach ($arr as $optionId => $set) {
			if (! $names) {
				foreach ($set as $i => $val) {
					$names[]  = $val;
					$values[] = $optionId . ':' . $i;
				}
			} else {
				$length    = count($names);
				$newNames  = [];
				$newValues = [];

				for ($l = 0; $l < $length; $l++) {
					$currName = $names[$l];

					foreach ($set as $j => $val) {
						$newNames[]  = $currName . ' / ' . $val;
						$newValues[] = $values[$l] . ',' . $optionId . ':' . $j;
					}
				}

				$names  = $newNames;
				$values = $newValues;
			}
		}

		return [$names, $values];
	}

	function index() {
		parent::index();

		$view    = $this->view;
		$product = new ProductSQL();
		$options = (isset($view->product['product_id']) ? $view->product : $this->global);
		$data    = $product->getData($options);

		if (isset($data['option'])) {
			foreach ($data['option'] as $id => &$option) {
				unset($option['array_key']);
			}
		} else {
			$data['option'] = [];
		}
		$optionIds = [];

		if (isset($view->product['product_option'])) {
			$product_option_value = &$view->product['product_option_value'];
			$product_option       = &$view->product['product_option'];

			$option_value_content = [];
			$names                = [];

			$default = '';

			if ($view->product['language_id'] != $this->global['default_language_id']) {
				$default = '[' . __('No translation') . ']';
			}

			foreach ($view->product['option_value_content'] as &$value) {
				$option_value_content[$value['option_id']][$value['option_value_id']] = $value;
				$names[$value['option_value_id']]                                     = $value['name'] ?? $default;
				//$optionIds[$value['product_option_id']][$value['product_option_value_id']] = $value['name'];
			}

			foreach ($product_option_value as &$value) {
				if (isset($product_option[$value['product_option_id']])) {
					$product_option[$value['product_option_id']]['values'][$value['product_option_value_id']] = $value;
					$optionIds[$value['product_option_id']][$value['product_option_value_id']]                = $names[$value['option_value_id']] ?? $default;
				}
			}

			$view->product['option_value_content'] = $option_value_content;
		} else {
			$view->product['product_option'] = [];
		}

		$view->combinations = [];

		if ($optionIds) {
			$combinations       = $this->optionCombinations($optionIds);
			$view->combinations = array_combine($combinations[1], $combinations[0]);
		}

		$view->product['manufacturer_id'] = (($view->product['manufacturer_id'] ?? 0) ? $view->product['manufacturer_id'] : '');
		$view->product['vendor_id']       = (($view->product['vendor_id'] ?? 0) ? $view->product['vendor_id'] : '');
		$view->product['status']          = $view->product['status'] ?? 1;
		$data['subtract_stock']           = [1 => __('Yes'), 0 => __('No')]; //Subtract stock options
		$data['status']                   = [0 => __('Disabled'), 1 => __('Enabled')];
		$view->set($data);
	}
}
