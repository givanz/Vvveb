import(common.tpl)

.settings|before = <?php
?>

.settings input[data-v-setting]|value = <?php 
	$_setting = '@@__data-v-setting__@@';
	echo htmlspecialchars($_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '');
?>

.settings textarea[data-v-setting] = <?php 
	$_setting = '@@__data-v-setting__@@';
	echo htmlspecialchars($_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '');
?>


.settings select[data-v-setting]|before = <?php 
	$_setting = '@@__data-v-setting__@@';
?>

#input-mail-driver option|deleteAllButFirst

#input-mail-driver option|before = <?php 
    if (isset($this->drivers)) {
	$setting = $_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '';
	foreach ($this->drivers as $value => $text) {
	?>

	#input-mail-driver option|innerText       = $text
	#input-mail-driver option|value           = $value
	#input-mail-driver option|addNewAttribute = <?php if ($setting == $value) echo 'selected';?>

#input-mail-driver option|after = <?php 
} } ?>

#input-mail-smtp-encryption option|deleteAllButFirst

#input-mail-smtp-encryption option|before = <?php 
    if (isset($this->smtp_encryption_options)) {
	$setting = $_POST['settings'][$_setting] ?? \Vvveb\arrayPath($this->email, $_setting) ?? '';
	foreach ($this->smtp_encryption_options as $value => $text) {
	?>

	#input-mail-smtp-encryption option|innerText       = $text
	#input-mail-smtp-encryption option|value           = $value
	#input-mail-smtp-encryption option|addNewAttribute = <?php if ($setting == $value) echo 'selected';?>

#input-mail-smtp-encryption option|after = <?php 
} } ?>


