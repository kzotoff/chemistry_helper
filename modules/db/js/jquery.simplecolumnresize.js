//
// helper function for column moving
//
(function($) {
	"use strict";
	$.fn.simpleMove = function(_target, _moveBefore) {
		if (_moveBefore) {
			$(this).detach().insertBefore(_target);
		} else {
			$(this).detach().insertAfter(_target);
		}
	}
})(jQuery);

//
// installed at table or at double-table wrapper (must has class "datablock-wrapper",
// and sub-tables are "datablock-header" and "datablock-content")
//
// TAG_TODO:
//   include cells selector to install handles to particular cells
//   autodetect if linked table has colgroup or not
//
(function($) {

	$.fn.simpleColumnResize = function(_options) {

		"use strict";

		var options = $.extend({
			dragHandleClass   : 'col_width_handle', // helper object located in every cell must have this class
			liveReorder       : true,               // reorder columns on-the-fly or on mouse-up
			liveResize        : true                // resize column on-the-fly or on drop
		}, _options);

		// indicates if we should accept drop event or it was made by someone else
		var weAreResizing = false;
		var weAreReordering = false;

		// objects we manipulate
		var target = { };
		
		// save initial mouse down place to detect single clicks
		var mousedownEvent = false;

		var reorderColumn = function(_target, _event, _indicator_only) {
			var newPosition;
			var closestColumnIndex = false; // closest place to move to
			var closestDistance = Infinity;
			var moveBefore = true; // true if we must move column before found, false if after
			var tryThis;
			var t;

			// ok, find the place to move column to, columns must differ
			_target.table.find('td, th').each(function(index) {

				// check positions after cells
				tryThis = Math.abs(_event.originalEvent.pageX - ($(this).offset().left + $(this).outerWidth()));
				if ((tryThis <= closestDistance)
					&&
					(index != _target.columnIndex)
				) {
					closestDistance = tryThis;
					closestColumnIndex = index;
					moveBefore = false;
				}

				// check before cells
				tryThis = Math.abs(_event.originalEvent.pageX - $(this).offset().left);
				if ((tryThis <= closestDistance)
					&&
					(index != _target.columnIndex)
				) {
					closestDistance = tryThis;
					closestColumnIndex = index;
					moveBefore = true;
				}
			});

			// ok, must reorder
			if (
				(closestColumnIndex !== false) &&
				(
					!moveBefore && (closestColumnIndex !== _target.columnIndex - 1) // don't move before and after self
				||
					 moveBefore && (closestColumnIndex !== _target.columnIndex + 1)
				)
			) {
				var swapWith = _target.table.find('td, th').eq(closestColumnIndex);
				if (_indicator_only) {
					$('.simple-reorder-indicator-live').css('display', 'block');
					var height = swapWith.outerHeight();
					var left = swapWith.offset().left;
					var top = swapWith.offset().top;
					if (!moveBefore) {
						left += swapWith.outerWidth();
					}
					$('.simple-reorder-indicator-live').css({
						'left'   : (left-1) +'px',
						'top'    : top      +'px',
						'height' : height   +'px'
					});
				} else {

					// move cells to new positions
					_target.table.find('td, th').eq(_target.columnIndex).simpleMove(swapWith, moveBefore);
					if (_target.linkedTable) {

						t = _target.linkedTable.children('colgroup').children('col').eq(closestColumnIndex);
						_target.linkedTable.find('col').eq(_target.columnIndex).detach().simpleMove(t, moveBefore);

						_target.linkedTable.children('tbody').children('tr').each(function() {
							t = $(this).children('td').eq(closestColumnIndex);
							$(this).children('td').eq(_target.columnIndex).detach().simpleMove(t, moveBefore);
						});
					}

					// change current index as we're in new position!
					if (moveBefore && (_target.columnIndex < closestColumnIndex)) {
						_target.columnIndex = closestColumnIndex - 1;
					}
					if (moveBefore && (_target.columnIndex > closestColumnIndex)) {
						_target.columnIndex = closestColumnIndex;
					}
					if (!moveBefore && (_target.columnIndex < closestColumnIndex)) {
						_target.columnIndex = closestColumnIndex;
					}
					if (!moveBefore && (_target.columnIndex > closestColumnIndex)) {
						_target.columnIndex = closestColumnIndex+1;
					}

				}
			
			}

		};

		// resizer
		// target must unclude at least table and column index, and may also contain linked table and object
		var resizeColumn = function(_target, _event) {

			var currentTDWidth;
			var newTDWidth;
			var newTableWidth;
			var scrollBarWidth = 0;

			if (!weAreResizing) {
				return;
			}

			currentTDWidth = parseInt(_target.column.css('width'));
			newTDWidth = _event.originalEvent.pageX - _target.column.offset().left - parseInt(_target.column.css('padding-right'));
			newTableWidth = parseInt(_target.table.css('width')) - (currentTDWidth - newTDWidth);

			if (newTDWidth < 0) {
				newTDWidth = 0;
			}

			// resize header container (otherwise, padding at right side will become invisible)
			if (_target.table.hasClass('dataTable')) {
				_target.table.parent().css('width', newTableWidth + 'px');
			}
			if (_target.table.parent().hasClass('datablock-header')) {
				_target.table.css('width', (newTableWidth-2)+'px'); // jQuery's reported width doesn't meet with real one
				_target.linkedTable.css('width', (newTableWidth-2)+'px');

			}

			// resize columns
			_target.column.css('width', newTDWidth+'px');
			if (_target.linkedColumn) {
				_target.linkedColumn.css('width', newTDWidth+'px');
			}
			
		};

		// set global-wide acceptor
		$('body').on('mouseup', function(event) {
			
			if (event.button != 0) {
				return;
			}

			if (mousedownEvent) {
				if (
					(event.originalEvent.pageX == mousedownEvent.originalEvent.pageX) &&
					(event.originalEvent.pageY == mousedownEvent.originalEvent.pageY)
				) {
					$(event.target).click();
				}
			}

			if (weAreResizing) {
				if (!options.liveResize) {
					resizeColumn(target, event);
				}
				weAreResizing = false;
			}

			if (weAreReordering) {
				if (!options.liveReorder) {
					reorderColumn(target, event, false);
				}
				$('.simple-reorder-indicator-live').css('display', 'none');
				$('.simple-reorder-object').remove();
				weAreReordering = false;
			}

			$('html').css('cursor', 'auto');
		});

		$('body').on('mousemove', function(event) {
			if (weAreResizing) {
				if (options.liveResize) {
					resizeColumn(target, event);
				}
			}

			if (weAreReordering) {
				reorderColumn(target, event, !options.liveReorder);
			}
		});

		// install reorder indicator (single for all of the instances)
		$('<div class="simple-reorder-indicator-live"></div>')
			.css({
				'position'         : 'fixed',
				'left'             : '0px',
				'top'              : '0px',
				'width'            : '3px',
				'height'           : '60px',
				'background-color' : '#235',
				'opacity'          : '0.5',
				'display'          : 'none'				
			})
			.appendTo($('body'));

		// ok, attach events to every target
		return this.each(function() {

			// patch for dataTable: no resizer for slave table (it has the same class as master)
			if ($(this).closest('.dataTables_scrollBody').length) {
				return;
			}
			// don't attach to JuliaCMS double-scroller slave table
			if ($(this).closest('.datablock-content').length) {
				return;
			}

			// default behavior - single table
			target.table = $(this);
			target.linkedTable = false;

			if ($(this).hasClass('dataTable')) {
				target.linkedTable = $(this).closest('.dataTables_scroll').children('.dataTables_scrollBody').children('.dataTable').first();
				target.dataTableAPI = target.table.DataTable();
			}

			// patch for JuliaCMS double-scroller: common container (div) is used instead of table
			if ($(this).hasClass('datablock-wrapper')) {
				target.table = $(this).find('.datablock-header>table');
				target.linkedTable = $(this).find('.datablock-content>table');
			}

			// disable text selection on headers
			target.table.find('thead, tr, th, .datablock-header-text').each(function() {
				this.onselectstart = function() { return false; };
				this.unselectable = "on";
				$(this).css('-moz-user-select', 'none');
				$(this).css('-khtml-user-select', 'none');
				$(this).css('user-select', 'none');
			});

			
			// install drag-starters at each handle
			target.table.find('tr').first().find('td, th').each(function() {
				$(this).css({'position' : 'relative' });
				if ($(this).find('.'+options.dragHandleClass).length == 0) {
					$(this).append(
						$('<div class="'+options.dragHandleClass+'"></div>').css({
							'position' : 'absolute',
							'right'    : '-5px',
							'top'      : '0px',
							'height'   : '100%',
							'width'    : '9px',
							'cursor'   : 'col-resize',
							// 'border'   : '1px blue solid',
							'z-index'  : '1'
						})
					);
				}
			});

			// prevent click propagation on handlers (FF will do, yes)
			$('.'+options.dragHandleClass).on('click', function(event) {
				event.stopPropagation();
			});

			// column reordering
			target.table.find('td, th').on('mousedown', function(event) {
				
				// prevent actions on checkboxes
				if (event.target.tagName == 'INPUT') {
					return;
				}
				
				// left button only!
				if (event.button != 0) {
					return;
				}
				var thisTarget = target;
				mousedownEvent = event;
				event.stopPropagation();
				weAreReordering = true;
				$('html').css('cursor', 'move');
				thisTarget.column = $(this);

				// linked table detection
				if (thisTarget.linkedTable) {
					thisTarget.columnIndex = thisTarget.column.index();
					// dataTable patch
					if (thisTarget.linkedTable.hasClass('dataTable')) {
						thisTarget.linkedColumn = thisTarget.linkedTable.find('thead').find('th').eq(thisTarget.columnIndex);
					} else {
						thisTarget.linkedColumn = thisTarget.linkedTable.find('colgroup').eq(0).find('col').eq(thisTarget.columnIndex);
					}
				}
				$(this).append(
					$('<div class="simple-reorder-object"></div>').css({
						'position'         : 'absolute',
						'width'            : $(this).outerWidth()+'px',
						'height'           : $(this).outerHeight()+'px',
						'top'              : '-1px',
						'left'             : '-1px',
						'background-color' : '#005',
						'opacity'          : '0.2',
						'box-sizing'       : 'border-box',
						'border'           : '0px #005 solid'
						
					})
				);
				target = thisTarget;
			});

			// column resizing
			target.table.find('.'+options.dragHandleClass).on('mousedown', function(event) {
				
				if (event.button != 0) {
					return;
				}				
				var thisTarget = target;
				event.stopPropagation();
				weAreResizing = true;
				$('html').css('cursor', 'col-resize');
				thisTarget.column = $(this).closest('td, th');

				// linked table detection
				if (thisTarget.linkedTable) {
					thisTarget.columnIndex = thisTarget.column.index();
					// dataTable patch
					if (thisTarget.linkedTable.hasClass('dataTable')) {
						thisTarget.linkedColumn = thisTarget.linkedTable.find('thead').find('th').eq(thisTarget.columnIndex);
					} else {
						thisTarget.linkedColumn = thisTarget.linkedTable.find('colgroup').eq(0).find('col').eq(thisTarget.columnIndex);
					}
				}
				target = thisTarget;
			});
		});
	}
})(jQuery);