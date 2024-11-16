import(common.tpl)

head > title = <?php echo htmlspecialchars(ucfirst($this->category_name));?>

[data-v-category-name] = <?php echo htmlspecialchars(ucfirst($this->category_name));?>