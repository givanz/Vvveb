import(crud.tpl, {"type":"post"})
import(content/edit.tpl, {"type":"post"})

/* Links */
[data-v-post] [data-v-url]|href = $this->post['url']
[data-v-post] [data-v-url] = $this->post['url']
[data-v-post] [data-v-design_url]|href = $this->post['design_url']

[data-v-template_missing] = <?php echo $this->template_missing;?>
[data-v-type_name_plural] = $this->type_name_plural
[data-v-type-name] = $this->type_name
[data-v-type] = $this->type
[data-v-posts-list-url]|href = $this->posts_list_url
