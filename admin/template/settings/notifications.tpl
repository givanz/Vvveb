import(common.tpl)

[data-v-notif-category]|deleteAllButFirst

[data-v-notif-category]|before = <?php
$categoryLabels = $this->categories ?? [];
foreach (($this->groupedEvents ?? []) as $cat => $catEvents) { ?>

[data-v-category-name] = <?php echo $categoryLabels[$cat] ?? ucfirst($cat); ?>

[data-v-event-row]|deleteAllButFirst

[data-v-event-row]|before = <?php
	$webhookNames = array_values((array) ($this->webhookNames ?? []));
	foreach ($catEvents as $evt) {
		$eventName     = $evt['event'];
		$cfg           = ($this->events ?? [])[$eventName] ?? [];
		$evtProviders  = $cfg['providers'] ?? ['email'];
		$evtRecipients = $cfg['recipients'] ?? ($evt['default_recipients'] ?? ['site_admin']);
		$extraEmails   = implode(', ', $cfg['additional_emails'] ?? []);
		$extraPhones   = implode(', ', $cfg['additional_phones'] ?? []);
		$evtWebhooks   = $cfg['additional_webhooks'] ?? [];
?>

[data-v-event-name] = <?php echo $eventName; ?>

[data-v-event-desc] = <?php echo htmlspecialchars($evt['description']); ?>


/* recipient checkboxes */
[data-v-recipient-check]|deleteAllButFirst

[data-v-recipient-check]|before = <?php
		foreach (['site_admin' => 'Site admin', 'user' => 'User (subject)', 'admin_user' => 'Admin user'] as $rt => $rl) { ?>

[data-v-recipient-check] .recipient-check-input|value = <?php echo $rt; ?>

[data-v-recipient-check] .recipient-check-input|name = <?php echo 'settings[events][' . $eventName . '][recipients][]';?>

[data-v-recipient-check] .recipient-check-input|addNewAttribute = <?php if (in_array($rt, $evtRecipients)) echo 'checked';?>

[data-v-recipient-label] = <?php echo $rl; ?>

[data-v-recipient-check]|after = <?php } ?>


/* provider checkboxes */
[data-v-provider-check]|deleteAllButFirst

[data-v-provider-check]|before = <?php
		foreach (($this->providerSlugs ?? []) as $ps) { ?>

[data-v-provider-check] .provider-check-input|value = <?php echo $ps; ?>

[data-v-provider-check] .provider-check-input|name = <?php echo 'settings[events][' . $eventName . '][providers][]';?>

[data-v-provider-check] .provider-check-input|addNewAttribute = <?php if (in_array($ps, $evtProviders)) echo 'checked';?>

[data-v-provider-label] = <?php echo ucfirst($ps); ?>

[data-v-provider-check]|after = <?php } ?>


/* extra emails / phones */
.extra-emails|name = <?php echo 'settings[events][' . $eventName . '][additional_emails]';?>

.extra-emails|value = <?php echo htmlspecialchars($extraEmails); ?>

.extra-phones|name = <?php echo 'settings[events][' . $eventName . '][additional_phones]';?>

.extra-phones|value = <?php echo htmlspecialchars($extraPhones); ?>


/* named webhook checkboxes */
[data-v-webhook-check]|deleteAllButFirst

[data-v-webhook-check]|before = <?php
		if (! $webhookNames) {
			echo '<!-- no webhooks defined -->';
		}
		foreach ($webhookNames as $whName) { ?>

[data-v-webhook-check] .webhook-check-input|value = <?php echo $whName; ?>

[data-v-webhook-check] .webhook-check-input|name = <?php echo 'settings[events][' . $eventName . '][additional_webhooks][]';?>

[data-v-webhook-check] .webhook-check-input|addNewAttribute = <?php if (in_array($whName, $evtWebhooks)) echo 'checked';?>

[data-v-webhook-label] = <?php echo $whName; ?>

[data-v-webhook-check]|after = <?php } ?>

[data-v-no-webhooks]|addNewAttribute = <?php if ($webhookNames) echo 'style="display:none"';?>

[data-v-event-row]|after = <?php } ?>

[data-v-notif-category]|after = <?php } ?>


