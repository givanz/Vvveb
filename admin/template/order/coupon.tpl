import(crud.tpl, {"type":"coupon"})

//coupon product
@coupon-product = [data-v-coupon] [data-v-coupon-product] [data-v-product]
@coupon-product|deleteAllButFirst

@coupon-product|before = <?php
if(isset($this->coupon['coupon_product']) && is_array($this->coupon['coupon_product']))
foreach ($this->coupon['coupon_product'] as $coupon_product_id => $product)  {
?>
	@coupon-product input[data-v-product-*]|value = $product['@@__data-v-product-(*)__@@']
	@coupon-product [data-v-product-*] = $product['@@__data-v-product-(*)__@@']
	
@coupon-product|after = <?php
	}	
?>


//coupon taxonomy
@coupon-taxonomy = [data-v-coupon] [data-v-coupon-taxonomy] [data-v-taxonomy]
@coupon-taxonomy|deleteAllButFirst

@coupon-taxonomy|before = <?php
if(isset($this->coupon['coupon_taxonomy']) && is_array($this->coupon['coupon_taxonomy']))
foreach ($this->coupon['coupon_taxonomy'] as $coupon_taxonomy_id => $taxonomy)  {
?>
	@coupon-taxonomy input[data-v-taxonomy-*]|value = $taxonomy['@@__data-v-taxonomy-(*)__@@']
	@coupon-taxonomy [data-v-taxonomy-*] = $taxonomy['@@__data-v-taxonomy-(*)__@@']
	
@coupon-taxonomy|after = <?php
	}	
?>

