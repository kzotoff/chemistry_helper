$(function() {

    $('.periodic-table').on('click', '.pt-item', function() {
        showElementInfo($(this).attr('data-number'));
    });

    popup.init();

});

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
        <div class="element-info"> \
            <h3>' + elem.title_ru + '</h3> \
            <h5>' + elem.title_en + '</h5> \
            <div class="el-info-item"><div>Порядковый номер:</div><div>' + elem.number + '</div></div> \
            <div class="el-info-item"><div>Группа:</div><div>' + elem.group + '</div></div> \
            <div class="el-info-item"><div>Период:</div><div>' + elem.period + '</div></div> \
            <div class="el-info-item"><div>Молярная масса:</div><div>' + elem.mass + '</div></div> \
            <div class="el-info-item"><div>Температура кипения:</div><div>' + elem.temp_boil + '&deg;C</div></div> \
            <div class="el-info-item"><div>Температура плавления:</div><div>' + elem.temp_melt + '&deg;C</div></div> \
            <div class="el-info-item"><div>Плотность:</div><div>' + elem.density + ' ' + (elem.density_unit || 'г/см<sup>3</sup>') + ' </div></div> \
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