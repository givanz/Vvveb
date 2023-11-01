@options = [data-v-component-product-options]
@option  = [data-v-component-product-options] [data-v-option]
@value   = [data-v-component-product-options] [data-v-option] [data-v-value]

@option|deleteAllButFirstChild
@value|deleteAllButFirstChild

@options|prepend = <?php
if (isset($_options_idx)) $_options_idx++; else $_options_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$product_options = $current_component = $this->_component['product_options'][$_options_idx] ?? [];

$options = $product_options['product_option'] ?? [];

$_pagination_count = $count = $product_options['count'] ?? 0;
$_pagination_limit = isset($options['limit']) ? $options['limit'] : 5;	
?>


@option|before = <?php
if($options && is_array($options)) {
	foreach ($options as $index => $option) {?>
		
		@option|data-option_id = $option['option_id']
		
		@option|id = <?php echo 'option-' . ($option['product_option_id'] ?? 0);?>
		
		@option [data-v-option-content] = <?php echo $option['content'] ?? '';?>
		
		@option img[data-v-option-*]|src = $option['@@__data-v-option-(*)__@@']
		
		@option [data-v-option-*]|innerText = $option['@@__data-v-option-(*)__@@']
		
		@option [data-v-option-input]|value = $option['value']
		@option [data-v-option-input]|name = <?php echo 'option[' . $option['option_id'] . ']';?>
		
		@option a[data-v-option-*]|href = $option['@@__data-v-option-(*)__@@']
		
		@value|before = <?php
			if(isset($option['values']) && is_array($option['values'])) {
				foreach ($option['values'] as $vindex => $value) {?>

			@option option[data-v-value] = $value['name']
			@option option[data-v-value]|value = $value['product_option_value_id']

			@value [data-v-value-*]|innerText = $value['@@__data-v-value-(*)__@@']
			
			@value [data-v-value-input]|name = <?php echo 'option[' . $option['option_id'] . ']';?>
			@value [data-v-value-input]|addNewAttribute = <?php if ($option['required']) echo 'required';?>
			@value [data-v-value-input]|value = $value['product_option_value_id']
			
			@value [data-v-value-price_formatted]|if_exists = $value['price']
						
			@value img[data-v-value-*]|src = $value['@@__data-v-value-(*)__@@']
	
		@value|after = <?php 
			} 
		} 
		?>
		
	@option|after = <?php 
	} 
}
?>
