@admins = [data-v-component-admins]
@admin  = [data-v-component-admins] [data-v-admin]

@admin|deleteAllButFirstChild

@admins|prepend = <?php
if (isset($_admins_idx)) $_admins_idx++; else $_admins_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$admins = $current_component = $this->_component['admins'][$_admins_idx] ?? [];

$count = $admins['count'] ?? 0;
$limit = isset($admins['limit']) ? $admins['limit'] : 5;	
?>


@admin|before = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
$_admins = $admins['admin'] ?? [];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['admin_id' => 1, 'username' => '']] : false;
$_admins = empty($_admins) ? $_default : $_admins;

if($_admins && is_array($_admins)) {
	foreach ($_admins as $index => $admin) {?>
		
		@admin|data-admin_id = $admin['admin_id']
		
		@admin|addClass = <?php if (!$vvveb_is_page_edit) echo 'level-' . ($admin['level'] ?? 0);?>
		
		@admin|id = <?php  if (!$vvveb_is_page_edit) echo 'admin-' . $admin['admin_id'];?>
		
		@admin [data-v-admin-content] = $admin['content']
		
		@admin img[data-v-admin-*]|src = $admin['@@__data-v-admin-(*)__@@']
		//@admin img[data-v-admin-*]|width = <?php echo (int)($admin['size'] ?? 60);?>
		
		@admin [data-v-admin-*]|innerText = $admin['@@__data-v-admin-(*)__@@']
		
		@admin a[data-v-admin-*]|href = $admin['@@__data-v-admin-(*)__@@']
	
	@admin|after = <?php 
	} 
}
?>