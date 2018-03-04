/**
 *
 * this cool function applies mass operation to dataBlock rows
 *
 * option list:
 *
 * jQuery|JSnode|string datablockWrapper (required)
 *     jQuery object, pure JS node object or jQuery selector of entire dataBlock
 *
 * string URLTemplate (required)
 *     http request template. should be something like this:
 *     localhost?ajaxproxy=db&action=call_api&method=do_it&row_id=%row_id%
 *
 * string rowElementSelector (optional. default: "tr")
 *     jQuery selector to select rows. May refer either to rows themselves
 *     (like 'tr' or 'tr[data-use-this="yes"]' or 'tr:visible') or to anything
 *     inside: 'tr>td:first-child>input[type="checkbox"]:checked'
 *
 * string findCaptionSelector (optional. default: false)
 *     jQuery selector to find something to be row caption (for logging only)
 *
 * function before (optional. default: false)
 *     function to be called before any actions. Should it return false, the
 *     main script will terminate with false result
 *
 * function after (optional. default: false)
 *     function to be called before returning
 *
 * string gaugeContainer (optional. default: '.mass-operator-dialog')
 *     log container CSS class
 *
 * string gaugeContainerLog (optional. default: '.mass-operator-log')
 *     log container text box CSS class
 *
 * string gaugeContainerOuter (optional. default: '.mass-operator-gauge-outer')
 *     log container gauge external box (gauge frame) CSS class
 *
 * string gaugeContainerInner (optional. default: '.mass-operator-gauge-inner')
 *     log container gauge itself class CSS class
 *
 * string gaugeContainerError (optional. default: '.mass-operator-error')
 *      CSS class for error message spans
 *
 */
massOperator = function( options ) {

	"use strict";

	options = $.extend({
		datablockWrapper    : false,
		URLTemplate         : false,
		rowElementSelector  : 'tr',
		findCaptionSelector : false,
		rowIdFunction       : function(row) { return $(row).attr('data-row-id');  },
		before              : function () { return true },
		after               : function () { return true },
		gaugeContainer      : '.mass-operator-dialog',
		gaugeContainerLog   : '.mass-operator-log',
		gaugeContainerOuter : '.mass-operator-gauge-outer',
		gaugeContainerInner : '.mass-operator-gauge-inner',
		gaugeContainerError : '.mass-operator-error'
	}, options);

	if (!options.before()) {
		return;
	}

	if ( !options.URLTemplate ) {
		console.log('massOperator: no URL template');
		return false;
	}
	if ( !options.datablockWrapper ) {
		console.log('massOperator: no target');
		return false;
	}

	var dialogHTML = ' \
		<div class='+options.gaugeContainer.replace('.', '')+'"> \
			<div class="'+options.gaugeContainerLog.replace('.', '')+'"></div> \
			<div class="'+options.gaugeContainerOuter.replace('.', '')+'"> \
				<div class="'+options.gaugeContainerInner.replace('.', '')+'"></div> \
			</div> \
		</div> \
	';

	// all items to use
	var itemList = [];
	var itemListTotalLength = 0;

	//
	var getFormattedTime = function() {
		var t = new Date();
		return t.getHours()+':'+t.getMinutes()+':'+t.getSeconds()+'.'+t.getMilliseconds();
	};

	// add 1 to gauge value
	var moreGauge = function(text) {
		$(options.gaugeContainerLog).html( $(options.gaugeContainerLog).html()+'<p>'+text+'</p>' );
		$(options.gaugeContainerOuter).find(options.gaugeContainerInner).css('width', (100 - parseInt(itemList.length / Math.max(itemListTotalLength, 1) * 100))+'%');
	};

	// recursive action
	var doOneAndCallNext = function(callback) {
		if (itemList.length > 0) {
			var elem = itemList.pop();
			var URL = options.URLTemplate.replace('%row_id%', elem.rowId);
			$.get(URL)
				.done(function(result, status, jqXHR) {
					if (jqXHR.getResponseHeader('X-JuliaCMS-Result-Status') == 'OK') {
						moreGauge('[OK] '+getFormattedTime()+' '+elem.caption);
					} else {
						moreGauge('[ERROR] '+elem.caption+'<div class="'+options.gaugeContainerError.replace('.', '')+'">'+result+'</div>');
					}
				})
				.fail(function(status, textStatus) {
					moreGauge(
						'[ERROR] ' +
						status.status + ' ' +         // HTTP code
						status.statusText + ' / ' +   // canonical code text (i.e., "Not found" for 404)
						status.responseText           // text part of result
					);
				})
				.always(function() {
					doOneAndCallNext(callback);
				});
		} else {
			if (typeof(callback) == 'function') {
				callback();
			}
		}
	};

	// create log and gauge
	$(dialogHTML).dialog({ width : 'auto' });

	// get all items
	$(options.datablockWrapper).find('.datablock-content').find(options.rowElementSelector).closest('tr').each(function() {
		itemList.push({
			rowId   : options.rowIdFunction(this),
			caption : options.findCaptionSelector ? $(this).find(options.findCaptionSelector).text() : ''
		});
	});

	// start iterator
	itemListTotalLength = itemList.length;

	$(options.gaugeContainerLog).html('<p>[STARTED] '+getFormattedTime()+'</p>');
	doOneAndCallNext( options.after );

};