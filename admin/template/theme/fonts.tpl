import(common.tpl)
import(pagination.tpl)

@font = [data-v-builtin] [data-v-font]
@font|deleteAllButFirstChild

@variant = [data-v-builtin] [data-v-font] [data-v-variant]
@variant|deleteAllButFirstChild

@font|before = <?php
if(isset($this->builtin) && is_array($this->builtin)) {
	foreach ($this->builtin as $family => $font) {?>
	
    @font [data-v-font-family]|innerText = $family
    @font [data-v-font-*]|innerText      = $font['@@__data-v-font-([-_\w]+)__@@']

	@variant|before = <?php
	if(is_array($font['variants'])) foreach ($font['variants'] as $variant) {?>

		@variant [data-v-variant-*]|innerText = $variant['@@__data-v-variant-([-_\w]+)__@@']
		
			@variant [data-v-values]|before = <?php
			if(is_array($variant)) foreach ($variant as $key => $value) {?>
			
				@variant [data-v-values] [data-v-values-key] = $key
				@variant [data-v-values] [data-v-values-value] = $value
			
			@variant [data-v-values]|after = <?php } ?>
		
	@variant|after = <?php } ?>
	
	@font|after = <?php 
	} 
}?>

@font = [data-v-fonts] [data-v-font]
@font|deleteAllButFirstChild

@variant = [data-v-fonts] [data-v-font] [data-v-variant]
@variant|deleteAllButFirstChild

@font|before = <?php
if(isset($this->fonts) && is_array($this->fonts)) {
	$i = 0;
	foreach ($this->fonts as $font) { $i++;?>
	
    @font [data-v-*]|innerText = $font['@@__data-v-([-_\w]+)__@@']
    @font [data-v-*]|name = <?php echo str_replace('[0]', "[$i]", '@@__name__@@');?>
	@font .accordion-button|data-bs-target = <?php echo '#font-' . $i?>;
	@font .accordion-collapse|id = <?php echo 'font-' . $i?>;
	
	@font [data-v-src]|id = <?php echo "font-$i-src"?>;
	@font [data-media-gallery]|data-target-input = <?php echo "#font-$i-src"?>;

	@font|after = <?php 
	} 
}?>


[data-v-theme-name] = $this->themename
