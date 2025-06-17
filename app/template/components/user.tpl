[data-v-component-user]|prepend = <?php 
	if (isset($_user_idx)) $_user_idx++; else $_user_idx = 0;
	$previous_component = isset($component)?$component:null;
	$user = $component = $this->_component['user'][$_user_idx] ?? [];
	//$user = \Vvveb\session('user');
?>

[data-v-component-user] [data-v-user-*]|innerText = $user['@@__data-v-user-(*)__@@']
[data-v-component-user] a[data-v-user-*]|href = $user['@@__data-v-user-(*)__@@']
[data-v-component-user] img[data-v-user-*]|src = <?php
	if (isset($user['@@__data-v-user-(*)__@@'])) {
		echo htmlspecialchars($user['@@__data-v-user-(*)__@@']);
	} else if ('@@__src__@@') {
		echo '@@__src__@@';
	} else {
		echo PUBLIC_PATH . 'media/placeholder.svg';
	}
?>	

[data-v-component-user] input[data-v-user-email]|value = <?php
	$email = $user['email'] ?? $_POST['email'] ?? '';
	echo htmlspecialchars($email);
?>	

[data-v-component-user]|append = <?php 
	$component = $previous_component;
?>
