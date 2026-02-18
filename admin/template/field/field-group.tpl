import(crud.tpl, {"type":"field_group"})

@field = [data-v-fields] [data-v-row] [data-v-field]
@row = [data-v-fields] [data-v-row]
@row|deleteAllButFirstChild
@field|deleteAllButFirstChild


@row|before = <?php

$close = false;
$row = -1;
$count = $this->count;
$index = 1;
$field = [];
$newRow = true;
$rowIndex = 0;
$fields = $this->fields ?? [];
if(isset($fields) && is_array($fields)) {
    foreach ($fields as $field_index => $field) {

	if ($row != $field['row']) {
		if ($row > -1) echo '</div>';
	?>
	
	@row|prepend = <?php 
		$row = $field['row'];
	} ?>		

	@field|class = <?php echo 'col ' . ($field['cols'] ?? '');?>
	@row|addNewAttribute = <?php echo "data-index=" . $rowIndex++;?>
    
	@field .input                      = <?php echo $field['field'];?>
	@field [data-v-field-settings]     = <?php echo $field['settings-tab'];?>
	@field [data-v-field-validation]   = <?php echo $field['validation-tab'];?>
	@field [data-v-field-presentation] = <?php echo $field['presentation-tab'];?>

    @field [data-v-field-*]|innerText  = $field['@@__data-v-field-(*)__@@']
    @field input[data-v-field-*]|value = $field['@@__data-v-field-(*)__@@']
    @field input[data-v-field-*]|value = $field['@@__data-v-field-(*)__@@']    
    @field a[data-v-field-*]|href        = $field['@@__data-v-field-(*)__@@']    
    @field select option|addNewAttribute = <?php if (isset($field['value']) && $field['value'] == '@@__value__@@') echo 'selected';?>

    @field [data-v-field-*]|name  = <?php echo "field[$field_index][@@__data-v-field-(*)__@@]";?>
    @field [data-v-field-*]|data-v-field-id  = $field['field_id']
    @field|data-id  = $field['field_id']

	// close row tag if last field or different row
    @row|append = <?php 
		if ($index >= $count) {
	?>
    @row|after = <?php 
		}
        $index++;
    } 
}?>


/* add type dropdown list */

@select = .field-types
@group = .field-types ul
@option = .field-types ul li.field
@@group|deleteAllButFirstChild
@option|deleteAllButFirstChild

@group|before = <?php
$groupType = $this->field_group['type'] ?? 'post';
$groupSubtype = $this->field_group['subtype'] ?? '';
if(isset($this->fieldTypes) && is_array($this->fieldTypes)) {
	foreach ($this->fieldTypes as $group => $fields) { $group = ucfirst($group);?>

		@group h6 = $group

		@option|before = <?php	
		foreach ($fields as $type => $value) { ?>

		@option span        = $value['name']
		@option a|data-type = $type
		@option i|class     = $value['icon']

		@option|after = <?php 
			}
		?>
	
	@group|after = <?php 
		$count++;
	} 
}?>

/* field type select */

@optgroup = [data-v-field-type] optgroup
@option = [data-v-field-type] option

@optgroup|deleteAllButFirstChild
@option|deleteAllButFirstChild

@optgroup|before = <?php
if(isset($this->fieldTypes) && is_array($this->fieldTypes)) {
	foreach ($this->fieldTypes as $group => $fields) { $group = ucfirst($group);?>

	@optgroup|label = $group
	
	@option|before = <?php	
		foreach ($fields as $type => $value) { ?>
		
		@option|value           = $type
		@option                 = $value['name']
		
	@option|after = <?php 
		}
	?>

@optgroup|after = <?php }
	} 
?>


/* post types */
[data-v-field_group-type] option|addNewAttribute = <?php if('@@__value__@@' == $groupType) echo 'selected';?>

[data-post-subtype]|before = <?php $subtype = '@@__data-post-subtype__@@';?>

@select = [data-v-field_group-subtype]
@option = [data-v-field_group-subtype] [data-option]
@option|deleteAllButFirstChild

@option|before = <?php 
	$postTypes = $subtype . 'Types';
	$options = $this->$postTypes ?? [];
	foreach ($options as $option) {?>

	@option|value = $option['type']
	@option = $option['name']
	@option|addNewAttribute = <?php if($option['type'] == $groupSubtype) echo 'selected';?>

@option|after = <?php } ?>