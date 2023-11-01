@addresscomp = [data-v-component-user-address]
@address  = [data-v-component-user-address] [data-v-user_address]

@address|deleteAllButFirstChild

@addresscomp|prepend = <?php
if (isset($_addresscomp_idx)) $_addresscomp_idx++; else $_addresscomp_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$addresscomp = $current_component = $this->_component['user_address'][$_addresscomp_idx] ?? [];

$count = $_pagination_count = $addresscomp['count'] ?? 0;
$_pagination_limit = isset($addresscomp['limit']) ? $addresscomp['limit'] : 5;	
$addresses = $addresscomp['user_address'] ?? [];
?>


@address|before = <?php
if($addresses) {
	foreach ($addresses as $index => $address) {?>
		
		@address|data-user_address_id = $address['user_address_id']
		
		@address|id = <?php echo 'address-' . $address['user_address_id'];?>
		
		@address [data-v-user_address-label-id]|id = <?php echo 'address_' . $address['user_address_id'];?>
		@address [data-v-user_address-label-for]|for = <?php echo 'address_' . $address['user_address_id'];?>
		
		@address img[data-v-user_address-*]|src = $address['@@__data-v-user_address-([a-zA-Z_\d]+)__@@']
		
		@address [data-v-user_address-*]|innerText = $address['@@__data-v-user_address-([a-zA-Z_\d]+)__@@']
		
		@address input[data-v-user_address-*]|value = $address['@@__data-v-user_address-([a-zA-Z_\d]+)__@@']
		
		@address a[data-v-user_address-*]|href = $address['@@__data-v-user_address-([a-zA-Z_\d]+)__@@']
	
	@address|after = <?php 
	} 
}
?>
