@news = [data-v-component-news] [data-v-news]

@news|deleteAllButFirstChild

[data-v-component-news]|prepend = <?php
if (isset($_news_idx)) $_news_idx++; else $_news_idx = 0;

$news_list = [];
$count = 0;
if(isset($this->_component['news']) && is_array($this->_component['news'][$_news_idx]['news'])) 
{
	$news_list = $this->_component['news'][$_news_idx];
	$count = $news_list['count'] ?? 0;
}

//$_pagination_count = $this->news[$_news_idx]['count'];
//$_pagination_limit = $this->news[$_news_idx]['limit'];
?>

[data-v-component-news] [data-v-news-*]|innerText = $news['@@__data-v-news-(*)__@@']

@news|before = <?php
if($news_list) {
	$index = 0;
	foreach ($news_list['news'] as $index => $news) {?>
	
	@news [data-v-news-*]|innerText = $news['@@__data-v-news-(*)__@@']
	@news [data-v-news-*]|title = $news['@@__data-v-news-(*)__@@']
    
    @news a[data-v-news-*]|href = <?php echo /*$news_list['domain'] . */$news['@@__data-v-news-(*)__@@'];?>
	
	@news|after = <?php 
		$index++;
	} 
}
?>
