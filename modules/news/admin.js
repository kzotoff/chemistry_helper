$(function() {
	$('#button_add').click(function() { editNewsInfo(-1); });
	
	$('[data-module="news"] table tr').on('click', function() {
		editNewsInfo( $(this).attr('data-row-id') );
	});
	
	$('[data-module="news"] table [data-action="edit-row"]').on('click', function(event) {
		event.stopPropagation();		
		editNewsInfo( $(this).closest('tr').attr('data-row-id') );
	});
	
	$('[data-module="news"] table  [data-action="edit-page"]').on('click', function(event) {
		event.stopPropagation();
		location.href = './'+$(this).closest('tr').attr('data-page')+'?edit'
	});
	
	$('[data-module="news"] table  [data-action="delete-row"]').on('click', function(event) {
		event.stopPropagation();		
		if (confirm('really delete?')) {
			location.href = './?module=news&action=delete&id='+$(this).closest('tr').attr('data-row-id');
		}
	});
});

function editNewsInfo(id) {
	backgroundLock();
	$.get('./?ajaxproxy=news&module=news&action=edit_elem&id='+id)
		.done(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: (id < 0 ? 'Добавить новость' : 'Редактирование новости'),
					modal: true,
					width: 'auto',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease();
					}
				});
		});
};