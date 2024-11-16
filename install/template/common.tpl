/*body|prepend = <?php var_dump($this);?>*/
head base|href = <?php echo Vvveb\themeUrlPath()?>;


input[type="text"]|value = 
<?php
 if (isset($_POST['@@__name__@@'])) 
		echo htmlspecialchars($_POST['@@__name__@@']);
	else if (isset($this->config['@@__name__@@'])) 
		echo htmlspecialchars($this->config['@@__name__@@']);
	else echo '@@__value__@@';		
?>

input[type="password"]|value = 
<?php
 if (isset($_POST['@@__name__@@'])) 
		echo htmlspecialchars($_POST['@@__name__@@']);
	else if (isset($this->config['@@__name__@@'])) 
		echo htmlspecialchars($this->config['@@__name__@@']);
	else echo '@@__value__@@';		
?>


input[type="email"]|value = 
<?php
 if (isset($_POST['@@__name__@@'])) 
		echo htmlspecialchars($_POST['@@__name__@@']);
	else if (isset($this->config['@@__name__@@'])) 
		echo htmlspecialchars($this->config['@@__name__@@']);
	else echo '@@__value__@@';		
?>


input[type="checkbox"]|value = 
<?php
	 if (isset($_POST['@@__name__@@'])) 
		echo htmlspecialchars($_POST['@@__name__@@']);
	else if (isset($this->config['@@__name__@@'])) 
		echo htmlspecialchars($this->config['@@__name__@@']);
	else echo '@@__value__@@';		
?>

select|before = <?php
	$name = '@@__name__@@';
?>

select option|addNewAttribute = 
<?php
	 if (isset($_POST[$name]) && $_POST[$name] == '@@__value__@@') 
		echo 'selected';
	else if (isset($this->config[$name]) && $this->config[$name] == '@@__value__@@') 
		echo 'selected';
?>

/*
input[type="checkbox"]|checked = 
<?php
	 if (isset($_POST['@@__name__@@']) && $_POST['@@__name__@@']) 
		echo $_POST['@@__value__@@']; 
	else echo 'false';
?>
*/


/* notifications */

/* notifications */
@error = [data-v-notifications] [data-v-notification-error]
@error|before = <?php 
if (isset($this->errors)) foreach($this->errors as $message) {?>
	
	@error [data-v-notification-text] = $message
		
@error|after = <?php 
	}
?>
		
		
@success = [data-v-notifications] [data-v-notification-success]
@success|before = <?php 
if (isset($this->success)) foreach($this->success as $message) {?>
	
	@success [data-v-notification-text] = $message
	
@success|after = <?php 
	}
?>


@info = [data-v-notifications] [data-v-notification-info]
@info|before = <?php 
if (isset($this->info)) foreach($this->info as $message) {?>
	
	@info [data-v-notification-text] = $message
	
@info|after = <?php 
	}
?>

@message = [data-v-notifications] [data-v-notification-message]
@message|before = <?php 
if (isset($this->message)) foreach($this->message as $message) {?>
	
	@message [data-v-notification-text] = $message
	
@message|after = <?php 
	}
?>



html|addNewAttribute = <?php if (isset($_COOKIE['theme'])) { 
	echo 'data-bs-theme="';
	if ($_COOKIE['theme'] == 'dark') echo 'dark'; else if ($_COOKIE['theme'] == 'light') echo 'light';else echo 'auto';  
	echo '"';
} ?>