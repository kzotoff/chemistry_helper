<?php //> Й <- UTF mark

/*

all real database working
static methods only
all functions must take 3 parameters: input, output (metadata), database connection

*/

class J_DB_API {

    /**
     * Location of files attached to the comments
     *
     * @const COMMENTS_ATTACHED_DIR
     */
    const COMMENTS_ATTACHED_DIR = 'userfiles/_data_modules/db/attached/';

    /**
     * Filter types to be used
     * @var array[][]
     */
    public static $filter_types = array(
        'equals' => array(
            'caption' => 'равно',
            'display' => ' = ',
            'sql'     => '%1$s = %3$s%2$s%3$s',
        ),
        'not_equals' => array(
            'caption' => 'не равно',
            'display' => ' <> ',
            'sql'     => '%1$s <> %3$s%2$s%3$s',
        ),
        'contains' => array(
            'caption' => 'содержит',
            'display' => ' содержит ',
            'sql'     => '%1$s like \'%%%2$s%%\'',
        ),
        'not_contains' => array(
            'caption' => 'не содержит',
            'display' => ' не содержит ',
            'sql'     => 'not (%1$s like \'%%%2$s%%\')',
        ),
        'greater' => array(
            'caption' => 'больше',
            'display' => ' > ',
            'sql'     => '%1$s > %3$s%2$s%3$s',
        ),
        'lesser' => array(
            'caption' => 'меньше',
            'display' => ' < ',
            'sql'     => '%1$s < %3$s%2$s%3$s',
        ),
        'empty' => array(
            'caption' => 'пустое',
            'display' => ' пустое ',
            'sql'     => '(%1$s = \'\' or %1$s is null)',
        ),
        'not_empty' => array(
            'caption' => 'не пустое',
            'display' => ' не пустое ',
            'sql'     => '%1$s > \'\'',
        ),
    );

    /**
     * Returns all filters for the report
     */
    public static function getFilters($report_id) {

        if (
            isset($_SESSION['storage']['db'][$report_id]['filters'])
            &&
            is_array($_SESSION['storage']['db'][$report_id]['filters'])
        ) {
            return $_SESSION['storage']['db'][$report_id]['filters'];
        }
        return array();
    }



    /**
     * Checks if storage is created and creates if needed
     */
    private static function storageCheck() {
        if ( ! isset($_SESSION['storage'])) {
            $_SESSION['storage'] = array();
        }
        if ( ! isset($_SESSION['storage']['db'])) {
            $_SESSION['storage']['db'] = array();
        }
    }

    /**
     * Retrieves value from session storage
     */
    public static function storageGet($key, $default = null) {
        self::storageCheck();
        if (isset($_SESSION['storage']['db'][$key])) {
            return $_SESSION['storage']['db'][$key];
        }
        if (is_null($default)) {
//            trigger_error('no storage element "'.$key.'"', E_USER_WARNING);
        }
        return $default;
    }

    /**
     * Sets value to session storage
     */
    public static function storageSet($key, $value) {
        self::storageCheck();
        $_SESSION['storage']['db'][$key] = $value;
    }


    /**
     * Returns row count for the report
     *
     * @param mixed[] $report_config report definition
     * @param bool $filtered apply user-defined filter or not
     * @return int record count or -1 on failure
     */
    public static function getReportRowCount($report_config, $filtered = true) {

        module_init('db');
        $DB = CMS::$cache['db']['object']->DB;

        $options = array('no_limit' => true, 'no_order' => true);
        if ( ! $filtered) {
            $options['no_filter'] = true;
        }

        if (isset($report_config['data_generator'])) {
            return -1;
        }
        $sql = J_DB_Helpers::getReportMainSQL($report_config, $DB, $options);
        $row_count = $DB->querySingle('select count(1) as totalrecords from ('.$sql.') innertable');

        if (is_numeric($row_count)) {
            return $row_count;
        }
        return -1;
    }

