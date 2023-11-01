import(common.tpl)

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
	
    [data-v-themes] [data-v-theme] [data-v-theme-*]|innerText  = $theme['@@__data-v-theme-([-_\w]+)__@@']
    [data-v-themes] [data-v-theme] a[data-v-theme-*]|href  = $theme['@@__data-v-theme-([-_\w]+)__@@']
    [data-v-themes] [data-v-theme] img[data-v-theme-*]|src  = $theme['@@__data-v-theme-([-_\w]+)__@@']
    [data-v-themes] [data-v-theme] input.form-check-input|addNewAttribute  = <?php if (isset($theme['active']) && $theme['active']) echo 'checked';?>
    [data-v-themes] [data-v-theme] input.form-check-input|value  = $theme['folder']
    
	[data-v-themes] [data-v-theme] [data-v-theme-activate-url]|href  = <?php echo Vvveb\url(['action' => 'activate', 'theme' => $theme['folder']]);?>
	[data-v-themes] [data-v-theme] [data-v-theme-delete-url]|href  = <?php echo Vvveb\url(['action' => 'delete', 'theme' => $theme['folder']]);?>
	[data-v-themes] [data-v-theme] [data-v-theme-preview-url]|href  = <?php echo Vvveb\url('index/index') . '?theme=' . $theme['folder'];?>
	
	[data-v-themes]  [data-v-theme]|after = <?php 
	} 
}?>
