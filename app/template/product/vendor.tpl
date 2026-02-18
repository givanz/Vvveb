import(common.tpl)

head > title                = $this->vendor['title']
[data-v-vendor-*]|innerText = $this->vendor['@@__data-v-vendor-(*)__@@']
img[data-v-vendor-*]|src    = $this->vendor['@@__data-v-vendor-(*)__@@']
[data-v-vendor-name]        = <?php echo htmlspecialchars(ucfirst($this->vendor['name'] ?? ''));?>
