@fields =  [data-v-component-fields]
@field  = [data-v-component-fields] [data-v-field]
@subfields  = [data-v-component-fields] [data-v-field] [data-v-subfields]
@subfield  = [data-v-component-fields] [data-v-field] [data-v-subfields] [data-v-subfields-field]

@field|deleteAllButFirstChild

@fields|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_fields_idx)) $_fields_idx++; else $_fields_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$fields = $current_component = $this->_component['fields'][$_fields_idx] ?? [];

$_pagination_count = $fields['count'] ?? 0;
$_pagination_limit = isset($fields['limit']) ? $fields['limit'] : 5;	
?>


@field|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$fields['field'] = empty($fields['field']) ? $_default : $fields['field'];

if($fields && is_array($fields['field'])) {
	foreach ($fields['field'] as $field_id => $field) { 
			if (!isset($field['value'])) continue;
			$is_array = is_array($field['value']);
			$hasType = false;
		?>
		
		//@field|data-field_id = $field['field_id']
		//@field|id = <?php echo 'field-' . $field['field_id'];?>
		
		@field|addClass = <?php echo 'field-' . $field['field_id'];?>
		
		@field img[data-v-field-*]|src = $field['@@__data-v-field-(*)__@@']
		
		@field [data-v-field-*]|innerText = $field['@@__data-v-field-(*)__@@']
		
		@field a[data-v-field-*]|href = $field['@@__data-v-field-(*)__@@']

		@field input[data-v-field-field_id]|addNewAttribute = <?php 
			if (isset($field['active']) && $field['active']) {
				echo 'checked';
			}
		?>
		
		@subfield|before = <?php
		if (isset($vvveb_is_page_edit) && $vvveb_is_page_edit) { $is_array = true;$field['value'] = ['' => ''];} 
		if ($is_array) foreach ($field['value'] as $subfield => $value) {?> 
		
			@subfield [data-v-subfields-field-*] = $subfield['@@__data-v-subfields-field-(*)__@@']
			@subfield [data-v-subfields-field-value] = $value
			@subfield [data-v-subfields-field-name] = $subfield
	
		@subfield|after = <?php 
			} 
		?>
		
		
		@field [data-v-type-*]|before = <?php 
			$type = '@@__data-v-type-(*)__@@';
			if (($type == $field['type']) || ($type == 'default' && !$hasType)) {
				$hasType = true;
		?>
		
		@field [data-v-type-*]|after = <?php 
			}
		?>
		
		
	@field|after = <?php 
	} 
}
?>
