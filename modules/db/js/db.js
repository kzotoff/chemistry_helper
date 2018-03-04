// WTF ?
displayError = function(text, messageType, buttons) {
    alert(text);
};

////////////////////////////////////////////////////////////////////////////////
// TAG_CRAZY TAG_EXPERIMENTAL start
$(function() {
    // if (Math.random() * 10 > 7) { alert('now apply checkbox'); }

    var $button = $('[onclick="functionsDB.showCheckedOnly(this);"]');
    var $datablockId = $button.closest('.datablock-wrapper').attr('data-report-id');
    $button.children('img').remove();
    $button.removeAttr('onclick');

    $checkbox = $('<input>');
    $checkbox.attr('type', 'checkbox');
    $checkbox.attr('data-meaning', 'only-checked-filter');
    $checkbox.prependTo($button);

    $button.on('click', function(event) {
        if (event.target != $checkbox.get(0)) {
            $checkbox.get(0).checked = !$checkbox.get(0).checked;
        }
        functionsDB.filterManager.applyFilterAndSave( $datablockId );

    });
    
});
// TAG_CRAZY TAG_EXPERIMENTAL end
////////////////////////////////////////////////////////////////////////////////

; typeof(functionsDB) == 'undefined' && (functionsDB = {});

JCMS.DB = {

    /**
     * All API-related functions such as sending requests and its handling
     *
     */
    API : {

        /**
         * Timeout on waiting form submission result
         */
        submitResponseTimeout : 60,

        /**
         * Prepares form or other container to be submitted - converts it to a form
         * and adds some tags.
         *
         * @param string|jQuery target when string, it must point to container with
         *                             attribute data-form-name="[target]"
         *                             when jQuery, it already must be that container
         * @return prepared form
         */
        prepareFormToSubmit : function( _target ) {

            var formName = '';

            // first, determine what we have
            if (typeof(_target) == 'string') {
                $target = $('*[data-form-name="' + _target+'"]');
                formName = _target;
            } else {
                $target = $(_target);
                formName = $target.attr('data-form-name');
            }

            // all ok?
            if ($target.length == 0) {
                console.log('JCMS.DB.API.prepareFormToSubmit: bad form to submit (' + typeof(_target) + ' ' + _target + ')');
                return false;
            }

            // wrap with <form> if not <form>
            if ( ! $target.is('form')) {
                $target.wrap('<form action="." method="post" data-form-name="'+formName+'"></form>');
                $target = $target.parent();
            }

            $target.attr('method', 'post');
            $target.attr('enctype', 'multipart/form-data');

            return $target;
        },


        /**
         * Submits a form asynchronously. Also can use any other container as form.
         *
         * @param {string|jQuery} formToSubmit form to submit. see prepareFormToSubmit for
         *                                     additional comments.
         * @param {string|jQuery} sourceButton button to be source for the event. May be
         * @param {object}        callbacks    function to call after error or timeout
         */
        submitAsync : function(formToSubmit, sourceButton, callbacks) {

            // change some look
            var $sourceButton = $(sourceButton);
            $sourceButton.attr('disabled', 'disabled');
            $sourceButton.addClass('button-with-loader');

            // we cannot use form serialization as file sending is not serializable, the only
            // way is to use real submitting. Hidden iframe will be used at target to ensure
            // that response will be correctly handled
            var iframeID = 'save_iframe_'+getRandomSuffix();
            $('<iframe id="'+iframeID+'" name="'+iframeID+'"></iframe>').css({'display' : 'none'}).appendTo($('body'));

            // prepare form and submit it
            formToSubmit = JCMS.DB.API.prepareFormToSubmit( formToSubmit );

            var postData = new FormData(formToSubmit[0]);
            formToSubmit.find('input, textarea, select').each(function() {
                if ((this.tagName.toLowerCase == 'input') && (this.type.toLowerCase == 'file')) {
                    postData.set(this.name, this.files[0]);
                }
            });

            $.ajax({
                url         : formToSubmit[0].getAttribute('action'),
                data        : postData,
                type        : 'POST',
                timeout     : JCMS.DB.API.submitResponseTimeout * 1000,
                contentType : false,
                processData : false,
                complete    : function(jqXHR, status) {
                    $sourceButton.removeAttr('disabled');
                    $sourceButton.removeClass('button-with-loader');
                },
                success     : function(result, status, jqXHR) {

                    ////////////////////////////////////////////////////////////////////////////////////////////////////
                    // TAG_TODO TAG_CRAZY the following is absolute shit and should be refactored ASAP /////////////////
                    ////////////////////////////////////////////////////////////////////////////////////////////////////
                    if ((formToSubmit.attr('data-form-name') == 'commentbox-adder-form') && ( $('[data-form-container="edit-dialog-form"]').length || $('[data-form-name="edit-dialog-form"]').length)) {
                        callAPI('comments_dialog', {
                            rowId     : $('[data-form-name="edit-dialog-form"]').find('input[name="row_id"]').val(),
                            container : $('div[data-meaning="comments-dynamic-box"]')
                        }, {
                            after: function() {
                                functionsDB.modifyCommentBoxInTemplates($('div[data-meaning="comments-dynamic-box"]').find('.commentbox-outer-wrapper'));
                            }
                        });
                    } else {

                        var callbackResult = true;
                        if (typeof(callbacks.success) == 'function') { // TAG_TODO задокументировать поведение дефолтного хендлера
                            callbackResult = callbacks.success(result);
                        }
                        if (callbackResult) {
                            JCMS.DB.API.handleResponse(result, status, jqXHR);
                        }
                    }
                },
                error       : function(jqXHR, status, error) {
                    alert(
                        status.responseText+' '+ // text part of result
                        status.status+' '+       // HTTP code
                        status.statusText        // canonical code text (i.e., "Not found" for 404)
                    );
                }
            });
        },

        /**
         * Submits a form to hidden iframe. Also can use any other container as form.
         *
         * @param {string|jQuery} formToSubmit form to submit. target <form> must have
         *                                     attribute "data-form-name" with this value.
         * @param {string|jQuery} sourceButton button to be source for the event. May be
         * @param {object}        callbacks    function to call after error or timeout
         */
        submitFormToIframe : function(formToSubmit, sourceButton, callbacks) {

            // change some look
            $sourceButton = $(sourceButton);
            $sourceButton.attr('disabled', 'disabled');
            $sourceButton.addClass('button-with-loader');

            // we cannot use form serialization as file sending is not serializable, the only
            // way is to use real submitting. Hidden iframe will be used at target to ensure
            // that response will be correctly handled
            var iframeID = 'save_iframe_'+getRandomSuffix();
            $('<iframe id="'+iframeID+'" name="'+iframeID+'"></iframe>').css({'display' : 'none'}).appendTo($('body'));

            // prepare form and submit it
            formToSubmit = JCMS.DB.API.prepareFormToSubmit( formToSubmit );
            formToSubmit.attr('target', iframeID);
            formToSubmit.submit();

            // now we should wait for server response and take some actions
            var readyTicks = 0;
            checkAnswerInterval = setInterval(function() {

                // check iframe content for status ("OK" or something else)
                var postResult = $('#'+iframeID).contents()[0].body.innerHTML;

                if (postResult > '') {
                    clearInterval(checkAnswerInterval);
                    if (postResult.substr(0, 2) == 'OK') {

                        ////////////////////////////////////////////////////////////////////////////////////////////////////
                        // TAG_TODO TAG_CRAZY
                        // the following is absolute shit and should be refactored ASAP ////////////////////////////////////
                        ////////////////////////////////////////////////////////////////////////////////////////////////////

                        // some special treatment for comments form
                        if ((formToSubmit.attr('data-form-name') == 'commentbox-adder-form') && ( $('[data-form-container="edit-dialog-form"]').length || $('[data-form-name="edit-dialog-form"]').length)) {
                            callAPI('comments_dialog', {
                                rowId     : $('[data-form-name="edit-dialog-form"]').find('input[name="row_id"]').val(),
                                container : $('div[data-meaning="comments-dynamic-box"]')
                            }, {
                                after: function() {
                                    functionsDB.modifyCommentBoxInTemplates($('div[data-meaning="comments-dynamic-box"]').find('.commentbox-outer-wrapper'));
                                }
                            });
                        } else {
                            callbacks.success(postResult);
                        }


                        // TAG_TODO сделать перезагрузку только контейнера ?
                    } else {
                        callbacks.error(postResult);
                    }

                    $sourceButton.removeAttr('disabled');
                    $sourceButton.removeClass('button-with-loader');

                }

                // if timed out, just inform user and reload the page
                if (readyTicks++ > JCMS.DB.API.submitResponseTimeout) {

                    clearInterval(checkAnswerInterval);
                    if (typeof(callbacks.timeout) == 'function') {
                        callbacks.timeout(JCMS.DB.API.submitResponseTimeout);
                    }
                    return;
                }

            }, 500);
        },

        /**
         * Initial response handling - detecting response result and type and take according action
         */
        handleResponse : function(result, status, jqXHR, options) {

            var headers = {
                status       : jqXHR.getResponseHeader('X-JuliaCMS-Result-Status'),
                type         : jqXHR.getResponseHeader('X-JuliaCMS-Result-Type'),
                command      : jqXHR.getResponseHeader('X-JuliaCMS-Result-Command'),
            }

            if (headers.status != 'OK') {
                displayError(result); // TAG_TODO обрабатывать type (?)
                return false;
            }

            switch (headers.type) {

                // if HTML has arrived, show it as dialog and attach handlers
                // or insert into container specified
                case 'html':
                    // container to auto-attach handlers to (form, div or anything else)
                    var resultContainer;
                    if (typeof(options.container) != 'undefined') {
                        $(options.container).html(result);
                        resultContainer = options.container;
                    } else {
                        
                        var newDialogId = 'dialog' + Math.random().toString().replace('.', '');
                        JCMS.background.lock({ withLoader : false });
                        resultContainer = $('<div id="'+newDialogId+'" class="api-html-result"></div>');
                        resultContainer
                            .html(result)
                            .dialog({
                                modal : false,
                                close : function() {
                                    $('#' + newDialogId).remove();
                                    tinymce.remove();
                                    JCMS.background.release();
                                },
                                width : 'auto'
                            });
                        var dialogTitleNode = $('[data-dialog-title]');
                        resultContainer.dialog('option', 'title', dialogTitleNode.length > 0 ? dialogTitleNode.first().attr('data-dialog-title') : '?');
                    }

                    // iterate all buttons in the container, add handlers to specially marked controls
                    resultContainer.find('[data-button-action]').each(function() {

                        var buttonAction = $(this).attr('data-button-action');
                        switch (buttonAction) {
                            case 'just-reload':
                                $(this).on('click', function() {
                                    $(this).addClass('button-with-loader').attr('disabled', 'disabled');
                                    location.reload();
                                });
                                break;
                            case 'form-cancel':
                                $(this).on('click', function() {
                                    $(resultContainer).dialog('close');
                                });
                                break;
                            case 'form-submit':
                                $(this).on('click', function() {
                                    JCMS.DB.API.submitAsync(
                                        $(this).attr('data-form-submit'),
                                        $(this),
                                        {
                                            timeout : function(timeout) { displayError('Превышено время ожидания ответа сервера. Возможно, запись не была сохранена.'); location.reload(); },
                                            error   : function(response) { displayError('Произошла ошибка при сохранении записи.\r\nОтвет сервера:'+response); location.reload(); },
                                            success : function(response) { if (response == 'OK') { location.reload(); return false; } return true; }
                                        }
                                    );
                                });
                                break;
                        }
                    });

                    // call handler attach function, if specified (must be loaded already!)
                    if (resultContainer.find('[data-attach-handlers]').length) {
                        var handlerAttachFunctionString = resultContainer.find('[data-attach-handlers]').attr('data-attach-handlers');
                        if (handlerAttachFunctionString) {
                            var handlerAttachFunctionArray = handlerAttachFunctionString.split(/[\s,]+/);
                            for (var z = 0; z < handlerAttachFunctionArray.length; z++) {
                                if (typeof(functionsDB[handlerAttachFunctionArray[z]]) == 'function') {
                                    functionsDB[handlerAttachFunctionArray[z]](resultContainer);
                                }
                            }
                        }
                    }
                    if ((typeof(callbacks) != 'undefined') && (typeof(callbacks.after) == 'function')) {
                        callbacks.after();
                    }
                    break;

                // nothing special, just for compatibility
                case 'plain':
                    alert(result);
                    functionsDB.handleCommand(headers);
                    break;

                // can't apply it, but it's OK too
                case 'json':
                    alert('JSON!');
                    break;

                // can't apply it, but it's OK too
                case 'xml':
                    alert('XML!');
                    break;

                // that's something special for me!
                case 'command':
                    functionsDB.handleCommand(headers);
                    break;
            }
        }

    },

};

