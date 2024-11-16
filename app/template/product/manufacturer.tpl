import(common.tpl)

head > title = <?php echo htmlspecialchars(ucfirst($this->manufacturer_name));?>

[data-v-manufacturer-name] = <?php echo htmlspecialchars(ucfirst($this->manufacturer_name));?>

[data-v-manufacturer-*]|innerText = $this->manufacturer['@@__data-v-manufacturer-(*)__@@']
img[data-v-manufacturer-*]|src    = $this->manufacturer['@@__data-v-manufacturer-(*)__@@']
[data-v-manufacturer-name]        = <?php echo htmlspecialchars(ucfirst($this->manufacturer['name'] ?? ''));?>
