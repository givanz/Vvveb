import(crud.tpl, {"type":"tax_rate"})

@region_group = [data-v-region-groups] [data-v-region-group]
@region_group|deleteAllButFirstChild

@region_group|before = <?php
$count = 0;
if(isset($this->region_groups) && is_array($this->region_groups)) {
	foreach ($this->region_groups as $region_group_index => $region_group) {?>
	
	[data-v-region-group-*]|innerText  = $region_group['@@__data-v-region-group-(*)__@@']
	option[data-v-region-group-*]|value = $region_group['@@__data-v-region-group-(*)__@@']	
	
	@region_group|after = <?php 
		$count++;
	} 
}?>
