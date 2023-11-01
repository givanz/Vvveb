import(common.tpl)
import(pagination.tpl)

[data-v-roles] [data-v-role]|deleteAllButFirstChild

[data-v-roles]  [data-v-role]|before = <?php
if(isset($this->roles) && is_array($this->roles)) {
	//$pagination = $this->roles[$_roles_idx]['pagination'];
	foreach ($this->roles as $index => $role) {?>
    
    [data-v-roles] [data-v-role] [data-v-role-url]|href = <?php echo Vvveb\url(['module' => 'admin/role', 'role_id' => $role['role_id']]);?>
	
	[data-v-roles] [data-v-role] [data-v-*]|innerText = $role['@@__data-v-(*)__@@']

	[data-v-roles]  [data-v-role]|after = <?php 
	} 
}?>