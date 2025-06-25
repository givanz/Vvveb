/*
 * Keep session open to avoid seeing login screen if not active for a few minutes, if session still expires login modal is shown.
*/

let HeartBeat = setInterval(function () {
	fetch(window.location.pathname + '?action=heartbeat')
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.text()
		})	
		.then(sessionStatus => {
			if (sessionStatus != "ok") {
				if (!document.getElementById("login")) {
					fetch(window.location.pathname + '?module=user/login&modal=true')
					.then(response => {
						if (!response.ok) { throw new Error(response) }
						return response.text();
					})
					.then(formHtml => {
						document.querySelector("#heartBeatLogin .modal-body").innerHTML = formHtml;
						let login = document.getElementById('heartBeatLogin');
						const heartBeatLogin = bootstrap.Modal.getOrCreateInstance(login);
						heartBeatLogin.style.display = "";
						login.addEventListener("submit", function(e) {
							let form = e.target.closest("form");
							if (form) {
								//$("#heartBeatLogin [data-v-notifications]").remove();

								document.querySelector('.btn-login .loading').classList.remove('d-none');
								document.querySelector('.btn-login .button-text').classList.add('d-none');
										
								fetch(window.location.pathname + '?module=user/login&modal=true', { method: "POST",			
									headers: {
									  //"Content-Type": "application/json",
									  'Content-Type': 'application/x-www-form-urlencoded',
									},
									body: new URLSearchParams(new FormData(form))})
								.then((response) => {
									if (!response.ok) { throw new Error(response) }
									return response.text()
								})
								.then(data => {
									//if no error message shown then hide login modal
									if (data.indexOf("data-v-notification-error") == -1) {
										heartBeatLogin.style.display = "none";
									} else {
										//if there are errors show login form with error message
										document.querySelector("#heartBeatLogin .modal-body").innerHTML = data;
									}

									document.querySelector('.btn-login .loading').classList.add('d-none');
									document.querySelector('.btn-login .button-text').classList.remove('d-none');
								})
								.catch(error => {
									document.querySelector('.btn-login .loading').classList.add('d-none');
									document.querySelector('.btn-login .button-text').classList.remove('d-none');
									console.log(error.statusText);
									displayToast("danger", "Login", "Error!");
								});
								
								
								e.preventDefault();
								return false;
							}
						});
					}).catch(error => {
						console.log(error.statusText);
					});					
				}
			}
		}).catch(error => {
			console.log(error.statusText);
		});
}, 3 * 60 * 1000);//3 minutes

export {HeartBeat};
