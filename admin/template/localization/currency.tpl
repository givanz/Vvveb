import(crud.tpl, {"type":"currency"})

[data-v-currency] select[data-v-currency-*] option|addNewAttribute = <?php if (isset($this->currency) && $this->currency['status'] == '@@__value__@@') echo 'selected';?>


[data-v-currency] [data-v-image]|data-v-image = $this->currency['image_url']
[data-v-currency] input[data-v-image]|value = $this->currency['image']
[data-v-currency] img[data-v-image]|src = <?php echo (isset($this->currency['image_url']) && $this->currency['image_url']) ? $this->currency['image_url'] : 'img/placeholder.svg';?>
