<?php //> Й <- UTF mark

/*

common DB working - data change, table generating and so on

(!) this module can use separate database connection

*/
require_once('lib/PDOWrapper.class.php');
require_once('DataSource.class.php');
require_once('api.php');
require_once('ui.php');
require_once('helpers.php');
require_once('phpexcel/PHPExcel.php');
require_once('phpexcel/PHPExcel/Shared/ZipArchive.php');

CMS::$R['db_userapi_functions'] = array();

$userapi_files = scandir('userfiles/_data_modules/db/userapi/');

foreach ($userapi_files as $userapi_file) {
    if (pathinfo($userapi_file, PATHINFO_EXTENSION) == 'php') {
        include_once('userfiles/_data_modules/db/userapi/'.$userapi_file);
        Logger::instance()->log('user API added: userfiles/_data_modules/db/userapi/'.$userapi_file, Logger::LOG_LEVEL_DEBUG);
    }
}

class J_DB extends JuliaCMSModule {

    /**
     * These constants indicates external save-helper function return statuses
     *
     * @const SAVE_HELPER_OK           ok, continue normal flow
     * @const SAVE_HELPER_OK_AND_LAST  ok, I did all by myself, no more actions please
     * @const SAVE_HELPER_FAILED       failed, cancel
     */
    const SAVE_HELPER_OK = 0;
    const SAVE_HELPER_OK_AND_LAST = 1;
    const SAVE_HELPER_FAILED = 2;

    /**
     * Field types
     *
     * @const FIELD_TYPE_INT
     * @const FIELD_TYPE_TEXT
     * @const FIELD_TYPE_DATETIME
     * @const FIELD_TYPE_REAL
     * @const FIELD_TYPE_BIT
     * @const FIELD_TYPE_ENUM
     * @const FIELD_TYPE_BIGTEXT
     * @const FIELD_TYPE_PICTURE
     * @const FIELD_TYPE_BLOB
     * @const FIELD_TYPE_UNKNOWN
     */
    const FIELD_TYPE_INT      =  1; //
    const FIELD_TYPE_TEXT     =  2;
    const FIELD_TYPE_DATETIME =  3;
    const FIELD_TYPE_REAL     =  4;
    const FIELD_TYPE_BIT      =  7;
    const FIELD_TYPE_ENUM     =  8; // possible values should be listed as array('value1', ...) and will be stores as values
    const FIELD_TYPE_BIGTEXT  =  9;
    const FIELD_TYPE_PICTURE  = 10; // really text, type is for calling filemanager when editing
    const FIELD_TYPE_LIST     = 11; // values are array('key1'=>'value1', ...), stores as keys
    const FIELD_TYPE_BLOB     = 20;
    const FIELD_TYPE_UNKNOWN  = 99;

    /**
     * Regular expressions for input filtering
     *
     * @const REGEXP_TEXT_STRICT
     * @const REGEXP_TEXT
     * @const REGEXP_IDENTIFIER
     * @const REGEXP_ANYTEXT
     * @const REGEXP_INT
     * @const REGEXP_FLOAT
     * @const REGEXP_GUID
     * @const REGEXP_EMAIL
     * @const REGEXP_PHONE
     * @const REGEXP_FILENAME
     * @const REGEXP_HOST
     * @const REGEXP_DATETIME
     * @const REGEXP_IP
     */
    const REGEXP_TEXT_STRICT  = '[a-zA-Zа-яА-Я\s.,()0-9\–\«\»\№]*';
    const REGEXP_TEXT         = '[a-zA-Zа-яА-Я\s.,()0-9\–\«\»\№<>!@#$%^&*()\-+_;:\~?"={}\/\[\]|]*';
    const REGEXP_IDENTIFIER   = '[a-zA-Z_][a-zA-Z0-9_]*';
    const REGEXP_ANYTEXT      = '[a-zA-Zа-яА-Я\s.,()0-9<>!@#$%^&*()\-\–\«\»\№<>+_;:\~?"={}\[\]|\\\/]*';
    const REGEXP_INT          = '[\-+]?[0-9]*';
    const REGEXP_FLOAT        = '[\-+]?[0-9.,]*(|[eE][\-+][0-9]+)';
    const REGEXP_GUID         = '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}';
    const REGEXP_EMAIL        = '(|[a-zA-Z0-9.\-_]+@[a-zA-Z0-9.\-_]+)';
    const REGEXP_PHONE        = '[()0-9.,\s\-+]*';
    const REGEXP_FILENAME     = '[a-zA-Zа-яА-Я0-9\-.,\s]*';
    const REGEXP_HOST         = '[a-zA-Z0-9.\-:/]*';
    const REGEXP_DATETIME     = '[0-9\-/:.\s]*';
    const REGEXP_IP           = '(|(25[0-5]|2[0-4]\d|[01]?\d\d?\.){3}25[0-5]|2[0-4]\d|[01]?\d\d?)';

