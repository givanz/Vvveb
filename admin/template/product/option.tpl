import(crud.tpl, {"type":"option"})

@option_value = [data-v-option_values] [data-v-option_value]
@option_value|deleteAllButFirstChild

@option_value|before = <?php
$count = 0;
$option_value_index = 0;
$option_value = [];
if(isset($this->option_values) && is_array($this->option_values)) {
	foreach ($this->option_values as $option_value_index => $option_value) { ?>
	

	@option_value [data-v-option_value-*]|innerText  = $option_value['@@__data-v-option_value-(*)__@@']
	@option_value input[data-v-option_value-*]|value = $option_value['@@__data-v-option_value-(*)__@@']
	@option_value input[data-v-option_value-*]|value = $option_value['@@__data-v-option_value-(*)__@@']	
	@option_value a[data-v-option_value-*]|href 	 = $option_value['@@__data-v-option_value-(*)__@@']	

	@option_value [data-v-option_value-*]|name  = <?php echo "option_value[$option_value_index][@@__data-v-option_value-(*)__@@]";?>
	@option_value [data-v-option_value-*]|data-v-option_value-id  = <?php echo $option_value['option_value_id'];?>
	
	// image input
	@option_value input[data-v-image]|name 			   = <?php echo 'option_value[' . $option_value_index . '][image]';?>
	@option_value input[data-v-image]|value 		   = $option_value['image']	
	@option_value input[data-v-image]|id 			   = <?php echo 'option_value_' . $option_value_index . '-input';?>
	
	// image	
	@option_value img[data-v-image]|id 				   = <?php echo 'option_value_' . $option_value_index. '-thumb';?>
	@option_value img[data-v-image]|src 			   = $option_value['image_url']
	@option_value img[data-v-image]|data-target-input  = <?php echo '#option_value_' . $option_value_index . '-input';?>
	@option_value img[data-v-image]|data-target-thumb  = <?php echo '#option_value_' . $option_value_index . '-thumb';?>

	@option_value button[data-media-gallery]|data-target-input  = <?php echo '#option_value_' . $option_value_index . '-input';?>
	@option_value button[data-media-gallery]|data-target-thumb  = <?php echo '#option_value_' . $option_value_index . '-thumb';?>
	
	@option_value|data-id  = <?php echo $option_value['option_value_id'];?>
	
	@option_value|after = <?php 
		$count++;
	} 
}?>

