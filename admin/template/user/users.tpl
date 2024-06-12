import(common.tpl)
import(pagination.tpl)

@user = [data-v-users] [data-v-user]
@user|deleteAllButFirstChild

@user|before = <?php
$module = $module ?? 'user/user';
$id = $id ?? 'user_id';

if(isset($this->users) && is_array($this->users)) {
	foreach ($this->users as $index => $user) { 
		$url = Vvveb\url(['module' => $module, $id => $user[$id]]);
		$status = $user['status_text'];
		$status_class = $user['status'] == 1 ?  'bg-success' :($user['status'] == 0 ? 'bg-body-secondary text-muted' : $user['status']);
	?>
	
	@user [data-v-*]|innerText = $user['@@__data-v-(*)__@@']
	@user input[data-v-*]|value = $user['@@__data-v-(*)__@@']

	@user img[data-v-*]|src = $user['@@__data-v-(*)__@@']

	
	@user [data-v-url]|href =<?php echo htmlentities($url);?>
	@user a[data-v-*]|href = $user['@@__data-v-(*)__@@']	
	@user [data-v-edit-url]|href =<?php echo htmlentities($url);?>
	@user [data-v-url]|title = $user['title']	
	@user [data-v-status]|addClass = <?php echo $status_class;?>
	@user [data-v-status] = <?php echo $status;?>
	
	
	@user|after = <?php 
	} 
}?>

import(filters.tpl)