$(function() {

    // global storage
    // TAG_TODO про эту хрень тоже в мануал написать
    functionsDB.initStorage();

    // init mouse coordinates
    mouseX = 100;
    mouseY = 100;

    // user interface content generator
    functionsDB.storage.apiProxy = '.';

    // catch any click coordinates
    $(document).on('mousedown', function(event) {
        mouseX = event.pageX;
        mouseY = event.pageY;
    });

    $('.datablock-wrapper').each(function() {
        functionsDB.installTablePlugins(this);

        // scroll back to saved position
        var $content = $(this).find('.datablock-content');
        var reportID = $(this).attr('data-report-id');
        if ( functionsDB.storage.session[ 'scroll_left_' + reportID ] ) {
            $content.scrollLeft( functionsDB.storage.session[ 'scroll_left_' + reportID ] );
        }
        if ( functionsDB.storage.session[ 'scroll_top_' + reportID ] ) {
            $content.scrollTop( functionsDB.storage.session[ 'scroll_top_' + reportID ] );
        }
    });

    $('.datablock-wrapper').each(function() {
        functionsDB.selection.load( this );
    });

    $('.datablock-wrapper').each(function() {
        functionsDB.filterManager.restoreFilterFromBrowserStorage( $(this).attr('data-report-id') );
        functionsDB.showVisibleRowCount(this);
    });

});

