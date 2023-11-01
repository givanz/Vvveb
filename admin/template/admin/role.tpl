import(common.tpl)

[data-v-role] [data-v-role-*] = $this->role['@@__data-v-role-(*)__@@']

/* input elements */
[data-v-role] input[data-v-role-*]|value = 
<?php
	 if (isset($_POST['role']['@@__data-v-role-(*)__@@'])) 
		echo $_POST['role']['@@__data-v-role-(*)__@@']; 
	 else if (isset($this->role['@@__data-v-role-(*)__@@'])) 
		echo $this->role['@@__data-v-role-(*)__@@'];
?>


/* textarea elements */
[data-v-role] textarea[data-v-role-*] = 
<?php
	 if (isset($_POST['role']['@@__data-v-role-(*)__@@'])) 
		echo $_POST['role']['@@__data-v-role-(*)__@@']; 
	 else if (isset($this->role['@@__data-v-role-(*)__@@'])) 
		echo $this->role['@@__data-v-role-(*)__@@'];
?>/* textarea elements */



[data-v-role] select[data-v-role-*]|before = 
<?php
	 $selected = '';	
	 if (isset($this->role['@@__data-v-role-(*)__@@'])) 
	 $selected = $this->role['@@__data-v-role-(*)__@@'];
?>



[data-v-controllers] [data-v-controller]|before = <?php
	foreach ($this->controllers['permissions'] as $i => $permission) { ?>
		
	[data-v-controllers] [data-v-controller] span = $permission	
	[data-v-controllers] [data-v-controller] input = $permission	
		
[data-v-controllers] [data-v-controller]|after = <?php 
} ?>		


//allow
@allow = [data-v-allow] [data-v-rule]
@allow|deleteAllButFirstChild
@allow|before = <?php
	if (isset($this->role['permissions']['allow']))
	foreach ($this->role['permissions']['allow'] as $i => $permission) {?>
		
	@allow span = $permission	
	@allow input = $permission	
		
@allow|after = <?php 
} ?>

//deny
@deny = [data-v-deny] [data-v-rule]
@deny|deleteAllButFirstChild
@deny|before = <?php
	if (isset($this->role['permissions']['deny']))
	foreach ($this->role['permissions']['deny'] as $i => $permission) {?>
		
	@deny span = $permission	
	@deny input = $permission	
		
@deny|after = <?php 
} ?>		


@permissions = [data-v-permissions]
@permission  = [data-v-permission]

@permissions|deleteAllButFirstChild
@permission|deleteAllButFirstChild

@permissions|before = <?php
$tree = $this->tree ?? [];
if ($tree) {
	$generate_menu = function (&$parent, $path = false) use (&$tree, &$generate_menu) {

?>

	@permission|before = <?php 

		foreach($parent as $id => $permission) {
			$uniq        = Vvveb\System\Functions\Str::random(5);
			$hasChildren = is_array($permission) && count($permission);
		?>		
			//catch all data attributes
			@permission [data-v-permission-*] = $permission['@@__data-v-permission-(*)__@@']
			@permission [data-v-name] = $id
			@permission input[type="hidden"] = <?php echo  ($path ? $path  . '/' : $path) . $id;?>
			@permission input[type="checkbox"]|id = $uniq
			@permission|class = <?php if ($hasChildren) echo 'folder'; else 'file';?>
			
					
		@permission|append = <?php 
			if ($hasChildren) $generate_menu($permission, ($path ? $path  . '/' : $path) . $id);
		?>
	
	@permission|after = <?php 
	} ?>

@permissions|after = <?php 
};
reset($tree);
$generate_menu($tree); }
$uniq = Vvveb\System\Functions\Str::random(5);
?>