    /**
     * Paging - first page
     */
    public static function pageFirst($input, &$return_metadata, $DB) {

        $report_result = self::getReportConfig($input, $report_id, $report_config);
        if ($report_result !== true) {
            $return_metadata['status'] = 'ERROR';
            return $config_result;
        }
        self::storageSet($report_id.'_page_start', 0);
        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Paging - prev page
     */
    public static function pagePrev($input, &$return_metadata, $DB) {

        $report_result = self::getReportConfig($input, $report_id, $report_config);
        if ($report_result !== true) {
            $return_metadata['status'] = 'ERROR';
            return $report_result;
        }

        $page_size = get_array_value($report_config, 'page_size', 0);
        if ($page_size == 0) {
            $return_metadata['status'] = 'ERROR';
            return 'bad page size';
        }

        self::storageSet($report_id.'_page_start', max(0, self::storageGet($report_id.'_page_start') - $page_size));
        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Paging - next page
     */
    public static function pageNext($input, &$return_metadata, $DB) {

        $report_result = self::getReportConfig($input, $report_id, $report_config);
        if ($report_result !== true) {
            $return_metadata['status'] = 'ERROR';
            return $config_result;
        }

        $page_size = get_array_value($report_config, 'page_size', 0);
        if ($page_size == 0) {
            $return_metadata['status'] = 'ERROR';
            return 'bad page size';
        }

        $row_count = self::getReportRowCount($report_config);

        // just take lesser of current+next and last possible
        self::storageSet($report_id.'_page_start', min( self::storageGet($report_id.'_page_start') + $page_size, (int)($row_count/$page_size)*$page_size ) );

        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Paging - next page
     */
    public static function pageLast($input, &$return_metadata, $DB) {

        $report_result = self::getReportConfig($input, $report_id, $report_config);
        if ($report_result !== true) {
            $return_metadata['status'] = 'ERROR';
            return $config_result;
        }

        $page_size = get_array_value($report_config, 'page_size', 0);
        if ($page_size == 0) {
            $return_metadata['status'] = 'ERROR';
            return 'bad page size';
        }

        $row_count = self::getReportRowCount($report_config);

        self::storageSet($report_id.'_page_start', (int)($row_count/$page_size)*$page_size );
        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Returns configuration data for report, based on input params
     *
     * $input may contain either inline entire definition or just report ID
     *
     * @param mixed[] $input
     * @param &$report_id report identifier found
     * @param &$report_config report configuration
     * @return true|string error text on failure or true on success
     */
    public static function getReportConfig($input, &$report_id, &$report_config) {

        if (isset($input['config'])) {
            $report_id = '*** inline report definition ***';
            $report_config = $input['config'];
        } else {
            if (!isset($input['report_id'])) {
                return 'no ID specified';
            }
            $report_id = $input['report_id'];

            if (!isset(CMS::$R['db_api_reports'][$report_id])) {
                return 'no report config for this ID ('.$report_id.')';
            }
            $report_config = CMS::$R['db_api_reports'][$report_id];
        }

        // some defaults
        if (!isset($report_config['builtin_buttons'])) {
            $report_config['builtin_buttons'] = array();
        }
        if (!isset($report_config['builtin_buttons']['fields'])) {
            $report_config['builtin_buttons']['fields'] = array();
        }
        return true;
    }

    /**
     * generates DOMDocument containing filtering information
     *
     * @param string $report_id
     * @return DOMDocument
     */
    public static function getFilterTypesDOM() {

        $dom = new DOMDocument('1.0', 'utf-8');

        $root = $dom->createElement('filter-types');
        $dom->appendChild($root);

        foreach (self::$filter_types as $filter_type => $filter_type_def) {
            $filter_type_node = $dom->createElement('filter-type');

            $filter_type_node->appendChild( $dom->createElement( 'type'    ))->nodeValue = $filter_type;
            $filter_type_node->appendChild( $dom->createElement( 'caption' ))->nodeValue = $filter_type_def['caption'];
            $filter_type_node->appendChild( $dom->createElement( 'display' ))->nodeValue = $filter_type_def['display'];

            $root->appendChild($filter_type_node);
        }
        return $dom;
    }

    /**
     * generates DOMDocument containing filtering information
     *
     * @param string $report_id
     * @return DOMDocument
     */
    public static function getFilterInfoDOM($report_id) {

        $result_array = array();

        foreach (self::getFilters($report_id) as $filter_index => $filter_def) {

            if ($field_definition = J_DB_Helpers::getFullFieldDefinition($filter_def['field'])) {

                $field_type = 'text';
                switch ($field_definition['type']) {
                    case J_DB::FIELD_TYPE_INT:
                    case J_DB::FIELD_TYPE_REAL:
                        $field_type = 'numeric';
                        break;
                        
                    case J_DB::FIELD_TYPE_DATETIME:
                        $field_type = 'datetime';
                        break;
                }
                $result_array[] = array(
                    'index' => $filter_index,
                    'field' => array(
                        'name'    => $filter_def['field'],
                        'type'    => $field_type,
                        'caption' => $field_definition['caption'],
                    ),
                    'filter' => array(
                        'type'    => $filter_def['type'],
                        'display' => self::$filter_types[$filter_def['type']]['display'],
                        'caption' => self::$filter_types[$filter_def['type']]['caption'],
                    ),
                    'param' => $filter_def['param'],
                );
            }
        }
        return array_to_xml($result_array, array('filter-info', 'filter-elem'));
    }

    /**
     * generates DOMDocument containing report's fields definitions
     *
     * @param string $report_id
     * @return DOMDocument
     */
    public static function getFieldsInfoDOM($report_id) {

        $result_array = array();
        self::getReportConfig(array('report_id' => $report_id), $return_report_id, $report_config);

        foreach ($report_config['fields'] as $field_info_1 => $field_info_2) {

            if ($field_definition = J_DB_Helpers::getFullFieldDefinition($field_info_1, $field_info_2)) {
                $result_array[] = array(
                    'field'    => $field_definition['field'],
                    'type'     => $field_definition['type'],
                    'caption'  => $field_definition['caption'],
                );
            }
        }
        return array_to_xml($result_array, array('fields-info', 'field-item'));
    }

    /**
     * generates XML data for the report specified
     *
     * parameters supported:
     *     id       : report identifier to generate XML for
     *     config   : direct report configuration
     *     checkbox : array with checkbox description. "class" and "name" elements are supported
     *
     * XML structure:
     * <report>
     *   <caption>Entire report caption</caption>
     *   <id_field>Field which should be ID of each data row</id_field>
     *   <json>visual enhancements data (see below)</json>
     *   <header>
     *     <field_caption>column caption for column 1</field_caption>
     *     ...
     *   </header>
     *   <data_set>
     *     <data_row>
     *       <data>value for row 1, field 1</data>
     *       ...
     *     </data_row>
     *     ...
     *   </data_set>
     *   <report_menu>
     *     ... report menu XML. see menu.php generator for comments, I don't want duplicate it here
     *   </report_menu>
     *
     *
     * <json> entry contains CDATA with the following JSON structure:
     * {
     *   "columns" : {           // columns descriptions
     *     "column1" : {         // field name
     *       "width" : 100       // field width
     *     }
     *   }
     * }
     *
     * @param array $params parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string XML string
     *
     */
    public static function generateReportXML($input, &$return_metadata, $DB) {

        $config_result = self::getReportConfig($input, $report_id, $report_config);

        if ($config_result !== true) {
            $return_metadata['status'] = 'ERROR';
            return $config_result;
        }

        // pre-generator
        // note that alghough $report_config is already assigned, it will be changed
        if ($before_generate = get_array_value($report_config, 'before_generate')) {
            if (is_callable($before_generate)) {
                $before_generate = array($before_generate);
            }
            foreach ($before_generate as $before_generate_function) {
                $before_generate_function($report_config);
            }
        }

        // check is checkbox was requested
        $checkbox = get_array_value($input, 'checkbox', false);

        // new datablock ID
        $block_id = create_guid();

        // some init
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $report_root = $xml->createElement('report');
        $xml->appendChild($report_root);

        $json = array();

        // get report main caption id menu link, don't forget report ID
        // record count will be added later
        $report_root->appendChild($xml->createElement('report_id'))          ->nodeValue = $report_id;
        $report_root->appendChild($xml->createElement('datablock_id'))       ->nodeValue = $block_id;
        $report_root->appendChild($xml->createElement('context-menu-button'))->nodeValue = CMS::$cache['db']['config']['config']['context_menu_button']['value'];
        $report_root->appendChild($caption_node = $xml->createElement('report_caption'))->nodeValue = get_array_value($report_config, 'caption', '');

        // add no-menu indicator if no context menu items defined
        if (!isset($report_config['context_menu']) || (count($report_config['context_menu']) == 0)) {
            $report_root->appendChild($xml->createElement('no-context-menu'))->nodeValue = 'yes';
        }

        // include menu_id into JSON
        $json['reportId']    = $report_id;
        $json['datablockId'] = $block_id;
        $json['caption']     = get_array_value($report_config, 'caption', '');

        // report fields captions. also create field definitions cache
        $field_captions = $xml->createElement('header');

        $json['columns'] = array();

        // cache all fields as getFullFieldDefinition may take a long time
        // TAG_TODO fill missing values with defaults to avoid using get_array_value
        $field_cache = array();

        // add checkbox head if requested
        if ($checkbox != false) {
            // column header info
            $field_caption = $xml->createElement('field_caption');
            $field_caption->setAttribute('special', 'checkbox');
            $field_caption->setAttribute('width', '30');
            $field_captions->appendChild($field_caption);
            if (is_array($checkbox) && isset($checkbox['class'])) {
                $field_caption->setAttribute('class', $checkbox['class']);
            }
            if (is_array($checkbox) && isset($checkbox['name'])) {
                $field_caption->setAttribute('name', $checkbox['name']);
            }
            $field_captions->appendChild($field_caption);
        }




        // now fields descriptions
        if (isset($report_config['fields']) && is_array($report_config['fields'])) {
            foreach($report_config['fields'] as $field_part_1 => $field_part_2) {

                $field = J_DB_Helpers::getFullFieldDefinition($field_part_1, $field_part_2);
                if (get_array_value($field, 'out_table', true) === true) {

                    // pre-calc enum_values if generated
                    if (isset($field['enum_values']) && is_callable($field['enum_values'])) {
                        $field['enum_values'] = $field['enum_values']();
                    }

                    // add to the cache to avoid these checks and merges while output data content
                    array_push($field_cache, $field);

                    // column header info
                    $field_caption = $xml->createElement('field_caption');
                    $field_caption->setAttribute('field', $field['field']);
                    $field_caption->setAttribute('width', $field['width']);
                    if (isset($field['sorter_type'])) {
                        $field_caption->setAttribute('sorter_type', $field['sorter_type']);
                    }
                    if (isset($field['head_class'])) {
                        $field_caption->setAttribute('head_class', $field['head_class']);
                    }
                    $field_caption->nodeValue = $field['caption'];
                    $field_captions->appendChild($field_caption);

                    // populate JSON source
                    $json['columns'][$field['field']]['width'] = $field['width'];
                }

            }
            $report_root->appendChild($field_captions);
        }

        // built-in buttons
        $builtin_buttons = array(
            'fast-search'   => false,
            'fast-filter'   => true,
            'pager'         => get_array_value($report_config, 'page_size', 0) > 0,
            'calendar'      => false,
            'counter'       => true,
            'filter-clear'  => isset($_SESSION['storage']['db'][$report_id]['filters']) && (count($_SESSION['storage']['db'][$report_id]['filters']) > 0),
        );
        if (isset($report_config['builtin_buttons'])) {
            $builtin_buttons = array_merge($builtin_buttons, $report_config['builtin_buttons']);
        }
        $report_root->appendChild( $builtin_buttons_node = $xml->createElement('builtin-buttons') );
        foreach ($builtin_buttons as $builtin_button_index => $builtin_button_option) {
            if ($builtin_button_option != false) {;
                $builtin_buttons_node->appendChild( $more_node = $xml->createElement($builtin_button_index) )->nodeValue = $builtin_button_option;

                // add some caption to calendar
                if ($builtin_button_index == 'calendar') {
                    $field_definition = J_DB_Helpers::getFullFieldDefinition($builtin_button_option);
                    $more_node->setAttribute('caption', $field_definition['caption']);

                }
            }
        }

        // special filter buttons
        if (isset($report_config['filter_groups']) && is_array($report_config['filter_groups'])) {
            $report_root->appendChild( $filter_buttons_node = $xml->createElement('filter-groups') );
            foreach ($report_config['filter_groups'] as $group_name => $filter_group) {
                $filter_buttons_node->appendChild( $filter_group_node = $xml->createElement('filter-group') );
                $filter_group_node->setAttribute('name', $group_name);
                $filter_group_node->setAttribute('type', $filter_group['type']);
                foreach ($filter_group['buttons'] as $filter_name => $filter_button) {
                    $filter_group_node->appendChild( $filter_button_node = $xml->createElement('filter-button') );
                    $filter_button_node->setAttribute('name', $filter_name);
                    foreach ($filter_button as $button_prop => $button_prop_val) {
                        $filter_button_node->appendChild( $xml->createElement($button_prop) )->nodeValue = $button_prop_val;
                    }
                }
            }
        }

        // filtering info
        $report_root->appendChild( $xml->importNode( self::getFilterInfoDOM($report_id)->documentElement, true ) );

        // check if alternate XML generator specified, use it instead generated
        if ($xml_generator = get_array_value($report_config, 'data_xml_generator', false)) {
            if (function_exists($xml_generator)) {

                $dom_to_import = $xml_generator($input);

                // note that generator should return data as common format, with <data_set> root tag
                $xml->documentElement->appendChild( $xml->importNode( $dom_to_import->documentElement, true) );

            } else {
                trigger_error('generateReportXML: XML generator specified does not exist', E_USER_WARNING);
                return false;
            }

        } else {

            $options = array();
            if (get_array_value($report_config, 'page_size', 0) > 0) {
                $options['records_from'] = self::storageGet($report_id.'_page_start', 0);
                $options['records_count'] = $report_config['page_size'];
            }

            if ($data_generator = get_array_value($report_config, 'data_generator', false)) {
                $data_source = new DataSource( $data_generator() );
            } else {
                $sql = J_DB_Helpers::getReportMainSQL($report_config, $DB, $options);
                if ($sql === false) {
                    $return_metadata['status'] = 'ERROR';
                    return 'error creating SQL';
                }
                if (($query = $DB->query($sql)) == false) {
                    $return_metadata['status'] = 'ERROR';
                    return 'syntax error or no connection';
                }

                $query->setFetchMode(PDO::FETCH_ASSOC);
                $data_source = new DataSource( $query );
            }

            $all_data_rows = $xml->createElement('data_set');

            $id_field =
                isset( CMS::$R['db_api_fields'][ $report_config['id_field'] ]['field'] )
                ? CMS::$R['db_api_fields'][ $report_config['id_field'] ]['field']
                : (
                    isset( $report_config['fields'] )
                    ? $report_config['fields'][$report_config['id_field']]['field']
                    : false
                )
            ;

            while ($data = $data_source->fetch()) {

                // row element
                $data_row = $xml->createElement('data_row');
                if (isset($report_config['id_field'])) {
                    $data_row->setAttribute('id', $data[ $id_field ]);
                }

                // checkbox, if requested
                if ($checkbox != false) {
                    $data_cell = $xml->createElement('special');
                    $data_cell->setAttribute('value', $data[ CMS::$R['db_api_fields'][$report_config['id_field']]['field'] ]);
                    if (isset($checkbox['class'])) {
                        $data_cell->setAttribute('class', $checkbox['class']);
                    }
                    if (isset($checkbox['name'])) {
                        $data_cell->setAttribute('name', $checkbox['name']);
                    }
                    $data_row->appendChild($data_cell);
                }

                // data fields
                foreach($field_cache as $field) {
                    if (get_array_value($field, 'out_table', true)) {
                        $data_cell = $xml->createElement('data');
                        $data_cell->setAttribute('field', $field['field']);

                        // apply user-defined modifier function
                        if (isset($field['out_func']) && is_callable($field['out_func'])) {
                            $data[$field['field']] = $field['out_func']($data[$field['field']]);
                        }

                        // dict-type fields must be converted from indexes to values
                        switch ($field['type']) {
                            case J_DB::FIELD_TYPE_LIST:
                                $result = '';
                                foreach (explode(' ', $data[$field['field']]) as $value_index) {
                                    if ($value_index > '') {
                                        $result .= get_array_value($field['enum_values'], $value_index, '[bad index]').PHP_EOL;
                                    }
                                }
                                $data[$field['field']] = trim($result);
                                break;
                        }

                        // ok, ready to output
                        if (get_array_value($field, 'out_as_is', false)) {
                            $data_cell->setAttribute('out_as_is', 'yes');
                            $data_cell->appendChild($xml->createCDATASection($data[$field['field']]));
                        } else {
                            $data_cell->nodeValue = $data[$field['field']];
                        }
                        $data_row->appendChild($data_cell);
                    }
                }
                $all_data_rows->appendChild($data_row);
            }
            $report_root->appendChild($all_data_rows);

            // record count
            $record_count_full = self::getReportRowCount($report_config, false);
            $record_count_filtered = self::getReportRowCount($report_config);
            $report_root->appendChild($xml->createElement('record-count-total'))->nodeValue = $record_count_full;
            $report_root->appendChild($xml->createElement('record-count-filtered'))->nodeValue = $record_count_filtered;

            if (get_array_value($report_config, 'page_size', 0) > 0) {
                $report_root->appendChild($xml->createElement('record-count-start'))->nodeValue = self::storageGet($report_id.'_page_start') + 1;
                $report_root->appendChild($xml->createElement('record-count-end'))->nodeValue = min(
                    self::storageGet($report_id.'_page_start') + get_array_value($report_config, 'page_size', 0),
                    $record_count_filtered
                );
            }
        }

        // common report menu information
        $report_menu_items = isset($report_config['report_menu']) ? $report_config['report_menu'] : array();

        $report_root->appendChild($report_menu_node = $xml->createElement('report-menu'));
        foreach ($report_menu_items as $name_or_array_1 => $name_or_array_2) {
            $report_menu_node->appendChild($menu_item_node = $xml->createElement('report-menu-item'));
            foreach (J_DB_Helpers::getFullActionDefinition($name_or_array_1, $name_or_array_2) as $property => $value)
            $menu_item_node->appendChild($xml->createElement($property))->nodeValue = $value;
        }


        // if user-data is requested, add it
        if ($user_data_function = get_array_value($report_config, 'user_data_function', false)) {
            $report_root->appendChild($xml->createElement('user-data'))->nodeValue = $user_data_function();
        }

// $xml->formatOutput = true; echo '<pre>'.htmlspecialchars($xml->saveXML()); exit;

        // add JSON
        // $report_root->appendChild($xml->createElement('json'))->appendChild(new DOMCdataSection(json_encode($json)));

        return $xml->saveXML($report_root);
    }


    /**
     * generates XML for context and other menus
     *
     * possible $input keys:
     *   root_tag   : root node name. "menu" is the default.
     *   menu_items : menu item list as array
     *
     * XML sample:
     * <menu>
     *   <menu_item image="images/pencil.png" js="alert(&quot;OK&quot;);" style="color: red;" class="test_class">Sample item</menu_item>
     *   <menu_divider/>
     *   <menu_item link="http://ya.ru" image="images/blank.png">New record</menu_item>
     *   <menu_item image="images/pencil.png">Edit record</menu_item>
     *   <menu_item api="edit_here">Edit this</menu_item>
     * </menu>
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string XML structure to transform of false on any error
     *
     */
    public static function generateContextMenuXML($input, &$return_metadata, $DB) {

        // some init
        $default_params = array(
            'root_tag' => 'menu'
        );
        $input = array_merge($default_params, $input);

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $menu_root = $xml->createElement($input['root_tag']);
        $xml->appendChild($menu_root);

        foreach($input['menu_items'] as $name_or_array_1 => $name_or_array_2) {

            $item = J_DB_Helpers::getFullActionDefinition($name_or_array_1, $name_or_array_2);

            // skip hiddens
            if (isset($item['hidden'])) {
                if ($item['hidden'] === true) {
                    continue;
                }
                if (is_callable($item['hidden'])) {
                    if (call_user_func($item['hidden'], $input, $return_metadata, $DB) == true) {
                        continue;
                    }
                }
            }

            // divider is separate tag
            if ($item['caption'] == '') {
                $menu_root->appendChild($xml->createElement('menu_divider'));
                continue;
            }

            // ok, get the element
            $menu_item = $xml->createElement('menu_item');
            $menu_item->nodeValue = $item['caption'];
            $menu_root->appendChild($menu_item);

            // disable if requested
            if ($item['disabled']) {
                $menu_item->setAttribute('disabled', 'disabled');
                continue;
            }

            // add some attributes
            foreach (array('image', 'link', 'blank', 'js', 'api', 'style', 'class', 'after') as $add_attr) {
                if (isset($item[$add_attr])) {
                    $menu_item->setAttribute($add_attr, $item[$add_attr]);
                }
            }
        }
        $return_metadata = array('type' => 'xml');
        return $xml->saveXML($menu_root);
    }


    /**
     * generates edit dialog based on report's fields settings
     *
     * possible $params keys:
     *   row_id : row ID
     *   data   : field values to use
     *
     * XML sample:
     * <edit-dialog>
     *     <report_id>1</report_id>
     *     <row_id>9A370D0A-8883-4E58-9605-9152D479A208</row_id>
     *     <new_record></new_record>
     *     <fields>
     *         <field field_name="first_name">
     *             <caption>Имя</caption>
     *             <value><![CDATA[Оз]]></value>
     *             <type>edit</type>
     *             <categories>
     *                 <category>common</category>
     *             </categories>
     *         </field>
     *         ...
     *     <categories>
     *         <category>common</category>
     *         ...
     *     </categories>
     * </edit-dialog>
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string XML string
     *
     */
    public static function generateEditorialXML($input, &$return_metadata, $DB) {

        // check input, of course
        if (!isset($input['report_id'])) {
            $return_metadata = array('status' => 'error');
            return '<b>[JuliaCMS][db module][generateEditorialXML] error</b>: no report ID specified';
        }
        $report_id = $input['report_id'];

        if (!isset(CMS::$R['db_api_reports'][$report_id])) {
            return '<b>[JuliaCMS][db module][generateEditorialXML] error</b>: no report config for this ID ('.$report_id.')';
        }
        $report_config = CMS::$R['db_api_reports'][$report_id];

        // check if we are creating new record or editing the existing one
        $new_record = get_array_value($input, 'new_record', false);

        // generate new ID when creating record as we need to know it for adding comments. Try to use config default, GUID otherwise
        if ($new_record) {
            $try_default = J_DB_Helpers::getFieldDefaultValue( J_DB_Helpers::getFullFieldDefinition( $report_config['id_field'] ) );
            $input['row_id'] = is_null($try_default) ? create_guid() : $try_default;
        }

        // get record data
        if (!$new_record) {
            $lb = $DB->lb;
            $rb = $DB->rb;
            $sql = J_DB_Helpers::getReportMainSQL($report_id, $DB, array('no_order'=>'yes'));
            $check_field_name = CMS::$R['db_api_fields'][$report_config['id_field']]['field'];
            $sql = "select * from ({$sql}) {$lb}ext{$rb} where {$lb}{$check_field_name}{$rb}='{$input['row_id']}'";
            $query = $DB->query($sql);
            $data = $query->fetch(PDO::FETCH_ASSOC);
        } else {
            foreach ($report_config['fields'] as $part1 => $part2) {
                $field = J_DB_Helpers::getFullFieldDefinition($part1, $part2);
                $data[$field['field']] = J_DB_Helpers::getFieldDefaultValue($field);
            }
        }

        // some init
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        // add row id
        $dialog_root = $xml->createElement('edit-dialog');
        $dialog_root->appendChild($xml->createElement('report_id'))->nodeValue = $report_id;
        $dialog_root->appendChild($xml->createElement('row_id'))->nodeValue = $input['row_id'];
        $dialog_root->appendChild($xml->createElement('new_record'))->nodeValue = get_array_value($input, 'new_record', false);

        // create fields list and compile categories list (will be added later)
        $dialog_fields_node = $xml->createElement('fields');
        $dialog_root->appendChild($dialog_fields_node);

        $category_list = array();
        foreach ($report_config['fields'] as $field_part_1 => $field_part_2) {
            $field_definition = J_DB_Helpers::getFullFieldDefinition($field_part_1, $field_part_2);

            // add field description
            if (get_array_value($field_definition, 'out_edit', true) === true) {
                $field_node = $xml->createElement('field');

                // field name as it comes from the query
                $field_node->setAttribute('field_name', $field_definition['field']);

                // caption. nothing special
                $field_node->appendChild($xml->createElement('caption'))->nodeValue = $field_definition['caption'];

                // value comes as CDATA because may contain HTML tags and other XSLT-inapropriate things
                $field_node->appendChild($xml->createElement('value'))->appendChild($xml->createCDATASection($data[$field_definition['field']]));

                // edit type - text, select, checkbox or something else
                $field_type = get_array_value($field_definition, 'type', J_DB::FIELD_TYPE_UNKNOWN);
                switch ($field_type) {
                    case J_DB::FIELD_TYPE_ENUM:
                    case J_DB::FIELD_TYPE_LIST:
                        $field_node->appendChild($type_node = $xml->createElement('type'))->nodeValue = 'select';
                        if ($field_type == J_DB::FIELD_TYPE_LIST) {
                            $type_node->setAttribute('multiple', 'multiple');
                        }
                        $field_node->appendChild($enum_values_node = $xml->createElement('enum-values'));
                        $enum_values =
                            is_callable($field_definition['enum_values'])
                            ? $field_definition['enum_values']()
                            : $field_definition['enum_values'];
                        foreach ($enum_values as $key => $value) {
                            $value_node = $xml->createElement('value');
                            $value_node->setAttribute('index', $key);
                            $value_node->nodeValue = $value;
                            $enum_values_node->appendChild($value_node)->nodeValue = $value;
                        }
                        break;
                    case J_DB::FIELD_TYPE_BIGTEXT:
                        $field_node->appendChild($xml->createElement('type'))->nodeValue = 'textarea';
                        break;
                    case J_DB::FIELD_TYPE_DATETIME:
                        $field_node->appendChild($xml->createElement('type'))->nodeValue = 'datetime';
                        break;
                    case J_DB::FIELD_TYPE_PICTURE:
                        $field_node->appendChild($xml->createElement('type'))->nodeValue = 'picture';
                        break;
                    default:
                        $field_node->appendChild($xml->createElement('type'))->nodeValue = 'edit';
                        break;
                }

                if (get_array_value($field_definition, 'readonly', false) === true) {
                    $field_node->setAttribute('readonly', 'readonly');
                }
                if ($add_class = get_array_value($field_definition, 'add_class', false)) {
                    $field_node->setAttribute('add_class', $add_class);
                }
                if (get_array_value($field_definition, 'password', false) === true) {
                    $field_node->setAttribute('password', 'password');
                }
                if (($sfm_cat = get_array_value($field_definition, 'sfm_cat', false)) !== false) {
                    $field_node->setAttribute('sfm-cat', $sfm_cat);
                }
                if (($placeholder = get_array_value($field_definition, 'placeholder', false)) !== false) {
                    $field_node->appendChild($xml->createElement('placeholder'))->nodeValue = $placeholder;
                }

                // all edit box categories
                $categories_node = $xml->createElement('categories');
                if (isset($field_definition['categories'])) {
                    foreach ($field_definition['categories'] as $category) {

                        $categories_node->appendChild($xml->createElement('category'))->nodeValue = $category;

                        // also add to list if not yet
                        if (!in_array($category, $category_list)) {
                            array_push($category_list, $category);
                        }
                    }
                }
                $field_node->appendChild($categories_node);
                $dialog_fields_node->appendChild($field_node);
            }
        }

        // now add compiled category list
        $category_list_node = $xml->createElement('categories'); // TAG_TODO возможно ли стащить в одну строку?
        $dialog_root->appendChild($category_list_node);

        // add "all" selector" if at least one category exists
        if (count($category_list) > 0) {
            $select_all_node = $xml->createElement('category');
            $select_all_node->setAttribute('all', 'all');
            $category_list_node->appendChild($select_all_node)->nodeValue = 'all';
        }
        foreach ($category_list as $edit_category) {
            $category_list_node->appendChild($xml->createElement('category'))->nodeValue = $edit_category;
        }

        // add title
        if ($new_record) {
            $dialod_title = get_array_value($report_config, 'caption-new', 'Новая запись');
        } else {
            $dialod_title = get_array_value($report_config, 'caption-edit', 'Редактирование');
            if (isset($report_config['caption-field'])) {
                $dialod_title .= ': '.$data[get_array_value(J_DB_Helpers::getFullFieldDefinition($report_config['caption-field']), 'field')];
            }
        }
        $dialog_root->appendChild($xml->createElement('title'))->nodeValue = htmlspecialchars($dialod_title);

// echo '<pre>'.htmlspecialchars($xml->saveXML($dialog_root)).'</pre>';
        $return_metadata = array('type' => 'xml');
        return $xml->saveXML($dialog_root);

    }

    /**
     * generates edit dialog based on report's fields settings
     *
     * possible $params keys:
     * TAG_TODO
     *
     * XML sample:
     * TAG_TODO
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string XML string
     *
     */
    public static function generateCommentsXML($input, &$return_metadata, $DB) {

        // check ID first
        if (($object_id = get_array_value($input, 'row_id', false)) === false) {
            $return_metadata = array('status'=>'ERROR');
            return 'bad row id';
        }

        if (($report_id = get_array_value($input, 'report_id', false)) === false) {
            // TAG_TODO сделать автоматическое определение репорта
            return 'WARNING: NO REPORT ID';
        }

        // get row caption for title (copypasted from generateEditorialXML) // TAG_TODO переделать все тут
        $dialod_title = 'Комментарии';
        $sql = J_DB_Helpers::getReportMainSQL($report_id, $DB);
        $report_config = CMS::$R['db_api_reports'][$report_id];
        $check_field_name = CMS::$R['db_api_fields'][$report_config['id_field']]['field'];
        $lb = $DB->lb;
        $rb = $DB->rb;
        $sql = "select * from ({$sql}) {$lb}ext{$rb} where {$lb}{$check_field_name}{$rb} = '{$input['row_id']}'";
        $query = $DB->query($sql);
        $data = $query->fetch(PDO::FETCH_ASSOC);
        if (isset($report_config['caption-field'])) {
            $dialod_title .= ': '.$data[get_array_value(J_DB_Helpers::getFullFieldDefinition($report_config['caption-field']), 'field')];
        }

        // object list. used for some special situations when comments must be retrieved for the
        // selected object and some its related objects (i.e., all user's comments for all objects)
        $object_list = array($object_id);

        // generate list for SQL
        // TAG_TODO вынести в функцию (зачем?)
        $object_list_for_sql = "'".implode("','", $object_list)."'";

        // create SQL. note that it can be slightly different for some reports
        $sql = "select * from (".J_DB_Helpers::getReportMainSQL('report_comments', $DB).") int where comments_object_id in (".$object_list_for_sql.") order by comments_stamp desc";

        // create XML for all the comments
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $xml_root = $xml->createElement('comments');
        $xml->appendChild($xml_root);

        $xml_root->appendChild($xml->createElement('report_id'))->nodeValue = $report_id;
        $xml_root->appendChild($xml->createElement('main_object_id'))->nodeValue = $object_id;

        $query = $DB->query($sql);
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $comment_node = $xml->createElement('comment');
            $xml_root->appendChild($comment_node);
            foreach ($data as $key=>$value) {
                $comment_node->appendChild($xml->createElement($key))->nodeValue = $value;
            }
        }

        $xml_root->appendChild($xml->createElement('title'))->nodeValue = $dialod_title;

        $return_metadata = array('type' => 'xml');
        return $xml->saveXML();
    }

    /**
     * Adds a comment
     *
     * $input keys required:
     *   row_id       : object to add comment to
     *   comment_text : comment itself
     *
     * Files attachment is also supported
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function commentsAdd($input, &$return_metadata, $DB) {

        // check ID first
        if (($object_id = $input['row_id']) == '') {
            $return_metadata = array('status'=>'ERROR');
            return 'bad row ID';
        }

        // check if no text and no files
        if ((trim($input['comments_comment_text']) == '') && ($_FILES['attachthis']['name'][0]) == '') {
            $return_metadata = array('status'=>'ERROR');
            return 'Нечего добавлять!';
        }

        $user_id = security_get_username_auth();
        $stamp = date('Y.m.d H:i:s');

        // get specials for the input
        $input_filter = array(
            'comment_text' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9\!\"\№\;\%\:\?\*\(\)\-\_\=\+\s]+$~smui'))
        );

        $filtered_input = get_filtered_input($input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));

        $sql = '
            insert into comments ("id", "object_id", "user_id", "stamp", "comment_text", "attached_name", "comment_state")
            values               (:id , :object_id , :user_id , :stamp , :comment_text , :attached_name , :comment_state )
        ';
        $prepared = $DB->prepare($sql);

        // iterate uploaded files, add comment for each
        // NOTE: if the line below generates an error "no index 'attachthis'",
        // ensure that the form has 'enctype="multipart/form-data"' attribute
        for ($file_index = 0; $file_index < count($_FILES['attachthis']['name']); $file_index++) {

            $comment_id = create_guid();

            // full comment text - append "(1/10)" in case of multiple files
            $comment_text_full = $filtered_input['comments_comment_text'];
            if (count($_FILES['attachthis']['name']) > 1) {
                $comment_text_full = '('.($file_index+1).'/'.count($_FILES['attachthis']['name']).') '.$comment_text_full;
            }

            // copy attached file, mark comment if failed
            if ($_FILES['attachthis']['name'][$file_index] > '') {
                $copy_result = move_uploaded_file($_FILES['attachthis']['tmp_name'][$file_index],self::COMMENTS_ATTACHED_DIR.$comment_id);
                if (!$copy_result) {
                    $comment_text_full .= '(file not copied - re-attach)';
                }
            }

            // add the comment to the base
            $prepared->execute(array(
                ':id'            => $comment_id,
                ':object_id'     => $object_id,
                ':user_id'       => $user_id,
                ':stamp'         => $stamp,
                ':comment_text'  => $comment_text_full,
                ':attached_name' => $_FILES['attachthis']['name'][$file_index],
                ':comment_state' => 'new comment'
            ));

        }
        return 'OK';
    }

    /**
     * Deletes a comment
     *
     * This requires separate function since attached files should be deleted too
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function commentsDelete($input, &$return_metadata, $DB) {

        if (!user_allowed_to_do('db.comment.delete')) {
            $return_metadata['status'] = 'error';
            return 'access denied';
        }

        // check ID first
        if (($object_id = $input['row_id']) == '') {
            $return_metadata = array('status'=>'ERROR');
            return 'bad row ID';
        }

        if ($DB->querySingle("select count(*) from comments where id = '$object_id'") == '0') {
            $return_metadata = array('status'=>'ERROR');
            return 'no comment with this ID';
        }

        unlink(self::COMMENTS_ATTACHED_DIR.$object_id);
        $DB->exec("delete from comments where id = '$object_id'");

        return 'OK';
    }

    /**
     * Sends attached file to output
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function commentsGetAttached($input, &$return_metadata, $DB) {

        // check ID first
        if (($object_id = $input['row_id']) == '') {
            $return_metadata = array('status'=>'ERROR');
            terminate('', 'Bad row ID', 400);
        }

        // check if comment exists
        if ($DB->querySingle("select count(*) from comments where id = '{$object_id}'") == '0') {
            $return_metadata = array('status'=>'ERROR');
            terminate('', 'No comment with this ID', 404);
        }

        // check if file was attached and exists now
        $attached_name = trim($DB->querySingle("select attached_name from comments where id = '{$object_id}'"));
        if ($attached_name == '') {
            terminate('', 'No file attached to this comment', 404);
        }
        $attached_full_name = self::COMMENTS_ATTACHED_DIR.$object_id;

        if (!file_exists($attached_full_name)) {
            terminate('', 'File missing', 500);
        }


        // send file type, according to file internal contents
        $output_name = str_replace('+', '%20', urlencode($attached_name));
        file_to_output($attached_full_name, array(
            'Content-Disposition: attachment; filename="'.$output_name.'"',
            'Content-Transfer-Encoding: binary',
            'Expires: 0',
            'Cache-Control: must-revalidate'
        ));

        exit;
    }

    /**
     * inserts a record to a table
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function recordInsert($input, &$return_metadata, $DB) {

        if (!user_allowed_to_do('db.record.insert')) {
            $return_metadata['status'] = 'error';
            return 'access denied';
        }

        if (!($report_config = get_array_value(CMS::$R['db_api_reports'], $input['report_id']))) {
            $return_metadata['status'] = 'error';
            return 'ERROR : no report with this ID';
        }

        $fields = '';       // fields list for INSERT statement
        $placeholders = ''; // values placeholders string, yeah we use prepared statement
        $values = array();  // values themselves

        // check on-insert hooks
        $before_insert_result = J_DB::SAVE_HELPER_OK;
        if (($before_save = get_array_value($report_config, 'before_insert', false)) != false) {
            foreach($before_save as $before_func_def) {
                if (($module_name = get_array_value($before_func_def, 'module', false)) != false) {
                    module_init($module_name);
                    $before_insert_result = CMS::$cache[$module_name]['object']->$before_func_def['function']($input);
                } else {
                    $before_insert_result = $before_func_def['function']($input);
                }
                if ($before_insert_result['result'] !== J_DB::SAVE_HELPER_OK) {
                    break;
                }
            }
            if ($before_insert_result['result'] == J_DB::SAVE_HELPER_FAILED) {
                $return_metadata['status'] = 'error';
                return 'ERROR: '.get_array_value($before_insert_result, 'message', 'no message');
            }
        }

        foreach ($report_config['fields'] as $part1 => $part2) {

            $field = J_DB_Helpers::getFullFieldDefinition($part1, $part2);

            // field skipped only if it's read-only AND no default value defined
            if ((get_array_value($field, 'readonly', false) === true) && (!isset($field['default']))) {
                continue;
            }

            // really updated field
            $updated_field       = isset($field['update_field']) ? $field['update_field'] : $field['table_field'];
            $updated_placeholder = isset($field['update_field']) ? $field['update_field'] : $field['table_field'];
            $fields       .= ($fields       > '' ? ', ' : '').$DB->lb.$updated_field.$DB->rb;
            $placeholders .= ($placeholders > '' ? ', ' : '').':'.$field['table_field'];

            // ID field value is calculated separately: if it exists in the input, get it, else get "row_id"
            // remember that fields have "edit_" prefixes in the input
            // значение ключевого поля вычисляется отдельно - если есть в запросе, берем его, иначе "row_id"
            // не забываем, что в запросе все поля с префиксом "edit_"
            if ($field['field'] == $report_config['id_field']) {
                if (isset($input['edit_'.$field['field']])) {
                    $value = $input['edit_'.$field['field']];
                } else {
                    $value = get_array_value($input, 'row_id', J_DB_Helpers::getFieldDefaultValue($field));
                }
            } else {
                // make enum value by index, in case of enum fields
                switch (get_array_value($field, 'type', J_DB::FIELD_TYPE_UNKNOWN)) {
                    case J_DB::FIELD_TYPE_LIST:
                        $value = implode(' ', get_array_value($input, 'edit_'.$field['field'], array()));
                        break;
                    case J_DB::FIELD_TYPE_ENUM:
                        $enum_values =
                            is_callable($field['enum_values'])
                            ? $field['enum_values']()
                            : $field['enum_values'];
                        $value = $enum_values[get_array_value($input, 'edit_'.$field['field'], 0)];
                        break;
                    default:
                        $value = get_array_value($input, 'edit_'.$field['field'], J_DB_Helpers::getFieldDefaultValue($field));
                        if (isset($field['regexp']) && !preg_match('~^'.$field['regexp'].'$~smui', $value)) {
                            popup_message_add($field['caption'].': некорректное значение', JCMS_MESSAGE_ERROR);
                            $value = ''; // don't try to use NULL here as it will cause error some later
                        }
                        break;
                }
            }
            $values[$updated_placeholder] = $value;
        }

        if ($before_insert_result['result'] != J_DB::SAVE_HELPER_OK_AND_LAST) {

            if (!isset($report_config['sql_insert'])) {
                $sql = "insert into {$DB->lb}{$report_config['main_table']}{$DB->rb} ({$fields}) values ({$placeholders})";
            } else {
                $sql = $report_config['sql_insert'];
            }
            $prepared = $DB->prepare($sql);
            foreach ($values as $field => $value) {
                if (preg_match('~:'.$field.'([^a-z0-9]|$)~ui', $sql)) {
                    $prepared->bindValue(':'.$field, $value);
                }
            }
            $prepared->execute();
        }

        $return_metadata['type']    = 'command';
        $return_metadata['command'] = 'reload';
        return 'OK';
    }

    /**
     * Adds an empty record to a table
     * TAG_TODO: implement field default values in the field descriptions
     * TAG_TODO: add $DB->lb usage
     * TAG_CRAZY TAG_TODO разобраться, зачем это вообще тут.
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function recordAddEmpty($input, &$return_metadata, $DB) {

        if (!user_allowed_to_do('db.record.insert')) {
            $return_metadata['status'] = 'error';
            return 'access denied';
        }

        $new_record_id = create_guid();
        if (!($report_config = get_array_value(CMS::$R['db_api_reports'], $input['report_id']))) {
            $return_metadata['status'] = 'error';
            return 'ERROR : no report with this ID';
        }

        switch($input['report_id']) {
            case '1':

//                foreach ($report_config['fields'] as $part1 => $part2) {
//                    $default = J_DB_Helpers::getFieldDefaultValue( J_DB_Helpers::getFullFieldDefinition($part1, $part2) );
//                }
//                $return_metadata['status'] = 'error';
//                return $t;
                $sql = "insert into clients (id, first_name) values ('$new_record_id', 'new client')";
                break;
            case '3':
                $sql = "insert into mailfrom (id, caption) values ('$new_record_id', 'new address')";
                break;
            case '4':
                $sql = "insert into templates (id, caption) values ('$new_record_id', 'new template')";
                break;
        }

        $DB->exec($sql);
        $return_metadata['type']    = 'command';
        $return_metadata['command'] = 'reload';
        return 'OK';
    }

    /**
     * Updates the record in database
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function recordSave($input, &$return_metadata, $DB) {

        if (!user_allowed_to_do('db.record.update')) {
            $return_metadata['status'] = 'error';
            return 'access denied';
        }

        // input check : report
        if (!isset($input['report_id'])) {
            $return_metadata['status'] = 'error';
            return '[recordSave] no report ID specified';
        }
        $report_id = $input['report_id'];

        if (($report_config = get_array_value(CMS::$R['db_api_reports'], $report_id, false)) === false) {
            $return_metadata['status'] = 'error';
            return '[recordSave] no report with this ID';
        }

        // input check: row identifier
        if (!isset($input['row_id'])) {
            $return_metadata['status'] = 'error';
            return '[recordSave] no record ID specified';
        }
        $row_id = $input['row_id'];

        // also must match field check regexp
        $id_field_regexp = get_array_value( J_DB_Helpers::getFullFieldDefinition($report_config['id_field']), 'regexp', '.*');
        if (preg_match('~'.$id_field_regexp.'~', $row_id) === 0) {
            $return_metadata['status'] = 'error';
            return '[recordSave] bad record ID';
        }

        // check if there any pre-saver function
        $before_save_result = array('result' => J_DB::SAVE_HELPER_OK);
        if (($before_save = get_array_value($report_config, 'before_save', false)) != false) {
            foreach($before_save as $before_func_def) {
                if (($module_name = get_array_value($before_func_def, 'module', false)) != false) {
                    module_init($module_name);
                    $before_save_result = CMS::$cache[$module_name]['object']->$before_func_def['function']($input);
                } else {
                    $before_save_result = $before_func_def['function']($input);
                }
                if ($before_save_result['result'] !== J_DB::SAVE_HELPER_OK) {
                    break;
                }
            }
            if ($before_save_result['result'] == J_DB::SAVE_HELPER_FAILED) {
                $return_metadata['status'] = 'error';
                return 'ERROR: '.get_array_value($before_save_result, 'message', 'no message');
            }
        }

        // if there no explicit UPDATE SQL specified, generate it // TAG_TODO TAG_CRAZY generate!!!
        if (($sql = get_array_value($report_config, 'sql_update', false)) === false) {
            $return_metadata['status'] = 'error';
            return 'ERROR: no update SQL';
        }

        if ($before_save_result['result'] != J_DB::SAVE_HELPER_OK_AND_LAST) {

            // ok, prepare SQL statement and bind values to it
            $prepared = $DB->prepare($sql);
            foreach ($report_config['fields'] as $part1 => $part2) {

                $field_definition = J_DB_Helpers::getFullFieldDefinition($part1, $part2);

    /*
                // check if special saver function exists, call if yes. pass raw input without any processing
                if (isset($field_definition['save_func']) && is_callable($field_definition['save_func'])) {
                    $field_definition['save_func']($row_id, get_array_value($input, 'edit_'.$field_definition['field'], J_DB_Helpers::getFieldDefaultValue($field_definition)));
                }
    */
                // read-only fields should not be placed to the query. note that field may be marked as read-only and have saver function,
                // so this check should be done after saver call
                if (get_array_value($field_definition, 'readonly', false) === true) {
                    continue;
                }

                // ok, bind it. skip if no placeholder exists
                if (preg_match('~:'.$field_definition['field'].'($|[^a-zA-Z0-9_])~', $sql)) {

                    // make enum value by index, in case of enum fields
                    switch (get_array_value($field_definition, 'type', J_DB::FIELD_TYPE_UNKNOWN)) {
                        case J_DB::FIELD_TYPE_LIST:
                            $new_value = implode(' ', get_array_value($input, 'edit_'.$field_definition['field'], array()));
                            break;
                        case J_DB::FIELD_TYPE_ENUM:
                            $enum_values =
                                is_callable($field_definition['enum_values'])
                                ? $field_definition['enum_values']()
                                : $field_definition['enum_values'];
                            $new_value = $enum_values[get_array_value($input, 'edit_'.$field_definition['field'], 0)];
                            break;
                        default:
                            $new_value = get_array_value($input, 'edit_'.$field_definition['field'], J_DB_Helpers::getFieldDefaultValue($field_definition));
                            if (isset($field_definition['regexp']) && !preg_match('~^'.$field_definition['regexp'].'$~smui', $new_value)) {
                                popup_message_add($field_definition['caption'].': некорректное значение', JCMS_MESSAGE_ERROR);
                                $new_value = '';
                            }
                            break;
                    }
                    // $updated_field = isset($field_definition['update_field']) ? $field_definition['update_field'] : ;
                    $prepared->bindValue(':'.$field_definition['field'], $new_value);
                }
            }

            // also add row identifier
            $prepared->bindValue(':row_id', $row_id);

            // yeah go on
            $prepared->execute();
        }

        return 'OK';
    }

