import(common.tpl)

head > title                  = $this->category['title']
[data-v-category-*]|innerText = $this->category['@@__data-v-category-(*)__@@']
img[data-v-category-*]|src    = $this->category['@@__data-v-category-(*)__@@']