//product related
@product-related = [data-v-product] [data-v-product-related] [data-v-related]
@product-related|deleteAllButFirst

@product-related|before = <?php
if(isset($this->product['product_related']) && is_array($this->product['product_related']))
foreach ($this->product['product_related'] as $product_related_id => $related)  {
?>
	@product-related input[data-v-related-*]|value = $related['@@__data-v-related-(*)__@@']
	@product-related [data-v-related-*] = $related['@@__data-v-related-(*)__@@']
	
@product-related|after = <?php
	}	
?>
