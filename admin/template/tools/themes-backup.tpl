import(common.tpl)

@theme = [data-v-themes] [data-v-theme]
@theme|deleteAllButFirstChild

@backup = [data-v-backups] [data-v-backup]
@backup|deleteAllButFirstChild

@backupFile = [data-v-backups] [data-v-backup] [data-v-files] [data-v-file]
@backupFile|deleteAllButFirstChild

@theme|before = <?php
if(isset($this->themes) && is_array($this->themes))  {
	foreach ($this->themes as $index => $theme) {
	?>
		@theme input[data-v-theme-input]|value = $theme['slug']
		@theme input[data-v-theme-input]|id = <?php echo 'theme-' . $theme['slug'];?>
		@theme label[data-v-theme-label]|for = <?php echo 'theme-' . $theme['slug'];?>
		
		@theme [data-v-theme-name]|innerText = $theme['name']
		@theme [data-v-theme-slug]|innerText = $theme['slug']
		@theme [data-v-theme-version]|innerText = $theme['version']
		@theme [data-v-theme-description]|innerText = $theme['description']
		@theme [data-v-theme-status]|innerText = $theme['status']
		@theme img[data-v-theme-thumb_url]|src = $theme['thumb_url']
		@theme img[data-v-theme-thumb_url]|hide = <?php $theme['thumb_url'] ?>
		
	@theme|after = <?php } 
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
