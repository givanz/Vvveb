import(common.tpl)

.settings|before = <?php
?>

.settings input[data-v-setting]|value = <?php 
	$_setting = '@@__data-v-setting__@@';
	echo $_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '';
?>

.settings textarea[data-v-setting] = <?php 
	$_setting = '@@__data-v-setting__@@';
	echo $_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '';
?>


.settings select[data-v-setting]|before = <?php 
	$_setting = '@@__data-v-setting__@@';
?>

.settings select[data-v-setting] option|addNewAttribute = <?php 
	$value   = '@@__value__@@';
	$setting = $_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '';
	if ($setting == $value) echo 'selected';
?>
