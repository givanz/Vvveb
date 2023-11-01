import(common.tpl) 

[data-v-user_address-*] = <?php echo $this->user_address['@@__data-v-user_address-([a-zA-Z_\d]+)__@@'] ?? '';?>
