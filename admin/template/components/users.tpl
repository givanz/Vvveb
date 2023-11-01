@user = [data-v-component-users] [data-v-user]
@user|deleteAllButFirstChild

[data-v-component-users]|prepend = <?php
if (isset($_users_idx)) $_users_idx++; else $_users_idx = 0;

$users = [];
$count = 0;

if(isset($this->_component['users']) && is_array($this->_component['users'][$_users_idx]['user'])) 
{
	$users = $this->_component['users'][$_users_idx];
	$count = $users['count'] ?? 0;
}

//$_pagination_count = $this->users[$_users_idx]['count'];
//$_pagination_limit = $this->users[$_users_idx]['limit'];
?>

[data-v-component-users] [data-v-users-*]|innerText = $users['@@__data-v-users-(*)__@@']

@user|before = <?php
if($users) {
	//$pagination = $this->users[$_users_idx]['pagination'];
	$index = 0;
	foreach ($users['user'] as $index => $user) {
	?>
	
	@user [data-v-user-*]|innerText = $user['@@__data-v-user-(*)__@@']
	@user [data-v-user-*]|title = $user['@@__data-v-user-(*)__@@']
    
    @user [data-v-user-url]|href = <?php echo Vvveb\url(['module' => 'user/user', 'user_id' => $user['user_id']]);?>
	
	@user|after = <?php 
		$index++;
	} 
}
?>
