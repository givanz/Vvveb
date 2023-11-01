import(crud.tpl, {"type":"user"})

[data-v-user] select[data-v-user-*] option|addNewAttribute = <?php if (isset($this->user) && $this->user['status'] == '@@__value__@@') echo 'selected';?>
