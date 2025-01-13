/* Template select */

/* template */
[data-v-{{type}}] select[data-v-templates]|before = <?php $optgroup = '';?>
@templates-select-option = [data-v-{{type}}] select[data-v-templates] [data-v-option]

@templates-select-option|deleteAllButFirstChild

[data-v-{{type}}] select[data-v-templates]|before = 
<?php
	 //set select name
	 $selected = '';	
	 //$name = '@@__data-v-{{type}}-(*)__@@';
	 $name = 'templates';
	 if (isset($_POST[$name])) {
		 $selected = $_POST[$name];
	 } else
	 if (isset($this->{{type}}[$name])) {
		$selected = $this->{{type}}[$name];
	 }
?>

/* Template select */

@templates-select-option|before = <?php
	if (isset($this->$name)) {
	$options = 	$this->$name;
	foreach($options as $key => $option){?>
	
		@templates-select-option|value = $option
		@templates-select-option = <?php echo ucfirst($option);?>

@templates-select-option|after = <?php
}}?>

@templates-select-option|addNewAttribute = <?php if ($option == $selected) echo 'selected';?>


@templates-select-option|before = <?php
if ($optgroup != $option['folder']) {
	$optgroup = $option['folder'];
	echo '<optgroup label="' . ucfirst($optgroup ?? '') . '">';
}
?>

@templates-select-option|after = <?php
if ($optgroup != $option['folder']) {
	$optgroup = $option['folder'];
	echo "/<optgroup>";
}
?>

@templates-select-option|value = <?php echo $option['file'];?>
@templates-select-option|addNewAttribute = <?php if (isset($this->{{type}}['template']) && ($option['file'] == $this->{{type}}['template'])) echo 'selected';?>
@templates-select-option = <?php echo ucfirst($option['title']);?>



/* language tabs */

[data-v-languages]|before = <?php $_lang_instance = '@@__data-v-languages__@@';$_i = 0;?>
@language = [data-v-languages] [data-v-language]
@language|deleteAllButFirstChild
//@language|addClass = <?php if ($_i == 0) echo 'active';?>

@language|before = <?php

foreach ($this->languagesList as $language) {
	$content = $this->{{type}}['{{type}}_content'][$language['language_id']] ?? [];
?>
	[data-v-languages] [data-v-language-id]|id = <?php echo 'lang-' . $language['code'] . '-' . $_lang_instance;?>
	[data-v-languages]  [data-v-language-id]|addClass = <?php if ($_i == 0) echo 'show active';?>

	@language [data-v-language-name] = $language['name']
	@language [data-v-language-img]|title = $language['name']
	@language [data-v-language-img]|src = <?php echo 'language/' . $language['code'] . '/' . $language['code'] . '.png';?>
	@language [data-v-language-link]|href = <?php echo '#lang-' . $language['code'] . '-' . $_lang_instance?>
	@language [data-v-language-link]|addClass = <?php if ($_i == 0) echo 'active';?>

@language|after = <?php 
$_i++;
}
?>


[data-v-{{type}}] input[data-v-{{type}}-content-*]|name = <?php echo '{{type}}_content[' . $language['language_id'] . '][@@__data-v-{{type}}-content-(*)__@@]';?>
[data-v-{{type}}] textarea[data-v-{{type}}-content-*]|name = <?php echo '{{type}}_content[' . $language['language_id'] . '][@@__data-v-{{type}}-content-(*)__@@]';?>

[data-v-{{type}}] input[data-v-{{type}}-content-*]|value = <?php
	$desc = '@@__data-v-{{type}}-content-(*)__@@';
	if (isset($content[$desc])) {
		if ($desc == 'content') {
			echo $content[$desc];
		} else {
			echo htmlspecialchars($content[$desc]);
		}
	}
?>

[data-v-{{type}}] [data-v-{{type}}-content-*]|innerText = <?php
	$desc = '@@__data-v-{{type}}-content-(*)__@@';
	if (isset($content[$desc])) {
		if ($desc == 'content') {
			echo $content[$desc];
		} else {
			echo htmlspecialchars($content[$desc]);
		}
	}
?>

[data-v-{{type}}] a[data-v-{{type}}-content-*]|href = <?php
	$desc = '@@__data-v-{{type}}-content-(*)__@@';
	if (isset($content[$desc])) {
		if ($desc == 'content') {
			echo $content[$desc];
		} else {
			echo htmlspecialchars($content[$desc]);
		}
	}
?>


[data-v-{{type}}] textarea[data-v-{{type}}-content-*] = <?php
	$desc = '@@__data-v-{{type}}-content-(*)__@@';
	if (isset($content[$desc])) {
		if ($desc == 'content') {
			echo $content[$desc];
		} else {
			echo htmlspecialchars($content[$desc]);
		}
	}
?>


[data-v-{{type}}] input[data-v-{{type}}-content-language_id]|value = <?php echo $language['language_id']; ?>



/* Revisions */

@revision = [data-v-languages] [data-v-language] [data-v-revision]
@revision|deleteAllButFirstChild

@revision|before = <?php
$revisions = $content['revision'];
foreach ($revisions as $revision) {
?>

	@revision [data-v-revision-*]|innerText = $revision['@@__data-v-revision-(*)__@@']
	@revision a[data-v-revision-*]|href     = $revision['@@__data-v-revision-(*)__@@']
	
	@revision|data-type		   = '{{type}}'
	@revision|data-{{type}}_id = $revision['{{type}}_id']
	@revision|data-language_id = $revision['language_id']
	@revision|data-created_at  = $revision['created_at']

@revision|after = <?php 
	}
?>

[data-v-{{type}}] [data-v-revisions_url]|href = <?php echo $this->revisions_url . '&language_id=' . $language['language_id'];?>


@site = [data-v-sites] [data-v-site]
@site|deleteAllButFirstChild

@site|before = <?php
if(isset($this->sitesList) && is_array($this->sitesList)) {
	foreach ($this->sitesList as $index => $site) {?>
	
	@site [data-v-*]|innerText = $site['@@__data-v-(*)__@@']
	@site [data-v-*]|title = $site['@@__data-v-(*)__@@']
	@site input[data-v-*]|addNewAttribute = <?php if (isset($site['selected']) && $site['selected']) echo 'checked';?>

	
	@site|after = <?php
	} 
}?>


import(content/post_taxonomy.tpl)