functionsDB.initStorage = function() {
    functionsDB.storage = new ObjectStorage('JCMS_DB_storage');
};

///////////////////////////////////////////////////////////////////////////////////////////////////
// selection works
///////////////////////////////////////////////////////////////////////////////////////////////////

// TAG_TODO записать в мануал - чекбоксовый инпут должен быть дочерним элементом ячейки, без оберток

functionsDB.selection = {

    /**
     * save row selection to local storage
     */
    save : function(target) {
        functionsDB.initStorage();
        var checked = [];
        $(target).closest('.datablock-wrapper').find('.datablock-content>table>tbody>tr>td:nth-child(1)>input:checked').each(function() {
            checked.push( this.parentNode.parentNode.getAttribute('data-row-id') );
        });
        functionsDB.storage.session.checkedList = checked;
        functionsDB.storage._save();
    },

    /**
     * restore row selection from local storage
     */
    load : function(target) {
        functionsDB.initStorage();
        var checked = functionsDB.storage.session.checkedList || [];

        $(target).closest('.datablock-wrapper').find('.datablock-content>table>tbody>tr>td:nth-child(1)>input').each(function() {
            this.checked = checked.indexOf(this.parentNode.parentNode.getAttribute('data-row-id')) >= 0 ? 'checked' : false;
            if (this.checked) {
                this.setAttribute('data-was-checked', 'yes');
            }
        });

        this.updateHeader(target);
    },

    /**
     * update header checkbox state depending on data
     */
    updateHeader : function(target) {

        var allChecked = true;
        var atLeastOneRow = false; // it should be faster than $().length as we don't need actual length
        var allCheckboxes = $(target).closest('.datablock-wrapper').find('.datablock-content tbody>tr>td:nth-child(1)>input[type="checkbox"]');

        allCheckboxes.each(function() {
            atLeastOneRow = true;
            if ( ! this.checked ) {
                allChecked = false;
                return false;
            }
        });

        if (allChecked && atLeastOneRow) {
            $(target).closest('.datablock-wrapper').find('.datablock-header input[data-meaning="row-select-checkbox"]').get(0).checked = 'checked';
        } else {
            $(target).closest('.datablock-wrapper').find('.datablock-header input[data-meaning="row-select-checkbox"]').removeAttr('checked');
        }
    }

};


