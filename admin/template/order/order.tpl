import(crud.tpl, {"type":"order"})

@order-product = [data-v-order] [data-v-order-product]

@order-product|deleteAllButFirstChild
@order-product|before = <?php

$products  = $this->products ?? [];
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


@total = [data-v-order] [data-v-order-totals-total]

@total|deleteAllButFirstChild
@total|before = <?php

$totals  = $this->total ?? [];
if(is_array($totals)) foreach ($totals as $index => $total) {
?>

	//catch all data attributes
	@total [data-v-order-total-*]|innerText = $total['@@__data-v-order-total-(*)__@@']
	@total a[data-v-order-total-*]|href = $total['@@__data-v-order-total-(*)__@@']
	@total input[data-v-order-total-*]|value = $total['@@__data-v-order-total-(*)__@@']

@total|after = <?php }?>


@history = [data-v-order] [data-v-order-history]

@history|deleteAllButFirstChild
@history|before = <?php

$histories  = $this->history ?? [];
if(is_array($histories)) foreach ($histories as $index => $history) {
?>

	//catch all data attributes
	@history [data-v-order-history-*]|innerText = $history['@@__data-v-order-history-(*)__@@']
	@history a[data-v-order-history-*]|href = $history['@@__data-v-order-history-(*)__@@']
	@history input[data-v-order-history-*]|value = $history['@@__data-v-order-history-(*)__@@']

@history|after = <?php }?>


[data-v-order] [data-v-order-*]|innerText = $this->order['@@__data-v-order-(*)__@@']

[data-v-order] [data-v-order-site_url]|href = $this->order['site_url']

[data-v-order] .badge[data-v-order-order_status]|addClass = <?php echo $this->order['class'] ?? '';?>

[data-v-order-print-url]|href = $this->printUrl
[data-v-order-print-shipping-url]|href = $this->printShippingUrl


@payment  = [data-v-payments] [data-v-payment]
@payment|deleteAllButFirstChild

@payment|before = <?php
$order_payment = $this->order_payment;
$count = 0;
if($order_payment && is_array($order_payment)) {
	$count = count($order_payment);
	foreach ($order_payment as $index => $payment) {?>
		
		@payment|data-payment_id = $payment['payment_id']
		
		@payment input[data-v-payment-*] = $payment['@@__data-v-payment-(*)__@@']
		
		@payment img[data-v-payment-*]|src = $payment['@@__data-v-payment-(*)__@@']
		
		@payment [data-v-payment-*]|innerText = $payment['@@__data-v-payment-(*)__@@']
		
		@payment a[data-v-payment-*]|href = $payment['@@__data-v-payment-(*)__@@']
	
	@payment|after = <?php 
	} 
}
?>

@shipping  = [data-v-shippings] [data-v-shipping]
@shipping|deleteAllButFirstChild

@shipping|before = <?php
$order_shipping = $this->order_shipping;
$count = 0;
if($order_shipping && is_array($order_shipping)) {
	$count = count($order_shipping);
	foreach ($order_shipping as $index => $shipping) {?>
		
		@shipping|data-shipping_id = $shipping['shipping_id']
		
		@shipping input[data-v-shipping-*] = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping img[data-v-shipping-*]|src = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping [data-v-shipping-*]|innerText = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping a[data-v-shipping-*]|href = $shipping['@@__data-v-shipping-(*)__@@']
	
	@shipping|after = <?php 
	} 
}
?>
