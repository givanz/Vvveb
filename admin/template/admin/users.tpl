[data-v-users]|before = <?php 
$module = 'admin/user';
$id = 'admin_id';
?>

import(user/users.tpl)

@user a[data-v-auth-token-url]|href = <?php echo Vvveb\url(['module' => 'admin/auth-token', 'admin_id' => $user['admin_id']]);?>	