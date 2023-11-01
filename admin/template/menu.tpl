/* menu */
//set selector prefix to have shorter and easier to read selectors for rules
@menu_item = [data-v-menu] [data-v-menu-item]
@submenumenu_item = [data-v-menu] [data-v-menu-item] [data-v-submenu]


@menu_item|deleteAllButFirst
@submenumenu_item [data-v-submenu-item]|deleteAllButFirst


@menu_item|before = <?php
if (isset($this->menu) && $this->menu)
foreach($this->menu as $key => $menu_item) {
	
	if (isset($menu_item['permission']) && !$menu_item['permission']) continue;?>

	@menu_item [data-v-menu-item-url]|href = $menu_item['url']
	@menu_item [data-v-menu-item-url]|title = $menu_item['name']
	@menu_item [data-v-menu-item-name] = $menu_item['name']
	@menu_item [data-v-menu-item-subtitle]|if_exists = $menu_item['subtitle']
	@menu_item [data-v-menu-item-subtitle] = $menu_item['subtitle']
	
	//icon
	@menu_item [data-v-menu-item-icon]|if_exists = $menu_item['icon']
	@menu_item [data-v-menu-item-icon]|class = $menu_item['icon']

	//icon img
	@menu_item [data-v-menu-item-icon-img]|if_exists = $menu_item['icon-img']
	@menu_item [data-v-menu-item-icon-img]|src = $menu_item['icon-img']

	//badge
	@menu_item [data-v-menu-item-badge]|if_exists = $menu_item['badge']
	@menu_item [data-v-menu-item-badge]|class = $menu_item['badge-class']
	@menu_item [data-v-menu-item-badge] = $menu_item['badge']

	@menu_item|addClass = <?php 
		if (isset($menu_item['heading'])) echo ' heading';
		if (isset($menu_item['class'])) echo $menu_item['class'];
	?>
	@menu_item [data-v-menu-item-url]|addClass = <?php if (isset($menu_item['items'])) echo 'items';?>
	@menu_item .mobile|addNewAttribute = <?php if (isset($menu_item['items'])) echo 'data-bs-toggle="dropdown"';?>
	@menu_item [data-v-menu-item-url]|data-target = <?php echo '#menu-' . $key;?>

	//@submenumenu_item|id = <?php echo '#menu-' . $key;?>
	//@submenumenu_item|addClass = <?php echo 'dropdown-menu';?>
	@submenumenu_item|addClass = <?php 
		if (isset($menu_item['show_on_modules']) && in_array($_GET['module'], $menu_item['show_on_modules'])) echo 'show';
	?>
	
	//@submenumenu_item|if_exists = $menu_item['items']
	
	@submenumenu_item|before = <?php
	if (!function_exists('_admin_menu_print')) {
		function _admin_menu_print($items) {
	?>
	
	@submenumenu_item [data-v-submenu-item]|addClass = <?php 
		if (isset($submenu_item['heading'])) echo ' heading';
		if (isset($submenu_item['class'])) echo $submenu_item['class'];
	?>	
	
	@submenumenu_item [data-v-submenu-item]|before = <?php
		foreach($items as $key => $submenu_item) {
			if (isset($submenu_item['permission']) && !$submenu_item['permission']) continue;?>
		
			@submenumenu_item [data-v-submenu-item-url]|href = $submenu_item['url']
			@submenumenu_item [data-v-submenu-item-name] = $submenu_item['name']
			@submenumenu_item [data-v-submenu-item-subtitle]|if_exists = $submenu_item['subtitle']
			@submenumenu_item [data-v-submenu-item-subtitle] = $submenu_item['subtitle']
			
			//icon
			@submenumenu_item [data-v-submenu-item-icon]|if_exists = $submenu_item['icon']
			@submenumenu_item [data-v-submenu-item-icon]|class = $submenu_item['icon']
			
			//icon img
			@submenumenu_item [data-v-submenu-item-icon-img]|if_exists = $submenu_item['icon-img']
			@submenumenu_item [data-v-submenu-item-icon-img]|src = $submenu_item['icon-img']
			
			//badge
			@submenumenu_item [data-v-submenu-item-badge]|if_exists = $submenu_item['badge']
			@submenumenu_item [data-v-submenu-item-badge]|class = $submenu_item['badge-class']
			@submenumenu_item [data-v-submenu-item-badge] = $submenu_item['badge']

			@submenumenu_item [data-v-submenu-item-url]|addClass = <?php 
				if (isset($submenu_item['items'])) echo 'items';
			?>
			
			@submenumenu_item [data-v-submenu-item-url]|after = <?php  
			if (isset($submenu_item['items'])) {
				_admin_menu_print($submenu_item['items']);
			} ?> 
			
			@submenumenu_item [data-v-submenu-item]|after = <?php  
		} ?> 


	@submenumenu_item|after = <?php } 
	?>


@menu_item|after = <?php  } 
	if (isset($menu_item['items'])) {
			_admin_menu_print($menu_item['items']);
	}
} ?>
