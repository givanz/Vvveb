import(common.tpl)

//theme inputs
[data-v-theme-inputs]|deleteAllButFirstChild

[data-v-theme-inputs]|before = <?php
if(isset($this->themeInputs) && is_array($this->themeInputs)) 
{
	foreach ($this->themeInputs as $id => $file) {?>
	
	[data-v-theme-inputs]|src = $file
	[data-v-theme-inputs]|id = $id

	[data-v-theme-inputs]|after = <?php 
	} 
}?>


//theme components
[data-v-theme-components]|deleteAllButFirstChild

[data-v-theme-components]|before = <?php
if(isset($this->themeComponents) && is_array($this->themeComponents)) 
{
	foreach ($this->themeComponents as $id => $file) {?>
	
	[data-v-theme-components]|src = $file
	[data-v-theme-components]|id = $id

	[data-v-theme-components]|after = <?php 
	} 
}?>

//theme blocks
[data-v-theme-blocks]|deleteAllButFirstChild

[data-v-theme-blocks]|before = <?php
if(isset($this->themeBlocks) && is_array($this->themeBlocks)) 
{
	foreach ($this->themeBlocks as $id => $file) {?>
	
	[data-v-theme-blocks]|src = $file
	[data-v-theme-blocks]|id = <?php echo 'theme-' . htmlspecialchars($id);?>

	[data-v-theme-blocks]|after = <?php 
	} 
}?>

//theme sections
[data-v-theme-sections]|deleteAllButFirstChild

[data-v-theme-sections]|before = <?php
if(isset($this->themeSections) && is_array($this->themeSections)) 
{
	foreach ($this->themeSections as $id => $file) {?>
	
	[data-v-theme-sections]|src = $file
	[data-v-theme-sections]|id = <?php echo 'theme-' . htmlspecialchars($id);?>

	[data-v-theme-sections]|after = <?php 
	} 
}?>

//theme js
[data-v-theme-js]|deleteAllButFirstChild

[data-v-theme-js]|before = <?php
if(isset($this->themeJs) && is_array($this->themeJs)) 
{
	foreach ($this->themeJs as $id => $file) {?>
	
	[data-v-theme-js]|src = $file
	[data-v-theme-js]|id = <?php echo 'theme-' . htmlspecialchars($id);?>

	[data-v-theme-js]|after = <?php 
	} 
}?>


/* template list for new template modal */
select[data-v-theme-*]|before = <?php $name = '@@__data-v-theme-(*)__@@'; $optgroup = '';?>
@templates-select-option = select[data-v-theme-*] [data-v-option]
@templates-select-option|deleteAllButFirstChild

@templates-select-option|before = <?php
if (isset($this->$name)) {
	$options = 	$this->$name;
	foreach($options as $key => $option){
	
		if (isset($option['folder']) && ($optgroup != $option['folder'])) {
			$optgroup = $option['folder'];
			echo '<optgroup label="' . ucfirst(htmlspecialchars($optgroup)) . '">';
		}
?>

	@templates-select-option|value = <?php echo htmlspecialchars($option['file']);?>
	@templates-select-option = <?php echo htmlspecialchars(ucfirst($option['title']));?>
	@templates-select-option|addNewAttribute = <?php if ($option['file'] == 'blank.html') echo 'selected';?>
	
@templates-select-option|after = <?php
	if ($optgroup != $option['folder']) {
		$optgroup = $option['folder'];
		echo "/<optgroup>";
	}
}}
?>

[data-vvveb-url]|data-vvveb-url = $this->saveUrl
form[data-vvveb-url]|action = $this->saveUrl


/* language tabs */

[data-v-languages]|before = <?php $_lang_instance = '@@__data-v-languages__@@';$_i = 0;?>
@language = [data-v-languages] [data-v-language]
@language|deleteAllButFirstChild
//@language|addClass = <?php if ($_i == 0) echo 'active';?>

@language|before = <?php

foreach ($this->languagesList as $language) {
	//$content = $this->{{type}}['{{type}}_content'][$language['language_id']] ?? [];
?>
	[data-v-languages] [data-v-language-id]|id = <?php echo 'lang-' . htmlspecialchars($language['code']) . '-' . $_lang_instance;?>
	[data-v-languages]  [data-v-language-id]|addClass = <?php if ($_i == 0) echo 'show active';?>

	@language [data-v-language-name] = $language['name']
	@language [data-v-language-code]|name = $language['code']
	@language [data-v-language-img]|title = $language['name']
	@language [data-v-language-img]|src = <?php echo 'language/' . htmlspecialchars($language['code']) . '/' . htmlspecialchars($language['code']) . '.png';?>
	@language [data-v-language-link]|href = <?php echo '#lang-' . htmlspecialchars($language['code']) . '-' . $_lang_instance?>
	@language [data-v-language-link]|addClass = <?php if ($_i == 0) echo 'active';?>

@language|after = <?php 
$_i++;
}
?>