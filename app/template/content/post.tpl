import(common.tpl)

head > title                            = $this->post['title']
head > meta[name="keywords"]|content    = $this->post['meta_keywords']
head > meta[name="description"]|content = $this->post['meta_description']