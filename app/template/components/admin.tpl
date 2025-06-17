[data-v-component-admin]|prepend = <?php 
	if (isset($_admin_idx)) $_admin_idx++; else $_admin_idx = 0;
	$previous_component = isset($component)?$component:null;
	$admin = $component = $this->_component['admin'][$_admin_idx] ?? [];
	//$admin = \Vvveb\session('admin');
?>

[data-v-component-admin] [data-v-admin-*]|innerText = $admin['@@__data-v-admin-(*)__@@']
[data-v-component-admin] a[data-v-admin-*]|href = $admin['@@__data-v-admin-(*)__@@']
[data-v-component-admin] img[data-v-admin-*]|src = <?php
	if (isset($admin['@@__data-v-admin-(*)__@@'])) {
		echo htmlspecialchars($admin['@@__data-v-admin-(*)__@@']);
	} else if ('@@__src__@@') {
		echo '@@__src__@@';
	} else {
		echo PUBLIC_PATH . 'media/placeholder.svg';
	}
?>	

[data-v-component-admin]|append = <?php 
	$component = $previous_component;
?>