    /**
     * Deletes a record
     * TAG_TODO add: call userland API before and after
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return string 'OK' or some error text
     */
    public static function recordDelete($input, &$return_metadata, $DB) {

        if (!user_allowed_to_do('db.record.delete')) {
            $return_metadata['status'] = 'error';
            return 'access denied';
        }

        $report_config = CMS::$R['db_api_reports'][$input['report_id']];

        // custom deletion support
        $before_delete_result = J_DB::SAVE_HELPER_OK;
        if (($before_delete = get_array_value($report_config, 'before_delete', false)) != false) {
            foreach($before_delete as $before_func_def) {
                if (($module_name = get_array_value($before_func_def, 'module', false)) != false) {
                    module_init($module_name);
                    $before_delete_result = CMS::$cache[$module_name]['object']->$before_func_def['function']($input);
                } else {
                    $before_delete_result = $before_func_def['function']($input);
                }
                if ($before_delete_result['result'] !== J_DB::SAVE_HELPER_OK) {
                    break;
                }
            }
            if ($before_delete_result['result'] == J_DB::SAVE_HELPER_FAILED) {
                $return_metadata['status'] = 'error';
                return 'ERROR: '.get_array_value($before_delete_result, 'message', 'no message');
            }
        }

        // standard
        if ($before_save_result['result'] != J_DB::SAVE_HELPER_OK_AND_LAST) {

            $table_name    = $DB->lb . CMS::$R['db_api_fields'][ $report_config['id_field'] ]['table']       . $DB->rb;
            $id_field_name = $DB->lb . CMS::$R['db_api_fields'][ $report_config['id_field'] ]['table_field'] . $DB->rb;

            $sql = "delete from $table_name where $id_field_name = :id";
            $prepared = $DB->prepare($sql);
            $prepared->execute(array(':id' => $input['row_id']));
        }

        $return_metadata = array('type'=>'command', 'command'=>'reload');
        return 'OK';
    }

