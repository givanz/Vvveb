import(crud.tpl, {"type":"user_group"})

[data-v-user_group] select[data-v-user_group-*] option|addNewAttribute = <?php if (isset($this->user_group) && $this->user_group['status'] == '@@__value__@@') echo 'selected';?>
