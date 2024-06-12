import(common.tpl)

/* notifications */
@log = [data-v-logs] [data-v-logs-line]
@log|before = <?php 
if (isset($this->log) && is_array($this->log)) foreach($this->log as $message) {?>
	
	@log [data-v-log-text] = <?php echo $message;?>
		
@log|after = <?php 
	}
?>