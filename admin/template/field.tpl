[data-v-field-*]|before = <?php
  if (!function_exists('field_@@__data-v-field-(*)__@@')) {
    function field_@@__data-v-field-(*)__@@($field) {
      $placeholder = $field['placeholder'] ?? '';
?>

[data-v-field-*]|class       = $field['class']
[data-v-field-*] label div   = $field['label']
[data-v-field-*] label|class = $field['label-class']
[data-v-field-*] > div|class = $field['input-class']
[data-v-field-*] label span  = $field['instructions']

[data-v-field-*] input[name="value"]|placeholder = $field['placeholder']
[data-v-field-*] input[name="value"]|value       = $field['value']
[data-v-field-*] input[name="value"]|name        = $field['name']

// [data-v-field-*] input|id          = <?php if (isset($field['id'])) echo 'field-' . $field['name'] . $field['id'];?>
[data-v-field-*] input|addNewAttribute = <?php if (isset($field['id'])) echo 'data-v-field-' . $field['id'];?>
[data-v-field-*] input|addNewAttribute = <?php if (isset($field['readonly']) && $field['readonly']) echo 'readonly';?>
[data-v-field-*] input|addNewAttribute = <?php if (isset($field['required']) && $field['required']) echo 'required';?>

[data-v-field-*]|after = <?php
   }
 }
?>
