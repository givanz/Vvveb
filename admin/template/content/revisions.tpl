import(common.tpl)

[data-v-post-*] = $this->post['@@__data-v-post-(*)__@@']
img[data-v-post-*]|src = $this->post['@@__data-v-post-(*)__@@']
[data-v-revision-*] = $this->revision['@@__data-v-revision-(*)__@@']

@revision = [data-v-revisions-list] [data-v-option]

@revision|deleteAllButFirstChild

@revision|before = <?php 
foreach ($this->revisions as $revision) {?>
	@revision|value = $revision['created_at'] 
	@revision       = <?php echo Vvveb\friendlyDate($revision['created_at']) . ' | ' . $revision['created_at'] . ' | ' .$revision['display_name'];?>
	
@revision|after = <?php 
	} 
?>
