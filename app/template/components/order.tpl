[data-v-component-order]|before = <?php
if (isset($order_idx)) $order_idx++; else $order_idx = 0;
$order = $this->_component['order'][$order_idx] ?? [];
?>

@order-product = [data-v-component-order] [data-v-cart] [data-v-order-product]

@order-product|deleteAllButFirstChild
@order-product|before = <?php

$products  = $order['products'] ?? [];
if(is_array($products)) foreach ($products as $index => $product) {
?>

	@order-product|data-product_id = $product['product_id']	
	@order-product img[data-v-order-product-image]|src = $product['image']

	//catch all data attributes
	@order-product [data-v-order-product-*]|innerText = <?php echo Vvveb\escHtml( $product['@@__data-v-order-product-(*)__@@'] ?? '' )?>
	@order-product a[data-v-order-product-*]|href = $product['@@__data-v-order-product-(*)__@@']
	@order-product img[data-v-order-product-*]|src = $product['@@__data-v-order-product-(*)__@@']
	@order-product input[data-v-order-product-*]|value = <?php echo Vvveb\escAttr( $product['@@__data-v-order-product-(*)__@@'] )?>

@order-product|after = <?php }?>


@cart-option = [data-v-component-order] [data-v-order-product] [data-v-product-option]
@cart-option|deleteAllButFirstChild


@cart-option|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => 'product_option_value_id'] : false;
$option_value = empty($product['option_value']) ? $_default : $product['option_value'];

if($option_value) {
	foreach ($option_value as $product_option_value_id => $value) { ?>

	@cart-option [data-v-product-option-*]|innerText = $value['@@__data-v-product-option-(*)__@@']


@cart-option|after = <?php } 
}
?>

@total = [data-v-component-order] [data-v-order-totals-total]

@total|deleteAllButFirstChild
@total|before = <?php

$totals  = $order['total'] ?? [];
if(is_array($totals)) foreach ($totals as $index => $total) {
?>

	//catch all data attributes
	@total [data-v-order-total-*]|innerText = $total['@@__data-v-order-total-(*)__@@']
	@total a[data-v-order-total-*]|href = $total['@@__data-v-order-total-(*)__@@']
	@total input[data-v-order-total-*]|value = $total['@@__data-v-order-total-(*)__@@']

@total|after = <?php }?>


@history = [data-v-component-order] [data-v-order-history]

@history|deleteAllButFirstChild
@history|before = <?php

$histories  = $order['history'] ?? [];
if(is_array($histories)) foreach ($histories as $index => $history) {
?>

	//catch all data attributes
	@history [data-v-order-history-*]|innerText = $history['@@__data-v-order-history-(*)__@@']
	@history a[data-v-order-history-*]|href = $history['@@__data-v-order-history-(*)__@@']
	@history input[data-v-order-history-*]|value = $history['@@__data-v-order-history-(*)__@@']

@history|after = <?php }?>


[data-v-component-order] [data-v-order-*]|innerText = $order['order']['@@__data-v-order-(*)__@@']

[data-v-component-order] [data-v-order-site_url]|href = $order['order']['site_url']
