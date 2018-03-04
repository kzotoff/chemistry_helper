$(function() {
	$('[data-module="filemanager"]').find('[data-action="filemanager-edit-item"]').on('click', function() {
		moduleFilemanager.editItem($(this).closest('td').attr('data-path'));
	});

	$('[data-module="filemanager"]').find('[data-action="filemanager-add-item"]').on('click', function() {
		moduleFilemanager.editItem();
	});

	$('[data-module="filemanager"]').find('[data-action="filemanager-delete-item"]').on('click', function() {
		moduleFilemanager.deleteThisItem($(this).closest('td').attr('data-path'), $(this).closest('td').attr('data-category'));
	});

});

moduleFilemanager = {};

moduleFilemanager.editItem = function(file) {
	backgroundLock();
	var URL;
	var caption;

	if (typeof(file) != 'string') { // means "new file"
		URL = './?ajaxproxy=filemanager&module=filemanager&action=create_elem&category='+$('#category_selector').val();
		caption = 'новый файл';
	} else {
		URL = './?ajaxproxy=filemanager&module=filemanager&action=edit_elem&file='+file;
		caption = file;
	}

	$.get(URL)
		.done(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: 'Редактирование файла: '+caption,
					modal: true,
					width: 'auto',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease();
					}
				});
			$('input[data-action="filemanager-edit-cancel"]').on('click', function() {
				$(this).closest('#edit_dialog').dialog('close');
			});

			// http://stackoverflow.com/questions/6140632/how-to-handle-tab-in-textarea
			$("#module_admin_edit_content").keydown(function(event) {
				if ($('.tab-behavior').find('input').attr('checked') != 'checked') {
					return;
				}

				if (event.keyCode === 9) { // tab was pressed
					// get caret position/selection
					var start = this.selectionStart;
					var end = this.selectionEnd;

					var value = $(this).val();

					// set textarea value to: text before caret + tab + text after caret
					$(this).val(
						value.substring(0, start)
						+ "\t"
						+ value.substring(end)
					);

					// put caret at right position again (add one for the tab)
					this.selectionStart = this.selectionEnd = start + 1;

					// prevent the focus lose
					event.preventDefault();
				}
			});
		});
};


moduleFilemanager.deleteThisItem = function(file, category) {
	if (confirm('really delete "'+file+'" ?')) {
		location.href = './?module=filemanager&action=delete&category='+category+'&filename='+file;
	}
}