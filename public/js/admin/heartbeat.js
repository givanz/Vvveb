/*
 * Keep session open to avoid seeing login screen if not active for a few minutes, if session still expires login modal is shown.
 */

function bindHeartbeatLogin(modalEl) {
	if (modalEl.dataset.loginBound) {
		return;
	}

	modalEl.dataset.loginBound = "1";

	modalEl.addEventListener("submit", function (e) {
		let form = e.target.closest("form");
		if (!form) {
			return;
		}

		e.preventDefault();

		let loginButton = modalEl.querySelector('.btn-login');
		let loading = loginButton?.querySelector('.loading');
		let text = loginButton?.querySelector('.button-text');

		if (loading) loading.classList.remove('d-none');
		if (text) text.classList.add('d-none');

		fetch(window.location.pathname + '?module=user/login&modal=true', {
			method: "POST",
			headers: {
				"X-Requested-With": "XMLHttpRequest",
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: new URLSearchParams(new FormData(form))
		})
		.then((response) => {
			if (!response.ok) {
				throw new Error(response.statusText || response.status);
			}
			return response.text();
		})
		.then(data => {
			//if no error message shown then hide login modal
			if (data.indexOf("data-v-notification-error") == -1) {
				bootstrap.Modal.getOrCreateInstance(modalEl).hide();
			} else {
				//if there are errors show login form with error message
				modalEl.querySelector(".modal-body").innerHTML = data;
			}
		})
		.catch(error => {
			console.error(error);
			if (typeof displayToast === "function") {
				displayToast("danger", "Login", "Error!");
			}
		})
		.finally(() => {
			if (loading) loading.classList.add('d-none');
			if (text) text.classList.remove('d-none');
		});

		return false;
	});
}

function heartbeatLogin(modalEl) {
	let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
	let body = modalEl.querySelector(".modal-body");

	if (!body) {
		return;
	}

	//the form is already loaded, simply show the modal again
	if (body.querySelector("form")) {
		modal.show();
		return;
	}

	fetch(window.location.pathname + '?module=user/login&modal=true')
	.then((response) => {
		if (!response.ok) {
			throw new Error(response.statusText || response.status);
		}
		return response.text();
	})
	.then(formHtml => {
		body.innerHTML = formHtml;
		bindHeartbeatLogin(modalEl);
		modal.show();
	})
	.catch(error => console.error(error));
}

let HeartBeat = setInterval(function () {
	fetch(window.location.pathname + '?action=heartbeat')
		.then((response) => {
			if (!response.ok) {
				throw new Error(response.statusText || response.status);
			}
			return response.text();
		})
		.then(sessionStatus => {
			if (sessionStatus.trim() === "ok") {
				return;
			}

			let modalEl = document.getElementById("heartBeatLogin");
			if (modalEl) {
				heartbeatLogin(modalEl);
			}
		})
		.catch(error => console.error(error));
}, 3 * 60 * 1000);//3 minutes

export {HeartBeat};
