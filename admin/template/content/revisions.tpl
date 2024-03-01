import(common.tpl)

[data-v-post-*] = $this->post['@@__data-v-post-(*)__@@']
img[data-v-post-*]|src = $this->post['@@__data-v-post-(*)__@@']
[data-v-revision-*] = $this->revision['@@__data-v-revision-(*)__@@']

@revision = [data-v-revisions-list] [data-v-option]

@revision|deleteAllButFirstChild

@revision|before = <?php 
foreach ($this->revisions as $revision) {?>
	@revision                 = <?php echo Vvveb\friendlyDate($revision['created_at']) . ' | ' . $revision['created_at'] . ' | ' .$revision['display_name'];?>
	@revision|value           = $revision['created_at'] 
	@revision|addNewAttribute = <?php if (isset($this->options['created_at']) && ($this->options['created_at'] == $revision['created_at'])) echo 'selected';?> 
	
@revision|after = <?php 
	} 
?>

[data-v-type_name_plural]    = $this->type_name_plural
[data-v-type-name]           = $this->type_name
[data-v-type]                = $this->type
