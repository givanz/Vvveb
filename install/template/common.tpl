//example class="copy_from_index.html"
.copy_from*|outerHTML = from(@@__class:copy_from:([^\,]+)__@@|@@__class:copy_from:[^\,]+\,([^\,]+)__@@)


/* modifiers */
.capitalize|register_filter = <?php ucfirst($content, $arg1, $arg2);?>


.if_*|after = <?php } ?>

.if_*|before = 
<?php if (@@macro if('@@__class__@@')@@) {?> 

[class*=":if_"]|addClass =  <?php @@macro class_if('@@__class__@@')@@?>
					  
				  
/*body|prepend = <?php var_dump($this);?>*/
head base|href = <?php echo Vvveb\themeUrlPath()?>;


input[type="text"]|value = 
<?php
	 if (isset($_POST['@@__name__@@'])) 
		echo $_POST['@@__name__@@'];
	else echo '@@__value__@@';		
?>

input[type="password"]|value = 
<?php
	 if (isset($_POST['@@__name__@@'])) 
		echo $_POST['@@__name__@@']; 
	else echo '@@__value__@@';
?>


input[type="email"]|value = 
<?php
	 if (isset($_POST['@@__name__@@'])) 
		echo $_POST['@@__name__@@'];
	else echo '@@__value__@@';		
?>


input[type="checkbox"]|value = 
<?php
	 if (isset($_POST['@@__name__@@'])) 
		echo $_POST['@@__name__@@'];
	 else echo '@@__value__@@';		
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
	
	@error [data-v-notification-text] = <?php echo $message;?>
		
@error|after = <?php 
	}
?>
		
		
@success = [data-v-notifications] [data-v-notification-success]
@success|before = <?php 
if (isset($this->success)) foreach($this->success as $message) {?>
	
	@success [data-v-notification-text] = <?php echo $message;?>
	
@success|after = <?php 
	}
?>


@info = [data-v-notifications] [data-v-notification-info]
@info|before = <?php 
if (isset($this->info)) foreach($this->info as $message) {?>
	
	@info [data-v-notification-text] = <?php echo $message;?>
	
@info|after = <?php 
	}
?>

@message = [data-v-notifications] [data-v-notification-message]
@message|before = <?php 
if (isset($this->message)) foreach($this->message as $message) {?>
	
	@message [data-v-notification-text] = <?php echo $message;?>
	
@message|after = <?php 
	}
?>