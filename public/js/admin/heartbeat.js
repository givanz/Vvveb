/*
 * Keep session open to avoid seeing login screen if not active for a few minutes, if session still expires login modal is shown.
*/

let HeartBeat = setInterval(function () {
	$.ajax(window.location.pathname + '?action=heartbeat')
		.done(function (sessionStatus) {
			//console.log(sessionStatus);
			if (sessionStatus != "ok") {
				if ($("#login").length == 0) {
					$.ajax(window.location.pathname + '?module=user/login&modal=true').done(function (formHtml) {
						$("#heartBeatLogin .modal-body").html(formHtml);
						const heartBeatLogin = bootstrap.Modal.getOrCreateInstance('#heartBeatLogin');
						heartBeatLogin.show();
						$("#heartBeatLogin").on("submit", "form", function(e) {
							let parameters = $(this).serializeArray();
							//$("#heartBeatLogin [data-v-notifications]").remove();
							
							$.ajax({
								url:window.location.pathname + '?module=user/login&modal=true',
								type: 'post',
								data: parameters,
								success: function(data) {
								//if no error message shown then hide login modal
								if (data.indexOf("data-v-notification-error") == -1) {
									heartBeatLogin.hide();
								} else {
									//if there are errors show login form with error message
									$("#heartBeatLogin .modal-body").html(data);
								}
								},beforeSend: function() {
									$('.btn-login .loading').removeClass('d-none');
									$('.btn-login .button-text').addClass('d-none');
								},complete: function() {
									$('.btn-login .loading').addClass('d-none');
									$('.btn-login .button-text').removeClass('d-none');
								}
							});
							
							e.preventDefault();
							return false;
						});
					});
				}
			}
		}).fail(function (data) {
			//alert(data.responseText);
		});;
}, 3 * 60 * 1000);//3 minutes

export {HeartBeat};
