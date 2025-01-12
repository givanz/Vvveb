import(common.tpl)

@entry = [data-v-table] [data-v-entry]

[data-v-info]|before = <?php
foreach ($this->info as $category => $info) {
?>

	[data-v-category] = <?php echo htmlspecialchars(ucfirst($category));?>
	.header|for = $category
	.header_check|id = $category

	@entry|deleteAllButFirst
	@entry|before = <?php foreach ($info as $name => $value) {
	?>

		@entry [data-v-name] = $name
		@entry [data-v-value] = <?php 
			if (is_string($value)) { 
				echo htmlspecialchars($value);
			} else {
				highlight_string(var_export($value, true)); 
			}
		?>


	@entry|after = 
	<?php } ?>

[data-v-info]|after = <?php } ?>

[data-v-phpinfo] = <?php echo $this->phpinfo ?? '';?>
