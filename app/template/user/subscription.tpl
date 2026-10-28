import(common.tpl)

[data-v-subscription-*]|innerText = $this->subscription['@@__data-v-subscription-(*)__@@']

//action buttons — only show relevant actions based on current status
form[data-v-action-cancel]|action = <?php echo Vvveb\url('user/subscription/cancel', ['subscription_id' => $this->subscription['subscription_id'] ?? 0]);?>
form[data-v-action-swap]|action = <?php echo Vvveb\url('user/subscription/swap', ['subscription_id' => $this->subscription['subscription_id'] ?? 0]);?>
form[data-v-action-skip]|action = <?php echo Vvveb\url('user/subscription/skip', ['subscription_id' => $this->subscription['subscription_id'] ?? 0]);?>

//swap plan selector
@plan = [data-v-plans] option[data-v-plan]
@plan|deleteAllButFirstChild

@plan|before = <?php
$plans = $this->plans ?? [];
$currentPlan = $this->subscription['subscription_plan_id'] ?? 0;
if (is_array($plans)) foreach ($plans as $index => $plan) {?>

	@plan|innerText = $plan['name']
	@plan|value = $plan['subscription_plan_id']
	@plan|addNewAttribute = <?php if (($plan['subscription_plan_id'] ?? 0) == $currentPlan) echo 'selected';?>

@plan|after = <?php }?>
form[data-v-action-pause]|action  = <?php echo Vvveb\url('user/subscription/pause', ['subscription_id' => $this->subscription['subscription_id'] ?? 0]);?>
form[data-v-action-resume]|action = <?php echo Vvveb\url('user/subscription/resume', ['subscription_id' => $this->subscription['subscription_id'] ?? 0]);?>

//action button visibility is handled in markup via data-v-if conditions

//status badge class
[data-v-subscription-status_name]|addClass = <?php
$statusId = $this->subscription['subscription_status_id'] ?? 0;
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

//subscription logs/history
@subscription_logs = [data-v-subscription-logs] [data-v-subscription-log]
@subscription_logs|deleteAllButFirstChild

@subscription_logs|before = <?php
if (isset($this->subscription_logs) && is_array($this->subscription_logs)) {
	foreach ($this->subscription_logs as $log) { ?>

	@subscription_logs [data-v-subscription-log-*]|innerText = $log['@@__data-v-subscription-log-(*)__@@']

	@subscription_logs|after = <?php
	}
} ?>

