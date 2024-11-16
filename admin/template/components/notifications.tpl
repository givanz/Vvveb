@notifications = [data-v-component-notifications]

[data-v-component-notifications]|prepend = <?php
if (isset($_notifications_idx)) $_notifications_idx++; else $_notifications_idx = 0;

$notificationComponent = $this->_component['notifications'][$_notifications_idx] ?? [];

$notifications = $notificationComponent['notifications'] ?? [];
$count = $notificationComponent['count'] ?? 0;
?>


@notifications [data-v-group]|deleteAllButFirstChild

@notifications [data-v-group]|before = <?php 
	foreach ($notifications as $name => $group) { 
		//don't include updates, they have custom display
		if ($name == 'updates') continue;
?>

	@notifications [data-v-group-name] = <?php if ($name) echo Vvveb\__(ucfirst($name));?>
	
	@notifications [data-v-group-notification]|deleteAllButFirstChild

	@notifications [data-v-group-notification]|before = <?php 
		foreach ($group as $key => $notification) { $notification['name'] = $key;
	?>


		@notifications a[data-v-group-notification-*]|href = $notification['@@__data-v-group-notification-(*)__@@']
		@notifications [data-v-group-notification-*]|innerText = <?php 
			$name = '@@__data-v-group-notification-(*)__@@';
			if (isset($notification[$name]) && $notification[$name]) echo Vvveb\humanReadable(Vvveb\__(ucfirst($notification[$name])));
		?>


	@notifications [data-v-group-notification-count]|addClass = <?php if (isset($notification['badge'])) echo $notification['badge'];?>
	@notifications [data-v-group-icon]|class  = $notification['icon']
	
	
	@notifications [data-v-group-notification]|after = <?php 
		}
	?>
	
@notifications [data-v-group]|after = <?php 
	}
?>


@notifications [data-v-notification-*] = <?php 
$name = '@@__data-v-notification-(*)__@@';
$default = '@@__innerHtml__@@';
if ($name == 'count') {
	echo $count;
} else
if (isset($notifications) && $name) {
	echo \Vvveb\arrayPath($notifications, $name, '','-');
} else { 
	echo $default;
}	
?>
