$(function() {
	$('#button_add').click(function() { editPageInfo(-1); });
	$('.item_info_row').click(function() { editPageInfo($(this).attr('data-row-id')); });
	$('a[data-button-action="edit"]').click(function(event) { editPageInfo($(this).closest('tr').attr('data-row-id')); event.stopPropagation(); });
	$('a[data-button-action="goto"]').click(function(event) { location.href = './'+$(this).closest('tr').attr('data-alias')+'&edit'; event.stopPropagation(); });
	$('a[data-button-action="delete"]').click(function(event) { deletePage($(this).closest('tr').attr('data-row-id')); event.stopPropagation(); });
});

function editPageInfo(id) {
	backgroundLock();
	$.get('./?ajaxproxy=content&module=content&action=edit_elem&id='+id)
		.done(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: (id < 0 ? 'Новая страница' : 'Свойства страницы'),
					modal: true,
					width: '70rem',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease();
					}
				});
		});
};

deletePage = function(id) {
	if (confirm('Удалить страницу?')) {
		location.href = './?module=content&action=delete&id='+id;
	}
}