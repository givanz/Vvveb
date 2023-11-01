/* notifications */
@error = [data-v-notifications] [data-v-notification-error]
@error|before = <?php 
if (isset($this->errors) && is_array($this->errors)) foreach($this->errors as $message) {?>
	
	@error [data-v-notification-text] = <?php echo $message;?>
		
@error|after = <?php 
	}
?>
		
@success = [data-v-notifications] [data-v-notification-success]
@success|before = <?php 
if (isset($this->success) && is_array($this->success)) foreach($this->success as $message) {?>
	
	@success [data-v-notification-text] = <?php echo $message;?>
	
@success|after = <?php 
	}
?>

@info = [data-v-notifications] [data-v-notification-info]
@info|before = <?php 
if (isset($this->info) && is_array($this->info)) foreach($this->info as $message) {?>
	
	@info [data-v-notification-text] = <?php echo $message;?>
	
@info|after = <?php 
	}
?>

@message = [data-v-notifications] [data-v-notification-message]
@message|before = <?php 
if (isset($this->message) && is_array($this->message)) foreach($this->message as $message) {?>
	
	@message [data-v-notification-text] = <?php echo $message;?>
	
@message|after = <?php 
	}
?>
