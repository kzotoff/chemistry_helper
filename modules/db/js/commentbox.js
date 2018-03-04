//
// scripts for comments editor
//

// this row should exist in every script using adding anything to functionsDB
if (typeof(functionsDB) == 'undefined') { functionsDB = {}; }


functionsDB.commentsFormHandlers = function(target) {

	$(target).find('[data-button-action]').each(function() {
		var buttonAction = $(this).attr('data-button-action');
		switch (buttonAction) {
			case 'comment-delete':
				var commentId = $(this).closest('.commentbox').attr('data-comment-id');
				$(this).on('click', function() {
					functionsDB.commentDelete(commentId);
				});
				break;
			case 'form-submit':
				// no actions here - common API mechanics used
				break;
		}
	});

}

functionsDB.commentDelete = function(commentId) {
	var commentBox = $('[data-comment-id="'+commentId+'"]');
	
	commentBox.find('[data-button-action="comment-delete"]').attr('src', 'images/loadingbar.gif');

	$.get('./?ajaxproxy=db&action=call_api&method=comments_delete&row_id='+commentId)
		.done(function(result, status, jqXHR) {
			if (jqXHR.getResponseHeader('X-JuliaCMS-Result-Status') == 'OK') {
				commentBox.closest('.ui-dialog-content').on('dialogclose', function () { location.reload(); } );
				commentBox.remove();
			} else {
				alert('Не удалось удалить комментарий.\r\nОтвет сервера: '+result);
				commentBox.find('[data-button-action="delete_comment"]').attr('src', 'images/red_cross_diag.png');
			}		
		});

}


