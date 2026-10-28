body|append = from(fields/optgroup.html|body > *)
/*
[data-v-field-*] select|placeholder     = $field['placeholder']
[data-v-field-*] select|name            = $field['name']
[data-v-field-*] select|addNewAttribute = <?php if (isset($field['readonly']) && $field['readonly']) echo 'readonly';?>
[data-v-field-*] select|addNewAttribute = <?php if (isset($field['required']) && $field['required']) echo 'required';?>
*/

@select   = [data-v-field-optgroup] select
@optgroup = [data-v-field-optgroup] select optgroup
@option   = [data-v-field-optgroup] select option
@option|deleteAllButFirstChild
@optgroup|deleteAllButFirstChild

@optgroup|before = <?php
$count = 0;
$option_index = 0;
$option = [];
if(isset($field['options']) && is_array($field['options'])) {
	foreach ($field['options'] as $optname => $options) { ?>

		@optgroup|label = <?php echo htmlspecialchars(ucfirst($optname));?>
		
		@option|before = <?php		
		foreach ($options as $value => $name) { ?>
			

			@option|innerText = $name
			@option|value     = $value
			
			@option|addNewAttribute = <?php if (isset($field['value']) && $field['value'] == $value) echo 'selected';?>

		@option|after = <?php 
			$count++;
		} ?>

	@optgroup|after = <?php 
	} 
}?>
