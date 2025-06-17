import(common.tpl)

[data-v-post_type-*] =  $this->post_type['@@__data-v-post_type-(*)__@@']

[data-v-image]|data-v-image = $this->post_type['image_url']
input[data-v-image]|value = $this->post_type['icon-img']
img[data-v-image]|src = <?php echo (isset($this->post_type['image_url']) && $this->post_type['image_url']) ? $this->post_type['image_url'] : PUBLIC_PATH . 'media/placeholder.svg';?>
