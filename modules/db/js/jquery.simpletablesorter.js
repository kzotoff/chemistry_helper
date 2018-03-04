/*

TAG_TODO:
1) автодобавление идентификаторов, если таковых нету
2) привести входные параметры в соответствие с ходовым ('option', 'optionName', 'optionValue')
3) каменты нужны

*/

(function($) {

	$.fn.simpleTableSorter = function(customOptions) {

		"use strict";

		var options = $.extend({

		}, customOptions);

		// sorter external wrapper
		var wrapper = this;

		// ascending/descending order
		var direction = 1;

		// sort helper function
		var helperFunction = function(_a, _b) {

			if (_a.value > _b.value) {
				return direction;
			}

			if (_a.value < _b.value) {
				return -direction;
			}

			return 0;

		}

		// sorter
		var sortRows = function(_content, _columnIndex) {

			var tbody = $(_content).find('tr').first().parent();
			var currentCSSDisplay = tbody.css('display');
			var arr = [];
			var i;
			var headerCell;
			var dataCell;
			var dataValue;
			var dataType;
			var dataNonTextSource;
			var $this;

			// create data array, also try to detect field type
			dataType = 'numeric';
			$(_content).find('tr').each(function() {

				$this = $(this);

				dataCell = $this.children('td').eq(_columnIndex);

				if ((dataNonTextSource = dataCell.children('input[type="checkbox"]')).length > 0) {
					dataValue = dataNonTextSource.get(0).checked ? '1' : '0';
				} else {
					dataValue = $this.children('td').eq(_columnIndex).text();
				}

				if ((dataType == 'numeric') && isNaN(+dataValue)) {
					dataType = 'text';
				}

				arr.push({
					id    : $this.attr('data-row-id'),
					value : dataValue
				});

			});

			// check if type if forced
			headerCell = $(_content).closest('.datablock-wrapper').find('.datablock-header').find('th').eq(_columnIndex);
			if (headerCell.attr('data-sorter-type') > '') {
				dataType = headerCell.attr('data-sorter-type');
			}

			// clean up bad values
			if (dataType == 'numeric') {
				for (i = 0; i < arr.length; i++) {
					arr[i].value = parseFloat(arr[i].value);
					if (isNaN(arr[i].value)) {
						arr[i].value = '';
					}
				}
			}

			arr.sort(helperFunction);

			tbody.css('display', 'none');
			for (i = 0; i < arr.length; i++) {
				tbody.append( tbody.children('[data-row-id="'+arr[i].id+'"]').detach() );
			}
			tbody.css('display', currentCSSDisplay);


			var headerTable = tbody.closest('.datablock-wrapper').find('.datablock-header');

			headerTable.find('td, th').removeClass('simplesorter-sorted simplesorter-asc simplesorter-desc');

			if (direction > 0) {
				headerTable.find('td, th').eq(_columnIndex).addClass('simplesorter-sorted simplesorter-asc');
			} else {
				headerTable.find('td, th').eq(_columnIndex).addClass('simplesorter-sorted simplesorter-desc');
			}

		}

		if (customOptions == 'rebuild') {
			return this.each(function() {
				$(this).find('.datablock-header').find('td, th').each(function(index) {
					if ($(this).hasClass('simplesorter-sorted')) {
						sortRows($(wrapper).find('.datablock-content>table'), index);
					}
				});
			});
		};

		// installer
		return this.each(function() {
			$(this).find('.datablock-header').find('td, th').each(function(index) {
				$(this).on('click', function() {
					direction = $(this).hasClass('simplesorter-asc') ? -1 : 1;
					sortRows($(wrapper).find('.datablock-content>table'), index);
				});
			});
		});
	}
})(jQuery);