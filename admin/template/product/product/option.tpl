//product options
@product-option = [data-v-product] [data-v-product-poption] [data-v-poption]
@value 			= [data-v-option-values] [data-v-option-value]

@product-option|deleteAllButFirstChild

@product-option|before = <?php
if(isset($this->product['product_option']) && is_array($this->product['product_option'])) {
$index = 0;
foreach ($this->product['product_option'] as $product_option_id => $poption) {
	$index++; 	
?>

	@product-option .values|addClass = <?php if (! ($poption['type'] == 'radio' || $poption['type'] == 'checkbox' || $poption['type'] == 'select') ) echo 'd-none';?>
	@product-option .default|addClass = <?php if ( ($poption['type'] == 'radio' || $poption['type'] == 'checkbox' || $poption['type'] == 'select') ) echo 'd-none';?>

	@product-option [data-v-poption-*]|name  = <?php echo "product_option[$product_option_id][@@__data-v-poption-(*)__@@]";?>

	@product-option input[data-v-poption-*]|value = $poption['@@__data-v-poption-(*)__@@']
	@product-option [data-v-poption-*]|innerText = <?php echo $poption['@@__data-v-poption-(*)__@@'];?>
	
	@product-option [data-v-poption-option_id]|before = <?php $selected = $poption['option_id'] ?? false;?>
	@product-option [data-v-poption-required]|addNewAttribute = <?php if ($poption['required'] == '1') echo 'checked';?>
	
	@product-option a[data-v-poption-product_option_id]|href = <?php echo '#option-' . $poption['product_option_id'];?>
	@product-option a[data-v-poption-product_option_id]|data-id = <?php echo $poption['product_option_id'];?>
	@product-option div[data-v-poption-product_option_id]|id = <?php echo 'option-' . $poption['product_option_id'];?>
	
	@product-option a[data-v-poption-product_option_id]|addClass = <?php if ($index == 1) echo 'active';?>
	[data-v-product] [data-v-product-poption] div[data-v-poption]|addClass = <?php if ($index == 1) echo 'active show';?>
	
	[data-v-product] [data-v-product-poption] div[data-v-poption]|id = <?php echo 'option-' . $poption['product_option_id'];?>
	
	@product-option [data-v-user_group_id]|name = <?php echo "product_option[$product_option_id][user_group_id]";?>
	
		
		@value|deleteAllButFirstChild
		@value|before = <?php if (isset($poption['values'])) foreach ($poption['values'] as $product_option_value_id => $poption_value) {?>
		
		@value input[type="radio"]|addNewAttribute = <?php if ($poption_value['@@__data-v-option-value-(*)__@@'] == '@@__value__@@') echo 'checked';?>
		@value input[type="checkbox"]|addNewAttribute = <?php if ($poption_value['@@__data-v-option-value-(*)__@@'] == 1) echo 'checked';?>

		@value|data-id  = <?php echo $poption_value['product_option_value_id'];?>

		@value [data-v-option-value-*]|name      = <?php echo "product_option[$product_option_id][product_option_value][$product_option_value_id][@@__data-v-option-value-(*)__@@]";?>
		[data-v-poption] .template [data-v-option-value-*]|name   = <?php echo "product_option[$product_option_id][product_option_value][#][@@__data-v-option-value-(*)__@@]";?>
		@value [data-v-option-value-*]|innerText = <?php echo $poption_value['@@__data-v-option-value-(*)__@@'] ?? '';?>
		
		@value|after = <?php
			}	
		?> 

@product-option|after = <?php
	}	
	reset($this->product['product_option']);
}	
?>

@option = select.option_value option
@option|deleteAllButFirstChild
@option|before = <?php
if (isset($poption['option_id']) && isset($this->product['option_value_content'][$poption['option_id']])) 
	foreach ($this->product['option_value_content'][$poption['option_id']] as $poption_value_id => $poption_value_content) {?> 
	
	@option = <?php echo $poption_value_content['name'] ?? '';?>
	@option|value = <?php echo $poption_value_content['option_value_id'] ?? '';?>
	@option|addNewAttribute = <?php if ($poption_value_content['option_value_id'] == $poption_value['option_value_id']) echo 'selected';?>
	
@option|after = <?php
	}	
?>
