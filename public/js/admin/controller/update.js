class UpdateController {

	action(url, btn) {
		var next = url;
		$("#progress").removeClass("d-none");
		
		function disableBtn() {
			$('.loading', btn).removeClass('d-none');
			$('.button-text', btn).addClass('d-none');
		}
		
		function enableBtn() {
			$('.loading', btn).addClass('d-none');
			$('.button-text', btn).removeClass('d-none');

		}

		var request = function () {
			return $.ajax({
				url: next,
				type: 'post',
				//data: $('input[name^=\'backup\']:checked'),
				dataType: 'json',
				beforeSend: function () {
					disableBtn();
				},
				success: function (json) {
					$('#progress-message').html('');
					
					if (json['error']) {
						$('#progress-bar').addClass('bg-danger');
						$('#progress-error').html('<div class="text-danger">' + json['error'] + '</div>');

						enableBtn();
					}

					let percent = (json['position']) * 100 / json['count'];				
					
					if (json['success']) {
						//$('#progress-bar').css('width', '100%').addClass('bg-success');
						$('#progress-success').html('<div class="text-success">' + json['success'] + '</div>');

						enableBtn();
					}

					if (json['info']) {
						$('#progress-message').html(json['info']);
					}

					
					if (json['step']) {
						$('#progress-status').html(json['step']);
					}	
					
					if (json['url'] && json['step'] && !json['error']) {
						next = json['url'];

						ajaxStack.add(request);
					} else {
						percent = 100;
						$('#progress-bar').addClass('bg-success');
						enableBtn();
						$('#progress-bar').css('width', '100%');
						
						return;
						if (json['success']) {
							window.location.href += '&success=' + json['success'];  
						} else if (json['error']) {
							window.location.href += '&error=' + json['error'];  
						} else {
							window.navigation.reload();
						}
					}

					$('#progress-bar').css('width', percent + '%');	
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					$('#progress-bar').addClass('bg-danger');
					enableBtn();
				}
			});
		};

		ajaxStack.add(request);		
	}
	
	core(url, btn = false) {
		url = url ?? '/admin/?module=tools/update&action=update&type=core';
		return this.action(url, btn);
	}

	plugin(url, btn = false) {
		return this.action(url, btn);
	}
	
}

let Update = new UpdateController();
export {Update};

window.Update = Update; 