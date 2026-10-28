class UpdateController {

	action(url, btn) {
		let next = url;
		let progress = document.getElementById("progress");
		if (progress) progress.classList.remove("d-none");
		
		function disableBtn() {
			if (!btn) return;
			let loading = btn.querySelector('.loading');
			let text = btn.querySelector('.button-text');
			if (loading) loading.classList.remove('d-none');
			if (text) text.classList.add('d-none');
		}
		
		function enableBtn() {
			if (!btn) return;
			let loading = btn.querySelector('.loading');
			let text = btn.querySelector('.button-text');
			if (loading) loading.classList.add('d-none');
			if (text) text.classList.remove('d-none');
		}

		disableBtn();				

		let request = function () {
			return fetch(next, {method: "POST",   headers: {
				"X-Requested-With": "XMLHttpRequest",
				"Content-Type": "application/x-www-form-urlencoded",
			  }, body: new URLSearchParams({csrf: document.querySelector('[name=csrf]')?.value ?? ""})})
			.then((response) => {
				next = false;
				if (!response.ok) {
					return Promise.resolve(response.text()).then((responseInText) => {
						return Promise.reject([response, responseInText]);
					});
				}
				return response.json()
			})
			.then((json) => {
					let progressMessage = document.getElementById('progress-message');
					let progressError = document.getElementById('progress-error');
					let progressSuccess = document.getElementById('progress-success');
					const progressBar = document.getElementById('progress-bar');

					if (progressMessage) progressMessage.textContent = '';
					
					if (json['error']) {
						if (progressBar) progressBar.classList.add('bg-danger');
						if (progressError) {
							progressError.textContent = json['error'];
						}

						enableBtn();
						//stop on fatal errors instead of marking the update as successful
						return;
					}

					let percent = (json['position'] || 0) * 100 / (json['count'] || 1);
					
					if (json['success']) {
						//document.getElementById('progress-bar').style.width = '100%';.classList.add('bg-success');
						if (progressSuccess) {
							progressSuccess.classList.add('text-success');
							progressSuccess.textContent = json['success'];
						}

						enableBtn();
					}

					if (json['info'] && progressMessage) {
						//server generated status with an optional badge
						progressMessage.innerHTML = json['info'];
					}

					
					if (json['step']) {
						const progressStatus = document.getElementById('progress-status');
						if (progressStatus) progressStatus.textContent = json['step'];
					}	
					
					if (json['url'] && json['step'] && !json['error']) {
						next = json['url'];
						
						ajaxStack.add(request);

					} else {
						percent = 100;
						if (progressBar) progressBar.classList.add('bg-success');
						enableBtn();
						if (progressBar) progressBar.style.width = '100%';
						
						return;
						if (json['success']) {
							window.location.href += '&success=' + json['success'];  
						} else if (json['error']) {
							window.location.href += '&error=' + json['error'];  
						} else {
							window.navigation.reload();
						}
					}

					if (progressBar) progressBar.style.width = percent + '%';					
			})/*.then((json) => {
				if (next) {
					ajaxStack.add(request);
				}
			})*/
			.catch(error => {
					let response, responseInText;

					if (Array.isArray(error)) {
						[response, responseInText] = error;
					}

					let message = responseInText || response?.statusText || error?.message || "Error!";
					message = String(message);

					if (typeof displayToast === "function") {
						displayToast("danger", "Error", message);
					}

					const success = document.getElementById('progress-success');
					if (success) success.textContent = '';

					const progressMessage = document.getElementById('progress-message');
					if (progressMessage) {
						progressMessage.classList.add('text-danger');
						progressMessage.textContent = message;
					}

					const progressBar = document.getElementById('progress-bar');
					if (progressBar) progressBar.classList.add('bg-danger');

					enableBtn();
					console.error(error);
			});
		};

		ajaxStack.add(request);		
	}
	
	core(url, btn = false) {
		url = url ?? (window.location.pathname + '?module=tools/update&action=update&type=core');
		return this.action(url, btn);
	}

	plugin(url, btn = false) {
		return this.action(url, btn);
	}
	
}

let Update = new UpdateController();
export {Update};

window.Update = Update; 
