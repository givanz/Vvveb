import(common.tpl)

@plugin = [data-v-plugins] [data-v-plugin]
@plugin|deleteAllButFirstChild

@backup = [data-v-backups] [data-v-backup]
@backup|deleteAllButFirstChild

@backupFile = [data-v-backups] [data-v-backup] [data-v-files] [data-v-file]
@backupFile|deleteAllButFirstChild

@plugin|before = <?php
if(isset($this->plugins) && is_array($this->plugins))  {
	foreach ($this->plugins as $index => $plugin) {
	?>
		@plugin input[data-v-plugin-input]|value = $plugin['slug']
		@plugin input[data-v-plugin-input]|id = <?php echo 'plugin-' . $plugin['slug'];?>
		@plugin label[data-v-plugin-label]|for = <?php echo 'plugin-' . $plugin['slug'];?>
		
		@plugin [data-v-plugin-name]|innerText = $plugin['name']
		@plugin [data-v-plugin-slug]|innerText = $plugin['slug']
		@plugin [data-v-plugin-version]|innerText = $plugin['version']
		@plugin [data-v-plugin-description]|innerText = $plugin['description']
		@plugin [data-v-plugin-status]|innerText = $plugin['status']
		@plugin [data-v-plugin-status]|addClass = <?php if ($plugin['status'] == 'active') echo 'bg-success-subtle';?>
		@plugin img[data-v-plugin-thumb_url]|src = $plugin['thumb_url']
		@plugin img[data-v-plugin-thumb_url]|hide = <?php $plugin['thumb_url']?>
		
	@plugin|after = <?php } 
}?>

@backup|before = <?php
$count = 0;
if(isset($this->backups) && is_array($this->backups))  {
	foreach ($this->backups as $index => $backup) {
		$key = 'key-' . $backup['key'];
		$count++;
	?>
	
		@backup [data-v-backup-target]|data-bs-target = <?php echo '#' . $key;?>
		@backup [data-v-backup-id]|id = $key
		
		@backup [data-v-backup-*]|innerText = $backup['@@__data-v-backup-(*)__@@']
		@backup input[data-v-backup-*]|value = $backup['@@__data-v-backup-(*)__@@']
		@backup button[data-v-backup-*]|value = $backup['@@__data-v-backup-(*)__@@']
		
		@backup a[data-v-backup-*]|href = $backup['@@__data-v-backup-(*)__@@']	

		@backupFile|before = <?php
		if(isset($backup['files']) && is_array($backup['files'])) {
			foreach ($backup['files'] as $fIndex => $file) {
		?>
				@backupFile [data-v-file-*]|innerText = $file['@@__data-v-file-(*)__@@']
				@backupFile input[data-v-file-*]|value = $file['@@__data-v-file-(*)__@@']
				@backupFile button[data-v-file-*]|value = $file['@@__data-v-file-(*)__@@']
				@backupFile a[data-v-file-*]|href = $file['@@__data-v-file-(*)__@@']
		@backupFile|after = <?php 
			} 
		}
		?>
		
	@backup|after = <?php } 
}?>
