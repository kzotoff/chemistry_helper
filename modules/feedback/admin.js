$(function() {
	$('[data-module="feedback"] table [data-button-action="delete"]').on('click', function(event) {
		var filename = $(this).closest('tr').find('[data-meaning="template-filename"]').html();
		moduleFeedback.deleteTemplate(filename);
		event.stopPropagation();
	});
	$('[data-module="feedback"] table [data-button-action="edit"]').on('click', function(event) {
		var filename = $(this).closest('tr').find('[data-meaning="template-filename"]').html();
		moduleFeedback.editTemplate(filename);
		event.stopPropagation();
	});
	$('[data-module="feedback"] table tbody tr').on('click', function(event) {
		var filename = $(this).closest('tr').find('[data-meaning="template-filename"]').html();
		moduleFeedback.editTemplate(filename);
		event.stopPropagation();
	});
});

moduleFeedback = {};

moduleFeedback.editTemplate = function(filename) {
	backgroundLock();
	$.get('./?ajaxproxy=feedback&&action=edit_template&filename='+filename)
		.done(function(result) {
			$('<div id="edit_template_dialog"></div>')
				.html(result)
				.dialog({
					title: ('Редактирование шаблона'),
					modal: true,
					width: '70rem',
					close: function() {
						$('#edit_template_dialog').remove();
						backgroundRelease();
					}
				});
			$('#edit_template_dialog [data-action="feedback-edit-submit"]').on('click', function() {
				$(this).closest('form').submit();
			})
			$('#edit_template_dialog [data-action="feedback-edit-cancel"]').on('click', function() {
				$('#edit_template_dialog').dialog('close');
			})
		})
		.fail(function() {
			alert('Ошибка при получении данных с сервера. Попробуйте немного позже');
		});
};

moduleFeedback.deleteTemplate = function(filename) {
	if (confirm('Удалить шаблон?')) {
		location.href = './?module=feedback&action=delete_template&filename='+filename;
	}
};
