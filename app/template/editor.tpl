//set editing flag
head|append = <?php
$vvveb_is_page_edit = $vvveb_is_page_edit = Vvveb\isEditor();
$is_admin = \Vvveb\isAdmin();
if ($is_admin && !$vvveb_is_page_edit) {
	echo '<link href="' . Vvveb\publicUrlPath() . 'admin/default/css/admin-bar.css" rel="stylesheet">';
}
?>

body|prepend = <?php
if (isset($is_admin) && $is_admin && !$vvveb_is_page_edit) {
		include_once(DIR_ROOT . '/admin/admin-bar.php');
}
?>
