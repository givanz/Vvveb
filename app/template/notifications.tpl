/* notifications */
@error = [data-v-notifications] [data-v-notification-error]
@error|before = <?php 
$type = '@@__data-v-type__@@';
if (isset($this->errors) && is_array($this->errors)) {
	foreach($this->errors as $id => $list) {
		if (!empty($type) && $id != $type) continue;
		
		if (!is_array($list)) {
			$list = [$list];
		}
		foreach ($list as $message) {
?>
	
	@error [data-v-notification-text] = <?php echo $message;?>
		
@error|after = <?php 
		}
	}
}
?>
		
		
@success = [data-v-notifications] [data-v-notification-success]
@success|before = <?php 
$type = '@@__data-v-type__@@';
if (isset($this->success) && is_array($this->success)) {
	foreach($this->success as $id => $list) {
		if (!empty($type) && $id != $type) continue;
		
		if (!is_array($list)) {
			$list = [$list];
		}
		foreach ($list as $message) {
?>
	
	@success [data-v-notification-text] = <?php echo $message;?>
	
@success|after = <?php 
		}
	}
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
$type = '@@__data-v-type__@@';
if (isset($this->message) && is_array($this->message)) {
	foreach($this->message as $id => $list) {
		if (!empty($type) && $id != $type) continue;
		
		if (!is_array($list)) {
			$list = [$list];
		}
		foreach ($list as $message) {
?>
	
	@message [data-v-notification-text] = <?php echo $message;?>
	
@message|after = <?php 
		}
	}
}
?>