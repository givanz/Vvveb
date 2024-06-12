@state = [data-v-component-states] [data-v-state]
@state|deleteAllButFirstChild

[data-v-component-states]|prepend = <?php
if (isset($_states_idx)) $_states_idx++; else $_states_idx = 0;
if(isset($this->_component['states']) && $this->_component['states'][$_states_idx]) {
	$states = $this->_component['states'][$_states_idx];
?>


// [data-v-component-states] [data-v-state-info-*] = $states['@@__data-v-state-info-(*)__@@']

[data-v-component-states] [data-v-state-info-active_icon]|addClass = <?php echo $states['active_icon'];?>
[data-v-component-states] [data-v-state-info-active_name] = $states['active_name']

@state|before = <?php
	$elements = $states['states'] ?? [];
	
	if (is_array($elements)) {
		foreach ($elements as $index => $state) {?>
		
		@state .dropdown-item|addClass = <?php if (isset($state['active']) && $state['active']) echo 'active'?>
		@state [data-v-state-icon]|addClass = <?php if (isset($state['icon'])) echo $state['icon'];?>
		
		@state [data-v-state-name] = $state['name']
		@state button|data-state = $state['name']
		@state button|value = $index
		@state [data-v-state-url]|href = <?php echo '//' . $state['url'];?>
		
		@state button[data-v-state-state_id]|value = $state['id']
		
		@state|after = <?php 
		} 
	}
?>


[data-v-component-states]|append = <?php 
}
?>