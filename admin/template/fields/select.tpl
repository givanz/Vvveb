body|append = from(fields/select.html|body > *)

[data-v-field-*] select|placeholder     = $field['placeholder']
[data-v-field-*] select|name            = $field['name']
[data-v-field-*] select|rows            = $field['rows']
[data-v-field-*] select|id              = <?php if (isset($field['id'])) echo 'field-' . $field['name'] . $field['id'];?>
[data-v-field-*] select|addNewAttribute = <?php if (isset($field['id'])) echo 'data-v-field-' . $field['id'];?>
[data-v-field-*] select|addNewAttribute = <?php if (isset($field['readonly']) && $field['readonly']) echo 'readonly';?>
[data-v-field-*] select|addNewAttribute = <?php if (isset($field['required']) && $field['required']) echo 'required';?>


@select = [data-v-field-select] select
@option = [data-v-field-select] select option
@option|deleteAllButFirstChild

@option|before = <?php
$count = 0;
$option_index = 0;
$option = [];
if(isset($field['options']) && is_array($field['options'])) {
	foreach ($field['options'] as $value => $name) { ?>
	

	@option|innerText  = $name
	@option|value      = $value
	
	@option|addNewAttribute = <?php if (isset($field['value']) && $field['value'] == $value) echo 'selected';?>

	@option|after = <?php 
		$count++;
	} 
}?>
