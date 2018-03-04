$(function() {
	$('.backup-in-table-buttons input[data-action="download"]').on('click', function() {
		if (confirm('Загрузить на локальный компьютер?')) {
			var filename = $(this).closest('tr').attr('data-filename');
			location.href = './?module=backup&action=download&backup_name='+filename;
		}
	});
	$('.backup-in-table-buttons input[data-action="restore"]').on('click', function() {
		if (confirm('Восстановить файлы из резервной копии? Действие не может быть отменено.')) {
			var filename = $(this).closest('tr').attr('data-filename');
			location.href = './?module=backup&action=restore&backup_name='+filename;
		}
	});
	$('.backup-in-table-buttons input[data-action="delete"]').on('click', function() {
		if (confirm('Удалить резервную копию? Действие не может быть отменено.')) {
			var filename = $(this).closest('tr').attr('data-filename');
			location.href = './?module=backup&action=delete&backup_name='+filename;
		}
	});

});