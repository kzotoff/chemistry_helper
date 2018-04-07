<?php //> Й <- UTF mark

/*

THIS FILE IS A DUMMY. MUST BE BUILT FROM THE DATABASE

report configurations

top-level array items:
	'caption'            => 'client list`                 (required) Report caption, will be attached to HTML title
	'main_table'         => 'clients'                      (required) Primary report table. All other will be joined here
	'id_field'           => 'client_id`                   (required) Report id column, will be used as row ID's
    'page_size'          => 100,                           (optional) Set to more than 0 activates paged output with given row count per page
	'sql_select'         => 'select * from clients'        (optional) SQL to use for SELECT instead of autogenerated
	'sql_insert'         => 'select * from clients'        (optional) SQL to use for INSERT instead of autogenerated
	'sql_update'         => 'select * from clients'        (optional) SQL to use for UPDATE instead of autogenerated
	'sql_delete'         => 'select * from clients'        (optional) SQL to use for DELETE instead of autogenerated
	'xsl'                => 'path/to/xsl.xsl'              (optional) XSL file to use instead of default "report.xsl". Path must be relative to CMS root.
	'editor'             => 'xsl/some.xsl'                 (optional) Editorial gialog XSL filename. "editorial.xsl" is the default. Relative path starts from module root
	'context_menu'       => array(...                      (optional) Action list for context menu
	'data_xml_generator' => callable                       (optional) Use this function for generating XML data structure. Most other fields will be ignored.
	'before_generate'    => callable                       (optional) function to call before report generation. Must accept only one parameter - report config (passed by reference)
	'default_order'      => 'field_1 asc, field_2 desc'    (optional) default order

	context menu items can be defined by the following styles:
		'edit_here`                                    // just a link to an action in "actions.php"
		'sample_action' => array('width'=>100),         // link and override options. For array description, see "actions.php"
		array('caption'=>'hey` 'api'=>'testRecord'),   // in-place action definition
		array(),                                        // divider - no caption set


	'report_menu'   => array(...                      (optional) Actions for common report menu


	'joined_tables' => array(                         (optional) Tables to join to the main table (or to previously joined)
		'clients' => array(
			'type'          => 'sql`                 "real" or "sql"
			'sql'           => 'select object_id, group_concat(comment_text, \` \') as comments from comments group by object_id`
			'table'         => '`                    table for the type "real"
			'alias'         => 'comments_synth`      alias to set ( TABLE AS ALIAS )
			'join_field'    => 'object_id`           field to join by (in joined table)
			'join_to_table' => 'clients`             table to join TO
			'join_to_field' => 'id`                  field in join target
			'join_hint'     => 'left`                join hint (LEFT/RIGHT/FULL)
		)
	),

	'fields'       => array(...                      (required) Fields to include. one may use the same way as with context menu (link and array)

also refer to doc:TAG:FIELDS_AND_MENUS for meanings of "context_menu" and "report_menu" arrays

*/

CMS::$R['db_api_reports'] = array(

	'report_settings' => array(
		'caption'         => 'Настройки',
		'main_table'      => 'settings',
		'id_field'        => 'settings_id',

		'sql_update' =>
			' update settings set '.
			'   `caption` = :settings_caption, '.
			'   `key`     = :settings_key, '.
			'   `value`   = :settings_value, '.
			'   `comment` = :settings_comment '.
			' where `id` = :row_id',

		'context_menu' => array(
			'record_edit',
			'record_delete_confirm',
		),

		'before_save' => array(
		),

		'before_insert' => array(
		),


		'report_menu'   => array(
			'record_add',
			'report_as_xlsx',
		),

		'fields' => array(
			'settings_id',
			'settings_caption',
			'settings_key',
			'settings_value',
			'settings_comment',
		),
	),


	'report_periodic' => array(
		'caption'         => 'Периодическая таблица',
		'main_table'      => 'periodic',
		'id_field'        => 'periodic_id',

		'sql_update' =>
			' update settings set '.
            '   `sign`            = `pediodic_sign`, '.
            '   `title_ru`        = `pediodic_title_ru`, '.
            '   `title_en`        = `pediodic_title_en`, '.
            '   `color`           = `periodic_color`, '.
            '   `number`          = `pediodic_number`, '.
            '   `period`          = `pediodic_period`, '.
            '   `group`           = `pediodic_group`, '.
            '   `mass`            = `pediodic_mass`, '.
            '   `density`         = `pediodic_density`, '.
            '   `density_unit`    = `pediodic_density_unit`, '.
            '   `temp_melt`       = `pediodic_temp_melt`, '.
            '   `temp_boil`       = `pediodic_temp_boil`, '.
            '   `discovered_year` = `pediodic_discovered_year`, '.
            '   `discovered_by`   = `pediodic_discovered_by`, '.
            '   `transcription`   = `pediodic_transcription` '.
			' where `id` = :row_id',

		'context_menu' => array(
			'record_edit',
			'record_delete_confirm',
		),

		'before_save' => array(
		),

		'before_insert' => array(
		),


		'report_menu'   => array(
			'record_add',
			'report_as_xlsx',
		),

		'fields' => array(
            'periodic_id',
            'periodic_sign',
            'periodic_title_ru',
            'periodic_title_en',
            'periodic_color',
            'periodic_number',
            'periodic_period',
            'periodic_group',
            'periodic_mass',
            'periodic_density',
            'periodic_density_unit',
            'periodic_temp_melt',
            'periodic_temp_boil',
            'periodic_discovered_year',
            'periodic_discovered_by',
            'periodic_transcription',
		),
	),





);
