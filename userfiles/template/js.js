$(function() {

    $('.periodic-table').on('click', '.pt-item', function() {
        showElementInfo($(this).attr('data-number'));
    });

    popup.init();

    kbd.init();

    $('body').on('keydown', function(event) {
        if (event.originalEvent.keyCode == 27) {
            popup.hide();
        }
    });
});

// обработчик клавиатуры
//
// имеет два режима: стандартный (игнорирует остальные нажатия) и режим обработки
// в переходе в режим обработки слушает клавиатуру в течение 3 секунд,
// при отсутствии ввода переходит в стандартный режим
//
// активация происходит при нажатии тильды (код кнопки 126)
//
// по таймауту или нажатии кнопки деактивации выполняем finalize, а там уже смотрим, что насыпалось
//
//
kbd = {

    // кнопка для активации (по умолчанию - тильда, 126), обрабатывается по keypress
    activateCharCode : 126,

    // выключатель, обрабатывается по keydown (чтобы можно было поймать ESC)
    deactivateCharCode : 27,

    // режим (стандартый, обработки)
    active : false,

    // визуальная индикация режима работы
    indicator : null,

    // набор данных, полученных с клавиатуры
    data : '',

    offTimer : null,

    //
    init : function() {


        $('body').on('keydown', function(event) {
            if (event.originalEvent.keyCode == kbd.deactivateCharCode) {
                kbd.finalize();
            }
        });

        $('body').on('keypress', function(event) {
            kbd.process(event);
        });

        kbd.indicator = $('<div>');
        kbd.indicator.css({
            position: 'absolute',
            top             : 0,
            right           : 0,
            height          : '16px',
            width           : '16px',
            backgroundColor : 'rgba(80, 255, 80, 0.64)',
            display         : 'none',
        });
        kbd.indicator.appendTo( $('body') );

        kbd.setIdle();
    },

    // включаем
    setActive : function() {
        kbd.data = '';
        kbd.active = true;
        kbd.indicator.css('display', 'block');
    },

    // выключаем
    setIdle : function() {

        kbd.data = '';
        kbd.active = false;
        kbd.indicator.css('display', 'none');
    },

    waitAndDeactivate : function() {
        clearTimeout(kbd.offTimer);
        kbd.offTimer = setTimeout(function() {
            kbd.finalize();
        }, 1000);
    },

    // обрабтка нажатия на кнопку
    // просто добавляет то, что нажато, в строку
    process : function(event) {

        // если нажали активатор - автивизируемся!
        if ((event.charCode == kbd.activateCharCode) && ( ! kbd.active)) {
            kbd.setActive();
            kbd.waitAndDeactivate();
            return;
        }

        // если в режиме активности, слушаем еще 1 секунду и заканчиваем
        if (kbd.active) {
            kbd.waitAndDeactivate();
        }

        kbd.data += String.fromCharCode(event.charCode);
    },

    // когда ввод окончен, обрабатываем, что получили
    finalize : function() {

        if ( ! kbd.active) {
            return;
        }

        var data = kbd.data;

        kbd.setIdle();

        // поскольку у нас финализирующая кнопка может быть ескейпом, надо подождать,
        // пока она будет отпущена, иначе попап закроется сразу
        setTimeout(function() {
            if (/^00001\s/.test(data)) {
                var itemCode = data.replace(/^00001\s+/, '');
                showElementInfo(itemCode);
            }
        }, 50);
    },

};

// управление попапом
popup = {

    init: function() {
        $('.popup-overlay, .popup-close').on('click', function() {
            popup.hide();
        });
        $('.popup-popup').on('click', function(event) {
            event.stopPropagation();
        });

    },

    show: function(content) {
        $('.popup-overlay').addClass('popup-visible');
        $('.popup-content').html(content || '?');
    },

    hide: function() {
        $('.popup-overlay').removeClass('popup-visible');
    },

};

var showElementInfo = function(number) {

    var elem = null;
    for (var i = 0; i < window.data.periodic.length; i++) {
        if (window.data.periodic[i].number == number) {
            elem = window.data.periodic[i];
        }
    }
    if ( ! elem) {
        console.warn('element ' + number + ' not found');
        return;
    }

    var elementPopupHtml = ' \
        <div class="element-info" data-color="' + elem.color + '"> \
            <h3>' + elem.title_ru + '</h3> \
            <h5>' + elem.title_en + '</h5> \
            <div class="el-info-item"><div>Порядковый номер:     </div><div>' + elem.number + '</div></div> \
            <div class="el-info-item"><div>Группа:               </div><div>' + elem.group + '</div></div> \
            <div class="el-info-item"><div>Период:               </div><div>' + elem.period + '</div></div> \
            <div class="el-info-item"><div>Молярная масса:       </div><div>' + elem.mass + '</div></div> \
            <div class="el-info-item"><div>Температура кипения:  </div><div>' + ($.trim(elem.temp_boil) ? elem.temp_boil + '&deg;C' : '') + '</div></div> \
            <div class="el-info-item"><div>Температура плавления:</div><div>' + ($.trim(elem.temp_melt) ? elem.temp_melt + '&deg;C' : '') + '</div></div> \
            <div class="el-info-item"><div>Плотность:            </div><div>' + ($.trim(elem.density) ? elem.density + ' ' + (elem.density_unit || 'г/см<sup>3</sup>') : '') + ' </div></div> \
        </div> \
     \
     \
     \
     \
     \
     \
     \
    ';

    popup.show(elementPopupHtml);
}