import(common.tpl)

[data-v-cart-page] [data-v-cart-cart-*]|innerText = $this->cart['@@__data-v-cart-cart-(*)__@@']

@cart-product = [data-v-cart] [data-v-cart-product]

@cart-product|deleteAllButFirstChild
@cart-product|before = <?php

$products  = $this->cart['products'];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['product_id' => 1, 'product_id' => 1, 'image' => '#']] : false;
$products = empty($products) ? $_default : $products;

if(is_array($products)) foreach ($products as $key => $product) {
?>

	//@cart-product [data-v-cart-product-name] = $product['name']
	//@cart-product [data-v-cart-product-content] = $product['content']
	//@cart-product [data-v-cart-product-amount] = <?php echo htmlspecialchars($product['amount']);?>

	@cart-product|data-product_id = $product['product_id']	
	@cart-product|data-key = $key	

	@cart-product img[data-v-cart-product-image]|src = $product['image']

	//catch all data attributes
	@cart-product [data-v-cart-product-*]|innerText  = $product['@@__data-v-cart-product-(*)__@@']
	@cart-product a[data-v-cart-product-*]|href      = $product['@@__data-v-cart-product-(*)__@@']
	@cart-product img[data-v-cart-product-*]|src     = $product['@@__data-v-cart-product-(*)__@@']
	@cart-product input[data-v-cart-product-*]|value = $product['@@__data-v-cart-product-(*)__@@']

@cart-product|after = <?php }?>


@cart-option = [data-v-cart] [data-v-cart-product] [data-v-product-option]
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

@total = [data-v-cart-page] [data-v-cart-totals] [data-v-cart-total]

@total|deleteAllButFirstChild
@total|before = <?php

$totals  = $this->cart['totals'];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$totals = empty($totals) ? $_default : $totals;

if(is_array($totals)) foreach ($totals as $index => $total) {
?>

	//catch all data attributes
	@total [data-v-cart-total-*]|innerText  = $total['@@__data-v-cart-total-(*)__@@']
	@total a[data-v-cart-total-*]|href      = $total['@@__data-v-cart-total-(*)__@@']
	@total input[data-v-cart-total-*]|value = $total['@@__data-v-cart-total-(*)__@@']

@total|after = <?php }?>


@coupon = [data-v-cart-page] [data-v-cart-coupons] [data-v-cart-coupon]

@coupon|deleteAllButFirstChild
@coupon|before = <?php

$coupons  = $this->cart['coupons'];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$coupons = empty($coupons) ? $_default : $coupons;

if(is_array($coupons)) foreach ($coupons as $index => $coupon) {
?>

	//catch all data attributes
	@coupon [data-v-cart-coupon-*]|innerText  = $coupon['@@__data-v-cart-coupon-(*)__@@']
	@coupon a[data-v-cart-coupon-*]|href      = $coupon['@@__data-v-cart-coupon-(*)__@@']
	@coupon input[data-v-cart-coupon-*]|value = $coupon['@@__data-v-cart-coupon-(*)__@@']

@coupon|after = <?php }?>
