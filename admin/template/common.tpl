// example <div data-v-copy-from="index.html,#element">
[data-v-copy-from]|outerHTML = from(@@__data-v-copy-from:([^\,]+)__@@|@@__data-v-copy-from:[^\,]+\,([^\,]+)__@@)

import(admin.tpl)
import(ifmacros.tpl)
import(notifications.tpl)
import(components.tpl)
import(menu.tpl)


[data-v-check-permission-*]|if_exists = $this->actionPermissions['@@__data-v-check-permission-(*)__@@']
[data-v-check-action-permission]|if_exists = $this->actionPermissions['@@__data-v-check-action-permission__@@']
[data-v-check-permission]|if_exists = $this->modulePermissions['@@__data-v-check-permission__@@']

html|addNewAttribute = <?php 
$vvveb_is_page_edit = Vvveb\isEditor();

if (isset($_COOKIE['theme']) && !$vvveb_is_page_edit) { 
	echo 'data-bs-theme="';
	if ($_COOKIE['theme'] == 'dark') echo 'dark'; else if ($_COOKIE['theme'] == 'light') echo 'light';else echo 'auto';  
	echo '"';
} 

if (isset($this->global['rtl']) && $this->global['rtl'] && !$vvveb_is_page_edit) { 
	echo 'dir="rtl"';
}
?>

html|lang = $this->global['locale']

#container|addClass = <?php if (isset($_COOKIE['sidebar-size']) && ($_COOKIE['sidebar-size'] == 'small-nav')) { 
	echo 'small-nav';
} ?>
