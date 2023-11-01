import(common.tpl)
import(plugin/plugins_list.tpl)
import(theme/themes_list.tpl)

@namespace = [data-v-namespaces] [data-v-namespace]
@subspace = [data-v-namespaces] [data-v-namespace] [data-v-subspaces] [data-v-subspace]

@namespace|deleteAllButFirstChild

@namespace|before = <?php
$i = 0;
if(isset($this->namespaces) && is_array($this->namespaces)) 
{
	foreach ($this->namespaces as $name => $subspaces) {?>
	
		@namespace [data-v-namespace-*]|innerText = $name
		
		@namespace [data-bs-target]|data-bs-target = <?php echo '#namespace-' . $i;?>
		@namespace .collapse|id = <?php echo 'namespace-' . $i++;?>

		@subspace|deleteAllButFirstChild
		@subspace|before = <?php
		if(isset($subspaces) && is_array($subspaces)) 
		{
			foreach ($subspaces as $name => $subspace) {?>
			
				@subspace [data-v-subspace-name] = $name
				@subspace input[data-v-subspace-*]|value = $subspace

			@subspace|after = <?php 
			} 
		}?>

	@namespace|after = <?php 
	} 
}?>