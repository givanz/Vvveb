[data-v-themes] [data-v-theme]|deleteAllButFirstChild

[data-v-themes] [data-v-theme]|before = <?php
if(isset($this->themes) && is_array($this->themes)) {
	//$pagination = $this->themes[$_themes_idx]['pagination'];
	foreach ($this->themes as $index => $theme) {?>
	
    [data-v-themes] [data-v-theme] [data-v-theme-*]|innerText  = $theme['@@__data-v-theme-([-_\w]+)__@@']
    [data-v-themes] [data-v-theme] a[data-v-theme-*]|href  = $theme['@@__data-v-theme-([-_\w]+)__@@']
    [data-v-themes] [data-v-theme] img[data-v-theme-*]|src  = $theme['@@__data-v-theme-([-_\w]+)__@@']
    
	[data-v-themes] [data-v-theme] [data-v-theme-activate-url]|href  = <?php echo Vvveb\url(['module' => 'theme/themes', 'action' => 'activate', 'theme' => $theme['folder']]);?>
	[data-v-themes] [data-v-theme] [data-v-theme-delete-url]|href  = <?php echo Vvveb\url(['module' => 'theme/themes', 'action' => 'delete', 'theme' => $theme['folder']]);?>
	[data-v-themes] [data-v-theme] [data-v-theme-preview-url]|href  = <?php echo Vvveb\url('index/index') . '&theme=' . $theme['folder'];?>
	[data-v-themes] [data-v-theme] [data-v-theme-edit-url]|href  = <?php echo Vvveb\url(['module' => 'editor/editor']) . '&url=/&template=index.html&theme=' . $theme['folder'];?>
	[data-v-themes] [data-v-theme] [data-v-theme-code-url]|href  = <?php echo Vvveb\url(['module' => 'editor/code']) . '&type=themes#%2F' . $theme['folder'];?>
	[data-v-themes]  [data-v-theme]|after = <?php 
	}
}?>


//import


//[data-v-import] [data-v-list]

[data-v-import] [data-v-list]|before = <?php 
$list = '@@__data-v-list__@@';
$path = $list;

if (!function_exists("import_generate_list")) {
	$import_generate_list = function (&$import, $path) use ($list, &$import_generate_list) {
		foreach ($import as $name => $value ) {
			$hasChildren = is_array($value);
			$uniqId = $name . rand();
?>


[data-v-import] [data-v-list] [data-v-list-item-name] = <?php echo ucfirst($name);?>
[data-v-import] [data-v-list] label|for = <?php echo 'menu-' . $uniqId;?>
[data-v-import] [data-v-list] input|id = <?php echo 'menu-' . $uniqId;?>

[data-v-import] [data-v-list] label.custom-control-label|for = <?php echo 'item-' . $uniqId;?>
[data-v-import] [data-v-list] input.btn-check|id = <?php echo 'item-' . $uniqId;?>

[data-v-import] [data-v-list] input.btn-check|name = <?php echo $path . '[' . $name . '][]';?>

//close function 
[data-v-import] [data-v-list] ul = <?php 
			if ($hasChildren) {
				$import_generate_list($value, $path . "[$name]");
			} 
?>			
			
[data-v-import] [data-v-list]|after = <?php			
		}
	}; 
} 
if ($this->$list) {
	reset($this->$list);
	$import_generate_list($this->$list, $path);
}
?>