    /**
     * Database connection storage
     *
     * @var PDOWrapper
     */
    var $DB;

    /**
     * Common DB actions such as adding/editing/deleting records, context menu calls and some more useful things
     *
     * possible array items:
     * the first group is responsoble for visual action representation
     *
     *     caption    : item caption. Divider will be created if empty, links and scripts will be ignored
     *     image      : image to display left to the caption
     *     disabled   : if set to true, item will be shown as disabled. Special class (see XSLT stylesheet) will be
     *                  added and all parameters except caption and icon will be ignored
     *  type       : button, input or something else. supported values: button (default), checkbox
     *     style      : direct style definition 2(i.e., "color: blue;")
     *     class      : CSS class to add
     *     hidden     : item will not be included in the output at all
     *
     * the second group is responsible for proper client-server interaction
     *     link       : will create javascript onclick="location.href=''". Has the lowest priority (see below).
     *                  Note that "http://" prefix is required on absolute links
     *     js         : direct javascript for onclick event. Overrides "link" element
     *     api        : force client-side script to make API call with this method name. Will override "link" and "js" items
     *     direct-api : same as api, except that API call results will be redirected directly to the browser, and will
     *               not be processed by scripts. Useful for XL-exporting calls
     *
     * the third group defines HTML generators and API handlers
     *
     * @name $actions
     */
    public static $actions = array(

        // just a sample action with all parameters possible
        'sample_action' => array(
            'caption'       => 'Sample item',
            'image'         => 'modules/db/images/pencil.png',
            'disabled'      => false,
            'class'         => 'test_class',
            'style'         => 'color: red;',
            'hidden'        => false,
            'link'          => 'http://ya.ru',
            'js'            => 'alert("OK");',
            'api'           => 'some_api_call'
        ),

        // page - rewind to first
        'page_first' => array(
            'caption'       => '',
            'image'         => 'modules/db/images/page_first.png',
            'api'           => 'page_first',
        ),
        // page - prev
        'page_prev' => array(
            'caption'       => '',
            'image'         => 'modules/db/images/page_prev.png',
            'api'           => 'page_prev',
        ),
        // page - next
        'page_next' => array(
            'caption'       => '',
            'image'         => 'modules/db/images/page_next.png',
            'api'           => 'page_next',
        ),
        // page - forward to the last
        'page_last' => array(
            'caption'       => '',
            'image'         => 'modules/db/images/page_last.png',
            'api'           => 'page_last',
        ),

        // add an empty record (no questions)
        'record_add_empty' => array(
            'caption'       => 'Вставить пустую запись',
            'image'         => 'modules/db/images/blank.png',
            'api'           => 'record_add_empty',
        ),

        // add an empty record (no questions)
        'record_add' => array(
            'caption'       => 'Новая запись',
            'image'         => 'modules/db/images/blank.png',
            'api'           => 'record_add',
        ),

        // show edit dialog
        'record_edit' => array(
            'caption'       => 'Редактировать',
            'image'         => 'modules/db/images/pencil.png',
            'api'           => 'record_edit',
        ),

        // edit a single value in dialog
        'edit_here' => array(
            'caption'       => 'Изменить...',
            'image'         => 'modules/db/images/pencil.png',
            'api'           => 'change_field',
        ),

        // delete record, no questions
        'record_delete' => array(
            'caption'       => 'УДАЛИТЬ БЕЗ ВОПРОСОВ',
            'image'         => 'modules/db/images/red_cross_diag.png',
            'api'           => 'record_delete',
        ),

        // delete confirmation dialog
        'record_delete_confirm' => array(
            'caption'       => 'Удалить запись',
            'image'         => 'modules/db/images/red_cross_diag.png',
            'api'           => 'record_delete_confirm',
        ),

        // delete only selected rows
        'record_delete_selected' => array(
            'caption'       => 'Удалить отмеченные',
            'image'         => 'modules/db/images/red_cross_diag.png',
            'js'            => 'functionsDB.deleteSelected();',
        ),

        // show comments to the record
        'comments_dialog' => array(
            'caption'       => 'Комментарии и документы',
            'image'         => 'modules/db/images/comments.png',
            'api'           => 'comments_dialog',
        ),

        // return attached file
        'comments_get_attached' => array(
            'caption'       => 'Загрузить приложенный файл',
            'image'         => '',
            'api'           => 'comments_get_attached',
        ),

        // export report to excel file
        'report_as_xlsx' => array(
            'caption'       => 'Экспорт',
            'image'         => 'modules/db/images/excel.png',
            'js'            => 'functionsDB.sendXLSRequest(this);',
        ),

        // JS function - hide unchecked rows
        'filter_checked_only' => array(
            'caption'       => 'Только отмеченные',
            'image'         => 'modules/db/images/check_uncheck.png',
            'js'            => 'functionsDB.showCheckedOnly(this);',
        ),
        
        // filter by selected field/value
        'filter_by_selection' => array(
            'caption'       => 'Фильтр по этому значению',
            'image'         => '',
            'api'           => 'filter_by_selection',
        ),

        // filter by selected field/value
        'filter_clear_all' => array(
            'caption'       => 'Сброс фильтра',
            'image'         => '',
            'api'           => 'filter_clear_all',
        ),

        // filter by selected field/value
        'filter_clear_one' => array(
            'caption'       => 'Сброс фильтра',
            'image'         => '',
            'api'           => 'filter_clear_one',
        ),

        // filter by selected field/value
        'filter_dialog' => array(
            'caption'       => 'Фильтр...',
            'image'         => '',
            'api'           => 'filter_dialog',
            'after'         => 'functionsDB.installFilterDialogEvents',
        ),

    );

