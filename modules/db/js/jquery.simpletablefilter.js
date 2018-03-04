(function($) {

	$.fn.simpleTableFilter = function(customOptions) {

		var options = $.extend({
			searchIn        : 'table',
			after           : function() {}
		}, customOptions);

		return this.each(function() {

			$(this).on('keydown', function(event) {
				if (event.which == 27) {
					$(this).val('');
				}				
			});
			
			$(this).on('keyup', function() {

			// ok, what to search?
				var searchThis = $(this).val().toUpperCase();
				// do search!
				$(options.searchIn).each(function() {
					filteredTable = $(this).closest('table');
					filteredTable.find('tbody tr').each(function() {
						var found = false;
						$(this).find('td').each(function() {
							if ($(this).text().toUpperCase().search(searchThis) != -1) {
								found = true;
							}
						});
						if (!found) {
							$(this).css('display', 'none');
						} else {
							$(this).css('display', 'table-row');
						}
					});
				});
				
				// call callback
				options.after();				
				
			});
		});
	}
})(jQuery);