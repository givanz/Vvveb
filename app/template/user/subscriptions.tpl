import(common.tpl)
import(pagination.tpl)

@subscriptions = [data-v-subscriptions] [data-v-subscription]
@subscriptions|deleteAllButFirstChild

@subscriptions|before = <?php
$count = 0;
if (isset($this->subscriptions) && is_array($this->subscriptions)) {
	foreach ($this->subscriptions as $index => $subscription) { ?>

	@subscriptions [data-v-subscription-subscription_id]|innerText = $subscription['subscription_id']
	@subscriptions [data-v-subscription-subscription_id]|href     = <?php echo Vvveb\url('user/subscription/index', ['subscription_id' => $subscription['subscription_id']]);?>
	@subscriptions [data-v-subscription-view]|href                 = <?php echo Vvveb\url('user/subscription/index', ['subscription_id' => $subscription['subscription_id']]);?>

	@subscriptions [data-v-subscription-*]|innerText           = $subscription['@@__data-v-subscription-(*)__@@']
	@subscriptions [data-v-subscription-product_name]|href         = <?php echo Vvveb\url('product/product/index', ['slug' => $subscription['product_slug']]);?>


	//status badge classes
	@subscriptions [data-v-subscription-status_name]|addClass = <?php
	$statusId = $subscription['subscription_status_id'] ?? 0;
	echo match((int)$statusId) {
		1 => 'badge bg-warning text-dark',
		2 => 'badge bg-success',
		3 => 'badge bg-danger',
		4 => 'badge bg-secondary',
		5 => 'badge bg-danger',
		6 => 'badge bg-dark',
		default => 'badge bg-info',
	};
	?>

	@subscriptions|after = <?php
		$count++;
	}
} ?>

