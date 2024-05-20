class CacheController {

	clear(e) {
		let link = e.currentTarget;
		//let url = window.location.pathname + '?module=tools/cache&action=delete';
		let url = link.href;

		fetch(url)
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.text()
		})
		.then((data) => {
			let message = data.querySelector(".notifications .alert [data-v-notification-text]").innerHTML;
			displayToast("bg-success", "Cache", message, "top");
		})
		.catch(error => {
			let message = data.querySelector(".notifications .alert [data-v-notification-text]").innerHTML;
			displayToast("bg-danger", "Cache", message, "top");
			//displayToast("bg-danger", "Error", "Error saving!");
		});			

		e.preventDefault();
		return false;
	}
	
}

let Cache = new CacheController();
export {Cache};
