import(common.tpl)

head > title                            = $this->product['title']
head > meta[name="keywords"]|content    = $this->product['meta_keywords']
head > meta[name="description"]|content = $this->product['meta_description']