[data-v-provider-card]|deleteAllButFirst

[data-v-provider-card]|before = <?php
$providersCfg      = $this->providers ?? [];
$providerFieldsAll = $this->providerFields ?? [];

foreach (($this->providerSlugs ?? []) as $slug) {
	$provConfig = $providersCfg[$slug] ?? [];
	$fields     = $providerFieldsAll[$slug] ?? [];
	$isEnabled  = $provConfig['enabled'] ?? false;
?>

[data-v-provider-name] = <?php echo ucfirst($slug); ?>

[data-v-provider-card] .provider-enabled-input|name = <?php echo 'settings[providers][' . $slug . '][enabled]';?>

[data-v-provider-card] .provider-enabled-input|addNewAttribute = <?php if ($isEnabled) echo 'checked';?>


/* generic settings fields (skip 'list' type — rendered by dedicated editors) */
[data-v-provider-field]|deleteAllButFirst

[data-v-provider-field]|before = <?php
	foreach ($fields as $field) {
		if (($field['type'] ?? '') === 'list') continue;
		$type  = $field['type'] ?? 'text';
		$name  = $field['name'] ?? '';
		$val   = $provConfig[$name] ?? '';
		$fname = 'settings[providers][' . $slug . '][' . $name . ']';
?>

[data-v-field-label] = <?php echo htmlspecialchars($field['label'] ?? $name); ?>

[data-v-provider-field] .field-input|outerHTML = <?php
		if ($type === 'textarea') {
			echo '<textarea class="form-control field-input" name="' . htmlspecialchars($fname) . '" rows="2"' . (!empty($field['placeholder']) ? ' placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '') . '>' . htmlspecialchars((string) $val) . '</textarea>';
		} else {
			$htmlType = in_array($type, ['password', 'url', 'number'], true) ? $type : 'text';
			echo '<input type="' . $htmlType . '" class="form-control field-input" name="' . htmlspecialchars($fname) . '" value="' . htmlspecialchars((string) $val) . '"' . (!empty($field['placeholder']) ? ' placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '') . '>';
		}
?>

[data-v-field-desc] = <?php echo htmlspecialchars($field['description'] ?? ''); ?>

[data-v-provider-field]|after = <?php } ?>


/* webhooks editor only applies to the webhooks provider card */
[data-v-webhooks-editor]|addNewAttribute = <?php if ($slug !== 'webhooks') echo 'style="display:none"';?>


[data-v-webhook-row]|deleteAllButFirst

[data-v-webhook-row]|before = <?php
	if ($slug === 'webhooks') {
		foreach ($provConfig['webhooks'] ?? [] as $wi => $wh) {
			$wbase = 'settings[providers][webhooks][webhooks][' . $wi . ']';
?>

.wh-enabled|name = <?php echo $wbase . '[enabled]';?>

.wh-enabled|addNewAttribute = <?php if (! empty($wh['enabled'])) echo 'checked';?>

.wh-name|name = <?php echo $wbase . '[name]';?>

.wh-name|value = <?php echo htmlspecialchars($wh['name'] ?? ''); ?>

.wh-url|name = <?php echo $wbase . '[url]';?>

.wh-url|value = <?php echo htmlspecialchars($wh['url'] ?? ''); ?>

.wh-method|name = <?php echo $wbase . '[method]';?>

.wh-method option|addNewAttribute = <?php if (($wh['method'] ?? 'POST') == '@@__value__@@') echo 'selected';?>

.wh-headers|name = <?php echo $wbase . '[headers]';?>

.wh-headers = <?php echo htmlspecialchars(is_array($wh['headers'] ?? '') ? implode("\n", array_map(fn ($k, $v) => "$k: $v", array_keys($wh['headers']), $wh['headers'])) : ($wh['headers'] ?? '')); ?>

[data-v-webhook-row]|after = <?php } } ?>

[data-v-provider-card]|after = <?php } ?>


#low-stock-threshold|value = <?php echo htmlspecialchars((string) ($this->lowStockThreshold ?? '')); ?>

#global-cooldown|value = <?php echo htmlspecialchars((string) ($this->providers['global']['cooldown'] ?? '')); ?>
