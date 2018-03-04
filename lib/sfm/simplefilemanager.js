simpleFilemanager = (function($) {
	'use strict';

	if (typeof($) != 'function') {
		console.log('simpleFilemanager: jQuery not found');
		return false;
	}

	// URL template
	var URL = './lib/sfm/simplefilemanager.php';

	var dialogHTML = '\
		<div class="sfm-dialog"> \
			<div class="sfm-header"> \
				<span class="sfm-header-path"> \
					<input type="text" class="sfm-focus-catcher" /> \
					[<span class="sfm-header-path-caption"></span>]/<span class="sfm-header-path-path">.</span> \
				</span> \
				<span class="sfm-header-buttons"> \
					<button class="sfm-button" data-sfm-action="upload"><img src="lib/sfm/images/add.png" alt="upload file" /></button> \
					<button class="sfm-button" data-sfm-action="remove"><img src="lib/sfm/images/remove.png" alt="remove" /></button> \
					<button class="sfm-button" data-sfm-action="mkdir"><img src="lib/sfm/images/mkdir.png" alt="new folder" /></button> \
					<button class="sfm-button" data-sfm-action="close"><img src="lib/sfm/images/close.png" alt="cancel" /></button> \
				</span> \
			</div> \
			<div class="sfm-work-area"> \
			</div> \
			<div class="sfm-main-buttons"> \
				<button class="sfm-button" data-sfm-action="insert"><img src="lib/sfm/images/ok.png" alt="ok" /></button> \
				<button class="sfm-button" data-sfm-action="close"><img src="lib/sfm/images/close.png" alt="cancel" /></button> \
			</div> \
			<div class="sfm-all-popups"> \
				<div class="sfm-popup" data-sfm-popup="mkdir"> \
					<div class="sfm-popup-inner"> \
						<div>create directory:</div> \
						<input type="text" name="mkdir_name" value="" placeholder="enter new name" /> \
						<button class="sfm-button" data-sfm-action="send-mkdir"><img src="lib/sfm/images/ok.png" alt="ok" /></button> \
						<button class="sfm-button" data-sfm-action="popup-close"><img src="lib/sfm/images/cancel.png" alt="cancel" /></button> \
					</div> \
				</div> \
				<div class="sfm-popup" data-sfm-popup="upload"> \
					<iframe name="sfm-uploader"></iframe> \
					<form enctype="multipart/form-data" class="sfm-popup-inner" target="sfm-uploader" method="post"> \
						<div>upload files:</div> \
						<input type="file" multiple="multiple" name="files[]" /> \
						<button type="button" class="sfm-button" data-sfm-action="send-upload"><img src="lib/sfm/images/ok.png" alt="ok" /></button> \
						<button type="button" class="sfm-button" data-sfm-action="popup-close"><img src="lib/sfm/images/cancel.png" alt="cancel" /></button> \
					</form> \
				</div> \
				<div class="sfm-popup" data-sfm-popup="remove"> \
					<div class="sfm-popup-inner"> \
						<div data-sfm-meaning="file-deletion-caption">The following files will be deleted:</div> \
						<div data-sfm-meaning="file-deletion-list"></div> \
						<button class="sfm-button" data-sfm-action="send-remove"><img src="lib/sfm/images/ok.png" alt="ok" /></button> \
						<button class="sfm-button" data-sfm-action="popup-close"><img src="lib/sfm/images/cancel.png" alt="cancel" /></button> \
					</div> \
				</div> \
				<div class="sfm-popup" data-sfm-popup="message"> \
					<div class="sfm-popup-inner"> \
						<div data-sfm-meaning="message-caption"></div> \
						<div data-sfm-meaning="message-text"></div> \
						<button class="sfm-button" data-sfm-action="popup-close"><img src="lib/sfm/images/ok.png" alt="ok" /></button> \
					</div> \
				</div> \
				<div class="sfm-popup" data-sfm-popup="waiter"> \
				</div> \
			</div> \
		</div> \
		<div class="sfm-background"></div> \
	';

	var elementHTML = '\
		<label class="sfm-elem" data-sfm-path="" data-sfm-type=""> \
			<input type="checkbox" data-sfm-meaning="elem-select" /> \
			<div class="sfm-elem-inner"> \
				<div class="sfm-elem-images"> \
					<div class="sfm-elem-icon"> \
						<img src="" alt="" /> \
					</div> \
					<div class="sfm-elem-preview"> \
						<img src="" alt="" /> \
					</div> \
				</div> \
				<input type="text" class="sfm-elem-caption" readonly="readonly" title="" /> \
			</div> \
		</label> \
	';

	// current path inside category
	var currentPath;

	// root-relative path to category root-relative
	var categoryRoot;

	// container to work in
	var workarea;

	// entire dialog
	var dialog;

	// background
	var background;

	// control to set value of
	var control;

	// resource type to traverse - pictures, files or something else. Will be handled server-side
	var category;

	// show desired popup
	var setPopupState = function(_selector, _state) {
		dialog.find('.sfm-popup').css({ 'display' : 'none' });
		dialog.find('.sfm-all-popups').css({ 'display' : 'none' });
		if (_state == 'on') {
			dialog.find(_selector).css({ 'display' : 'table' });
			dialog.find('.sfm-all-popups').css({ 'display' : 'block' });
		}
		
		if (_selector == '[data-sfm-popup="mkdir"]') {
			glueFocus('div[data-sfm-popup="mkdir"] input[name="mkdir_name"]');
		} else {
			glueFocus('input.sfm-focus-catcher');
		}
	};

	// image check intervals
	var intervals = {};
	
	//
	var addListElem = function(data) {
		var moreElem = $(elementHTML);
		moreElem.attr('data-sfm-path', '');
		moreElem.attr('data-sfm-type', data.type);
		moreElem.find('.sfm-elem-icon').children('img').attr('src', data.icon);
		if (data.image) {
			moreElem.find('.sfm-elem-preview').children('img').attr('src', data.image);
		} else {
			moreElem.find('.sfm-elem-preview').remove();
		}
		moreElem.find('.sfm-elem-caption').val(data.caption);

		// sorta like tooltips
		moreElem.find('.sfm-elem-caption').attr('title', data.caption);
		moreElem.attr('title', data.caption);
		
		moreElem.appendTo(workarea);
	};

	// if image preview already visible, icon must be removed
	// returns quantity of elements with bot icon and preview containers (what means more pass is required)
	var removeIconsFromDisplayedImages = function() {
		var allImages = workarea.find('.sfm-elem-images').has('.sfm-elem-icon').has('.sfm-elem-preview');
		allImages.each(function(index) {
			if ($(this).find('.sfm-elem-preview').find('img').width() > 0) {
				$(this).closest('.sfm-elem-images').find('.sfm-elem-icon').remove();
			}
		});	
		return allImages.length;
	};

	// get and display directory contents
	var showDir = function(path) {
	
		if (typeof(path) === 'undefined') {
			path = '.';
		}
	
		if (!workarea) {
			console.log('simpleFilemanager: working area not found');
			return;
		}

		setPopupState('[data-sfm-popup="waiter"]', 'on');

		$.get(URL + '?action=list&cat='+category+'&path='+encodeURIComponent(path))
			.always(function() {
				setPopupState('[data-sfm-popup="waiter"]', 'off');
			})		
			.done(function(result, status, jqXHR) {
				var item;
				var slashPosition;
				workarea.html('');
				try {
					// get new directory contents
					var parsedResult = JSON.parse(result);
					dialog.find('.sfm-header-path-caption').text(parsedResult.caption);
					currentPath = parsedResult.currentPath;			
					categoryRoot = parsedResult.categoryRoot;
					dialog.find('.sfm-header-path-path').text(currentPath);
					for (item in parsedResult.items) {
						addListElem(parsedResult.items[item]);
					}

					// start checker
					if (removeIconsFromDisplayedImages() > 0) {
						var newInterval = setInterval(function() {
							if (removeIconsFromDisplayedImages() == 0) {
								clearInterval(newInterval);
							}
						}, 200);
					}

					// check the file, if set at path
					var filename = path;
					while (slashPosition = filename.search('/') >= 0) {
						filename = filename.substr(slashPosition + 1);
					}
					if (filename > '') {
						$('.sfm-elem-caption').each(function() {
							if ($(this).val() == filename) {
								$(this).closest('.sfm-elem').find('[data-sfm-meaning="elem-select"]').get(0).checked = true;
								return false;
							}
						});
					}
					
				} catch(error) {
					showMessage('bad response');
				}
			})
			.fail(function(status) {
				showMessage(
					status.responseText+' / '+  // text part of result
					status.status+' / '+        // HTTP code
					status.statusText           // canonical code text (i.e., "Not found" for 404)
				);
			})
		;

	};

	var showMessage = function(message, caption, callback) {
		$('[data-sfm-meaning="message-text"]').html(message);
		$('[data-sfm-meaning="message-caption"]').html(typeof(caption) != 'undefined' ? caption : '');
		
		if (typeof(callback) == 'function') {
			$('[data-sfm-popup="message"] button').one('click', function() {
				callback();
			});
		}
		setPopupState('[data-sfm-popup="message"]', 'on');
	};

	var close = function() {
		$('.sfm-dialog').remove();
		$('.sfm-background').remove();
		setTimeout(function() { $(control).focus(); }, 100);
	};

	var operationMkdir = function() {
		setPopupState('[data-sfm-popup="waiter"]', 'on');
		$.get(URL+ '?action=mkdir&cat='+category + '&path='+currentPath + '&name='+encodeURIComponent($('input[name="mkdir_name"]').val()) )
			.always(function() {
				setPopupState('[data-sfm-popup="waiter"]', 'off');
			})
			.done(function(result) {
				showDir(currentPath);
			})
			.fail(function(status) {
				showMessage( status.responseText+' / '+ status.status+' / '+ status.statusText );
			})
		;
	};
	
	var operationUpload = function() {
		$('.sfm-popup[data-sfm-popup="upload"] form').attr('action', URL + '?cat='+category + '&path='+currentPath + '&action=upload');
		
		setPopupState('[data-sfm-popup="waiter"]', 'on');
		$('.sfm-popup[data-sfm-popup="upload"] form').submit();
		var checkSubmitResultInterval = setInterval(function() {
			
			var iframeContent = $($('.sfm-popup[data-sfm-popup="upload"]').children('iframe').get(0).contentDocument.body).html();
			
			if ($.trim(iframeContent) == '') {
				return;
			}
			
			clearInterval(checkSubmitResultInterval);
			
			if ($.trim(iframeContent) == 'OK') {
				setPopupState('[data-sfm-popup="waiter"]', 'off');
				showDir(currentPath);
			} else {
				showMessage(iframeContent, 'errors occured during upload:', function() { showDir(currentPath); });
			}
		}, 1000)
		
	};
	
	var prepareRemove = function() {
		var fileList = [];
		$('input[data-sfm-meaning="elem-select"]:checked').each(function() {
			fileList.push(
				($(this).closest('.sfm-elem').attr('data-sfm-type') == 'dir' ? '[DIRECTORY] ' : '') +
				$(this).closest('.sfm-elem').find('.sfm-elem-caption').val()	
			);
		});
		$('[data-sfm-meaning="file-deletion-list"]').html(fileList.toString().replace(/,/g, '<br />'));
		setPopupState('[data-sfm-popup="remove"]', 'on');
	}
	
	var operationRemove = function() {
		var fileList = [];
		$('input[data-sfm-meaning="elem-select"]:checked').each(function() {
			fileList.push($(this).closest('.sfm-elem').find('.sfm-elem-caption').val());
		});
		setPopupState('[data-sfm-popup="waiter"]', 'on');
		$.get(URL+ '?action=remove&cat='+category+ '&path='+currentPath+ '&list='+encodeURIComponent(fileList.toString()) )
			.always(function() {
				setPopupState('[data-sfm-popup="waiter"]', 'off');
			})
			.done(function(result) {
				if ($.trim(result) != 'OK') {
					showMessage(result, 'some errors occured:', function() { showDir(currentPath); });
				} else {
					showDir(currentPath);
				}
			})
			.fail(function(status) {
				showMessage( status.responseText+' / '+ status.status+' / '+ status.statusText );
			})
		;
	};
	
	var glueFocusOnFocus = function(event) {
		event.stopPropagation();
	}
	var glueFocusOnBlur = function(event) {
		if ($('.sfm-glued-focus').length) {
			$('.sfm-glued-focus').first().focus();
		}
	}	
	var glueFocus = function(_selector) {
		dialog.off('focus', '.sfm-glued-focus', glueFocusOnFocus);
		dialog.off('blur', '.sfm-glued-focus', glueFocusOnBlur);
		$('.sfm-glued-focus').removeClass('sfm-glued-focus');
		
		$(_selector).addClass('sfm-glued-focus');
		dialog.on('focus', _selector, glueFocusOnFocus);
		dialog.on('blur', _selector, glueFocusOnBlur);

		$(_selector).focus();
	};

	return {

		start : function(_category, _control, _attachTarget, _callbacks) {
			category = _category;
			control = _control;
			
			var callbacks = $.extend({
				confirm : null
			}, _callbacks);

			if (typeof(_attachTarget) == 'undefined') {
				_attachTarget = 'body';
			}

			// create common dialog parts and attach handlers
			$(dialogHTML).appendTo($(_attachTarget));
			dialog = $('.sfm-dialog');

			// header buttons
			dialog.on('click', '[data-sfm-action="upload"]', function() {
				setPopupState('[data-sfm-popup="upload"]', 'on');
			});
			dialog.on('click', '[data-sfm-action="remove"]', function() {
				prepareRemove();
			});
			dialog.on('click', '[data-sfm-action="mkdir"]', function() {
				setPopupState('[data-sfm-popup="mkdir"]', 'on');
			});
			dialog.on('click', '[data-sfm-action="close"]', function() {
				close();
			});
			
			// main OK button
			dialog.on('click', '[data-sfm-action="insert"]', function() {
				var selected = workarea.find('input[data-sfm-meaning="elem-select"]:checked');
				var elem;

				if (selected.length != 1) {
					showMessage('you must select exactly one element');
					return false;
				}
				elem = selected.first().closest('.sfm-elem');
				if (elem.attr('data-sfm-type') != 'file') {
					showMessage('this is a directory, not a file');
					return false;
				}
				_control.val( currentPath + elem.find('.sfm-elem-caption').val() );
				
				if (typeof(callbacks.confirm) === 'function') {
					callbacks.confirm( currentPath + elem.find('.sfm-elem-caption').val() );
				}
				close();
			});
			
			// universal popup closer
			dialog.on('click', '[data-sfm-action="popup-close"]', function() {
				setPopupState('*', 'off');
			});

			// file operations (upload, remove, mkdir)
			dialog.on('click', '[data-sfm-action="send-mkdir"]', function() {
				operationMkdir();
			});
			dialog.on('click', '[data-sfm-action="send-upload"]', function() {
				operationUpload();
			});
			dialog.on('click', '[data-sfm-action="send-remove"]', function() {
				operationRemove();
			});

			// attach folder handlers
			dialog.on('click', '.sfm-elem input[data-sfm-meaning="elem-select"]', function(event) {
				event.stopPropagation();
			});

			dialog.on('click', '.sfm-elem[data-sfm-type="dir"], .sfm-elem[data-sfm-type="updir"]', function(event) {
				event.preventDefault();
				showDir(currentPath + $(this).find('.sfm-elem-caption').val() + '/');
			});

			// jQuery UI steals focus back to its dialog, kick it off
			dialog.on('focus', '.sfm-popup input', function(event) {
				event.stopPropagation();
			});
			dialog.on('focus', '.sfm-elem-caption', function(event) {
				event.stopPropagation();
			});
			
			// cancel directory change on caption click
			dialog.on('click', '.sfm-elem-caption', function(event) {
				event.stopPropagation();
			});
			
			// prevent jQ UI from stealing focus
			glueFocus('input.sfm-focus-catcher');

			// hook on ESC
			dialog.on('keydown', function(event) { 
				event.stopPropagation();
				if (event.which == 27) {
					if ($('.sfm-all-popups').css('display') != 'none') {
						setPopupState('*', 'off');
					} else {
						close();
					}
				};
			});

			// define working area (AJAX responses will be inserted there
			workarea = $('.sfm-work-area');

			// some adjusts
			dialog.css('top', parseInt(($(window).height() - dialog.height())/2)+'px');
/*
			$('.sfm-all-popups').css({
				'height' : workarea.outerHeight()+'px',
				'top'    : workarea.position().top
			});
*/
			$('.sfm-all-popups').css({
				'height' : dialog.outerHeight()+'px',
				'top'    : 0
			});

			// ok, launch
			showDir($(_control).val());

		}

	};
})(jQuery)