//
// editor box functions
//
if (typeof(functionsDB) == 'undefined') { functionsDB = {}; }

///////////////////////////////////////////////////////////////////////////////////////////////////
// typical handler attach function ////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.editorialFormHandlers = function(popupBox) {
	var editorial = popupBox.children('.edit-dialog-wrapper');
	var editorialButtons = $(editorial).find('.edit-dialog-categories').find('input[data-action="show-category"]');
	editorialButtons.click(function() {
		if ($(this).attr('data-show-all') == 'yes') { // TAG_TODO записать в мануал про назначение этого атрибута
			functionsDB.editorialFilterCategories(editorial, '*');
		} else {
			functionsDB.editorialFilterCategories(editorial, $(this).attr('data-category'));
		}
	});

	// add datepicker to some fields
	$('[data-editor-type="datetime"]').datetimepicker({ dateFormat: 'dd.mm.yy' });

	// also select first category as full list is very tall
	if (editorialButtons.length > 0) {
		editorialButtons.first().click();
	}

	// add picture selector
	editorial.find('.edit-dialog-select-picture').on('click', function() {
		simpleFilemanager.start('pictures', $(this).parent().children('.form-control'));
	});

	// TAG_TODO this should be automated - autodetect such things and get SFM category automatically
	editorial.find('[data-sfm-category="css"]').on('click', function() {
		simpleFilemanager.start('css', $(this).parent().children('.form-control'));
	});
	
	editorial.find('.add-tinymce-inline').tinymce({
		// inline : true,
		plugins     : "advlist autolink lists link image charmap searchreplace visualblocks code insertdatetime media table contextmenu paste filelink minigallery",
		toolbar     : "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link filelink minigallery | superscript",
		menubar     : "edit insert view format table tools",
		contextmenu : "cut copy paste | link chooseimage inserttable | row column cell",
		language    : "ru"
		// statusbar : false
	});
	
	editorial.find('select.add-autocomplete').combobox();

}

///////////////////////////////////////////////////////////////////////////////////////////////////
// shows-hides edit boxes by category. "*" means "all" ////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.editorialFilterCategories = function(editorial, category) {

	// iterate all edit controls // TAG_TODO в мануале написать про необходимость класса form-group
	$(editorial).find('.form-group').each(function() {
		// show item if category found or displaying all
		if ((category == '*') || ($(this).attr('data-categories').indexOf('/'+category+'/') != -1)) {
			if ($(this).css('height') == '0px') {
				$(this).animate({
					'height'        : '30px',
					'margin-bottom' : '15px',
				}, 'fast', function() { $(this).css({'overflow' : 'visible', 'height' : 'auto' }) });
			}
		} else {
			if ($(this).css('height') != '0px') {
				$(this).css('overflow', 'hidden');
				$(this).animate({
					'height'        : '0px',
					'margin-bottom' : '0px'
				}, 'fast');
			}
		}
	});

}