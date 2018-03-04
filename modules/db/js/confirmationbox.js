//
// confirmation box scripts
//
// TAG_TODO документировать это всё
// this row should exist in every script using adding anything to functionsDB
if (typeof(functionsDB) == 'undefined') { functionsDB = {}; }


functionsDB.confirmationBoxAttachHandlers = function(target) {

	// simply iterate all buttons, add handlers if class found	
	$(target).find('[data-button-action]').each(function() {

		var buttonAction = $(this).attr('data-button-action');
		switch (buttonAction) {
			case 'confirm-action':
				$(this).on('click', function() {
					functionsDB.sendAPICall($(this));
				});
				break;
			case 'cancel-action':
				$(this).on('click', function() {
					$(target).dialog('close');
				});
				break;
			
		}
		
	});

}

// TAG_TODO написать сюда документацию по стандарту рисования окошек вызова API
functionsDB.sendAPICall = function(source) {
	
	// change some look
	source.attr('disabled', 'disabled');
	source.addClass('button-with-loader');

	var container = $(source).closest('.confirmation-box-outer-wrapper');

	// find API method
	if (methodInput = container.find('*[data-api-param="method"]')) {
		var method = container.find('*[data-api-param="method"]').attr('data-api-value');
	} else {
		alert('no method found, cancelling');
		return false;
	}
	
	// create params array for API caller
	var overrides = {};
	container.find('*[data-api-param]').each(function() {
		overrides[$(this).attr('data-api-param')] = $(this).attr('data-api-value');
	});
	
	callAPI(method, overrides);
}
