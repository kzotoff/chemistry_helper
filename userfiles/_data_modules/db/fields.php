<?php //> Й <- UTF mark

/*

All database fields list

item sample (all description elements are required. unpredictable behavior will occur if any options are skipped)
	array(
		'table'          => 'projects',               source table, alias, view, stored proc, real, generated, temporary or other
		'table_field'    => 'id',                     field name in the source table. used for generating SQL only
		'update_field'   => 'more_id'                 field to be updated (for list-type fields)
		'field'          => 'usn_project',            field name to represent in the query (aka alias)
		'type'           => J_DB::FIELD_TYPE_TEXT,    field type
		'caption'        => 'ID',                     caption to display
		'out_table'      => true,                     include or not to result set in table form (secutiry option). Default is true
		'out_edit'       => true,                     include or not to result set in edit_form (secutiry option). Default is true
		'add_class'      => 'some-class'              CSS class to add to data cells
		'head_class'     => 'some-class'              CSS class to add to header row
		'width'          => 100,                      display column width
		'regexp'         => J_DB::REGEXP_INT,         field value must match this (eihter while updatind or validation)
		'comment'        => 'Идентификатор'           human-readable field description
		'categories'     => array('personal', ...)    categories to place field at row-edit dialog
		'readonly'       => true                      means the field cannot be changed directly through common editorial dialog
		'sorter_type'    => 'numeric'                 force SimpleTableSorter use this as column type (refer to SimpleTableSorter manual for appropriate values)
		'sfm_cat'        => 'picture'                 force attach simpleFilemanager and use this category
		'out_func'       => [callable]                function to be applied to the value before output
		'xls_func'       => [callable]                function to be applied to the value before exporting. (!) Will override "out_func"
	)

*/

module_init('db');


CMS::$R['db_api_fields'] = array(

	'settings_id' => array(
		'table'       => 'settings',
		'table_field' => 'id',
		'field'       => 'settings_id',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'regexp'      => J_DB::REGEXP_GUID,
		'caption'     => 'ID',
		'default'     => function() { return create_guid(); },
		'readonly'    => true,
		'out_table'   => false,
		'out_edit'    => false,
		'width'       => 200,
	),
	'settings_caption' => array(
		'table'       => 'settings',
		'table_field' => 'caption',
		'field'       => 'settings_caption',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Настройка',
		'width'       => 200,
	),
	'settings_key' => array(
		'table'       => 'settings',
		'table_field' => 'key',
		'field'       => 'settings_key',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Ключ',
		'width'       => 200,
	),
	'settings_value' => array(
		'table'       => 'settings',
		'table_field' => 'value',
		'field'       => 'settings_value',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Значение',
		'width'       => 200,
	),
	'settings_comment' => array(
		'table'       => 'settings',
		'table_field' => 'comment',
		'field'       => 'settings_comment',
		'type'        => J_DB::FIELD_TYPE_BIGTEXT,
		'caption'     => 'Примечание',
		'width'       => 600,
	),

    
	'periodic_id' => array(
		'table'       => 'periodic',
		'table_field' => 'id',
		'field'       => 'periodic_id',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'ID',
		'width'       => 100,
        'out_edit'    => false,
        'out_table'   => false,
	),
	'periodic_sign' => array(
		'table'       => 'periodic',
		'table_field' => 'sign',
		'field'       => 'periodic_sign',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Знак',
		'width'       => 50,
	),
	'periodic_title_ru' => array(
		'table'       => 'periodic',
		'table_field' => 'title_ru',
		'field'       => 'periodic_title_ru',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Название',
		'width'       => 110,
	),
	'periodic_title_en' => array(
		'table'       => 'periodic',
		'table_field' => 'title_en',
		'field'       => 'periodic_title_en',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Название латинское',
		'width'       => 110,
	),
	'periodic_color' => array(
		'table'       => 'periodic',
		'table_field' => 'color',
		'field'       => 'periodic_color',
		'type'        => J_DB::FIELD_TYPE_ENUM,
        'enum_values' => array('cyan', 'magenta', 'yellow', 'green'),
		'caption'     => 'Цвет',
		'width'       => 110,
	),
	'periodic_number' => array(
		'table'       => 'periodic',
		'table_field' => 'number',
		'field'       => 'periodic_number',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Номер',
		'width'       => 50,
	),
	'periodic_period' => array(
		'table'       => 'periodic',
		'table_field' => 'period',
		'field'       => 'periodic_period',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Период',
		'width'       => 50,
	),
	'periodic_group' => array(
		'table'       => 'periodic',
		'table_field' => 'group',
		'field'       => 'periodic_group',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Группа',
		'width'       => 50,
	),
	'periodic_mass' => array(
		'table'       => 'periodic',
		'table_field' => 'mass',
		'field'       => 'periodic_mass',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Атомная масса',
		'width'       => 100,
	),
	'periodic_density' => array(
		'table'       => 'periodic',
		'table_field' => 'density',
		'field'       => 'periodic_density',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Плотность',
		'width'       => 100,
	),
	'periodic_density_unit' => array(
		'table'       => 'periodic',
		'table_field' => 'density_unit',
		'field'       => 'periodic_density_unit',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Единица плотности',
		'width'       => 50,
	),
	'periodic_temp_melt' => array(
		'table'       => 'periodic',
		'table_field' => 'temp_melt',
		'field'       => 'periodic_temp_melt',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Температура плавления',
		'width'       => 100,
	),
	'periodic_temp_boil' => array(
		'table'       => 'periodic',
		'table_field' => 'temp_boil',
		'field'       => 'periodic_temp_boil',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Температура кипения',
		'width'       => 100,
	),
	'periodic_discovered_year' => array(
		'table'       => 'periodic',
		'table_field' => 'discovered_year',
		'field'       => 'periodic_discovered_year',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Год открытия',
		'width'       => 70,
	),
	'periodic_discovered_by' => array(
		'table'       => 'periodic',
		'table_field' => 'discovered_by',
		'field'       => 'periodic_discovered_by',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Кем открыт',
		'width'       => 150,
	),
	'periodic_transcription' => array(
		'table'       => 'periodic',
		'table_field' => 'transcription',
		'field'       => 'periodic_transcription',
		'type'        => J_DB::FIELD_TYPE_TEXT,
		'caption'     => 'Как читается',
		'width'       => 110,
	),



);
