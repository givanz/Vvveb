import(common.tpl)
import(language.tpl)

[data-v-requirements]|if_exists = $this->requirements

[data-v-requirements] [data-v-requirement]|deleteAllButFirstChild


[data-v-requirements]  [data-v-requirement]|before = <?php 
if(isset($this->requirements) && is_array($this->requirements)) 
{
	foreach ($this->requirements as $requirement) 
	{
	?>
	
	[data-v-requirements] [data-v-requirement] [data-v-requirement-text]|innerText = $requirement

	
	[data-v-requirements]  [data-v-requirement]|after = <?php 
	} 
}?>

[data-v-themes] [data-v-theme]|deleteAllButFirstChild

[data-v-themes] [data-v-theme]|before = <?php
if(isset($this->themes) && is_array($this->themes)) {
	//$pagination = $this->themes[$_themes_idx]['pagination'];
	foreach ($this->themes as $index => $theme) { ?>
	
    [data-v-themes] [data-v-theme] [data-v-theme-*]|innerText  = $theme['@@__data-v-theme-(*)__@@']
    [data-v-themes] [data-v-theme] a[data-v-theme-*]|href  = $theme['@@__data-v-theme-(*)__@@']
    [data-v-themes] [data-v-theme] img[data-v-theme-*]|src  = $theme['@@__data-v-theme-(*)__@@']
    [data-v-themes] [data-v-theme] input.form-check-input|addNewAttribute  = <?php if (isset($theme['active']) && $theme['active']) echo 'checked';?>
    [data-v-themes] [data-v-theme] input.form-check-input|value  = $theme['folder']

	[data-v-themes]  [data-v-theme]|after = <?php 
	} 
}?>


[name="homepage"] option|deleteAllButFirstChild

[name="homepage"] option|before = <?php
if(isset($this->templates) && is_array($this->templates)) {
	foreach ($this->templates as $file => $name) { ?>
	
		[name="homepage"] option|value = $file
		[name="homepage"] option       = $name
    
	[name="homepage"] option|after = <?php 
	} 
}?>
