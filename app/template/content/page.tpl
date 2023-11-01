import(common.tpl)

head > title = $this->post['name']
head > meta[name="keywords"]|content = $this->post['meta_keywords']
head > meta[name="description"]|content = $this->post['meta_description']

//body|append = <?php var_dump($this->post);?>