@group = [data-v-field-groups] [data-v-field-group]
@field = [data-v-fields] [data-v-row] [data-v-field]
@row = [data-v-fields] [data-v-row]
@row|deleteAllButFirstChild
@field|deleteAllButFirstChild


@group|before = <?php 
$groups = $this->fields[$language['language_id']] ?? [];
$fields = [];
if(isset($groups) && is_array($groups)) {
	foreach ($groups as $group => $fields) { ?>
	
	@group [data-v-field-group-name] = $group
	@group .header|for = $group
	@group .header_check|id = $group
	
@group|after = <?php } } ?>



@row|before = <?php

$close = false;
$row = -1;
//$count = $this->count;
$index = 1;
$field = [];
$newRow = true;
$rowIndex = 0;

if(isset($fields) && is_array($fields)) {
	$count = count($fields);
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

    @field [data-v-field-*]|innerText    = $field['@@__data-v-field-(*)__@@']
    @field input[data-v-field-*]|value   = $field['@@__data-v-field-(*)__@@']
    @field input[data-v-field-*]|value   = $field['@@__data-v-field-(*)__@@']    
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