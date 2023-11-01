import(crud.tpl, {"type":"site"})

[data-v-theme-list] option|deleteAllButFirst

[data-v-theme-list] option|before = <?php
	foreach ($this->themeList as $code => $theme) {
?>

	[data-v-theme-list] option|value = $code
	[data-v-theme-list] option|addNewAttribute = <?php if (isset($this->site['theme']) && ($code == $this->site['theme'])) echo 'selected';?>
	[data-v-theme-list] option = <?php 
		echo $theme['name'];
	?>

[data-v-theme-list] option|after = <?php 
} ?>


@templates-select-option = [data-v-template-list] [data-v-option]

@templates-select-option|deleteAllButFirstChild

@templates-select-option|before = <?php
	$options = 	$this->templateList;
	$optgroup = false;
	foreach($options as $key => $option){?>
	
		@templates-select-option|value = $option
		@templates-select-option = <?php echo ucfirst($option);?>

@templates-select-option|after = <?php
}?>

@templates-select-option|addNewAttribute = <?php if (isset($selected) && $option == $selected) echo 'selected';?>


@templates-select-option|before = <?php
if (($optgroup != $option['folder'])) {
	$optgroup = $option['folder'];
	echo '<optgroup label="' . ucfirst($optgroup) . '">';
}
?>

@templates-select-option|after = <?php
if (($optgroup != $option['folder'])) {
	$optgroup = $option['folder'];
	echo "/<optgroup>";
}
?>

@templates-select-option|value = <?php echo $option['file'];?>
@templates-select-option|addNewAttribute = <?php if (isset($this->site['template']) && $option['file']== $this->site['template']) echo 'selected';?>
@templates-select-option = <?php echo ucfirst($option['title']);?>


input[data-v-site-*]|value = <?php
	$name = '@@__data-v-site-(*)__@@';
	$_default = '@@__value__@@';
	 if (isset($_POST['site'][$name])) {
		$value = $_POST['site'][$name]; 
	 } else if (isset($this->site[$name])) {
		$value = $this->site[$name];
	 } else { 
		$value = $_default;
	}
	
	echo $value;
?>		

input[data-v-site-*][type=checkbox]|addNewAttribute = <?php
	if (isset($this->site[$name])) echo 'checked';
?>


[data-v-site-*]|innerText = $this->site['@@__data-v-site-(*)__@@']
[data-v-site-*]|title = $this->site['@@__data-v-site-(*)__@@']
a[data-v-site-*]|title|href = $this->site['@@__data-v-site-(*)__@@']


input[data-v-setting]|value = <?php 
	$_setting = '@@__data-v-setting__@@';
	$_default = '@@__value__@@';
	$value = $_POST['settings'][$_setting] ?? $this->setting[$_setting] ?? $_default;
	echo $value;
	//name="settings[setting-name] > get only setting-name
	//$_setting = '@@__name:\[(.*)\]__@@';
?>

img[data-v-setting]|src = <?php 
	$_setting = '@@__data-v-setting__@@';
	$_default = '@@__value__@@';
	$value = $this->setting[$_setting] ?? $_default;
	echo $value;
	//name="settings[setting-name] > get only setting-name
	//$_setting = '@@__name:\[(.*)\]__@@';
?>

[data-v-date_format]|deleteAllButFirst

[data-v-date_format]|before = <?php
	$custom = true;
	foreach($this->date_format as $format => $text) {
		$checked = ($this->site['date_format'] ?? 'F j, Y') == $format;
		if ($checked) $custom = false;
	?>
	
	[data-v-date_format-text] = $text
	[data-v-date_format-value] = $format
	input[data-v-date_format-value]|value = $format
	input[data-v-date_format-value]|addNewAttribute = <?php if ($checked) echo 'checked';?>
	#date_format_custom|addNewAttribute = <?php if ($custom) echo 'checked';?>
	
[data-v-date_format]|after = <?php
	}
?>	

[data-v-time_format]|deleteAllButFirst

[data-v-time_format]|before = <?php
	$custom = true;
	foreach($this->time_format as $format => $text) {
		$checked = ($this->site['time_format'] ?? 'H:i') == $format;
		if ($checked) $custom = false;
	?>
	
	[data-v-time_format-text] = $text
	[data-v-time_format-value] = $format
	input[data-v-time_format-value]|value = $format
	input[data-v-time_format-value]|addNewAttribute = <?php if ($checked) echo 'checked';?>
	#time_format_custom|addNewAttribute = <?php if ($custom) echo 'checked';?>
	
[data-v-time_format]|after = <?php
	}
?>	



[data-v-resize]|before = <?php
$setting = '@@__name:\[(.*)\]__@@';
?>

[data-v-resize] option|deleteAllButFirstChild

[data-v-resize] option|before = <?php
    if (isset($this->resize))
	foreach ($this->resize as $value => $name) {
?>

	[data-v-resize] option|value = $value
	[data-v-resize] option|addNewAttribute = <?php if (isset($this->site[$setting]) && $this->site[$setting] == $value) echo 'selected';?>
	[data-v-resize] option = <?php 
		echo $name;
	?>

[data-v-resize] option|after = <?php 
} ?>
/*

select[data-v-setting]|before = 
<?php
	$name = '@@__data-v-setting__@@';

	 $selected = '';	
	 if (isset($this->setting[$name])) 
	 $selected = $this->setting[$name];
?>

[data-v-setting] [data-v-option]|deleteAllButFirstChild
[data-v-setting] [data-v-option]|before = <?php 
	if (isset($this->$name))
	foreach ($this->$name as $value => $text) {
	?>

	[data-v-setting] [data-v-option]|value = $value
	[data-v-setting] [data-v-option]|addNewAttribute = <?php if ($value == $selected) echo 'selected';?>
	[data-v-setting] [data-v-option] = $text

[data-v-setting] [data-v-option]|after = <?php 
} ?>
*/

input[type="checkbox"][data-v-*]|addNewAttribute = <?php
	if (isset($this->setting[$_setting])) echo 'checked';
?>
