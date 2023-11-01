@cart-product = [data-v-component-cart] [data-v-cart-product]
@cart-product|deleteAllButFirstChild

[data-v-component-cart]|prepend = <?php
if (isset($_cart_idx)) $_cart_idx++; else $_cart_idx = 0;

$previous_component = isset($current_component)?$current_component:null;
$cart = $current_component = $this->_component['cart'][$_cart_idx] ?? [];

$_pagination_count = $cart['count'] ?? 0;
$_pagination_limit = isset($cart['limit']) ? $cart['limit'] : 5;

$products = $cart['products'] ?? [];
?>

[data-v-component-cart] [data-v-total_items] = $cart['total_items']
[data-v-component-cart] [data-v-grand-total] = $cart['total']
[data-v-component-cart] [data-v-grand-total_formatted] = $cart['total_formatted']
[data-v-component-cart] [data-v-cart-cart-*]|innerText = $cart['@@__data-v-cart-cart-(*)__@@']

@cart-product|before = <?php

if($products) {
	foreach ($products as $index => $product) { ?>

	//@cart-product [data-v-product-name] = $product['name']
	//@cart-product [data-v-product-price] = $product['price']
	//@cart-product [data-v-product-content] = $product['content']

	//catch all data attributes
	@cart-product [data-v-cart-product-*]|innerText = $product['@@__data-v-cart-product-(*)__@@']

	@cart-product [data-v-cart-product-url]|href = 
		<?php echo htmlentities(Vvveb\url(['module' => 'product', 'product_id' => $product['product_id']]));?>
	@cart-product [data-v-cart-product-remove-url]|href = 
		<?php echo htmlentities(Vvveb\url(['module' => 'cart', 'action' => 'remove', 'product_id' => $product['product_id']]));?>
		
	@cart-product|data-product_id = $product['product_id']		

	@cart-product [data-v-cart-product-image]|src = $product['image']


@cart-product|after = <?php } 
}
?>

@total = [data-v-component-cart] [data-v-cart-totals] [data-v-cart-total]

@total|deleteAllButFirstChild
@total|before = <?php

$totals  = $cart['totals'] ?? [];
if(is_array($totals)) foreach ($totals as $index => $total) {
?>

	//catch all data attributes
	@total [data-v-cart-total-*]|innerText =  $total['@@__data-v-cart-total-(*)__@@']
	@total a[data-v-cart-total-*]|href = $total['@@__data-v-cart-total-(*)__@@']
	@total input[data-v-cart-total-*]|value = $total['@@__data-v-cart-total-(*)__@@']

@total|after = <?php }?>
