@ordercomp = [data-v-component-orders]
@order  = [data-v-component-orders] [data-v-order]

@order|deleteAllButFirstChild

@ordercomp|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();

if (isset($_ordercomp_idx)) $_ordercomp_idx++; else $_ordercomp_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$ordercomp = $current_component = $this->_component['orders'][$_ordercomp_idx] ?? [];

$count = $_pagination_count = $ordercomp['count'] ?? 0;
$_pagination_limit = isset($ordercomp['limit']) ? $ordercomp['limit'] : 5;	
?>


@order|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$ordercomp['order'] = empty($ordercomp['order']) ? $_default : $ordercomp['order'];

if($ordercomp && is_array($ordercomp['order'])) {
	foreach ($ordercomp['order'] as $index => $order) {?>
		
		@order|data-order_id = $order['order_id']
		
		@order|id = <?php echo 'order-' . $order['order_id'];?>
		
		@order img[data-v-order-*]|src = $order['@@__data-v-order-(*)__@@']
		
		@order [data-v-order-*]|innerText = $order['@@__data-v-order-(*)__@@']
		
		@order a[data-v-order-*]|href = $order['@@__data-v-order-(*)__@@']
	
	@order|after = <?php 
	} 
}
?>