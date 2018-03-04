/**
 * This is core CMS library
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 */


/**
 * Root object for all modules' functions. Every module should add its own functions
 * as part of this object, just like this:
 *
 * JCMS.ModuleName = {
 *      doThis: function() { alert('wow!'); },
 *      ...
 * }
 *
 */
JCMS = {

    /**
     * background locker
     */
    background : {

        /**
         * Storage for onUnlock
         */
        unlockFunctions : [],

        /**
         *
         */
        currentBackground : null,

        /**
         * Locks the background
         *
         * @param userOptions various options to use. Supperted are:
         *        withLoader  : show animated icon at the center. True by default.
         *        hideOnClick : when true, background will unlock when clicked (weak mode)
         *        onUnlock    : function to be executed when unlocking
         */
        lock : function(userOptions) {
            var options = {
                withLoader  : true,
                hideOnClick : false
            };
            $.extend(options, userOptions);

            // create background if not yet
            if ( ! this.currentBackground ) {
                this.currentBackground = $('<div>');
                this.currentBackground.css({
                    'position'         : 'fixed',
                    'background-color' : 'rgba(255,255,255,0.6)',
                    'height'           : Math.max($(window).height(), $(document).height())+'px',
                    'width'            : Math.max($(window).width(),  $(document).width() )+'px',
                    'top'              : '0px'
                });
                this.currentBackground.appendTo($('body'));
            }

            // update on-click state
            this.currentBackground.unbind('click');
            if (options.hideOnClick) {
                this.currentBackground.on('click', function() {
                    JCMS.background.release();
                });
            }

            // update animation visibility
            if (options.withLoader) {
                $('<div>')
                    .css({
                        'position'   : 'fixed',
                        'background' : 'transparent url("images/loadingbar.gif") center center no-repeat',
                        'height'     : $(window).height() + 'px',
                        'width'      : $(window).width()  + 'px',
                        'top'        : '0px'
                    })
                    .appendTo(this.currentBackground)
                    ;
            } else {
                JCMS.background.removeLoader();
            }

            // queue unlock function
            if (typeof(options.onUnlock) == 'function') {
                this.unlockFunctions.push(options.onUnlock);
            }
        },

        /**
         * Releases the background
         */
        release : function() {
            $(this.currentBackground).remove();
            this.currentBackground = null;
            
            var moreFunction;
            while (this.unlockFunctions.length) {
                moreFunction = this.unlockFunctions.pop();
                moreFunction();
            }
        },

        /**
         * Remove animated indicator
         */
        removeLoader : function() {
            if ($(this.currentBackground).find('img').length) {
                $(this.currentBackground).find('img').remove();
            }
        }
    }

};


$(function() {

    // install TinyMCE
    tinymce.init({
        selector : '.apply_tinymce',
        visual : true,
        menu : {
            edit   : {title: 'Edit',   items: 'undo redo | cut copy paste pastetext | selectall | code'},
            insert : {title: 'Insert', items: 'chooseimage filelink | link media'},
            view   : {title: 'View',   items: 'visualblocks'},
            format : {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'},
            table  : {title: 'Table',  items: 'inserttable tableprops deletetable | cell row column'}
        },
        plugins : [
            "advlist autolink lists link image charmap print preview anchor",
            "searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste",
            "minigallery filelink"
                ],
        toolbar : "insertfile undo redo | styleselect fontselect fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | minigallery filelink",
        extended_valid_elements: "template[*] input[type=\"button\"]"
    });

    // display popups
    $('<div class="popup-container"></div>').appendTo($('body'));
    $('.popup-message').each(function() {
        popupMessageShow($(this));
    });

});

///////////////////////////////////////////////////////////////////////////////////////////////////
// displays hidden popup message //////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// container can be
// *) jQuery object pointing to existing container
// *) object with parameters to create popup:
// {
//     popupClass : popup class to use (popup-info, popup-ok, popup-warning, popup-error or popup-fatal)
//    message    : text to display
// }
//
///////////////////////////////////////////////////////////////////////////////////////////////////
popupMessageShow = function(container) {
    if (container.jquery) {
        $(container).css('display', 'block');
        $(container).detach().appendTo('.popup-container');
        $(container).on('click', function() { popupMessageRemove(container); });
        setTimeout(function() { popupMessageRemove(container); }, 7000);
    }
    if (container.popupClass) { // this means we have explicit inline popup decscription
        var morePopup = $('<div class="popup-message '+container.popupClass+'"></div>').html(container.message).appendTo('.popup-container');
        morePopup.on('click', function() { popupMessageRemove(morePopup); });
        setTimeout(function() { popupMessageRemove(morePopup); }, 7000);
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// hides and removes visible container ////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
popupMessageRemove = function(container) {
    $(container)
        .animate({'opacity' : '0'}, 'slow')
        .animate({'height' : '0px'}, 'slow', function() { $(container).remove(); })
}

/**
 * locks background and displays loading indicator
 *
 * @param userOptions various options to use. Supperted are:
 *        withLoader  : show animated icon at the center. True by default.
 *        hideOnClick : when true, background will unlock when clicked (weak mode)
 *        onUnlock    : function to be executed when unlocking
 */
backgroundLock = function(userOptions) {

    var options = {
        withLoader  : true,
        hideOnClick : false
    };
    $.extend(options, userOptions);

    var screenHeight = Math.max($(window).height(), $(document).height());
    var screenWidth =  Math.max($(window).width(),  $(document).width() );

    backgroundRelease();

    $('<div id="loading_back"></div>')
        .css({
            'width'      : screenWidth+'px',
            'height'     : screenHeight+'px',
            'top'        : '0px'
        })
        .on('click', function() {
            if (typeof(options.onUnlock) == 'function') {
                (options.onUnlock)();
            }
            if (options.hideOnClick) {
                backgroundRelease();
            }
        })
        .appendTo($('body'))
        ;

    if (options.withLoader) {
        $('<div id="loading_bar"></div>')
            .css({
                'width'      : screenWidth+'px',
                'height'     : screenHeight+'px',
                'top'        : '0px'
            })
            .appendTo($('body'))
            ;
    }
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// releases locked background. if "full" argument is not true, just removes loading indicator,
///////////////////////////////////////////////////////////////////////////////////////////////////
backgroundRelease = function(full) {
    if (full !== false) {
        $('#loading_back').remove();
    }
    $('#loading_bar').remove();
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// debug tool /////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
viewObjectContents = function(someObject) {
    var result = '';
    for (prop in someObject) {
        result += prop + ' = ' + someObject[prop] + '\r\n';
    }
    alert(result);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// creates uniquie random suffix //////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
getRandomSuffix = function() {
    return Math.random().toString().replace('.', '');
}