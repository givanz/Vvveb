class CacheController {

	clear(e, element) {
		let url = element.href;

		fetch(url)
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.text()
		})
		.then((data) => {
			let response = new DOMParser().parseFromString(data, "text/html");
			let message = response.querySelector(".notifications .alert [data-v-notification-text]")?.innerHTML;
			displayToast("success", "Cache", message, "top");
		})
		.catch(error => {
			displayToast("danger", "Cache", "Error", "top");
			//displayToast("danger", "Error", "Error saving!");
		});			

		e.preventDefault();
		return false;
	}
	
}

let Cache = new CacheController();
export {Cache};
