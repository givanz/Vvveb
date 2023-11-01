import(common.tpl)

//[data-v-user] [data-v-user-*] = $this->user['@@__data-v-user-(*)__@@']

/* input elements */
[data-v-user] input[data-v-user-*]|value = 
<?php
	 if (isset($_POST['@@__data-v-user-(*)__@@'])) 
		echo $_POST['@@__data-v-user-(*)__@@']; 
	 else if (isset($this->user['@@__data-v-user-(*)__@@'])) 
		echo $this->user['@@__data-v-user-(*)__@@'];
?>


/* textarea elements */
[data-v-user] textarea[data-v-user-*] = 
<?php
	 if (isset($_POST['@@__data-v-user-(*)__@@'])) 
		echo $_POST['@@__data-v-user-(*)__@@']; 
	 else if (isset($this->user['@@__data-v-user-(*)__@@'])) 
		echo $this->user['@@__data-v-user-(*)__@@'];
?>/* textarea elements */



[data-v-user] select[data-v-user-*]|before = 
<?php
	 $selected = '';	
	 if (isset($this->user['@@__data-v-user-(*)__@@'])) 
	 $selected = $this->user['@@__data-v-user-(*)__@@'];
?>


[data-v-user] select[data-v-user-*] option|addNewAttribute = <?php if (isset($this->user) && $this->user['status'] == '@@__value__@@') echo 'selected';?>

[data-v-role-list] [data-v-role]|before = <?php 
	foreach ($this->roles as $i => $role) { ?>
				
		[data-v-role-list] [data-v-role] = $role['display_name']
		[data-v-role-list] [data-v-role]|value = $role['role_id']
		[data-v-role-list] [data-v-role]|addNewAttribute = <?php if (isset($this->user) && ($role['role_id'] == $this->user['role_id'])) echo 'selected';?>

[data-v-role-list] [data-v-role]|after = <?php 
	}
?>
