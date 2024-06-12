import(common.tpl)
import(pagination.tpl)

[data-v-save-url]|href = $this->saveUrl
[data-v-domains-url]|href = $this->domainsUrl

@translation = [data-v-translations] tbody tr
@translation|deleteAllButFirstChild

@translation|before = <?php
$count = 0;
if(isset($this->translations) && is_array($this->translations)) {
	foreach ($this->translations as $key => $translation) {
		if (isset($translation['msgstr'])) {?>
	
			@translation td:nth(1)|innerText  = $key
			@translation td:nth(2)|innerText  = $translation['msgstr'][0]
			
			@translation|after = <?php 
		$count++;
		} 
	} 
}?>
