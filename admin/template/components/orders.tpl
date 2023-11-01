@order = [data-v-component-orders] [data-v-order]
@order|deleteAllButFirstChild

[data-v-component-orders]|prepend = <?php
if (isset($_orders_idx)) $_orders_idx++; else $_orders_idx = 0;

$orders = [];
$count = 0;
if(isset($this->_component['orders']) && is_array($this->_component['orders'][$_orders_idx]['order'])) 
{
	$orders = $this->_component['orders'][$_orders_idx];
	$count = $orders['count'] ?? 0;
}

//$_pagination_count = $this->orders[$_orders_idx]['count'];
//$_pagination_limit = $this->orders[$_orders_idx]['limit'];
?>

[data-v-component-orders] [data-v-orders-*]|innerText = $orders['@@__data-v-orders-(*)__@@']

@order|before = <?php
if($orders) {
	//$pagination = $this->orders[$_orders_idx]['pagination'];
	$index = 0;
	foreach ($orders['order'] as $index => $order) {?>
	
	@order [data-v-order-*]|innerText = $order['@@__data-v-order-(*)__@@']
	@order [data-v-order-*]|title = $order['@@__data-v-order-(*)__@@']
    
    @order [data-v-order-url]|href = <?php echo Vvveb\url(['module' => 'order/order', 'order_id' => $order['order_id']]);?>
	
	@order|after = <?php 
		$index++;
	} 
}
?>


