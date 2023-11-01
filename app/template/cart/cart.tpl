import(common.tpl)

[data-v-cart-page] [data-v-cart-cart-*]|innerText = $this->cart['@@__data-v-cart-cart-(*)__@@']

@cart-product = [data-v-cart] [data-v-cart-product]

@cart-product|deleteAllButFirstChild
@cart-product|before = <?php

$products  = $this->cart['products'];
if(is_array($products)) foreach ($products as $index => $product) {
?>

	//@cart-product [data-v-cart-product-name] = $product['name']
	//@cart-product [data-v-cart-product-content] = $product['content']
	//@cart-product [data-v-cart-product-amount] = <?php echo $product['amount'];?>

	@cart-product|data-product_id = $product['product_id']	
	@cart-product img[data-v-cart-product-image]|src = $product['image']

	//catch all data attributes
	@cart-product [data-v-cart-product-*]|innerText = <?php echo Vvveb\escHtml( $product['@@__data-v-cart-product-(*)__@@'] ?? '' )?>
	@cart-product a[data-v-cart-product-*]|href = $product['@@__data-v-cart-product-(*)__@@']
	@cart-product img[data-v-cart-product-*]|src = $product['@@__data-v-cart-product-(*)__@@']
	@cart-product input[data-v-cart-product-*]|value = <?php echo Vvveb\escAttr( $product['@@__data-v-cart-product-(*)__@@'] )?>

@cart-product|after = <?php }?>


@total = [data-v-cart-page] [data-v-cart-totals] [data-v-cart-total]

@total|deleteAllButFirstChild
@total|before = <?php

$totals  = $this->cart['totals'];
if(is_array($totals)) foreach ($totals as $index => $total) {
?>

	//catch all data attributes
	@total [data-v-cart-total-*]|innerText = $total['@@__data-v-cart-total-(*)__@@']
	@total a[data-v-cart-total-*]|href = $total['@@__data-v-cart-total-(*)__@@']
	@total input[data-v-cart-total-*]|value = $total['@@__data-v-cart-total-(*)__@@']

@total|after = <?php }?>
