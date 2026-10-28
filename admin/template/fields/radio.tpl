body|append = from(fields/radio.html|body > *)

[data-v-field-checkbox] input|addNewAttribute = <?php if ((isset($field['checked']) && $field['checked'])) echo 'checked';?>
[data-v-field-checkbox] input|value           = $field['value']