///////////////////////////////////////////////////////////////////////////////////////////////////
// filter engine
///////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Full resulting filter consist of some AND-merged groups, and every group
 * has some OR-combined filters. Every added filter MUST contain:
 *    1) its target (datablock) // TAG_TODO remove it - transfer manager to datablock-attached object
 *    2) group identifier string
 *    3) element identifier string
 *    4) filter definition (see somewhere aroud for description)
 *
 * definition = {
 *   fieldName : "field to apply filter to",
 *   condition : "may be 'contains', 'regexp' or 'checked' (fieldName ignored, applied to checkbox column)",
 *   value     : "some text value to check against"
 * }
 *
 * addFilter(
 *    report     : datablock ID to apply filter
 *    group      : string to itentify the filter group
 *    element    : filter element inside its group
 *    definition : see above
 *
 *
 * checkbox filter has condition set to 'checked', and value may be 'YES' to
 * show only checked rows, 'NO' to show unchecked and any other value to show all rows
 *
 */
functionsDB.filterManager = {

    filters : {},

    addFilter : function(report, group, element, definition) {
        if (typeof(this.filters[report]) == 'undefined') {
            this.filters[report] = {};
        }
        if (typeof(this.filters[report][group]) == 'undefined') {
            this.filters[report][group] = {
                type : 'or',
                elements : {}
            };
        }
        this.filters[report][group].elements[element] = definition;

        // console.log('filter added: '+report+'/'+group+'/'+element+'='+definition);
    },

    removeFilter : function(report, group, element) {
        if (typeof(this.filters[report]) == 'undefined') {
            return;
        }
        if (typeof(this.filters[report][group]) == 'undefined') {
            return;
        }
        delete(this.filters[report][group].elements[element]);

        // drop empty group
        if ($.isEmptyObject(this.filters[report][group].elements)) {
            delete(this.filters[report][group]);
        }

        // console.log('filter removed: '+report+'/'+group+'/'+element);
    },

    storeFilterInBrowserStorage : function(report) {
        this.buildFilterObject( report );
        functionsDB.storage.session[ 'filterManager' ] = JSON.stringify(this.filters);
    },

    restoreFilterFromBrowserStorage : function(report) {

        JCMS.background.lock();

        if (typeof(functionsDB.storage.session[ 'filterManager' ]) != 'string') {
            this.filters = { };
        } else {
            this.filters = JSON.parse(functionsDB.storage.session[ 'filterManager' ]);
        }

        if (typeof(this.filters[report]) != 'undefined') {
            var $target = $('.datablock-wrapper[data-report-id="'+report+'"]');

            if (typeof(this.filters[report]['built-in-text-filter']) != 'undefined') {
                $target.find('input[data-meaning="simple-filter-box"]').val( this.filters[report]['built-in-text-filter'].elements['manual'].value );
            }

            if (typeof(this.filters[report]['built-in-calendar-filter']) != 'undefined') {
                $target.find('input[data-meaning="simple-calendar"]').val( this.filters[report]['built-in-calendar-filter'].elements['manual'].value );
            }

            if (typeof(this.filters[report]['built-in-checked-filter']) != 'undefined') {
				var checkedFilterCheckbox = $target.find('input[data-meaning="only-checked-filter"]').get(0);
				if (checkedFilterCheckbox && checkedFilterCheckbox.checked) {
					this.filters[report]['built-in-checked-filter'].elements['one_and_only'].value == 'YES';
				}
            }

            for (group in this.filters[report]) {
                $group = $target.find('[data-filter-group="'+group+'"]');
                if ($group.length > 0) {
                    for (button in this.filters[report][group].elements) {
                        $group.find('[data-filter-name="'+button+'"]').get(0).checked = true;
                    }
                }

            }
            this.applyFilter(report);
        }

        this.updateControlState(report);
        JCMS.background.release();
    },

    buildFilterObject : function(report) {

        $target = $('.datablock-wrapper[data-report-id="'+report+'"]');

        // build filter array first
        this.filters = {};
        this.filters[report] = {};

        // text filter
        var textFilterValue = $target.find('input[data-meaning="simple-filter-box"]').val();
        if (textFilterValue > '') {
            this.addFilter(
                $target.attr('data-report-id'),
                'built-in-text-filter',
                'manual',
                {
                    fieldName : '*',
                    condition : 'contains',
                    value     : textFilterValue
                }
            );
        } else {
            this.removeFilter($target.attr('data-report-id'), 'built-in-text-filter', 'manual');
        }

        // calendar filter
        var $caledarFilterInput = $target.find('input[data-meaning="simple-calendar"]');
        var calendarFilterValue = $caledarFilterInput.val();
        if (calendarFilterValue > '') {
            this.addFilter(
                $target.attr('data-report-id'),
                'built-in-calendar-filter',
                'manual',
                {
                    fieldName : $caledarFilterInput.attr('data-field-name'),
                    condition : 'starts',
                    value     : calendarFilterValue
                }
            );
        } else {
            this.removeFilter($target.attr('data-report-id'), 'built-in-calendar-filter', 'manual');
        }

        // buttons
        $target.find('.datablock-filter-buttons').find('input:checked').each(function() {
            functionsDB.filterManager.addFilter(
                $target.attr('data-report-id'),
                $(this).closest('[data-filter-group]').attr('data-filter-group'),
                $(this).closest('[data-filter-name]').attr('data-filter-name'),
                {
                    fieldName : $(this).attr('data-field-name'),
                    condition : $(this).attr('data-filter-condition'),
                    value     : $(this).attr('data-filter')
                }
            );
        });

        // checked-only filter
        var checkedFilterCheckbox = $target.find('input[type="checkbox"][data-meaning="only-checked-filter"]').get(0);
        if (checkedFilterCheckbox && checkedFilterCheckbox.checked) {
            functionsDB.filterManager.addFilter(
                $target.attr('data-report-id'),
                'built-in-checked-filter',
                'one_and_only',
                {
                    fieldName : '', // filter manager will ignore this string
                    condition : 'checked',
                    value     : 'YES'
                }

            );
        } else {
            functionsDB.filterManager.removeFilter(
                $target.attr('data-report-id'),
                'built-in-checked-filter',
                'one_and_only'
            );
        }

    },

    /**
     * Disable or enable some buttons, hide or show others
     */
    updateControlState : function(report) {
        var $target = $('.datablock-wrapper[data-report-id="'+report+'"]');
        $target.find('.datablock-header-control').each(function() {
            if ($.trim($(this).find('input').val()) > '') {
                $(this).find('[data-meaning="simple-filter-cleaner"]').removeAttr('disabled');
            } else {
                $(this).find('[data-meaning="simple-filter-cleaner"]').attr('disabled', 'disabled');
            }

        });
    },

    applyFilter : function(report) {

        this.updateControlState(report);
        this.buildFilterObject(report);

        var $target = $('.datablock-wrapper[data-report-id="'+report+'"]');


        rowCache = [];
        var filterSet = this.filters[report];

        if (typeof(filterSet) == 'undefined') {
            return;
        }

        $target.children('.datablock-content').children('table').children('tbody').find('tr').each(function() {
            var showThisRowGlobal = true;
            var showThisRowByFilter;
            var filterDef;
            var cell;
            var $this = $(this);
            var searchIn;

            // проверяем каждую группу фильтров
            for (filter in filterSet) {
                showThisRowByFilter = false;
                for (element in filterSet[filter].elements) {
                    filterDef = filterSet[filter].elements[element];
                    if (filterDef.fieldName == '*') {
                        searchIn = $this.text();
                    } else {
                        if (typeof(filterDef.cellIndex) == 'undefined') {
                            filterDef.cellIndex = $this.find('td[data-field-name="'+filterDef.fieldName+'"]').index();
                        }

                        searchIn = $this.children('td').eq(filterDef.cellIndex).text();
                    }
                    // TAG_TODO проверить быстродействие с кешем индекса и без оного
                    switch (filterDef.condition) {
                        case 'checked':
                            switch (filterDef.value) {
                                case 'YES':
                                    showThisRowByFilter = $this.children('td').first().children('input').get(0).checked;
                                    break;
                                case 'NO':
                                    showThisRowByFilter = ! $this.children('td').first().children('input').get(0).checked;
                                    break;
                                default:
                                    showThisRowByFilter = true;
                            }
                            break;
                        case 'contains':
                            if (searchIn.toUpperCase().indexOf( filterDef.value.toUpperCase() ) >= 0) {
                                showThisRowByFilter = true;
                                break;
                            }
                            break;
                        case 'not_contains':
                            if (searchIn.toUpperCase().indexOf( filterDef.value.toUpperCase() ) < 0) {
                                showThisRowByFilter = true;
                                break;
                            }
                            break;
                        case 'starts':
                            if (searchIn.toUpperCase().indexOf( filterDef.value.toUpperCase() ) == 0) {
                                showThisRowByFilter = true;
                                break;
                            }
                            break;
                        case 'regexp':
                            var searchRegExp = new RegExp( filterDef.value, 'i' ); // TAG_TODO тут регэксп создается каждый раз, надо кешировать
                            if (searchIn.toUpperCase().search( searchRegExp ) >= 0) {
                                showThisRowByFilter = true;
                                break;
                            }
                            break;
                    }
                }

                showThisRowGlobal = showThisRowGlobal && showThisRowByFilter;
                if (!showThisRowGlobal) {
                    break;
                }
            }

            if (showThisRowGlobal) {
                $this.css('display', 'table-row');
            } else {
                $this.css('display', 'none');
            }

        });

        functionsDB.showVisibleRowCount( $target );
    },

    applyFilterAndSave : function(report) {
        this.storeFilterInBrowserStorage(report);
        this.applyFilter(report);
    }


};

///////////////////////////////////////////////////////////////////////////////////////////////////
// hides rows without marks, shows marked
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.showCheckedOnly = function(source) {
    $checkboxes = $(source).closest('.datablock-wrapper').find('.datablock-content').find('[data-meaning="row-select-checkbox"]');

    if ($checkboxes.filter(':checked').length == 0) {
        displayError('no checked rows, all will be shown');
        $(source).closest('.datablock-wrapper').find('.datablock-content').find('tr').css('display', 'table-row');
    } else {
        $checkboxes.each(function() {
            $(this).closest('tr').css('display', $(this).get(0).checked ? 'table-row' : 'none' );
        });
    }
    functionsDB.showVisibleRowCount($(source).closest('.datablock-wrapper'));
};

functionsDB.showVisibleRowCount = function(_target) {

    var $target = $(_target);
    if (!$target.hasClass('datablock-wrapper')) {
        $target = $target.closest('.datablock-wrapper');
    }
    if (!$target.hasClass('datablock-wrapper')) {
        $target = $target.find('.datablock-wrapper');
    }
	var $countBox = $target.find('.datablock-row-count');

    var $allRows = $target.find('.datablock-content').find('tr');
    var totalCount = $allRows.length;
    var checkedCount = $allRows.find('td:nth-child(1)>input[type="checkbox"]:checked').length;
    var visibleCount = $allRows.filter('tr:visible').length;

	// checked
    $target.find('.datablock-row-count').find('[data-meaning="count-checked"]').html( checkedCount );
	if (checkedCount) {
		$countBox.find('[data-meaning="count-checked"]').closest('.datablock-row-count-hideshow').removeClass('datablock-row-count-hidden');
	} else {
		$countBox.find('[data-meaning="count-checked"]').closest('.datablock-row-count-hideshow').addClass('datablock-row-count-hidden');
	}
	
	// visible
    $countBox.find('[data-meaning="count-visible"]').html( visibleCount );
	if (visibleCount != totalCount) {
		$countBox.find('[data-meaning="count-visible"]').closest('.datablock-row-count-hideshow').removeClass('datablock-row-count-hidden');
	} else {
		$countBox.find('[data-meaning="count-visible"]').closest('.datablock-row-count-hideshow').addClass('datablock-row-count-hidden');
	}

};

///////////////////////////////////////////////////////////////////////////////////////////////////
// installs some plugins at the table
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.installFilterDialogEvents = function() {

    // general API buttons
    functionsDB.installAPIButtonHandlers('.filter-dialog', '*[data-meaning="api-button"]');

    // field selector
    $('.filter-dialog .filter-elem:not(.filter-elem-template) [data-meaning="field-selector"]').combobox();
    $('.filter-dialog .filter-elem:not(.filter-elem-template) [data-meaning="filter-type"]').combobox();

    // edit helpers: datetime picker
       $('.filter-dialog [data-control-type="datetime"]').datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'HH:mm:ss' });

    // deletion buttons
    $('.filter-dialog .filter-elem-remove').on('click', function() {
        var $elem = $(this).closest('.filter-elem');

        // send deletion request if existing filter
        if ( ! $elem.hasClass('filter-elem-unsaved')) {
            callAPI(
                'filter_clear_one',
                {
                    reportId               : $elem.closest('.filter-current').attr('data-report-id'),
                    handleResult           : false
                },
                {
                    after : function() {
                        $elem.remove();
                    }
                },
                {
                    api_param_filter_index : $elem.attr('data-filter-index')
                }
            );
            $elem.closest('.ui-dialog-content').on('dialogclose', function () { location.reload(); } );
        } else {
            $elem.remove();
        }
    });

    //
    $('.filter-dialog .filter-elem-add').on('click', function() {

        // ok, clone myself excluding clone button. It can be only one!
        // and place it at the filter list end
        $template = $(this).closest('.filter-elem-template');
        $newElem = $template.clone(true);
        $newElem.insertBefore($template);

        $newElem.removeClass('filter-elem-template');
        $newElem.addClass('filter-elem-unsaved');
        $newElem.find('.filter-elem-add').remove();

        // create combos at new copy. Cannot do it at template as it leads to sttrange behavior
        $newElem.find('[data-meaning="field-selector"]').combobox();
        $newElem.find('[data-meaning="filter-type"]').combobox();

        // now change internal "new" id to something other
        var newIdent = 'filter-elem-new-'+$('.filter-elem').length;

        $newElem.find('[data-meaning="field-selector"]').attr('name', newIdent + '-field');
        $newElem.find('[data-meaning="filter-type"]').attr('name', newIdent + '-type');
        $newElem.find('[data-meaning="filter-param"]').attr('name', newIdent + '-param');
    });


};

