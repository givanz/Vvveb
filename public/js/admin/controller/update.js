class UpdateController {

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
					document.getElementById('progress-message').innerHTML = '';
					
					if (json['error']) {
						document.getElementById('progress-bar').classList.add('bg-danger');
						document.getElementById('progress-error').innerHTML = '<div class="text-danger">' + json['error'] + '</div>';

						enableBtn();
					}

					let percent = (json['position']) * 100 / json['count'];				
					
					if (json['success']) {
						//document.getElementById('progress-bar').style.width = '100%';.classList.add('bg-success');
						document.getElementById('progress-success').innerHTML = '<div class="text-success">' + json['success'] + '</div>';

						enableBtn();
					}

					if (json['info']) {
						document.getElementById('progress-message').innerHTML = json['info'];
					}

					
					if (json['step']) {
						document.getElementById('progress-status').innerHTML = json['step'];
					}	
					
					if (json['url'] && json['step'] && !json['error']) {
						next = json['url'];
						
						ajaxStack.add(request);

					} else {
						percent = 100;
						document.getElementById('progress-bar').classList.add('bg-success');
						enableBtn();
						document.getElementById('progress-bar').style.width = '100%';
						
						return;
						if (json['success']) {
							window.location.href += '&success=' + json['success'];  
						} else if (json['error']) {
							window.location.href += '&error=' + json['error'];  
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
					let message = error?.statusText ?? "Error updating!";
					displayToast("danger", "Error", message);

					if (error.hasOwnProperty('text')) error.text().then( errorMessage => {
						let message = errorMessage.substr(0, 200);
						displayToast("danger", "Error", message);
					});

					if (typeof error.json === "function") {
						error.json().then(jsonError => {
							message = jsonError;
						}).catch(genericError => {
							message = error?.statusText + " " + message;
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
