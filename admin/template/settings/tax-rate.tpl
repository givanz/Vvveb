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

form[data-v-tax_rate]|append = <?php
if (! empty($this->user_groups)) {
	echo '<div class="mb-3 row"><label class="col-sm-2 col-form-label">' . htmlspecialchars($this->user_groups_label ?? 'Customer groups') . '</label><div class="col-sm-10">';
	echo '<div class="form-text mb-2">' . htmlspecialchars($this->user_groups_hint ?? '') . '</div>';
	$selected = $this->rate_user_groups ?? [];
	foreach ($this->user_groups as $user_group) {
		$id      = (int)($user_group['user_group_id'] ?? 0);
		$name    = htmlspecialchars($user_group['name'] ?? ('#' . $id));
		$checked = in_array($id, $selected) ? ' checked' : '';
		echo '<div class="form-check"><input class="form-check-input" type="checkbox" name="user_group[]" value="' . $id . '"' . $checked . '><label class="form-check-label">' . $name . '</label></div>';
	}
	echo '</div></div>';
}
?>
