import(common.tpl)

head > title = <?php echo htmlentities(ucfirst($this->category_name));?>

[data-v-category-name] = <?php echo htmlentities(ucfirst($this->category_name));?>