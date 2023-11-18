//keep post values
input|value = <?php if (isset($_POST['@@__name__@@'])) echo $_POST['@@__name__@@']; else echo '@@__value__@@';?>

import(common.tpl)

[data-v-return] select[data-v-return-*]|before = 
<?php
	 $name = '@@__data-v-return-(*)__@@';
	 $selected = '';	
	 if (isset($this->return[$name])) 
	 $selected = $this->return[$name];
?>

@reason = [data-v-return_reason] [data-v-option]
[data-v-return_reason] [data-v-option]|deleteAllButFirstChild
[data-v-return_reason] [data-v-option]|before = <?php 
    if (isset($this->return_reason_id))
	foreach ($this->return_reason_id as $value => $text) {
	?>

	@reason|value = $value
	// @reason|addNewAttribute = <?php if ($value == $selected) echo 'selected';?>
	@reason span = <?php if (is_array($text)) { if (isset($text['name'])) echo Vvveb\humanReadable($text['name']);} else echo $text;?>  

@reason|after = <?php 
} ?>
