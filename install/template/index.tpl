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



[data-v-language-list] option|deleteAllButFirst

[data-v-language-list] option|before = <?php
	foreach ($this->languagesList as $code => $language) {
		//$code = $language['code'];
?>

	[data-v-language-list] option|addNewAttribute = <?php 
		if ($this->currentLanguage == $code) echo 'selected';
	?>

	[data-v-language-list] option img|src = <?php 
			echo '/img/flags/' . $code . '.png';
	?>	
	
	[data-v-language-list] option|style = <?php 
			$code = $language['code'];
			echo 'background-image:url(/img/flags/' . $code . '.png)';
	?>
	
	[data-v-language-list] option|value = $code
	[data-v-language-list] option = <?php 
		if (isset($language['emoji'])) {
			echo $language['emoji'] . ' ';
		}
		echo $language['name'];
	?>
	

[data-v-language-list] option|after = <?php 
} ?>