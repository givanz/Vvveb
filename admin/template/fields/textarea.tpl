body|append = from(fields/textarea.html|body > *)

[data-v-field-*] textarea|placeholder = $field['placeholder']
[data-v-field-*] textarea             = $field['value']
[data-v-field-*] textarea|name        = $field['name']
[data-v-field-*] textarea|rows        = $field['rows']
[data-v-field-*] textarea|cols        = $field['cols']
// [data-v-field-*] textarea|id          = <?php if (isset($field['id'])) echo 'field-' .  $field['name'] . $field['id'];?>
[data-v-field-*] textarea|addNewAttribute = <?php if (isset($field['id'])) echo 'data-v-field-' . $field['id'];?>
[data-v-field-*] textarea|addNewAttribute = <?php if (isset($field['readonly']) && $field['readonly']) echo 'readonly';?>
[data-v-field-*] textarea|addNewAttribute = <?php if (isset($field['required']) && $field['required']) echo 'required';?>
