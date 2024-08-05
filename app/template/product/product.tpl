import(common.tpl)

head > title = $this->product['name']
head > meta[name="keywords"]|content = $this->product['meta_keywords']
head > meta[name="description"]|content = $this->product['meta_description']

//body|append = <?php var_dump($this->product);?>
