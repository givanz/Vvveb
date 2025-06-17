import(common.tpl)

//[data-v-user] [data-v-user-*] = $this->user['@@__data-v-user-(*)__@@']

/* input elements */
[data-v-user] input[data-v-user-*]|value = 
<?php
	 if (isset($_POST['admin']['@@__data-v-user-(*)__@@'])) 
		echo htmlspecialchars($_POST['admin']['@@__data-v-user-(*)__@@']); 
	 else if (isset($this->user['@@__data-v-user-(*)__@@'])) 
		echo htmlspecialchars($this->user['@@__data-v-user-(*)__@@']);
?>


/* textarea elements */
[data-v-user] textarea[data-v-user-*] = 
<?php
	 if (isset($_POST['admin']['@@__data-v-user-(*)__@@'])) 
		echo htmlspecialchars($_POST['admin']['@@__data-v-user-(*)__@@']); 
	 else if (isset($this->user['@@__data-v-user-(*)__@@'])) 
		echo htmlspecialchars($this->user['@@__data-v-user-(*)__@@']);
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

/* Avatar */
[data-v-user] [data-v-avatar]|data-v-avatar = $this->user['avatar_url']
[data-v-user] input[data-v-avatar]|value = $this->user['avatar']
[data-v-user] img[data-v-avatar]|src = <?php echo (isset($this->user['avatar_url']) && $this->user['avatar_url']) ? $this->user['avatar_url'] : PUBLIC_PATH . 'media/placeholder.svg';?>

/* Site access */

#all-sites-check|addNewAttribute = <?php if (!isset($this->user['site_access']) || empty($this->user['site_access'])) echo 'checked';?>

[data-v-sites] [data-v-site]|deleteAllButFirstChild

[data-v-sites]  [data-v-site]|before = <?php
if(isset($this->sitesList) && is_array($this->sitesList)) {
	//$pagination = $this->sites[$_sites_idx]['pagination'];
	foreach ($this->sitesList as $index => $site) { ?>
	
	[data-v-sites] [data-v-site] [data-v-*]|innerText = $site['@@__data-v-(*)__@@']
	[data-v-sites] [data-v-site] input[type="checkbox"]|addNewAttribute = <?php if (isset($this->user['site_access']) && in_array($site['site_id'], $this->user['site_access'])) echo 'checked';?>

	
	[data-v-sites]  [data-v-site]|after = <?php
	} 
}?>
