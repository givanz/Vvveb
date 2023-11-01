@attributes =  [data-v-component-product-attributes]
@attribute  = [data-v-component-product-attributes] [data-v-attributes]

@attributes|prepend = <?php
if (isset($_attributes_idx)) $_attributes_idx++; else $_attributes_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$attributes = $current_component = $this->_component['product_attributes'][$_attributes_idx] ?? [];

$_pagination_count = $attributes['count'] ?? 0;
$_pagination_limit = isset($attributes['limit']) ? $attributes['limit'] : 5;	
?>

@attribute|deleteAllButFirstChild
@attribute [data-v-attribute]|deleteAllButFirstChild

@attribute|before = <?php
if($attributes && is_array($attributes['attribute'])) {
	$group = false;
	foreach ($attributes['attribute'] as $index => $attribute) {?>
		
		@attribute [data-v-group]|before = <?php 
			if ($group != $attribute['group']) { 
				$group = $attribute['group'];
		?>
		
		@attribute [data-v-group]|after = <?php 
		} ?>
		
		@attribute|data-attribute_id = $attribute['attribute_id']
		
		@attribute|id = <?php echo 'attribute-' . $attribute['attribute_id'];?>
		
		@attribute [data-v-attribute-content] = <?php echo $attribute['content'];?>
		
		@attribute img[data-v-attribute-*]|src = $attribute['@@__data-v-attribute-(*)__@@']
		
		@attribute [data-v-attribute-*]|innerText = $attribute['@@__data-v-attribute-(*)__@@']
		
		@attribute a[data-v-attribute-*]|href = $attribute['@@__data-v-attribute-(*)__@@']
	
	@attribute|after = <?php 
	} 
}
?>
