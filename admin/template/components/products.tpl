@product = [data-v-component-products] [data-v-product]
@product|deleteAllButFirstChild

[data-v-component-products]|prepend = <?php
if (isset($_products_idx)) $_products_idx++; else $_products_idx = 0;

$products = [];
$count = 0;
if(isset($this->_component['products'][$_products_idx])) {
	$products = $this->_component['products'][$_products_idx];
	$count = $products['count'] ?? 0;
}

$_pagination_count = $count;
$_pagination_limit = $products['limit'] ?? 5;
?>

[data-v-component-products] [data-v-category] = <?php $_category = current($products['products']);echo $_category['category'];?>
[data-v-component-products] [data-v-manufacturer] = <?php $_manufacturer = current($products['products']);echo $_manufacturer['manufacturer'];?>


[data-v-component-products]  [data-v-product]|before = <?php
if(is_array($products['products']))  {
	$index = 0;
	foreach ($products['products'] as $index => $product) { ?>

	@product [data-v-product-image]|src = $product['image']
	@product [data-v-product-image-url] = $product['image']

	
    //catch all data attributes
    @product [data-v-product-*]|innerText = $product['@@__data-v-product-(*)__@@']
	
	@product [data-v-product-content] = <?php echo $product['content'];?>
	
    
	@product [data-v-product-cart-url]|href = <?php echo htmlentities(Vvveb\url('checkout/cart' ,$product));?>
	@product [data-v-product-cart-url]|data-v-product_id = $product['product_id']
	
	@product [data-v-product-url]|href =<?php echo htmlentities(Vvveb\url('product/product/index', $product));?>
	@product [data-v-product-url]|title = $product['title']	
	
	@product|after = <?php 
		$index++;
	} 
}?>