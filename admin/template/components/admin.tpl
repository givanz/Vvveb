[data-v-component-admin]|prepend = <?php 
	if (isset($_admin_idx)) $_admin_idx++; else $_admin_idx = 0;
	$previous_component = isset($component)?$component:null;
	$admin = $component = $this->_component['admin'][$_admin_idx] ?? [];
	$admin['edit-url'] = Vvveb\url(['module' => 'admin/user', 'admin_id' => $admin['admin_id'] ?? 1]);
	//$admin = \Vvveb\session('admin');
?>

[data-v-component-admin] [data-v-admin-*]|innerText = $admin['@@__data-v-admin-(*)__@@']
[data-v-component-admin] a[data-v-admin-edit-url]|href = $admin['edit-url']

[data-v-component-admin]|append = <?php 
	$component = $previous_component;
?>