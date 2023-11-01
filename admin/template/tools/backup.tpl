import(common.tpl)
import(pagination.tpl)

@backup = [data-v-backups] [data-v-backup]
@backup|deleteAllButFirstChild

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
		
		@backup a[data-v-backup-*]|href = $backup['@@__data-v-backup-(*)__@@']	
		
	@backup|after = <?php } 
}?>
