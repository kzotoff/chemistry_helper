similarHighlight = function() {

	$('.sim-compare-tests .sim-compare-data').each(function() {
		$(this).find('div[data-code]').each(function() {
			var code = this.getAttribute('data-code');
			var refValue = $('.sim-compare-reference [data-code="'+code+'"] .sim-reference-value').text();
			if ($(this).text() == refValue) {
				$(this).css('background-color', '#afa');
			} else {
				$(this).css('background-color', '#faa');
			}
		
		});
	});
	

};

callSimilarHighlight = function() {
	setTimeout(similarHighlight, 2000);
}