    /**
     * Mapping between external API names and internal methods
     *
     * @name $methods
     */
    private static $methods = array(
        'get_report_as_xml'          => array('class' => 'J_DB_API',  'method' => 'generateReportXML'),
        'page_first'                 => array('class' => 'J_DB_API',  'method' => 'pageFirst'),
        'page_prev'                  => array('class' => 'J_DB_API',  'method' => 'pagePrev'),
        'page_next'                  => array('class' => 'J_DB_API',  'method' => 'pageNext'),
        'page_last'                  => array('class' => 'J_DB_API',  'method' => 'pageLast'),
        'record_add_empty'           => array('class' => 'J_DB_API',  'method' => 'recordAddEmpty'),
        'record_add'                 => array('class' => 'J_DB_UI',   'method' => 'recordAdd'),
        'record_edit'                => array('class' => 'J_DB_UI',   'method' => 'recordEdit'),
        'generate_editorial_xml'     => array('class' => 'J_DB_API',  'method' => 'generateEditorialXML'),
        'generate_comments_xml'      => array('class' => 'J_DB_API',  'method' => 'generateCommentsXML'),
        'comments_dialog'            => array('class' => 'J_DB_UI',   'method' => 'commentsDialog'),
        'comments_add'               => array('class' => 'J_DB_API',  'method' => 'commentsAdd'),
        'comments_delete'            => array('class' => 'J_DB_API',  'method' => 'commentsDelete'),
        'record_delete_confirm'      => array('class' => 'J_DB_UI',   'method' => 'recordDeleteConfirm'),
        'record_delete'              => array('class' => 'J_DB_API',  'method' => 'recordDelete'),
        'record_insert'              => array('class' => 'J_DB_API',  'method' => 'recordInsert'),
        'record_save'                => array('class' => 'J_DB_API',  'method' => 'recordSave'),
        'comments_get_attached'      => array('class' => 'J_DB_API',  'method' => 'commentsGetAttached'),
        'report_as_xlsx'             => array('class' => 'J_DB_API',  'method' => 'reportToExcel'),
        'filter_by_selection'        => array('class' => 'J_DB_API',  'method' => 'filterBySelection'),
        'filter_clear_all'           => array('class' => 'J_DB_API',  'method' => 'filterClearAll'),
        'filter_clear_one'           => array('class' => 'J_DB_API',  'method' => 'filterClearOne'),
        'filter_dialog'              => array('class' => 'J_DB_API',  'method' => 'filterDialog'),
        'filter_apply_mass'          => array('class' => 'J_DB_API',  'method' => 'filterApplyMass'),
    );

