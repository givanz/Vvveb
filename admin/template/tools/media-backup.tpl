import(common.tpl)

[data-v-media-count]|innerText = $this->mediaCount
[data-v-media-size]|innerText = $this->mediaSize

@backup = [data-v-backups] [data-v-backup]
@backup|deleteAllButFirstChild

@backupFile = [data-v-backups] [data-v-backup] [data-v-backup-files] [data-v-backup-file]
@backupFile|deleteAllButFirstChild

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
				@backupFile [data-v-*]|innerText = $file['@@__data-v-(*)__@@']
				@backupFile input[data-v-*]|value = $file['@@__data-v-(*)__@@']
				@backupFile button[data-v-*]|value = $file['@@__data-v-(*)__@@']
				@backupFile a[data-v-*]|href = $file['@@__data-v-(*)__@@']
		@backupFile|after = <?php 
			} 
		}
		?>
		
	@backup|after = <?php } 
}?>
