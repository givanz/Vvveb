import(fields/*.tpl)
import(field.tpl);

body|append = <?php
foreach ($this->fields as $name => $field) {
	if (!$field) continue;
	$function = 'field_' . $field['type'];
	$function($field);
}
?>

[data-v-field-google_map] input.address|value = $field['value']['address']
[data-v-field-google_map] input.lat|value     = $field['value']['lat']
[data-v-field-google_map] input.long|value    = $field['value']['long']
[data-v-field-google_map] input.zoom|value    = $field['value']['zoom']

[data-v-field-google_map] input.address|name = <?php echo $field['name'] . '[address]';?>
[data-v-field-google_map] input.lat|name     = <?php echo $field['name'] . '[lat]';?>
[data-v-field-google_map] input.long|name    = <?php echo $field['name'] . '[long]';?>
[data-v-field-google_map] input.zoom|name    = <?php echo $field['name'] . '[zoom]';?>


//[data-v-field-google_map] input.zoom|style = <?php echo "background:red";?>

import(ifmacros.tpl);
