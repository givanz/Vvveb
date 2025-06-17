import(crud.tpl, {"type":"user"})

[data-v-user] select[data-v-user-*] option|addNewAttribute = <?php if (isset($this->user) && $this->user['status'] == '@@__value__@@') echo 'selected';?>

/* Avatar */
[data-v-user] [data-v-avatar]|data-v-avatar = $this->user['avatar_url']
[data-v-user] input[data-v-avatar]|value = $this->user['avatar']
[data-v-user] img[data-v-avatar]|src = <?php echo (isset($this->user['avatar_url']) && $this->user['avatar_url']) ? $this->user['avatar_url'] : PUBLIC_PATH . 'media/placeholder.svg';?>
