import(common.tpl)

@group = .search-results .group
@item  = .search-results .group .list-group-item

@group|deleteAllButFirstChild
@item|deleteAllButFirstChild

@group|before = <?php 
	foreach($this->search as $groupName => $group) {
?>
	@group [data-v-group-name]|innerText = <?php echo htmlspecialchars(ucfirst($groupName));?>

	@item|before = <?php 
		foreach($group as $name => $item) {
	?>
	
	@item [data-v-item-*]|innerText = $item['@@__data-v-item-(*)__@@']
	@item [data-v-item-url]|href    = $item['url']
	@item [data-v-item-icon]|class  = $item['icon']
	@item [data-v-item-image]|src   = $item['src']

	@item|after = <?php 
	} ?>

@group|after = <?php 
} ?>