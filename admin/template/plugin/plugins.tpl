import(common.tpl)
import(plugin/plugins_list.tpl)

@category = [data-v-plugin-categories] [data-v-plugin-category]
@category|deleteAllButFirstChild

@category|before = <?php
if(isset($this->categories) && is_array($this->categories))  {
foreach ($this->categories as $category => $plugins) {?>	
	
	@category [data-v-category-link] = $category
	@category [data-v-category-link]|addClass = <?php if (isset($this->category) && $category == $this->category) echo 'active';?>
	@category [data-v-category-link]|href = <?php echo htmlentities(Vvveb\url(['module' => 'plugin/plugins', 'category' => $category]));?>
	
@category|after = <?php } 
}?>

#safemode|if_exists = $this->safemode
	