    /**
     * Input filter both for AJAX handler and input filter
     * User API function also will filter input themselves
     *
     * @name $input_filter
     */
    public static $input_filter = array(
        'action'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui')),
        'method'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui')),
        'report_id'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui')),
        'row_id'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\-.]+$~ui')),
        'field_name' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9а-яА-ЯёЁ/+_=]+$~ui'))
    );

    /**
     *
     */
    function __construct() {
        $this->DB = $this->createPDOWrapper();
    }

    /**
     *
     *
     */
    private static function createPDOWrapper() {
        $DB = new PDOWrapper(
            CMS::$cache['db']['config']['config']['server_driver']['value'],
            CMS::$cache['db']['config']['config']['server_host'],
            CMS::$cache['db']['config']['config']['server_login'],
            CMS::$cache['db']['config']['config']['server_password'],
            CMS::$cache['db']['config']['config']['server_db_name']
        );
        Logger::instance()->log('database connection initiated (but not connected yet)!', Logger::LOG_LEVEL_DEBUG);
        return $DB;
    }

    /**
     * Standard descendant
     *
     * able to call user API
     */
    function requestParser($template) {

        // use both POST and GET!
        $merged_post_get = array_merge($_GET, $_POST);

        if (!isset($merged_post_get['module']) || ($merged_post_get['module'] != 'db')) {
            return $template;
        }

        // will redirect at the end if "target" become non-empty
        $redirect_target = false;

/*******************************************************************************************************/
        // TAG_TODO why calling API at request parser?
/*******************************************************************************************************/

        // add field filters if report specified
        if (isset($merged_post_get['report_id']) && isset($this->REG['db_api_reports'][$merged_post_get['report_id']])) {
            foreach ($this->REG['db_api_reports'][$merged_post_get['report_id']]['fields'] as $field_part1 => $field_part2) {
                $field_definition = $this->getFullFieldDefinition($field_part1, $field_part2);
                $this->input_filter['edit_' . $field_definition['field']] = array(
                    'filter'  => FILTER_VALIDATE_REGEXP,
                    'options' => array('regexp' => '~^' . $field_definition['regexp'] . '$~msu')
                );
            }
        }

        // note that full filtering is used here as API functions may require unlimited parameter list
        $filtered_input = get_filtered_input(self::$input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));

        // call API and check if any special flags there
        $return_metadata = array();

        $this->callAPI($filtered_input, $return_metadata);

        if (($return_metadata['type'] == 'command') && ($return_metadata['command'] == 'reload')) {
            $redirect_target = $_SERVER['HTTP_REFERER'];
        }
