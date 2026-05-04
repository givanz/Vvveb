@page = [data-pagination] [data-page]
@page|deleteAllButFirstChild

[data-pagination]|before = <?php $maxpages = 5; $visible_pages = 3; 

$parent_component = '@@__data-v-parent-component__@@';
$parent_index = '@@__data-v-parent-index__@@';
$query_string = '@@__data-v-query-string__@@';
$parameters = [];//@@__data-v-parameters__@@;

if ($query_string) {
	$query_string = ($_SERVER['QUERY_STRING'] ?? '');
	$query_string = $query_string ? "?$query_string" : '';
}

if ($parent_component) {
	$component = $this->_component[$parent_component][$parent_index];	
}

if(isset($component['count'])) {
	
if (isset($component['limit'])) $limit = $component['limit']; else $limit = 10;
	
$pagecount = ceil($component['count'] / $limit);

$page = 1;
$page_stop = $pagecount;
$url = '@@__data-v-url__@@';

if (empty($url)) {
	$url = Vvveb\System\Core\FrontController :: getModule();
	if (is_array($parameters)) {
		$parameters += Vvveb\System\Core\Request :: getInstance()->get;
	} else {
		$parameters = Vvveb\System\Core\Request :: getInstance()->get;
	}
}

if (isset($_GET['page'])) {
	$current_page = (int) $_GET['page']; 
} else  if (isset($this->current_page)) {
	$current_page = $this->current_page; 
} else {
	$current_page = 1;
}

$current_page = max($current_page, 1);

if ($pagecount > $maxpages)
{
	if ($current_page > $visible_pages)
	{
		if (($current_page + $visible_pages) > $pagecount)
		{
			$page = $pagecount - $visible_pages - 1;
			$page_stop = $pagecount;
		} else 
		{
			$page = $current_page - $visible_pages;
			$page_stop = $current_page + $visible_pages;
		}
	} else
	{
		$page = 1;
		$page_stop = $maxpages;
	}
}

if (!function_exists('pageUrl')) {
	function pageUrl($page, &$parameters, &$query_string, &$url) {
		if ($page > 1) {
			return htmlspecialchars(Vvveb\url($url, ['page' => $page] + $parameters)) . $query_string;
		} else {
			unset($parameters['page']);
			return htmlspecialchars(Vvveb\url($url, $parameters)) . $query_string;
		}
	}
}
?>

@page|before = <?php  
	for (;$page <= $page_stop;$page++) {
?>

	[data-pagination] [data-pages] = $pagecount
	
	@page [data-page-no] = $page
	@page [data-page-url]|href = <?php echo pageUrl($page, $parameters, $query_string, $url);?>
	@page|addClass = <?php if (($current_page == $page) && !$vvveb_is_page_edit) echo 'active'?>

@page|after = <?php 
	} 
?>

	[data-pagination] [data-count] = $component['count']
	[data-pagination] [data-current-page] = $current_page
	[data-pagination] [data-current-url]|action = <?php echo pageUrl($page, $parameters, $query_string, $url);?>
	
	[data-pagination] [data-first] [data-page-url]|href = <?php echo pageUrl(1, $parameters, $query_string, $url);?>
	[data-pagination] [data-prev]  [data-page-url]|href = <?php echo pageUrl(max($current_page - 1, 1), $parameters, $query_string, $url);?>
	[data-pagination] [data-next]  [data-page-url]|href = <?php echo pageUrl(min($current_page + 1, $pagecount), $parameters, $query_string, $url);?>
	[data-pagination] [data-last]  [data-page-url]|href = <?php echo pageUrl($pagecount, $parameters, $query_string, $url);?>


[data-pagination]|after = <?php 
	} 
?>
