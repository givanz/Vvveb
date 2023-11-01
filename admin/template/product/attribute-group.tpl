import(crud.tpl, {"type":"attribute_group"})

@attribute = [data-v-attributes] [data-v-attribute]
@attribute|deleteAllButFirstChild

@attribute|before = <?php
$count = 0;
$attribute_index = 0;
$attribute = [];
if(isset($this->attributes) && is_array($this->attributes)) {
	foreach ($this->attributes as $attribute_index => $attribute) { ?>
	

	@attribute [data-v-attribute-*]|innerText  = $attribute['@@__data-v-attribute-(*)__@@']
	@attribute input[data-v-attribute-*]|value  = $attribute['@@__data-v-attribute-(*)__@@']
	@attribute input[data-v-attribute-*]|value = $attribute['@@__data-v-attribute-(*)__@@']	
	@attribute a[data-v-attribute-*]|href 	 = $attribute['@@__data-v-attribute-(*)__@@']	

	@attribute [data-v-attribute-*]|name  = <?php echo "attribute[$attribute_index][@@__data-v-attribute-(*)__@@]";?>
	@attribute [data-v-attribute-*]|data-v-attribute-id  = <?php echo $attribute['attribute_id'];?>
	@attribute|data-id  = <?php echo $attribute['attribute_id'];?>
	
	
	@attribute|after = <?php 
		$count++;
	} 
}?>
