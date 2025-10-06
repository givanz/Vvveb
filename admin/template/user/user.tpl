import(crud.tpl, {"type":"user"})

[data-v-user] select[data-v-user-*] option|addNewAttribute = <?php if (isset($this->user) && $this->user['status'] == '@@__value__@@') echo 'selected';?>

/* Avatar */
[data-v-user] [data-v-avatar]|data-v-avatar = $this->user['avatar_url']
[data-v-user] input[data-v-avatar]|value = $this->user['avatar']
[data-v-user] img[data-v-avatar]|src = <?php echo (isset($this->user['avatar_url']) && $this->user['avatar_url']) ? $this->user['avatar_url'] : PUBLIC_PATH . 'media/placeholder.svg';?>

/* Cover */
[data-v-user] [data-v-cover]|data-v-cover = $this->user['cover_url']
[data-v-user] input[data-v-cover]|value = $this->user['cover']
[data-v-user] img[data-v-cover]|src = <?php echo (isset($this->user['cover_url']) && $this->user['cover_url']) ? $this->user['cover_url'] : PUBLIC_PATH . 'media/placeholder.svg';?>

a[data-v-user-url]|href = $this->user_url

[data-v-user] [data-v-user-*]|innerText = $this->user['@@__data-v-user-(*)__@@']