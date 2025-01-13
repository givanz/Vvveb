import(common.tpl)

[data-v-page] [data-v-compare-*]|innerText = $this->compare['@@__data-v-compare-(*)__@@']

@compare-product = [data-v-products] [data-v-product]

@compare-product|deleteAllButFirstChild

@compare-product|before = <?php

$products  = $this->products['product'] ?? [];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['product_id' => 1, 'product_id' => 1, 'image' => '#']] : false;
$products = empty($products) ? $_default : $products;

if(is_array($products)) foreach ($products as $key => $product) {
?>

	//@compare-product [data-v-product-name] = $product['name']
	//@compare-product [data-v-product-content] = $product['content']
	//@compare-product [data-v-product-amount] = <?php echo htmlspecialchars($product['amount']);?>

	@compare-product|data-product_id = $product['product_id']	
	@compare-product|data-key = $key	

	@compare-product img[data-v-product-image]|src = $product['image']

	//catch all data attributes
	@compare-product [data-v-product-*]|innerText  = $product['@@__data-v-product-(*)__@@']
	@compare-product a[data-v-product-*]|href      = $product['@@__data-v-product-(*)__@@']
	@compare-product img[data-v-product-*]|src     = $product['@@__data-v-product-(*)__@@']
	@compare-product input[data-v-product-*]|value = $product['@@__data-v-product-(*)__@@']

@compare-product|after = <?php }?>


[data-v-specs]|deleteAllButFirstChild

[data-v-specs]|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => 'product_spec_value_id'] : false;
$specs = empty($this->specs) ? $_default : $this->specs;
$index = 0;
$name = current($this->names);
if($specs) {
	foreach ($specs as $values) {?>

	[data-v-specs-name] = $name

[data-v-specs]|after = <?php 
		$index++; 
		$name = next($this->names);
	} 
}
?>

@compare-spec = [data-v-specs] [data-v-spec]
@compare-spec|deleteAllButFirstChild

@compare-spec|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => 'product_spec_value_id'] : false;
$values = empty($values) ? $_default : $values;

if($values) {
	foreach ($values as $value) { ?>

	@compare-spec = $value
	

@compare-spec|after = <?php } 
}
?>