import(listing.tpl, {"type":"admin_auth_token", "list": "admin_auth_tokens"})

[data-v-admin_auth_token] [type="text"][data-v-admin_auth_token-*]|name = <?php echo 'admin_auth_token[' . ($admin_auth_token['admin_auth_token_id'] ?? '#') . '][@@__data-v-admin_auth_token-(*)__@@]';?>
[data-v-admin_auth_token] [type="hidden"][data-v-admin_auth_token-*]|name = <?php echo 'admin_auth_token[' . ($admin_auth_token['admin_auth_token_id'] ?? '#') . '][@@__data-v-admin_auth_token-(*)__@@]';?>
[data-v-admin_auth_token] [type="password"][data-v-admin_auth_token-*]|name = <?php echo 'admin_auth_token[' . ($admin_auth_token['admin_auth_token_id'] ?? '#') . '][@@__data-v-admin_auth_token-(*)__@@]';?>

a[data-v-user-url]|href = $this->user_url