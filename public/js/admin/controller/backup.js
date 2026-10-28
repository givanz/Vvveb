class BackupController {

	action(url, params, btn) {
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
			  }, body: new URLSearchParams({...params, csrf: document.querySelector('[name=csrf]')?.value ?? ""})})
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
					const status = document.getElementById('progress-status');
					const progressBar = document.getElementById('progress-bar');
					const setText = (id, value) => {
						const el = document.getElementById(id);
						if (el && value !== undefined && value !== null) {
							el.textContent = value;
						}
					};

					if (status) status.textContent = '';
					
					if (json['error']) {
						if (progressBar) progressBar.classList.add('bg-danger');
						if (status) {
							status.classList.add('text-danger');
							status.textContent = json['error'];
						}

						enableBtn();
						//stop on fatal errors instead of continuing to the next step
						return;
					}

					let percent = (json['position'] || 0) * 100 / (json['count'] || 1);
					
					if (json['success']) {
						if (progressBar) progressBar.style.width = "100%";//.classList.add('bg-success');
						if (status) {
							status.classList.remove('text-danger');
							status.classList.add('text-success');
							status.textContent = json['success'];
						}

						enableBtn();
					}

					if (json['table']) {
						if (status) {
							status.classList.remove('text-danger', 'text-success');
							status.textContent = json['table'];
						}
					}

					if (json['file']) setText('progress-file', json['file']);
					if (json['position'] !== undefined) setText('progress-position', json['position']);
					if (json['count'] !== undefined) setText('progress-count', json['count']);
					if (json['page']) setText('progress-page', json['page']);

					
					if (json['url'] && (!json['count'] || (json['position'] < json['count']))) {
						next = json['url'];

						ajaxStack.add(request);
					} else {
						percent = 100;
						if (progressBar) {
							progressBar.classList.add('bg-success');
							progressBar.style.width = '100%';
						}
						enableBtn();

						if (json['success']) {
							let url = window.location.href;
							if (url.indexOf('success=') === -1) {
								url += (url.indexOf('?') > -1 ? '&' : '?') + 'success=' + encodeURIComponent(json['success']);
								window.location.href = url;
							} else {
								window.location.reload();
							}
						} else if (window.navigation?.reload) {
							window.navigation.reload();
						} else {
							window.location.reload();
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

						displayToast("danger", "Error", message);

						const progressSuccess = document.getElementById('progress-success');
						if (progressSuccess) progressSuccess.textContent = '';

						const progressStatus = document.getElementById('progress-status');
						if (progressStatus) {
							progressStatus.classList.add('text-danger');
							progressStatus.textContent = message;
						}

						const progressBar = document.getElementById('progress-bar');
						if (progressBar) progressBar.classList.add('bg-danger');

						enableBtn();
						console.error(error);
				});
		};

		ajaxStack.add(request);		
	}
	
	make(url, params = {}, btn = false) {
		url = url ?? (window.location.pathname + '?module=tools/backup&action=save');
		return this.action(url, params, btn);
	}

	restore(url, params = {}, btn = false) {
		return this.action(url, params, btn);
	}
	
}

let Backup = new BackupController();
export {Backup};

window.Backup = Backup; 
