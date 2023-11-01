import(common.tpl)
import(pagination.tpl)

@{{type}} = [data-v-{{list}}] [data-v-{{type}}]
@{{type}}|deleteAllButFirstChild

@{{type}}|before = <?php
$count = 0;
if(isset($this->{{list}}) && is_array($this->{{list}})) {
	foreach ($this->{{list}} as $index => ${{type}}) { ?>
	
	@{{type}} [data-v-{{type}}-*]|innerText  = ${{type}}['@@__data-v-{{type}}-(*)__@@']
	@{{type}} input[data-v-{{type}}-*]|value = ${{type}}['@@__data-v-{{type}}-(*)__@@']	
	@{{type}} a[data-v-{{type}}-*]|href 	 = ${{type}}['@@__data-v-{{type}}-(*)__@@']	
	@{{type}} img[data-v-{{type}}-*]|src 	 = ${{type}}['@@__data-v-{{type}}-(*)__@@']	
	@{{type}} img[data-v-{{type}}-img]|src 	 = ${{type}}['image']
	
	@{{type}}|after = <?php 
		$count++;
	} 
}?>