/**
 * installs handlers at api-related buttons
 */
functionsDB.installAPIButtonHandlers = function( _target, _selector ) {

    $(_target).find(_selector).on('click', function() {

        functionsDB.storage.contextMenuSource = $(this);
        var APIMethod;
        var override = {};
        var params = {};
        var attrs = [];

        if ($(this).attr('data-direct-api-method') > '') {
            APIMethod = $(this).attr('data-direct-api-method');
            override = { direct : true };
        } else {
            APIMethod = $(this).attr('data-api-method');
        }

        attrs = this.attributes;
        for (var i = 0; i < attrs.length; i++) {
            if (attrs[i].name.search('data-api-param-') == 0) {
                params[ attrs[i].name.replace('data-', '').replace(/\-/g, '_') ] = attrs[i].value;
            }
        }

        APICallbacks = {};
        if ($(this).attr('data-api-after') > '') {
            APICallbacks.after = functionsDB.createComplexCallPath( $(this).attr('data-api-after') );
        }


        if (APIMethod > '') {
            callAPI(APIMethod, override, APICallbacks, params);
        }

    });

};

///////////////////////////////////////////////////////////////////////////////////////////////////
// installs some plugins at the table
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.installTablePlugins = function(_target) {

    // general API buttons
    var $target = $(_target);
    if (!$target.hasClass('datablock-wrapper')) {
        $target = $target.find('.datablock-wrapper');
    }
    // attach click listeners to buttons
    this.installAPIButtonHandlers($target, '*[data-meaning="api-button"]');

    // handle checkboxes
    $('.datablock-header input[data-meaning="row-select-checkbox"]').on('click', function(event) {
        event.stopPropagation();

        // count hidden-and-checked
        var hiddenCount = 0;
        var hiddenCheckedCount = 0;
        $(this).closest('.datablock-wrapper').find('.datablock-content>table>tbody>tr:hidden').each(function() {
            hiddenCount++;
            if ($(this).children('td').eq(0).children('input[type="checkbox"]').get(0).checked) {
                hiddenCheckedCount++;
            }
        });

        // this is much faster than doing it separately inside "each" loop
        if (this.checked) {
            $(this).closest('.datablock-wrapper').find('.datablock-content>table>tbody>tr:visible>td:nth-child(1)>input[type="checkbox"]').prop('checked', 'checked');
        } else {
            $(this).closest('.datablock-wrapper').find('.datablock-content>table>tbody>tr:visible>td:nth-child(1)>input[type="checkbox"]').removeProp('checked');
        }
        functionsDB.selection.save(this);

        if (hiddenCount > 0) {
            popupMessageShow({ popupClass: 'popup-warning', message: 'Напоминаю: скрытых строк - '+hiddenCount+', из них отмечено '+hiddenCheckedCount});
        }
        functionsDB.showVisibleRowCount(this);

    });

    // prevent context menu on checkboxes
    $('.datablock-header input[data-meaning="row-select-checkbox"]').on('click', function(event) {
        event.stopPropagation();
    });
    $('.datablock-content td, .datablock-header td').on('click', 'input[data-meaning="row-select-checkbox"]', function(event) {
        event.stopPropagation();

        // also check/uncheck header checkbox
/*        
        if (
            $(this).closest('tbody').find('tr>td:nth-child(1)>input[type="checkbox"]:checked').length
            ==
            $(this).closest('tbody').find('tr>td:nth-child(1)>input[type="checkbox"]').length
        ) {
            $(this).closest('.datablock-wrapper').find('.datablock-header input[data-meaning="row-select-checkbox"]').get(0).checked = 'checked';
        } else {
            $(this).closest('.datablock-wrapper').find('.datablock-header input[data-meaning="row-select-checkbox"]').removeAttr('checked');
        }
*/
        functionsDB.showVisibleRowCount(this);
        functionsDB.selection.updateHeader(this);
        functionsDB.selection.save(this);
        sheduleRowRefilter($(event.target));        
    });

    var rowCheckboxClickTimeout;
    sheduleRowRefilter = function($target) {
        clearTimeout(rowCheckboxClickTimeout);
        rowCheckboxClickTimeout = setTimeout(function() {
            functionsDB.filterManager.applyFilterAndSave( $target.closest('.datablock-wrapper').attr('data-report-id') );
        }, 1500);
    };

    // quick search box
    $target.find('input[data-meaning="simple-search-box"]').each(function() {
        var params = {};
        if ($(this).attr('data-search-in')) {
            params.searchIn = $(this).attr('data-search-in');
        };
        if ($(this).attr('data-scroll-container')) {
            params.scrollContainer = $(this).attr('data-scroll-container');
        };
        $(this).simpleTableSearch(params);
           $(this).on('keyup', function() {
            functionsDB.filterManager.updateControlState($target.attr('data-report-id'));
        });
    });

    // calendars
    $target.find('input[data-meaning="simple-calendar"]').each(function() {
        $(this).datepicker({
            dateFormat    : 'yy-mm-dd',
            firstDay      : 1,
            beforeShowDay : function( date ) {
                var weekDay = date.getDay();
                var dateClass = weekDay == 0 || weekDay == 6 ? 'calendar-holiday' : ''
                return [true, dateClass, ''];
            },
            onSelect      : function(text, object) {
                functionsDB.filterManager.applyFilterAndSave( $target.attr('data-report-id') );
            }

        });
        $(this).on('keyup', function() {
            functionsDB.filterManager.applyFilterAndSave( $target.attr('data-report-id') );
        });
    });

    // filter cleaners
    $target.find('[data-meaning="simple-filter-cleaner"]').on('click', function() {
        var filterType = this.getAttribute('data-target-filter-type');
        var filterFieldName = this.getAttribute('data-target-field-name');

        var controlSelector = 'input[data-meaning="'+filterType+'"]' + (this.getAttribute('data-target-field-name') > '' ? '[data-field-name="'+this.getAttribute('data-target-field-name')+'"]' : '');
        var $filterControl = $(controlSelector).val('');
        functionsDB.filterManager.applyFilterAndSave( $target.attr('data-report-id') );
    });

    // quick filter box
    $target.find('input[data-meaning="simple-filter-box"]').on('keyup', function() {
        functionsDB.filterManager.applyFilterAndSave( $target.attr('data-report-id') );
    });
/*
    $target.find('input[data-meaning="simple-filter-box"]').each(function() {
        var params = {};
        if ($(this).attr('data-search-in')) {
            params.searchIn = $(this).attr('data-search-in');
            params.after = function() { functionsDB.showVisibleRowCount($target); };
        };
        $(this).simpleTableFilter(params);
    });
*/

    // filter buttons
    $target.find('.datablock-filter-buttons').find('input[type="checkbox"]').change(function() {
        functionsDB.filterManager.applyFilterAndSave( $target.attr('data-report-id') );
    });

    // filter informer popup
    $('.datablock-row-count').on('click', function() {
        var $popup = $(this).find('.datablock-buttons-popup');
        JCMS.background.lock({
            withLoader  : false,
            hideOnClick : true,
            onUnlock    : function() { $popup.css('display', 'none'); }
        });        
        $popup.css('display', $popup.css('display') == 'none' ? 'block' : 'none');
    });
    $('.datablock-buttons-popup').on('click', function(event) {
        event.stopPropagation();
    });

    // install sorter, resizer and scroller
    var containerHeight;
    var tableContentHeight;

    // column width and reorder master
    $target.simpleColumnResize();

    // simple sorter
    $target.simpleTableSorter();

    // syncronous scrolling of header and content tables
    var headerScroller = $target.find('.datablock-header');
    $target.find('.datablock-content').on('scroll', function() {
        headerScroller.scrollLeft($(this).scrollLeft());
        var reportID = $target.attr('data-report-id');
        functionsDB.storage.session[ 'scroll_left_' + reportID ] = $(this).scrollLeft();
        functionsDB.storage.session[ 'scroll_top_' + reportID ] = $(this).scrollTop();
    });

    if ($target.children('.datablock-content').length > 0) {

        // adjust content wrapper to its container height
        // case 1: container already has pre-defined size
        // case 2: just stretch the table down to the screen bottom
        if ($target.parent().hasClass('datablock-fixed-frame')) {
            tableContentHeight =
                $target.parent().outerHeight()
                - ( $target.children('.datablock-content').position().top - $target.parent().position().top )
                - parseInt($target.parent().css('border-bottom-width'))
                ;
            $target.find('.datablock-content').css('height', tableContentHeight + 'px');
        } else {

            // delay a bit - immediate calculating will fail sometime
            var $div = $target.find('.datablock-content');
            $div.css('overflow', 'hidden');

            // FFFFFFFFFUUUUUUUUUUUUUUUUUUUUUU
            // почему-то $div.offset().top может не сразу начать выдавать верное значение
            setTimeout(function() {
                    tableContentHeight = $(window).height() - $div.offset().top;
                    $div.css('height', tableContentHeight + 'px');
            }, 200);
            setTimeout(function() {
                    tableContentHeight = $(window).height() - $div.offset().top;
                    $div.css('height', tableContentHeight + 'px');
            }, 500);
            setTimeout(function() {
                    tableContentHeight = $(window).height() - $div.offset().top;
                    $div.css('height', tableContentHeight + 'px');
                    $div.css('overflow', 'auto');
            }, 1500);
        }

        // single row events
        // context menu
        var contextMenuEvent = $target.attr('data-config-context-button') == 'right' ? 'contextmenu' : 'click';
        $target.find('.datablock-content>table').children('tbody').children('tr').children('td').on(contextMenuEvent, function(event) {
            event.preventDefault();

            // special behavior on checkbox TDs
            if ( $(this).children('input[data-meaning="row-select-checkbox"]').length ) {
                $(this).children('input[data-meaning="row-select-checkbox"]').click();
                return true;
            }

            // is there menu allowed at all?
            if ($(this).closest('.datablock-content').attr('data-no-context-menu') == 'yes') {
                return false;
            }

            var rowID = $(this).closest('tr').attr('data-row-id');

            var prevRowID = false;
            var $prevRow;
            if (($prevRow = $(this).closest('tr').prev()).length) { prevRowID = $prevRow.attr('data-row-id'); }

            var nextRowID = false;
            var $nextRow;
            if (($nextRow = $(this).closest('tr').next()).length) { nextRowID = $nextRow.attr('data-row-id'); }

            functionsDB.storage.contextMenuSource = this; // store click event source (API caller needs this)
            var URL = compileURL(                         // generate context menu URL and show it
                functionsDB.storage.apiProxy,
                {
                    ajaxproxy   : 'db',
                    action      : 'contextmenu',
                    report_id   : $(this).closest('.datablock-wrapper').attr('data-report-id'),
                    row_id      : rowID,
                    field_name  : $(this).attr('data-field-name')
                },
                { cache_killer : true }
            );
            contextMenuShow(URL);
        });
    }

/*
    // also, some JSON-formatted data can come along HTML, accept it
    // TAG_TODO если будет два датаблока, то данные одного не сохранятся
    if ($target.children('.json_data').length > 0) {
        functionsDB.storage.tableData = JSON.parse($target.children('.json_data').text());
    }
*/

}

