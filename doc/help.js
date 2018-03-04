$(function() {
	// create special CSS class for parent document (don't want to add it to other .css)
	if ($(window.parent.document).find('#help-subsystem-styles').length == 0) {
		$('<style type="text/css" id="help-subsystem-styles">.full-opaque { opacity : 1 !important; }</style>')
			.appendTo($(window.parent.document).find('head'));
	}
	
	$('.article-head').click(function() {
		// check if we clicked on displayed article
		var justCollapseThis = $(this).closest('.article').hasClass('visible');
		$('.article').removeClass('visible');
		if (!justCollapseThis) {
			$(this).closest('.article').addClass('visible');
		}
	});

	// force expanding minimized articles at the current page (catch it by links, started with hash-sign)
	$('a').on('click', function() {
		if ($(this).attr('href').substr(0,1) == '#') {
			var targetId = $(this).attr('href').substr(1);
			if (!$('#'+targetId).hasClass('visible')) {
				$('#'+targetId).find('.article-head').click();
			}
		}
	});

	// external jQuery dialog cannot catch ESC inside iframe, let's help him
	$('body').on('keydown', function(event) {
		if (event.keyCode == 27) {
			window.parent.moduleAdmin.closeHelp();
		}
	});

	// check if there any anchor in the href, expand article if any specified
	var anchor = /#.*/.exec(location.href);
	if (anchor > '') {
		anchor = anchor.toString().substr(1);
		if ($('#'+anchor).length > 0) {
			if (!$('#'+anchor).hasClass('visible')) {
				$('#'+anchor).find('.article-head').click();
			}
		}
		$(document).scrollTop($('#'+anchor).find('.article-head').offset().top);
	}

	setOpacity = function(opaque) {
		var objectList = '.admin-box-main'
		if (typeof(opaque) == 'boolean' && opaque == true) {
			$(window.parent.document).find(objectList).addClass('full-opaque');
		} else {
			$(window.parent.document).find(objectList).removeClass('full-opaque');
		}
	};

	showHelpArrow = function(event, target) {

		target = $(target)[0];
		
		// available arrows list (maximum usable length and images)
		// all images must be 150 px width
		var arrows = [
			{ caption: '1', maxLength : 600,      originalImageLength :  600, images : [ 'arrow_fantasy_1.png']                        },
			{ caption: '2', maxLength : Infinity, originalImageLength : 1200, images : [ 'arrow_fantasy_2.png', 'arrow_fantasy_3.png'] }
		];
		
		// help container, for proper position calculation
		var iframe = $(window.parent.document).find('#help_container').get(0);

		// start and end points
		var x1 = event.pageX + iframe.offsetLeft + iframe.parentNode.offsetLeft + parseInt($(iframe).css('padding-left'));
		var y1 = event.pageY + iframe.offsetTop  + iframe.parentNode.offsetTop + parseInt($(iframe).css('padding-top'));

		var x2 = target.offsetLeft + parseInt(target.offsetWidth / 2);
		var y2 = target.offsetTop  + parseInt(target.offsetHeight / 2);

		// basic arrow properties - length and angle
		var arrowLength = Math.sqrt((x1-x2)*(x1-x2) + (y1-y2)*(y1-y2));
		var arrowAngle = Math.acos((x2-x1)/arrowLength)/Math.PI*180 * (y2 < y1 ? -1 : 1) - 90;
		
		// make arrow a bit shorter to avoid target element overlapping
		arrowLength = arrowLength - 20;
		
		// ok, find appropriate arrow
		var useThisArrow = arrows[0];
		for (var i = 0; i < arrows.length; i++) {
			if ((arrowLength > useThisArrow.maxLength) && (arrowLength <= arrows[i].maxLength)) {
				useThisArrow = arrows[i];
			}
		}

		// calculate real image width as we need to find rotate origin
		var arrowWidth = parseInt(arrowLength/useThisArrow.originalImageLength * 150);
		var transformOrigin = parseInt(arrowWidth/2)+'px 0px';
		
		// get random image from the list
		var picFilename = useThisArrow.images[parseInt(Math.random()*100 % useThisArrow.images.length)];

		// place arrow
		$(window.parent.document).find('body').append(
			$('<img class="help-arrow" src="doc/'+picFilename+'" alt="" />').css({
				'position'                 : 'fixed',
				'z-index'                  : '3000',
				'opacity'                  : '0.45',
				'height'                   : (arrowLength-25)+'px',
				'left'                     : (x1-parseInt(arrowWidth/2))+'px',
				'top'                      : (y1-1)+'px',
				
				'transform'                : 'rotate(' + arrowAngle + 'deg)',
				'transform-origin'         : transformOrigin,
				'-o-transform'             : 'rotate(' + arrowAngle + 'deg)',
				'-o-transform-origin'      : transformOrigin,
				'-moz-transform'           : 'rotate(' + arrowAngle + 'deg)',
				'-moz-transform-origin'    : transformOrigin,
				'-ms-transform'            : 'rotate(' + arrowAngle + 'deg)',
				'-ms-transform-origin'     : transformOrigin,
				'-webkit-transform'        : 'rotate(' + arrowAngle + 'deg)',
				'-webkit-transform-origin' : transformOrigin
			})
		);

	}

	removeHelpArrow = function() {
		$(window.parent.document).find('.help-arrow').animate({ 'opacity' : '0'}, 'fast', function() { $(window.parent.document).find('.help-arrow').remove(); });
	}

	// helper buttons - flashes an element to help find it
	$('[data-flash-item]').on('click', function(event) {

		// check if target element designated
		var showItem = $(this).attr('data-flash-item');
		if (!showItem) {
			return;
		}

		// ok, will highlight this element
		var target = $(window.parent.document).find(showItem);

		if (target.length) {
			showHelpArrow(event, target);
			setOpacity(true);
			
			// initial color - get from saved first, from current if not saved
			var initialBackColor;
			if (typeof(target.attr('data-flasher-initial-background')) == 'undefined') {
				target.attr('data-flasher-initial-background', target.css('background-color'));
				initialBackColor = target.css('background-color');
			} else {
				initialBackColor = target.attr('data-flasher-initial-background');
			}

			target
				.animate({ 'background-color' : 'red'            }, 'fast')
				.animate({ 'background-color' : initialBackColor }, 'fast')
				.animate({ 'background-color' : 'red'            }, 'fast')
				.animate({ 'background-color' : initialBackColor }, 'fast')
				.animate({ 'background-color' : 'red'            }, 'fast')
				.animate({ 'background-color' : initialBackColor }, 'fast', function() { setOpacity(false); target.removeAttr('data-flasher-initial-background'); removeHelpArrow(); });
			return true;
		}

		// element not found, try to load screenshot or something else
		var URL = encodeURI($(this).attr('data-flash-item')).replace(/[=%]/g, '');
		if (URL) {
			URL = 'help.php?proxy=demo-'+URL+'.html';
			var result = true;
			$.get(URL)
				.done(function(result) {
					$('<div id="demo=dialog"></div>')
						.html(result)
						.dialog({
							width : 'auto',
							modal : true,
							close : function(event) { $('#demo-dialog').remove(); event.stopPropagation(); $('[data-button-action="help-to-content"]').get(0).focus(); }
						});

				})
				.fail(function() {
					alert('Не удалось найти элемент');
					result = false;
				});
			return result;
		}

		alert('Элемент отсутствует на экране');
		return false;
	});


});