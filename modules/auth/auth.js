functionsAuth = {};


functionsAuth.changePasswordDialog = function(source) {
	
	var userId = $(functionsDB.storage.contextMenuSource).closest('tr').attr('data-row-id');
	var URL = './?ajaxproxy=auth&action=change_password&row_id='+userId;
	
	backgroundLock();

	$.get(URL)
		.done(function(result) {
			$('<div></div>')
				.html(result)
				.dialog({
					modal : true,
					width : 'auto'
				});
			$('#chpassform').submit(function(event) {
				event.preventDefault();
				functionsAuth.submitPasswordForm();
			});
		});
};

functionsAuth.submitPasswordForm = function() {
	$('#chpass_messagebox').html('<img src="images/loadingbar.gif" alt="loading" />');
	$('#chpass_messagebox').removeClass('login_messagebox_error').addClass('login_messagebox_waiting');
	$('#chpass_messagebox').fadeIn('fast', 'linear');
	$.post(
		'.?ajaxproxy=auth&action=chpass',
		{ username: $('#username').val(), password: $('#password').val(), password1: $('#password1').val(), password2: $('#password2').val() },
		function(data, status) {
			$('#chpass_messagebox').html(data.substr(3));
			if (data.substr(0,2) == 'OK') {
				$('#chpass_messagebox').removeClass('login_messagebox_waiting').addClass('login_messagebox_success');
				setTimeout(function() { location.href='.'; }, 2000);
			} else {
				$('#chpass_messagebox').removeClass('login_messagebox_waiting').addClass('login_messagebox_error');
				setTimeout(function() { $('#chpass_messagebox').fadeOut('fast', 'linear'); }, 3000);
			}
		}
	);

};