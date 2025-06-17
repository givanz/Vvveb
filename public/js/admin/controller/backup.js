class BackupController {

	action(url, btn) {
		let next = url;
		document.getElementById("progress").classList.remove("d-none");
		
		function disableBtn() {
			btn.querySelector('.loading').classList.remove('d-none');
			btn.querySelector('.button-text').classList.add('d-none');
		}
		
		function enableBtn() {
			btn.querySelector('.loading').classList.add('d-none');
			btn.querySelector('.button-text').classList.remove('d-none');
		}

		disableBtn();				

		let request = function () {
			return fetch(next, {method: "POST",   headers: {
				"X-Requested-With": "XMLHttpRequest",
			  }})
			.then((response) => {
				next = false;
				if (!response.ok) {
					return Promise.reject(response);
				}				
				if (!response.ok) { 
					let message = response.statusText + " " + response.body();
					throw new Error(message); 
				}
				return response.json()
			})
			.then((json) => {
					document.getElementById('progress-status').innerHTML = '';
					
					if (json['error']) {
						document.getElementById('progress-bar').classList.add('bg-danger');
						document.getElementById('progress-status').innerHTML = '<div class="text-danger">' + json['error'] + '</div>';

						enableBtn();
					}

					let percent = (json['position']) * 100 / json['count'];				
					
					if (json['success']) {
						document.getElementById('progress-bar').style.width = "100%";//.classList.add('bg-success');
						document.getElementById('progress-status').innerHTML = '<div class="text-success">' + json['success'] + '</div>';

						enableBtn();
					}

					if (json['table']) {
						document.getElementById('progress-status').innerHTML = json['table'];
					}					
					
					if (json['file']) {
						document.getElementById('progress-file').innerHTML = json['file'];
					}					
					
					if (json['position']) {
						document.getElementById('progress-position').innerHTML = json['position'];
					}			
					
					if (json['count']) {
						document.getElementById('progress-count').innerHTML = json['count'];
					}			
					
					if (json['page']) {
						document.getElementById('progress-page').innerHTML = json['page'];
					}

					
					if (json['url'] && (!json['count'] || (json['position'] < json['count']))) {
						next = json['url'];

						ajaxStack.add(request);
					} else {
						percent = 100;
						document.getElementById('progress-bar').classList.add('bg-success');
						enableBtn();
						document.getElementById('progress-bar').style.width = '100%';

						if (json['success']) {
							window.location.href += '&success=' + json['success'];  
						} else {
							window.navigation.reload();
						}
					}

					document.getElementById('progress-bar').style.width = percent + '%';					
				})/*.then((json) => {
					if (next) {
						ajaxStack.add(request);
					}
				})*/
				.catch(error => {
						let message = error.statusText ?? error;

						if (typeof error.json === "function") {
							error.json().then(jsonError => {
								message = jsonError;
							}).catch(genericError => {
								message = error.statusText + " " + message;
							});
						}				
					
						console.log(error);
						document.getElementById('progress-success').innerHTML = '';
						document.getElementById('progress-message').innerHTML = '<div class="text-danger">' + message + '</div>';
						//console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
						document.getElementById('progress-bar').classList.add('bg-danger');
						enableBtn();
				});
		};

		ajaxStack.add(request);		
	}
	
	make(url, btn = false) {
		url = url ?? (window.location.pathname + '?module=tools/backup&action=save');
		return this.action(url, btn);
	}

	restore(url, btn = false) {
		return this.action(url, btn);
	}
	
}

let Backup = new BackupController();
export {Backup};

window.Backup = Backup; 