/*******************************************************************************************************/

        // make redirection if was requested above
        if ($redirect_target) {
            terminate('', 'Location: '.$redirect_target, 302);
        }

        return $template;
    }


    /**
     * Content generator - creates table-formed report
     *
     */
    function contentGenerator($template) {

        // append userapi scripts and CSS
        if (is_dir('userfiles/_data_modules/db/js/')) {
            $user_js_files = scandir('userfiles/_data_modules/db/js/');
            foreach ($user_js_files as $user_js_file) {
                if (pathinfo($user_js_file, PATHINFO_EXTENSION) == 'js') {
                    add_JS('userfiles/_data_modules/db/js/'.$user_js_file);
                    Logger::instance()->log('user API script added: '.$user_js_file, Logger::LOG_LEVEL_DEBUG);
                }
            }
        }
        if (is_dir('userfiles/_data_modules/db/css/')) {
            $user_css_files = scandir('userfiles/_data_modules/db/css/');
            foreach ($user_css_files as $user_css_file) {
                if (pathinfo($user_css_file, PATHINFO_EXTENSION) == 'css') {
                    add_CSS('userfiles/_data_modules/db/css/'.$user_css_file);
                    Logger::instance()->log('user API CSS added: '.$user_css_file, Logger::LOG_LEVEL_DEBUG);
                }
            }
        }

        // replace all templates to generated content
        while (preg_match( macro_regexp('db'), $template, $match) > 0) {

            // parse template parameters into array
            $params = parse_plugin_template($match[0]);

            // generate HTML
            if (!isset($params['id'])) {
                $table_html = '<b>[JuliaCMS][db] error:</b> no ID specified for the table. Add id="report_id" to the macro.';
            } else {
                // all API/UI require "report_id" parameter
                $params['report_id'] = $params['id'];
                $table_html = J_DB_UI::generateTable($params, $this->DB);
            }

            // replace
            $template = str_replace($match[0], $table_html, $template);
        }

        // yeah we are ready
        return $template;

    }

    /**
     * AJAX requests handler
     *
     * nothing special - mainly API call
     */
    function AJAXHandler() {

        $filtered_input = get_filtered_input(self::$input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));

        switch($filtered_input['action']) {
            case 'contextmenu':
                $report_id  = $filtered_input['report_id'];
                $row_id     = $filtered_input['row_id']; // TAG_TODO вот тут нужен идентификатор
                $field_name = null; // TAG_TODO и тут нужен
                return J_DB_UI::generateContextMenu($report_id, $row_id, $field_name, $this->DB);
                break;
            case 'call_api':
                return $this->callAPI($filtered_input, $return_metadata);
                break;
            default:
                return 'error: bad action or not set at all';
                break;
        }
    }

    /**
     * Entry point for API, UI and USER-API methods.
     *
     * Default metadata values are:
     *    status : OK, type : plain, command : ''
     * they will be added automatically, so you don't need to set all parameters.
     * The following metadata values are used:
     *    status  : OK or ERROR
     *    type    : content type (plain, html, xml, json, command)
     *    command : some command for AJAX receiver at client side
     * These metadata will be sent as additional headers along with the text answer. The headers
     * will be "X-JuliaCMS-Result-Status", "X-JuliaCMS-Result-Type" and "X-JuliaCMS-Result-Status"
     * respectively.
     *
     * @param array $input data to use for calling deeper (for example, _GET contents may be placed here)
     * @param array &$return_metadata metadata describing result operation (status, error text etc.)
     * @param resource $DB (optional) database connection to use. If not specified, instance's connection will be used
     * @return string API output. May be either something like "OK" as "successful" or data of any kind
     */
    public static function callAPI($input, &$return_metadata, $DB = false) {

        // start with empty metadata
        $return_metadata = array();

        // we need exactly one pass but with ability to break anywhere from it
        do {

            // determine API method name
            if (!isset($input['method'])) {
                $return_metadata['status'] = 'ERROR';
                $result = 'API ERROR : method not specified ('.__LINE__.')';
                break;
            }
            $method_name = $input['method'];

            // TAG_CRAZY code SDFKLGHDFKLGHDFGJKLDFHSGKLDFHGFJKLGDFJG
            $methods = array_merge(self::$methods, UserLogic::$methods);

            // check if method definition exists
            if (!isset($methods[$method_name])) {
                $return_metadata['status'] = 'ERROR';
                $result = 'API ERROR : unknown method "'.$method_name.'" (line '.__LINE__.')';
                break;
            }
            $method = $methods[$method_name];

            // check if class exists (good idea for userland API)
            if (!class_exists($method['class'])) {
                $return_metadata['status'] = 'ERROR';
                $result = 'API ERROR : method class "'.$method['class'].'" not exists ('.__LINE__.')';
                break;
            }
            $method_class = $method['class'];

            // check if class method exists
            if (!method_exists($method['class'], $method['method'])) {
                $return_metadata['status'] = 'ERROR';
                $result = 'API ERROR : method "'.$method['method'].'" not exists ('.__LINE__.')';
                break;
            }
            $method_method = $method['method'];

            // all OK and we can call user API
            if (!$DB) {
                $DB = self::createPDOWrapper();
            }
            $result = $method_class::$method_method($input, $return_metadata, $DB);

        } while (false);

        // add default metadata values
        $return_metadata_default = array('status'=>'OK', 'type'=>'plain', 'command'=>'');
        $return_metadata = array_merge($return_metadata_default, $return_metadata);

        // send our special headers
        header('X-JuliaCMS-Result-Status: '.$return_metadata['status']);
        header('X-JuliaCMS-Result-Type: '.$return_metadata['type']);
        header('X-JuliaCMS-Result-Command: '.$return_metadata['command']);

        return $result;

    }

}

// TAG_TODO как-то это ваще некрасиво выглядит, инклюды все эти
if (file_exists('userfiles/_data_modules/db/fields.php' )) { include_once('userfiles/_data_modules/db/fields.php');          }
if (file_exists('userfiles/_data_modules/db/reports.php')) { include_once('userfiles/_data_modules/db/reports.php');         }



?>