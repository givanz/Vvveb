var prevSearchValue;

class SearchController {

	autocomplete(e, element) {
		let url = element.href;

		if (element.value) {
			let loading = this.form.querySelector(".loading");
			if (loading && prevSearchValue != element.value) {
				loading.classList.remove('d-none');
				document.querySelector(".search-results")?.replaceChildren();
			}
			
			prevSearchValue = element.value;

			delay(() => loadAjax(window.location.pathname + location.search, ".search-results", function(data) { 
				if (loading) {
					loading.classList.add('d-none');
				}
			}, {'module':'tools/search', search:element.value}), 1000);
		} else {
			document.querySelector(".search-results")?.replaceChildren();
		}
	}
	
}

let Search = new SearchController();
export {Search};
