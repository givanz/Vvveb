import(common.tpl)

head > title                            = $this->category['title']
head > meta[name="keywords"]|content    = $this->category['meta_keywords']
head > meta[name="description"]|content = $this->category['meta_description']
[data-v-category-*]|innerText           = $this->category['@@__data-v-category-(*)__@@']
img[data-v-category-*]|src              = $this->category['@@__data-v-category-(*)__@@']
img[data-v-category-*]|alt              = $this->category['meta_description']
