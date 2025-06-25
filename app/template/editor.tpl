//set editing flag
head|append = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
$is_admin = \Vvveb\isAdmin();
if ($is_admin && $vvveb_is_page_edit) {
	$customCssFile = DIR_THEMES .  Vvveb\System\Sites::getTheme() . DS . 'css'. DS . 'custom.css';
	if (file_exists($customCssFile)) {
		$css = file_get_contents($customCssFile);//'body {background:red}';
		echo "<style id=\"vvvebjs-styles\">$css</style>";
	}
}
?>

#vvvebjs-styles|hide = $vvveb_is_page_edit

body|prepend = <?php
if (isset($is_admin) && $is_admin && !$vvveb_is_page_edit && APP == 'app') {
		include_once(DIR_ROOT . '/admin/admin-bar.php');
}
?>
