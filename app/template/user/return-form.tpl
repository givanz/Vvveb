//keep post values
input[type="radio"]|addNewAttribute = <?php if (isset($_POST['@@__name__@@']) && $_POST['@@__name__@@'] == '@@__value__@@') echo 'checked';?>
input[type="checkbox"]|addNewAttribute = <?php if (isset($_POST['@@__name__@@']) && $_POST['@@__name__@@'] == '@@__value__@@') echo 'checked';?>
input[type="text"]|value = <?php if (isset($_POST['@@__name__@@'])) echo htmlspecialchars($_POST['@@__name__@@']); else echo '@@__value__@@';?>
input[type="email"]|value = <?php if (isset($_POST['@@__name__@@'])) echo htmlspecialchars($_POST['@@__name__@@']); else echo '@@__value__@@';?>
textarea = <?php if (isset($_POST['@@__name__@@'])) echo htmlspecialchars($_POST['@@__name__@@']); else echo '@@__value__@@';?>

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
    if (isset($this->return_reason_id)) {
		$selected = $_POST['return_reason_id'] ?? '';
		foreach ($this->return_reason_id as $value => $text) {
			
	?>

	@reason [name="return_reason_id"]|value = $text['return_reason_id']
	@reason [name="return_reason_id"]|addNewAttribute = <?php if ($text['return_reason_id'] == $selected) echo 'checked';?>
	@reason span = <?php if (is_array($text)) { if (isset($text['name'])) echo htmlspecialchars(Vvveb\humanReadable($text['name']));} else echo $text;?>  

@reason|after = <?php 
} }?>
