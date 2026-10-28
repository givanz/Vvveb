//set editing flag
head|append = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
$is_admin = \Vvveb\isAdmin();
if ($is_admin && $vvveb_is_page_edit) {

	$theme         = $_GET['theme'] ?? Vvveb\System\Sites::getTheme();
	$customCssFile = DIR_THEMES . $theme . DS . 'css'. DS . 'custom.css';
	$css = '';

	if (file_exists($customCssFile)) {
		$css = file_get_contents($customCssFile);
	}

	echo "<style id=\"vvvebjs-styles\">$css</style>";
	
	if (isset($_GET['preview'])) {
		echo '<link data-vvveb-helpers href="' . PUBLIC_PATH . 'css/vvvebjs-editor-helpers.css" rel="stylesheet">';
	}
}
?>

#vvvebjs-styles|hide = $vvveb_is_page_edit

body|prepend = <?php
if (isset($is_admin) && $is_admin && !$vvveb_is_page_edit && APP == 'app') {
	include_once(DIR_ROOT . '/admin/admin-bar.php');
}
?>
