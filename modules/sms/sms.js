$(function() {

	functionsSMS = {};

	// ULR template
	functionsSMS.URL = './?ajaxproxy=sms&action=send&row_id=%row_id%';
	
	$('#report_sms_delete_all').on('click', function() {
		$.get('./?ajaxproxy=sms&action=delete_all').done(function() { location.reload(); });
	});

	$('#report_sms_send_all').on('click', function() {
		
		// all items to use
		functionsSMS.sendList = [];
		
		// TAG_TODO сюда прикрутить автоматическое определение экземпляра отчета, из которого отправляем - на случай, если их несколько на экране
		// add only items without "sent" mark
		$('[data-report-id="report_sms"]').find('.datablock-content').find('tr').each(function() {
			if ($(this).find('td[data-field-name="sms_sent"]').html() == '') {
				functionsSMS.sendList.push($(this).attr('data-row-id'));			
			}
		});
		
		// create logger gauge
		// TAG_TODO тащить его аяксом тоже, а не тут мутить
		$('<div class="sms-sender-gauge-dialog"> <div class="sms-sender-log"></div> <div class="sms-sender-gauge"> <div class="sms-sender-gauge-inner"></div> </div></div>')
			.dialog({ width : 'auto' });

		// start iterator
		functionsSMS.sendListTotalLength = functionsSMS.sendList.length;
		functionsSMS.sendOneAndCallNext(function() {
			alert('Готово!');
			location.reload();
		});
	});

	functionsSMS.moreGauge = function(text) {
		$('.sms-sender-log').html($('.sms-sender-log').html()+text+'<br />');
		$('.sms-sender-gauge-inner').css('width', (100 - parseInt(functionsSMS.sendList.length / Math.max(functionsSMS.sendListTotalLength, 1) * 100))+'%');
	}
	
	functionsSMS.sendOneAndCallNext = function(callback) {
		if (functionsSMS.sendList.length > 0) {
			var elemId = functionsSMS.sendList.pop();
			var URL = functionsSMS.URL.replace('%row_id%', elemId);
			$.get(URL)
				.done(function(result) {
					functionsSMS.moreGauge('[OK] '+result);
				})
				.fail(function(result) {
					functionsSMS.moreGauge('[BIG FAIL] '+result);
				})
				.always(function() {
					functionsSMS.sendOneAndCallNext(callback);
				});
		} else {
			if (typeof(callback) == 'function') {
				callback();
			}
		}		
	}

});