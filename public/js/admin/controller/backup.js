class BackupController {

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

					if (json['error']) {
						$('#progress-bar').addClass('bg-danger');
						$('#progress-status').html('<div class="text-danger">' + json['error'] + '</div>');

						enableBtn();
					}

					let percent = (json['position']) * 100 / json['count'];				
					
					if (json['success']) {
						$('#progress-bar').css('width', '100%').addClass('bg-success');
						$('#progress-status').html('<div class="text-success">' + json['success'] + '</div>');

						enableBtn();
					}

					if (json['table']) {
						$('#progress-status').html(json['table']);
					}					
					
					if (json['file']) {
						$('#progress-file').html(json['file']);
					}					
					
					if (json['position']) {
						$('#progress-position').html(json['position']);
					}			
					
					if (json['count']) {
						$('#progress-count').html(json['count']);
					}			
					
					if (json['page']) {
						$('#progress-page').html(json['page']);
					}

					
					if (json['url'] && (!json['count'] || (json['position'] < json['count']))) {
						next = json['url'];

						ajaxStack.add(request);
					} else {
						percent = 100;
						$('#progress-bar').addClass('bg-success');
						enableBtn();
						$('#progress-bar').css('width', '100%');

						if (json['success']) {
							window.location.href += '&success=' + json['success'];  
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
	
	make(url, btn = false) {
		url = url ?? '/admin/?module=tools/backup&action=save';
		return this.action(url, btn);
	}

	restore(url, btn = false) {
		return this.action(url, btn);
	}
	
}

let Backup = new BackupController();
export {Backup};

window.Backup = Backup; 
