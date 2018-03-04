addElement = function(d) {
	if ((typeof d.tagName == 'undefined')
		|| (typeof d.target == 'undefined')
		) {
		return;
	}
	newElement = document.createElement(d.tagName);
	if (d.className) {
		newElement.className=d.className;
	}
	if (d.id) {
		newElement.id=d.id;
	}
	d.target.appendChild(newElement);
	return newElement;
}

entitiesToChars = function(str) {
	return str
		.replace('&gt;','>')
		.replace('&lt;','<')
		.replace('&quot;','"')
		.replace('&amp;','&');

}

$(function() {
	$('.buttondiv')
		.mousedown(function() { $(this).removeClass('bn').addClass('bp'); })
		.mouseup(function() { $(this).removeClass('bp').addClass('bn'); })
		;

	$('#b_add_photo').click(function() { $('#addphotoform').dialog({ modal: true, width: 500, resizable: false, title:'Загрузка файла' }); });
	$('#b_add_album').click(function() { $('#addalbumform').dialog({ modal: true, width: 500, resizable: false, title:'Новый альбом' }); });
	$('#b_del_item').click(function() { deletePhotos(function() { location.href='main.php'; }); });
	$('#b_move_item').click(function() { $('#moveform').dialog({ modal: true, width: 500, resizable: false, title:'Перемещение' }); });

	if ($('#errorform').length>0) {
		$('#ef_ok').click(function() { $('#errorform').remove(); });
		$('#errorform').dialog({ modal: true, width: 300, resizable: false, title:'Ошибка' });
	}

	$('#movebutton').click(function() { $(this)[0].disabled = true; moveSelected(function() { location.href = './main.php'; }); });
	$('.onetimebutton').click(function() { $(this)[0].disabled = true; $(this)[0].form.submit(); });

	
	$('.album_picture').on('click', function() {
		var checkbox = $(this).find('input[type="checkbox"]');
		if (checkbox.attr('checked') == 'checked') {
			checkbox.removeAttr('checked');
		} else {
			checkbox.attr('checked','checked');
		}
		updateAlbumItemClass($(this));
	});
	
	
	$('.album_picture input[type="checkbox"]').on('click', function(event) {
		event.stopPropagation();
		updateAlbumItemClass($(this).closest('.album_picture'));
	});
	
});

updateAlbumItemClass = function(target) {
	checkbox = $(target).find('input[type="checkbox"]');
	if (checkbox.attr('checked') == 'checked') {
		$(target).addClass('album_item_selected');
	} else {
		$(target).removeClass('album_item_selected');
	}
}

deletePhotos = function(callback) {
	var list=[];
	t=$('.album_item .cb:checked');
	massTotal=t.length;
	if (massTotal==0) {
		callback.call();
		return;
	}

	for (i=0; i<t.length; i++) { list[i]=t[i].id.substr(3,t[i].id.length-3); }

	confirmString='Помечено файлов (альбомов): '+massTotal+'\n'+'Удалить?';
	if (!confirm(confirmString)) {
		return false;
	}
	
	addElement({ tagName:'div', id:'mass_totalback', target:document.body });
	gaugeBack = addElement({ tagName:'div', className:'gaugeback', target:document.body });
	addElement({ tagName:'div', id:'mass_gauge', target:gaugeBack });
	addElement({ tagName:'div', className:'gaugeback gaugetext', target:document.body }).innerHTML = 'deleting....';
	
	$('#mass_gauge').css('width','1px');
	$('.gaugeback').css('left',(Math.floor($(window).width()/2)-200)+'px');
	$('#mass_totalback').css({
		'width':($(window).width()+'px'),
		'height':($(window).height()+'px')
	});
	deletePhotosRecursive(list, callback);
}

deletePhotosRecursive = function(list, callback) {
	if (list.length>0) {
		$.post('manage.php', { action:'delete', id:list[list.length-1], no_redirect:1 } )
			.complete(function(event) {
				list.length--;
				$('#mass_gauge').css('width', Math.floor((400/massTotal)*(massTotal-list.length))+'px');
				if (list.length>0) {
					deletePhotosRecursive(list, callback);
				} else {
					if (typeof callback == 'function') {
						callback.call();
					}
				}
			});
	}
}

moveSelected = function(callback) {

	var target = $('#movetarget').val();
	var list=[];
	t=$('.album_item .cb:checked');
	massTotal=t.length;
	if (massTotal==0) {
		callback.call();
		return;
	}

	for (i=0; i<t.length; i++) { list[i]=t[i].id.substr(3,t[i].id.length-3); }

	confirmString='Помечено файлов (альбомов): '+massTotal+'\n'+'Переместить?';
	if (!confirm(confirmString)) {
		return false;
	}
	
	addElement({ tagName:'div', id:'mass_totalback', target:document.body });
	gaugeBack = addElement({ tagName:'div', className:'gaugeback', target:document.body });
	addElement({ tagName:'div', id:'mass_gauge', target:gaugeBack });
	addElement({ tagName:'div', className:'gaugeback gaugetext', target:document.body }).innerHTML='moving....';
	
	$('#mass_gauge').css('width','1px');
	$('.gaugeback').css('left',(Math.floor($(window).width()/2)-200)+'px');
	$('#mass_totalback').css({
		'width':($(window).width()+'px'),
		'height':($(window).height()+'px')
	});
	
	movePhotosRecursive(list, target, callback);
}

movePhotosRecursive = function(list, target, callback) {
	if (list.length>0) {
		$.post('manage.php', { action:'move', id:list[list.length-1], no_redirect:1, target:target } )
			.complete(function(event) {
				list.length--;
				$('#mass_gauge').css('width', Math.floor((400/massTotal)*(massTotal-list.length))+'px');
				if (list.length>0) {
					movePhotosRecursive(list, target, callback);
				} else {
					if (typeof callback == 'function') {
						callback.call();
					}
				}
			});
	}
}