functionsDB.sendXLSRequest = function(source, additionalPostData) {
    var reportId = $(source).closest('.datablock-wrapper').attr('data-report-id');

    var IDList = '';
    var $wrapper = $(source).closest('.datablock-wrapper');
    $wrapper.find('.datablock-content>table>tbody>tr').each(function() {
        if ($(this).css('display') != 'none') {
            IDList += (IDList == '' ? '' : ', ') + $(this).attr('data-row-id');
        }
    });

    var gridLayout = [];
    $wrapper.find('.datablock-header>table>thead>tr').first().find('td, th').each(function() {
        gridLayout.push({ field : $(this).attr('data-field'), width : parseInt($(this).css('width')) });
    });

    var form = $('<form method="post" action=".?ajaxproxy=db&action=call_api&method=report_as_xlsx"></form>');
    form.append('<input type="hidden" name="id_list" value="'+IDList+'" />');
    form.append('<input type="hidden" name="layout" value="'+JSON.stringify(gridLayout).replace(/"/g, '&quot;')+'" />');
    form.append('<input type="hidden" name="report_id" value="'+reportId+'" />');

    if (typeof(additionalPostData) == 'object') {
        for (key in additionalPostData) {
            form.append('<input type="hidden" name="'+key+'" value="'+additionalPostData[key]+'" />');
        }
    }

    form.appendTo($('body')).submit();
    form.remove();
};

// if callback is something like some.object.function, we will need to do
// such strange action. See http://stackoverflow.com/questions/359788
functionsDB.createComplexCallPath = function(str) {

    // if callback is something like some.object.function, we will need to do
    // such strange action. See http://stackoverflow.com/questions/359788
    var afterAPIFunctionPath = str.split('.');
    var afterAPIFunctionContext = window;
    for (var i = 0; i < afterAPIFunctionPath.length - 1; i ++) {
        afterAPIFunctionContext = afterAPIFunctionContext[ afterAPIFunctionPath[i] ];
    }
    return afterAPIFunctionContext[ afterAPIFunctionPath.pop() ];
};

///////////////////////////////////////////////////////////////////////////////////////////////////
// submits a form to hidden iframe
//
// formToSubmit : form must have attribute "data-form-name" with this value
// sourceButton : button to be source for the event (primarily for adding waiter classes)
// messages     : error strings
//     messages.error   : on error
//     messages.timeout : on time got out
// callbacks    : function to call after error, success or timeout respectively
//
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.submitFormToIframe = function(formToSubmit, sourceButton, messages, callbacks) {

    // change some look
    $sourceButton = $(sourceButton);
    $sourceButton.attr('disabled', 'disabled');
    $sourceButton.addClass('button-with-loader');

    // wa cannot use form serialization as file sending is not serializable, the only way is to use submitting
    // hidden iframe will be used at target to ensure that response will be correctly handled
    var iframeID = 'save_iframe_'+getRandomSuffix();
    $('<iframe id="'+iframeID+'" name="'+iframeID+'"></iframe>').css({'display' : 'none'}).appendTo($('body'));

    // now get the form
    // formToSubmit parameter can be either string or jQuery object, check if first
    if (typeof(formToSubmit) == 'string') {
        // if string, check whether form already exists
        if ($('form[data-form-name="'+formToSubmit+'"]').length > 0) {
            formToSubmit = $('form[data-form-name="'+formToSubmit+'"]');
        } else if ($('*[data-form-container="'+formToSubmit+'"]').length > 0) {
            formContainer = $('*[data-form-container="'+formToSubmit+'"]');
            formContainer.wrap('<form action="." method="post" data-form-name="'+formToSubmit+'"></form>');
            formToSubmit = formContainer.closest('form');
        }
    } else {
        // form can point to some object, convert it to jQuery and wrap with form if required
        var testObject = $(formToSubmit);
        if (formToSubmit.get(0).tagName == 'form') {
            formToSubmit = testObject;
        } else {
            testObject.wrap('<form method="post" action="." data-form-name="'+testObject.attr('data-form-name')+'"></form>');
            formToSubmit = testObject.closest('form');
        }
    }

    formToSubmit.attr('method', 'post');
    formToSubmit.attr('target', iframeID);
    formToSubmit.attr('enctype', 'multipart/form-data');
    formToSubmit.submit();

    // now we should wait for server response and take some actions
    var readyTicks = 0;
    checkAnswerInterval = setInterval(function() {

        // check iframe content for status ("OK" or something else)
        var postResult = $('#'+iframeID).contents()[0].body.innerHTML;

        if (postResult > '') {
            clearInterval(checkAnswerInterval);
            if (postResult.substr(0, 2) == 'OK') {

                ////////////////////////////////////////////////////////////////////////////////////////////////////
                // TAG_TODO TAG_CRAZY
                // the following is absolute shit and should be refactored ASAP ////////////////////////////////////
                ////////////////////////////////////////////////////////////////////////////////////////////////////

                // some special treatment for comments form
                if ((formToSubmit.attr('data-form-name') == 'commentbox-adder-form') && ( $('[data-form-container="edit-dialog-form"]').length || $('[data-form-name="edit-dialog-form"]').length)) {
                    callAPI('comments_dialog', {
                        rowId     : $('[data-form-name="edit-dialog-form"]').find('input[name="row_id"]').val(),
                        container : $('div[data-meaning="comments-dynamic-box"]')
                    }, {
                        after: function() {
                            functionsDB.modifyCommentBoxInTemplates($('div[data-meaning="comments-dynamic-box"]').find('.commentbox-outer-wrapper'));
                        }
                    });
                } else {

                    callbacks.success(postResult);

                }


                // TAG_TODO сделать перезагрузку только контейнера
            } else {
                alert(messages.error+'\r\nОтвет сервера:\r\n'+postResult);
                callbacks.error(postResult);
            }
        }

        // if timed out, just inform user and reload the page
        if (readyTicks++ > 60) {
            clearInterval(checkAnswerInterval);
            if (messages.timeout > '') {
                alert(messages.timeout);
            }
            callbacks.timeout();
            return;
        }

    }, 500);

}

///////////////////////////////////////////////////////////////////////////////////////////////////
// displays a context menu, AJAX-received
///////////////////////////////////////////////////////////////////////////////////////////////////
contextMenuShow = function(URL) {

    contextMenuHide();

    var menuMargin = 3; // margin between menu and screen borders
    JCMS.background.lock({
        withLoader  : false,
        hideOnClick : true,
        onUnlock    : function() { contextMenuHide(); }
    });

    $
        .get(URL)
        .done(function(result) {
            if (result.length > 0) {
                // mark click source
                var sourceRow = $(functionsDB.storage.contextMenuSource);
                if (sourceRow.length) {
                    sourceRow.closest('tr').addClass('db-menu-source-row');
                };

                // show menu and apply handlers
                var menuDiv = $('<div class="context_menu"></div>')
                    .appendTo($('body'))
                    .html(result)
                    .offset({
                        top  : Math.min(mouseY, $(window).height() + $(window).scrollTop()  - menuMargin - $('.context_menu').height()),
                        left : Math.min(mouseX, $(window).width()  + $(window).scrollLeft() - menuMargin - $('.context_menu').width() )
                    })
                ;
                menuDiv.find('tr').on('click', function() {
                    contextMenuHide();

                    var APICallbacks = {};
                    if ($(this).attr('data-after-api') > '') {
                        APICallbacks.after = functionsDB.createComplexCallPath( $(this).attr('data-after-api') );
                    }

                    if ($(this).attr('data-call-api')) {
                        callAPI($(this).attr('data-call-api'), {}, APICallbacks);
                    }
                });
            } else {
                contextMenuHide();
            }
            JCMS.background.removeLoader();

        })
        .fail(function() {
            displayError('error displaying menu. please reload the page');
            JCMS.background.release();
        })
        ;
}


///////////////////////////////////////////////////////////////////////////////////////////////////
//
// hides the menu and background if required
//
///////////////////////////////////////////////////////////////////////////////////////////////////
contextMenuHide = function() {
    $('.context_menu').remove();
    $('.db-menu-source-row').removeClass('db-menu-source-row');
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// create request string from URL and parameters
//
///////////////////////////////////////////////////////////////////////////////////////////////////
compileURL = function(URL, parameters, options, directParams) {

    options = options || {};
    var directParamString = '';

    switch (typeof(directParams)) {
        case 'object':
            directParamString = '';
            for (var elem in directParams) {
                directParamString += (directParamString ? '&' : '') + elem + '=' + directParams[elem];
            }
            break;
        case 'string':
            directParamString = directParams;
            break;
    }

    var request = '';

    // combine all parameters into string
    for (paramName in parameters) {
        request += (request > '' ? '&' : '?') + paramName + '=' + parameters[paramName];
    }

    // caching-prevention feature
    if (options.addCacheKiller === true) {
        request += (request > '' ? '&' : '?') + 'killcache' + String(Math.random()).substr(2);
    }

    // add explicitly set data
    if (directParamString) {
        request += (request > '' ? '&' : '?') + directParamString;
    }

    return URL + request;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// API calls "command" result handler
//
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.handleCommand = function(headers) {

    var data = headers.command.split(' ');

    switch (data[0]) {
        case 'reload':
            JCMS.background.lock();
            location.reload();
            break;
        case 'href':
            location.href = data[1];
            break;
    }

    return true;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// mass deleter
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.deleteSelected = function() {

    // TAG_TODO сделать удаление только из одной таблицы (сейчас из всех на экране!)

    var rowSelector = 'tr>td:first-child>input:checked';
    if ($(rowSelector).length == 0) {
        return;
    }

    var reportId = $('.datablock-wrapper').attr('data-report-id');

    massOperator({
        URLTemplate         : './?ajaxproxy=db&action=call_api&report_id='+reportId+'&method=record_delete&row_id=%row_id%',
        before              : function() { return confirm('Удалить отмеченные строки?'); },
        after               : function() { alert('Готово!'); location.reload(); },
        datablockWrapper    : '[data-report-id="'+reportId+'"]',        // TAG_TODO вот это вообще бред - определять датаблок по коду, определенному ранее по датаблоку же
        rowElementSelector  : rowSelector,
        findCaptionSelector : 'td[data-field-name="address_book_name"]' // TAG_TODO имя поля заголовка стираемого предмета нужно вынести из модуля в настройки репорта

    });
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// general API caller
//
// override.reportId     : replace click source's report ID with this value
// override.rowId        : we can also force other source row
// override.fieldName    : and even field
// override.container    : box to place content. Will be created automatically if not specified
// override.handleResult : allow or not built-in response handler
//
// callbacks.before      : NOT REALIZED
// callbacks.after       : callable, will be called after API call performed and processed (dialog boxes and so on)
//
///////////////////////////////////////////////////////////////////////////////////////////////////
callAPI = function(methodName, override, callbacks, directParams) {

    var source = $(functionsDB.storage.contextMenuSource);
    var encodedCommonData = source.closest('.datablock-wrapper').attr('data-user-data');

    // default options
    var options = {
        reportId   : source.closest('.datablock-wrapper').attr('data-report-id'),
        rowId      : source.closest('tr').attr('data-row-id'),
        fieldName  : source.attr('data-field-name'),
        direct     : false,
        container  : undefined,
        commonData : typeof(encodedCommonData) == 'undefined' ? '' : encodedCommonData,
        handleResult : true
    };
    $.extend(options, override);

    var URL = compileURL(
        functionsDB.storage.apiProxy,
        {
            ajaxproxy   : 'db',
            action      : 'call_api',
            method      : methodName,
            report_id   : options.reportId,
            row_id      : options.rowId,
            field_name  : options.fieldName,
            common_data : options.commonData
        },
        {},
        directParams
    );

    // special mode, no processing, just make bridge to a browser
    if (options.direct) {
        location.href = URL;
        return;
    }

    JCMS.background.lock();
    $.get(URL)
        .done(function(result, status, jqXHR) {
            JCMS.background.release();
            if (options.handleResult) {
                JCMS.DB.API.handleResponse(result, status, jqXHR, options);
            }
        })
        .fail(function(status, textStatus) {
            alert(
                status.responseText+' '+ // text part of result
                status.status+' '+       // HTTP code
                status.statusText        // canonical code text (i.e., "Not found" for 404)
            );
            JCMS.background.release();
            return false;
        })
        .always(function() {
            if ((typeof(callbacks) != 'undefined') && (typeof(callbacks.after) == 'function')) {
                callbacks.after();
            }
        })
    ;

}