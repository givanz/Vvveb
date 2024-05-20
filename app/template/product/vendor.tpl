import(common.tpl)

head > title = <?php echo htmlentities(ucfirst($this->vendor_name));?>

[data-v-vendor-*]|innerText = $this->vendor['@@__data-v-vendor-(*)__@@']
img[data-v-vendor-*]|src    = $this->vendor['@@__data-v-vendor-(*)__@@']
[data-v-vendor-name]        = <?php echo htmlentities(ucfirst($this->vendor['name'] ?? ''));?>
