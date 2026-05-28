//product variant
@product-variant = [data-v-product] [data-v-product-variant] [data-v-variant]
@product-variant|deleteAllButFirst

@product-variant|before = <?php
if(isset($this->product['product_variant']) && is_array($this->product['product_variant']))
foreach ($this->product['product_variant'] as $options => $variant) {
	$product_variant_id = $variant['product_variant_id'];
?>
	@product-variant|data-id                       = $variant['product_variant_id']
	@product-variant [data-v-variant-name]         = $variant['name']
	@product-variant input[data-v-variant-*]|value = $variant['@@__data-v-variant-(*)__@@']
	@product-variant input[data-v-variant-*]|name  = <?php echo "product_variant[$product_variant_id][@@__data-v-variant-(*)__@@]";?>

	@product-variant [data-v-variant-combination]|value = $options
	
	@product-variant [data-v-variant-combination]|before = <?php 
		$name = 'combinations';
		$selected = $variant['options'] ?? 1;
	?>		
	
@product-variant|after = <?php
	}	
?>


[data-v-option-variant-warning]|before = <?php 
	if (!isset($this->product['product_option']) || !is_array($this->product['product_option']) || (count($this->product['product_option']) < 2)) { 
?>
[data-v-option-variant-warning]|after = <?php } ?>