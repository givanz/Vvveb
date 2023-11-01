import(common.tpl)

@entry = [data-v-table] [data-v-entry]

[data-v-info]|before = <?php
foreach ($this->info as $category => $info) {
?>

	[data-v-category] = <?php echo ucfirst($category);?>
	.header|for = $category
	.header_check|id = $category

	@entry|deleteAllButFirst
	@entry|before = <?php foreach ($info as $name => $value) {
	?>

		@entry [data-v-name] = $name
		@entry [data-v-value] = $value


	@entry|after = 
	<?php } ?>

[data-v-info]|after = <?php } ?>

[data-v-phpinfo] = <?php echo $this->phpinfo ?? '';?>
