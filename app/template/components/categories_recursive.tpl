[data-v-component-categories] > li|deleteAllButFirst
[data-v-component-categories] > li ul|delete

[data-v-component-categories] ul|before = <?php 
if (isset($_categories_idx)) $_categories_idx++; else $_categories_idx = 0;
$categories = $this->_component['categories'][$_categories_idx];

$_pagination_count = $categories'count'] ?? 0;
$_pagination_limit = $categories['limit'] ?? 5;

if (!function_exists("generate_menu")) {function generate_menu($parent, &$categories){
?>

[data-v-component-categories] li|before = <?php
foreach($categories as $id => $category) {
	if ($category['parent'] == $parent) {       
?>

[data-v-component-categories] > li > a|innerText = $category['name']
[data-v-component-categories] > li > a|href = <?php echo '/?module=category&category_id=' . $id;?>
[data-v-component-categories] > li|category_id = $id

[data-v-component-categories] > li > a|after = <?php generate_menu($id, $categories);?>

 
//close foreach
[data-v-component-categories] li|after = <?php } }?>

//close function 
[data-v-component-categories]|after = <?php } } reset($categories);generate_menu($categories[key($categories)]['parent'], $categories);?>

//set variable for if macro
[data-v-component-categories] .if_*.tplttmacroif = 'category';