    /**
     * Creates report XLSX
     *
     * @param array $input parameters
     * @param array $return metadata parameters
     * @param resource $DB database connection to use
     * @return XSLX file content
     */
    public static function reportToExcel($input, &$return_metadata, $DB) {

        preg_match_all('~[\d\w\-\.]+~', $input['id_list'], $rows_to_export);
        $row_id_list = $rows_to_export[0];

        $layout = json_decode($input['layout'], 1);

        // TAG_CRAZY TAG_TODO фильтрацию параметров прикрутить
        $report_id = $input['report_id'];
        $data_start_row = 4;
//        $file_type = 'Excel5';
        $file_type = 'Excel2007';
        
        $common_style_array = array('font' => array('name' => 'Arial', 'size' => 10));
        $header_style_array = array_merge_recursive($common_style_array, array('font' => array('bold' => true)));

        $report_config = CMS::$R['db_api_reports'][$report_id];

        // if pre-generate functions defined, call them
        if ($before_generate = get_array_value($report_config, 'before_generate')) {
            if (is_callable($before_generate)) {
                $before_generate = array($before_generate);
            }
            foreach ($before_generate as $before_generate_function) {
                $before_generate_function($report_config);
            }
        }

        // cache field definitions
        $field_cache = array();
        foreach($report_config['fields'] as $field_part_1 => $field_part_2) {
            $field = J_DB_Helpers::getFullFieldDefinition($field_part_1, $field_part_2);
            if (get_array_value($field, 'out_table', true) === true) {
                $field_cache[$field['field']] = $field;
            }
        }

        // remove inappropriate data from layout (like checkbox columns)
        foreach ($layout as $layout_index => $layout_field) {
            if (!isset($layout_field['field']) || !isset($field_cache[$layout_field['field']])) {
                unset($layout[$layout_index]);
            }
        }

        // get data *******************************************************************************
        if ($data_generator = get_array_value($report_config, 'data_generator', false)) {
            $data_source = new DataSource( $data_generator() );
        } else {
            $sql = J_DB_Helpers::getReportMainSQL(CMS::$R['db_api_reports'][$report_id], $DB);
            if (($query = $DB->query($sql)) == false) {
                $return_metadata['status'] = 'ERROR';
                return 'seems there is syntax error or no connection';
            }
            $data_source = new DataSource($query);
        }

        // ok let's start
        $report_caption = get_array_value(CMS::$R['db_api_reports'][$report_id], 'caption', 'unknown report');
        $column_width_divider = 8; // excel's width unit is much more than one pixel
        $xl = new PHPExcel();
        $xl->setActiveSheetIndex(0);
        $xl_writer_object = PHPExcel_IOFactory::createWriter($xl, $file_type);

        // common formatting (zero margin, fit cool) **********************************************
        // TAG_TODO make it optional
        $xl->getActiveSheet()->getPageMargins()->setTop(0);
        $xl->getActiveSheet()->getPageMargins()->setRight(0);
        $xl->getActiveSheet()->getPageMargins()->setLeft(0);
        $xl->getActiveSheet()->getPageMargins()->setBottom(0);
        $xl->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

        $xl->getActiveSheet()->setPrintGridlines(true);
        $xl->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $xl->getActiveSheet()->getPageSetup()->setFitToHeight(10);

        // caption ********************************************************************************
        $xl->getActiveSheet()->setCellValue('A1',date('d.m.Y h:i:s').', '.$report_caption);
        $xl->getActiveSheet()->getStyle('A1')->applyFromArray($header_style_array);
        $xl->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        // header *********************************************************************************
        $column = 1;
        $header_row = $data_start_row - 1;
        foreach ($layout as $layout_field) {
            $field = $field_cache[$layout_field['field']];
            $column_letter = J_DB_Helpers::getExcelLetter($column);
            $xl->getActiveSheet()->getColumnDimension($column_letter)->setWidth($layout_field['width']/$column_width_divider);

            $xl->getActiveSheet()->getStyle($column_letter.$header_row)->applyFromArray($header_style_array);
            $xl->getActiveSheet()->setCellValue($column_letter.$header_row, $field['caption']);
            $column++;
        }

        // contents *******************************************************************************
        // we don't know order so using this strange method
        $row = $data_start_row;
        $all_data = array();
        $id_field = CMS::$R['db_api_fields'][ $report_config['id_field'] ]['field'];
        while ($data = $data_source->fetch(PDO::FETCH_ASSOC)) {
            if (!in_array($data[$id_field], $row_id_list)) {
                continue;
            }
            $all_data[$data[$id_field]] = $data;
        }
        foreach ($row_id_list as $row_id) {

            $column = 1;
            // foreach ($field_cache as $field) {
            foreach ($layout as $layout_field) {
                $field = $field_cache[$layout_field['field']];
                $text = $all_data[$row_id][$field['field']];
                if (isset($field['xls_func']) && is_callable($field['xls_func'])) {
                    $text = $field['xls_func']($text);
                } else if (isset($field['out_func']) && is_callable($field['out_func'])) {
                    $text = $field['out_func']($text);
                }
                $xl->getActiveSheet()->setCellValue(J_DB_Helpers::getExcelLetter($column).$row, $text);
                $column ++;
            }
            $row ++;
        }

        // more cool formatting. note that it must be after content output as now $column and $row have their maximum values
        // check if any columns exported at all
        if ($column > 1) {
            $xl->getActiveSheet()->getStyle('A2:'.J_DB_Helpers::getExcelLetter($column - 1).($row - 1))->getAlignment()->setWrapText(true);
            $xl->getActiveSheet()->getStyle('A2:'.J_DB_Helpers::getExcelLetter($column - 1).($row - 1))->applyFromArray($common_style_array);
        }

        $xl->getActiveSheet()->freezePane('A'.$data_start_row);

        // move selection to the start
        $xl->getActiveSheet()->getStyle('A'.$data_start_row.':A'.$data_start_row)->getAlignment();

        // yeah completed!
        switch ($file_type) {
            case 'Excel2007':
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="report.xlsx"');
                break;
            case 'Excel5':
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="report.xls"');
                break;
        }
        header('Cache-Control: max-age=0');

        // seems that writer can't just return XLS as string so using output buffering
        // (!) remember we must return XLS string instead of printing it (!)
        // first get if anything already in buffer. If only BOM is there, silently skip it, keep other data (like warnings!)
        $prev = ob_get_clean();
        if ($prev == chr(0xEF).chr(0xBB).chr(0xBF)) {
            $prev = '';
        }

        ob_start();
        $xl_writer_object->save('php://output');
        $result = ob_get_clean();
        return $prev.$result;
    }



