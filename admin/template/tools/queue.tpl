import(common.tpl)

/* Queue monitoring page — provider settings, worker commands, stats, failed jobs */

[data-v-queue-worker-cli] = $this->worker_cli
[data-v-queue-drain-cli] = $this->drain_cli
[data-v-cron-url] = $this->cron_url

[data-v-failed-count] = $this->failedCount

/* ── Provider settings form ── */
#queue-settings-form|before = <?php
use function Vvveb\__;

$config    = $this->config ?? [];
$providers = $this->providers ?? [];
$provider  = $this->provider ?? 'database';
?>

#queue-settings-form option[value="database"]|addNewAttribute = <?php echo ($provider === 'database') ? 'selected' : '';?>
#queue-settings-form option[value="redis"]|addNewAttribute = <?php echo ($provider === 'redis') ? 'selected' : '';?>

[data-v-queue-provider-note]|innerText = <?php
	$currentProvider = $providers[$provider] ?? [];
	echo ($currentProvider['note'] ?? '') . (empty($currentProvider['available']) ? ' (' . __('phpredis extension missing!') . ')' : '');
?>

[data-v-queue-driver]|innerText = <?php echo __('Inherited from site database connection:') . ' <strong>' . ($this->driverLabel ?? '') . '</strong>';?>

#q-max-attempts|value = <?php echo (int) ($config['max_attempts'] ?? 3);?>
#q-base-delay|value = <?php echo (int) ($config['retry']['base_delay_seconds'] ?? 30);?>
#q-max-delay|value = <?php echo (int) ($config['retry']['max_delay_seconds'] ?? 3600);?>
#q-reserved-timeout|value = <?php echo (int) ($config['reserved_timeout_seconds'] ?? 300);?>

#cron-pass-enabled|addNewAttribute = <?php echo ! empty($config['cron_pass']['enabled']) ? 'checked' : '';?>

/* ── Queues stats table ── */
@queues = [data-v-component-queues]

@queues tr[data-v-queue-stat]|before = <?php
$queueStats = $this->queueStats ?? [];

foreach ($queueStats as $queueName => $stats) {
	$pending = (int) ($stats['ready'] ?? 0) + (int) ($stats['delayed'] ?? 0);
?>

@queues [data-v-queue-name]|innerText = <?php echo $queueName;?>
@queues [data-v-queue-ready]|innerText = <?php echo (int) ($stats['ready'] ?? 0);?>
@queues [data-v-queue-delayed]|innerText = <?php echo (int) ($stats['delayed'] ?? 0);?>
@queues [data-v-queue-reserved]|innerText = <?php echo (int) ($stats['reserved'] ?? 0);?>
@queues [data-v-queue-size]|innerText = <?php echo $pending + (int) ($stats['reserved'] ?? 0);?>

@queues tr[data-v-queue-stat]|after = <?php } ?>

@queues tr[data-v-no-queues]|style = <?php echo count($queueStats ?? []) ? 'display:none' : '';?>

/* ── Failed jobs table ── */
@failed = [data-v-component-failed-jobs]

@failed tr[data-v-failed-job]|before = <?php
$failedJobs = $this->failedJobs ?? [];
?>

@failed tr[data-v-failed-job]|before = <?php foreach ($failedJobs as $failedJob) { ?>

@failed [data-v-failed-job-job]|innerText = <?php echo $failedJob['job'];?>
@failed [data-v-failed-job-id]|innerText = <?php echo $failedJob['id'];?>
@failed [data-v-failed-job-queue]|innerText = <?php echo $failedJob['queue'];?>
@failed [data-v-failed-job-error]|innerText = <?php echo mb_substr($failedJob['error'] ?? '', 0, 200);?>
@failed [data-v-failed-job-attempts]|innerText = <?php echo (int) ($failedJob['attempts'] ?? 0) . '/' . (int) ($failedJob['max_attempts'] ?? 0);?>
@failed [data-v-failed-job-failed_at]|innerText = <?php echo date('Y-m-d H:i:s', (int) ($failedJob['failed_at'] ?? time()));?>

@failed [data-v-failed-job-id-value]|value = <?php echo $failedJob['id'];?>
@failed [data-v-failed-job-id-value-delete]|value = <?php echo $failedJob['id'];?>

@failed tr[data-v-failed-job]|after = <?php } ?>

@failed tr[data-v-no-failed-jobs]|style = <?php echo count($failedJobs ?? []) ? 'display:none' : '';?>
