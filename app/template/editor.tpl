//set editing flag
head|append = <?php
$is_editor = $vvveb_is_page_edit = Vvveb\isEditor();
$is_admin = \Vvveb\isAdmin();
if ($is_admin && !$is_editor) {
	echo '<link href="' . Vvveb\publicUrlPath() . 'admin/default/css/admin-bar.css" rel="stylesheet">';
}
?>

body|prepend = <?php
if (isset($is_admin) && $is_admin && !$is_editor) {
		include_once(DIR_ROOT . '/admin/admin-bar.php');
}
?>
