import(listing.tpl, {"type":"{{type}}", "list": "{{list}}"})

@{{type}}|addClass = <?php if (${{type}}['status'] == 0) echo 'pending';if (${{type}}['status'] == 2) echo 'spam';if (${{type}}['status'] == 3) echo 'trash';?>

@status = [data-v-{{type}}-status] [data-v-status]
@status|deleteAllButFirstChild

@status|before = <?php
if(isset($this->{{type}}_status) && is_array($this->{{type}}_status))  {
foreach ($this->{{type}}_status as $type => $status) {?>	
	
	@status [data-v-status-link] = $status
	@status [data-v-status-link]|addClass = <?php if (isset($this->status) && $type == $this->status) echo 'active';?>
	@status [data-v-status-link]|href = <?php echo htmlentities(Vvveb\url(['module' => $this->module . str_replace('_','-','/{{list}}'), 'status' => $type]));?>
	
@status|after = <?php } 
}?>

	
