@stat = [data-v-component-stats] [data-v-stat]
@stat|deleteAllButFirstChild

[data-v-component-stats]|prepend = <?php
if (isset($_stats_idx)) $_stats_idx++; else $_stats_idx = 0;

$stats = [];

if(isset($this->_component['stats']) && is_array($this->_component['stats'][$_stats_idx])) 
{
	$stats = $this->_component['stats'][$_stats_idx];
}

//$_pagination_count = $this->stats[$_stats_idx]['count'];
//$_pagination_limit = $this->stats[$_stats_idx]['limit'];
?>

[data-v-component-stats] [data-v-stats-*]|innerText = $stats['@@__data-v-stats-(*)__@@']

@stat|before = <?php
if($stats) {
	//$pagination = $this->stats[$_stats_idx]['pagination'];
	foreach ($stats['stats'] as $index => $stat){?>
	
	@stat [data-v-stat-*]|innerText = $stat['@@__data-v-stat-(*)__@@']
    
    @stat [data-v-stat-url]|href = <?php echo Vvveb\url(['module' => 'stat/stat', 'stat_id' => $stat['stat_id']]);?>
	
	@stat|after = <?php 
	} 
}
?>


