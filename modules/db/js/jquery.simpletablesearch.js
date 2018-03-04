/*

params are:

main:
	searchIn        : eleemnts selector to look up
	scrollContainer : common container to scroll
	
supplementary:
	goodColor       : color to flash search input when sample found
	badColor        : flash when sample not found
	useAttribute    : flash background-color, border or something else

*/

(function($) {

	$.fn.simpleTableSearch = function(customOptions) {

		var options = $.extend({
			searchIn        : '*',
			scrollContainer : 'body',
			goodColor       : 'green',
			badColor        : 'red',
			useAttribute    : 'background-color'
		}, customOptions);

		// jQuery get mad about using variables as style names like this: $(selector).animate( { useAttribute : 'value' } );
		// so we need some workaround
		function animationObject(param, value) {
			var obj = {};
			obj[param] = value;
			return obj;
		}

		return this.each(function() {

			var inputInitialColor = $(this).css(options.useAttribute);
			var currentlyAnimatedObject = false;
			var currentlyAnimatedObjectDefaultColor;

			$(this).on('keydown', function(event) {
				if (event.which == 27) {
					$(this).val('');
					return;
				}				
			});
			
			$(this).on('keyup', function(event) {

				// ok, what to search?
				var searchThis = $(this).val();
				if ($.trim(searchThis) == '') {
					return;
				}

				// let's do search
				var found = false;
				$(options.searchIn).each(function() {
					if ($(this).text().toUpperCase().search(searchThis.toUpperCase()) != -1) {
						
						// element must be visible!
						if (!$(this).is(':visible')) {
							return;
						}
						
						// yeah we found it!
						found = true;

						// expose object
						var scrollTo = $(this).offset().top - $(options.scrollContainer).offset().top + $(options.scrollContainer).scrollTop();
						$(options.scrollContainer).animate( { 'scrollTop' : Math.max(0, scrollTo-50) }, 100);

						// cancel previous animation if any
						if (currentlyAnimatedObject) {
							currentlyAnimatedObject.stop();
							currentlyAnimatedObject.css(animationObject(options.useAttribute, currentlyAnimatedObjectDefaultColor));
							currentlyAnimatedObject = false;
						}

						// remember what we will animate
						currentlyAnimatedObject = $(this);
						currentlyAnimatedObjectDefaultColor = $(this).css(options.useAttribute);

						// and launch again
						currentlyAnimatedObject
							.animate(animationObject(options.useAttribute, options.goodColor), 200)
							.animate(animationObject(options.useAttribute, currentlyAnimatedObjectDefaultColor), 700, function() { currentlyAnimatedObject = false; })
						;

						// cancel further search
						return false;
					}
				});

				// flash input itself if nothing found
				if (!found) {
					$(this)
						.clearQueue()
						.animate(animationObject(options.useAttribute, options.badColor), 'fast')
						.animate(animationObject(options.useAttribute, inputInitialColor), 'fast')
					;
				}
			});
		});
	}
})(jQuery);