//product variant
@product-variant = [data-v-product] [data-v-product-variant] [data-v-variant]
@product-variant|deleteAllButFirst

@product-variant|before = <?php
if(isset($this->product['product_variant']) && is_array($this->product['product_variant']))
foreach ($this->product['product_variant'] as $product_variant_id => $variant)  {
?>
	@product-variant input[data-v-variant-*]|value = $variant['@@__data-v-variant-(*)__@@']
	@product-variant [data-v-variant-*] = $variant['@@__data-v-variant-(*)__@@']
	
@product-variant|after = <?php
	}	
?>