    /**
     * Applies filter by selected field and value
     */
    public static function filterClearAll($input, &$return_metadata, $DB) {

        // no input filtering - no params
        $_SESSION['storage']['db'][$input['report_id']]['filters'] = array();
        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }



    /**
     * Applies filter by selected field and value
     */
    public static function filterClearOne($input, &$return_metadata, $DB) {


        // TAG_TODO какой приоритетнее - report_id или api_param_report_id?
        $report_id = false;
        $test_report_id = get_array_value($input, 'report_id', false);
        if (
            $test_report_id &&
            (self::getReportConfig(array('report_id' => $test_report_id), $out_report_id, $out_report_config) === true)
        ) {
            $report_id = $test_report_id;
        }
        if ( ! $report_id) {
            $test_report_id = get_array_value($input, 'api_param_report_id', false);
            if (
                $test_report_id &&
                (self::getReportConfig(array('report_id' => $test_report_id), $out_report_id, $out_report_config) === true)
            ) {
                $report_id = $test_report_id;
            }
        }

        if ( ! $report_id) {
            $return_metadata = array('status' => 'error');
            return 'bad report id';
        }

        $filter_index = get_array_value($input, 'api_param_filter_index', -1);

        // ok, do it!
        unset( $_SESSION['storage']['db'][$report_id]['filters'][$filter_index] );
        
        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Applies multiple filters
     *
     * @param string $report_id report to apply filter to
     * @param string $index existing filter index to update or un-existent to add new
     * @param string $field field name to use
     * @param string $type filter type (see self::$filter_types) or "-" for silent skip
     * @param string $param filter parameter (comparison string etc.)
     * @return bool true on success, false on any error
     */
    public static function filterChangeOne($report_id, $index, $field, $type, $param) {

        if ($type == '-') {
            return true;
        }

        static $report_configs = null;
        if ( ! isset($report_configs[$report_id])) {
            if ( ! self::getReportConfig(array('report_id' => $report_id), $return_report_id, $report_config)) {
                $return_metadata = array('status' => 'error');
                return 'bad report id';
            }
            $report_configs[$report_id] = $report_config;
        } else {
            $report_config = $report_configs[$report_id];
        }

        // field
        if ( ! J_DB_Helpers::getFullFieldDefinition($field)) {
            popup_message_add('bad field name "'.$field.'"', JCMS_MESSAGE_WARNING);
            return false;;
        }

        // type
        if ( ! isset(self::$filter_types[$type])) {
            popup_message_add('bad filter type  "'.$type.'"', JCMS_MESSAGE_WARNING);
            return false;
        }

        // filter parameter
        // validate?

        // filter index
        $existing_filters = self::getFilters($report_id);
        if (isset($existing_filters[$index])) {
            $use_index = $index;
        } else {
            $use_index = count($existing_filters) > 0 ? max(array_keys($existing_filters)) + 1 : 1;
        }
        
        // clear parameter if not needed
        if (strpos(self::$filter_types[$type]['sql'], '%2$s') === false) {
            $param = '';            
        }

        $_SESSION['storage']['db'][$report_id]['filters'][$use_index] = array(
            'field' => $field,
            'type'  => $type,
            'param' => $param,
        );

        return true;
    }

    /**
     * Applies multiple filters
     */
    public static function filterApplyMass($input, &$return_metadata, $DB) {

        if ( ! self::getReportConfig($input, $report_id, $report_config)) {
            $return_metadata = array('status' => 'error');
            return 'bad report id';
        }

        // search for any "filter-elem-INDEX-field" in the input, when found, try to apply
        foreach ($input as $input_index => $input_value) {
            if (preg_match('~^filter-elem-((new-)?(\d+))-field$~', $input_index, $match)) {
                self::filterChangeOne(
                    $report_id,
                    $match[1],
                    get_array_value($input, 'filter-elem-'.$match[1].'-field', false),
                    get_array_value($input, 'filter-elem-'.$match[1].'-type', false),
                    get_array_value($input, 'filter-elem-'.$match[1].'-param', false)
                );
                
            }
        }

        // $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Applies filter by selected field and value
     */
    public static function filterBySelection($input, &$return_metadata, $DB) {

        $input_filter = array(
            'field_name' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_IDENTIFIER)),
            'report_id'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_IDENTIFIER)),
        );
        $_INPUT = get_filtered_input($input_filter, array(FILTER_GET_BY_LIST));

        // check input
        $field_def = J_DB_Helpers::getFullFieldDefinition($_INPUT['field_name']);
        if ($field_def == false) {
            $return_metadata['status'] = 'error';
            return 'bad field name';
        }

        if (! isset(CMS::$R['db_api_reports'][$_INPUT['report_id']])) {
            $return_metadata['status'] = 'error';
            return 'bad report id';
        }
        $id_field_def = J_DB_Helpers::getFullFieldDefinition(CMS::$R['db_api_reports'][$_INPUT['report_id']]['id_field']);

        $value = $DB->querySingle('select '.$field_def['table_field'].' from '.$field_def['table'].' where '.$id_field_def['table_field'].' = \''.$input['row_id'].'\'');

        // apply filter
        $_SESSION['storage']['db'][ $_INPUT['report_id'] ][ 'filters' ][] = array(
            'field' => $_INPUT['field_name'],
            'type'  => 'equals',
            'param' => $value,
        );

        // rewind to start
        self::pageFirst($input, $return_metadata, $DB);

        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'OK';
    }

    /**
     * Applies filter by selected field and value
     */
    public static function filterDialog($input, &$return_metadata, $DB) {

        $input_filter = array(
            'field_name' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_IDENTIFIER)),
            'report_id'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_IDENTIFIER)),
        );
        $_INPUT = get_filtered_input($input_filter, array(FILTER_GET_BY_LIST));

        $report_id = $_INPUT['report_id'];

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $filters = self::getFilters($report_id);

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $report_root = $xml->createElement('filter-dialog');
        $xml->appendChild($report_root);
        $report_root->appendChild($xml->createElement('report-id'))->nodeValue = $report_id;

        $report_root->appendChild( $xml->importNode( self::getFilterInfoDOM($report_id)->documentElement, true ) );
        $report_root->appendChild( $xml->importNode( self::getFilterTypesDOM()->documentElement, true ) );
        $report_root->appendChild( $xml->importNode( self::getFieldsInfoDOM($report_id)->documentElement, true ) );

// $xml->formatOutput = true; $return_metadata['type'] = 'html';  return '<pre>'.htmlspecialchars($xml->saveXML()).'</pre>';
        $return_metadata = array('type' => 'html');
        return XSLTransform($xml->saveXML(), __DIR__.'/xsl/dialog_filter_column.xsl');
    }

}