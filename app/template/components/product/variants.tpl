@variants = [data-v-component-product-variants]
@variant  = [data-v-component-product-variants] [data-v-variant]
@value   = [data-v-component-product-variants] [data-v-variant] [data-v-value]

@variant|deleteAllButFirstChild
@value|deleteAllButFirstChild

@variants|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_variants_idx)) $_variants_idx++; else $_variants_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$product_variants = $current_component = $this->_component['product_variants'][$_variants_idx] ?? [];

$variants = $product_variants['product_variant'] ?? [];

$_pagination_count = $count = $product_variants['count'] ?? 0;
$_pagination_limit = isset($variants['limit']) ? $variants['limit'] : 5;	
?>


@variant|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [
  1 =>  [
    'product_variant_id' => 1,
    'variant_id' => 1,
	'required' => 1,
    'type' => 'radio',
    'values' => [ 0 => 
       [
        'product_variant_value_id' => 1,
        'product_variant_id' => 1,
        'product_id' => 1,
        'variant_id' => 1,
        'variant_value_id' => 1,
		'price' => 1,
		'image' => 'img',
		]
	]
  ] 
] : false;

$variants = empty($variants) ? $_default : $variants;

if($variants && is_array($variants)) {
	foreach ($variants as $index => $variant) {?>
		
		@variant|data-variant_id = $variant['variant_id']
		
		@variant|id = <?php echo 'variant-' . ($variant['product_variant_id'] ?? 0);?>
		
		@variant [data-v-variant-content] = <?php echo($variant['content'] ?? '');?>
		
		@variant img[data-v-variant-*]|src = $variant['@@__data-v-variant-(*)__@@']
		
		@variant [data-v-variant-*]|innerText = $variant['@@__data-v-variant-(*)__@@']
		
		@variant [data-v-variant-input]|value = $variant['value']
		@variant [data-v-variant-input]|name = <?php echo 'variant[' . $variant['product_variant_id'] . ']';?>
		
		@variant a[data-v-variant-*]|href = $variant['@@__data-v-variant-(*)__@@']
		
		@value|before = <?php
			if(isset($variant['values']) && is_array($variant['values'])) {
				foreach ($variant['values'] as $vindex => $value) {?>

			@variant variant[data-v-value] = $value['name']
			@variant variant[data-v-value]|value = $value['product_variant_value_id']

			@value [data-v-value-*]|innerText = $value['@@__data-v-value-(*)__@@']
			
			@value [data-v-value-input]|name = <?php echo 'variant[' . $variant['product_variant_id'] . ']';?>
			@value [data-v-value-input]|addNewAttribute = <?php if ($variant['required']) echo 'required';?>
			@value [data-v-value-input]|value = $value['product_variant_value_id']
			
			@value [data-v-value-price_formatted]|if_exists = $value['price']
						
			@value img[data-v-value-*]|src = $value['@@__data-v-value-(*)__@@']
	
		@value|after = <?php 
			} 
		} 
		?>
		
	@variant|after = <?php 
	} 
}
?>
