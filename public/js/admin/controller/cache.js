class CacheController {

	clear(e, element) {
		element = element ?? e.currentTarget;
		let url = element?.getAttribute('href');

		//fallback for pages where the href was not generated from data-v-url-params
		if ((!url || url === '#') && element?.dataset.vUrlParams) {
			try {
				url = window.location.pathname + '?' + new URLSearchParams(JSON.parse(element.dataset.vUrlParams)).toString();
			} catch (error) {
				console.error("Invalid cache action url", error);
			}
		}

		if (!url || url === '#') {
			displayToast("danger", "Cache", "Invalid cache action!", "top");
			e.preventDefault();
			return false;
		}

		fetch(url, {
			method: "POST",
			headers: {
				"X-Requested-With": "XMLHttpRequest",
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: new URLSearchParams({csrf: document.querySelector('[name=csrf]')?.value ?? ""})
		})
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.text()
		})
		.then((data) => {
			let response = new DOMParser().parseFromString(data, "text/html");
			let notification = response.querySelector(".notifications .alert");
			let message = notification?.querySelector("[data-v-notification-text]")?.textContent?.trim();
			let success = !notification || notification.classList.contains("alert-success");

			displayToast(success ? "success" : "danger", "Cache", message || (success ? "Cache cleared" : "Error clearing cache"), "top");
		})
		.catch(error => {
			displayToast("danger", "Cache", "Error clearing cache", "top");
			console.error(error);
		});			

		e.preventDefault();
		return false;
	}
	
}

let Cache = new CacheController();
export {Cache};
