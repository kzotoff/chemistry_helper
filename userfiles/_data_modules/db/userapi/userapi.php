<?php //> Й <- UTF mark

/*

context-specific routines

*/

class UserLogic {

	/**
	 * Userland API actions. The structure is the same as defined in J_DB class
	 *
	 * @name $actions
	 */
	public static $actions = array(
		'show_visit_details' => array(
			'caption'       => 'Показать данные',
			'api'           => 'show_visit_details',
			'image'         => '',
		),
		'delete_visit_details' => array(
			'caption'       => 'Удалить запись',
			'api'           => 'delete_visit_details',
			'image'         => 'modules/db/images/red_cross_diag.png',
		),
		'recalc_stat_table' => array(
			'caption'       => 'Пересчитать статистику',
			'api'           => 'recalc_stat_table',
		),
		'rebuild_summary_table' => array(
			'caption'       => 'Перестроить сводную таблицу...',
			'js'            => 'reportRebuildDialog();',
		),
		'show_similar_visits' => array(
			'caption'       => 'Показать похожие посещения',
			'api'           => 'show_similar_visits',
			'after'         => 'callSimilarHighlight',
		),

	);

	/**
	 * Mapping between external API names and internal methods
	 *
	 * @name $methods
	 */
	public static $methods = array(
		'log_js_data'                 => array('class' => 'UserLogic', 'method' => 'logJsData'                ),
		'show_visit_details'          => array('class' => 'UserLogic', 'method' => 'showVisitDetails'         ),
		'delete_visit_details'        => array('class' => 'UserLogic', 'method' => 'deleteVisitDetails'       ),
		'delete_visit_details_all'    => array('class' => 'UserLogic', 'method' => 'deleteVisitDetailsAll'    ),
		'show_similar_visits'         => array('class' => 'UserLogic', 'method' => 'showSimilarVisits'        ),

		'recalc_stat_table'           => array('class' => 'UserLogic', 'method' => 'recalcStatTable'          ),
		'write_export_table'          => array('class' => 'UserLogic', 'method' => 'writeExportTable'         ),
		'export_get_field_list'       => array('class' => 'UserLogic', 'method' => 'exportGetFieldList'       ),
		'export_one_step'             => array('class' => 'UserLogic', 'method' => 'exportOneStep'            ),
	);


	/**
	 * Возвращает ссылку на соединение с БД
	 */
	public static function getDB() {
		module_init('db');
		return CMS::$cache['db']['object']->DB;
	}

	/**
	 * Логгируем, что нам там скрипт прислал
	 */
	public static function logJsData($input, &$return_metadata, $DB) {
		ReconBot::instance()->cacheClear();
		return ReconBot::instance()->logJsData($input['data']);
	}

	/**
	 * Удаление одного блока
	 */
	public static function deleteVisitDetails($input, &$return_metadata, $DB) {

		// в качестве идентификатора строки у нас будет идентификатор сессии
		$DB
			->prepare('delete from `data` where `session_id` = :session_id')
			->execute(array(':session_id' => $input['row_id']));

		$return_metadata = array('type' => 'command', 'command' => 'reload');
		return 'OK';
	}

	/**
	 * Удаление всего журнала
	 */
	public static function deleteVisitDetailsAll($input, &$return_metadata, $DB) {

		$DB->exec('delete from `data`');
		$return_metadata = array('type' => 'command', 'command' => 'reload');
		return 'OK';
	}

	/**
	 * Подробности одного захода
	 */
	public static function showVisitDetails($input, &$return_metadata, $DB) {

		if (!preg_match('~^\w+$~', $input['row_id'])) {
			$visit_id = session_id();
		} else {
			$visit_id = $input['row_id'];
		}

		$data = $DB->query('select * from `data` where `session_id` = \''.$visit_id.'\' order by `param`')->fetchAll(PDO::FETCH_ASSOC);
		$result = '';

		$tr = '<tr><td><div>%s</div></td><td><div>%s</div></td></tr>';
		$table = '<div class="showme"><table>%s</table></div>';

		foreach ($data as $row) {
			$result .= sprintf($tr, $row['param'], $row['value']);
		}
		$full = sprintf($table, $result);
		$return_metadata['type'] = 'html';
		return $full;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// экспортные развлечения /////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*
-- скрипт для проверки того, затирает ли мультитабличный апдейт
-- записи, которых нет в добавлеяемой таблице
-- по окончанию теста первая запись в таблице t1 должна быть (1, 1)

create table t1 (id int, data int);
create table t2 (id int, data int);

insert into t1 (id, data) values (1, 1);
insert into t1 (id, data) values (2, 0);
insert into t1 (id, data) values (3, 0);

insert into t2 (id, data) values (2, 1);

update t1, t2 set t1.data = t2.data where t1.id = t2.id;
*/

	/**
	 * Таблица для экспорта - просто запишем в CSV
	 */
	public static function writeExportTable($input, &$return_metadata, $DB) {

		file_put_contents('export.txt', '');
		$data_source = $DB->query('select * from `export` order by `stamp`');

		$data = $data_source->fetch(PDO::FETCH_ASSOC);

		$out = '';
		foreach ($data as $index => $data_elem) {
			$out .= ($out ? ';' : '') . '"' . $index. '"';
		}
		file_put_contents('export.txt', $out.PHP_EOL, FILE_APPEND);

		while (true) {
			$out = '';
			foreach ($data as $index => $data_elem) {
				$out .= ($out ? ';' : '') . '"' . $data_elem. '"';
			}
			file_put_contents('export.txt', $out.PHP_EOL, FILE_APPEND);

			$data = $data_source->fetch(PDO::FETCH_ASSOC);
			if ( ! $data) {
				break;
			}
		}
		return 'OK';
	}

	/**
	 * Таблица со статистикой
	 */
	public static function recalcStatTable($input, &$return_metadata, $DB) {

        self::getDB()->exec('delete from stats');
        self::getDB()->exec('insert into stats (id, code, caption) select id, code, caption from modules order by code');
        self::getDB()->exec('update stats, (select param, count(value) as c from data group by param) s1 set stats.data_total = s1.c where s1.param = stats.code');
        self::getDB()->exec('update stats, (select param, count(distinct value) as c from data group by param) s1 set stats.data_diff = s1.c where s1.param = stats.code');


        $return_metadata = array('type' => 'command', 'command' => 'reload');
        return 'stats recalc completed';
    }

	/**
	 * Таблица для экспорта - пересоздание
	 */
	private static function exportTableReCreate($table_name) {

		// сама таблица и два поля для начала
		self::getDB()->exec('drop table if exists `'.$table_name.'`');
		self::getDB()->exec('create table `'.$table_name.'` (`session_id` varchar(60), `stamp` varchar(20))');
		self::getDB()->exec('alter table `'.$table_name.'` add index ( `session_id` )');

        return 'table re-created';
	}

	/**
	 * Таблица для экспорта - обновление колонок
	 */
	private static function exportTableUpdateColumns($table_name) {

		// список имеющихся полей
		$existing_fields = array();
		foreach (self::getDB()->query('describe `'.$table_name.'`')->fetchAll(PDO::FETCH_ASSOC) as $existing_field_info) {
			$existing_fields[] = $existing_field_info['Field'];
		}

		foreach (self::getDB()->query('select `code` from `modules`')->fetchAll(PDO::FETCH_ASSOC) as $required_field_info) {
			if ( ! in_array($required_field_info['code'], $existing_fields)) {
				self::getDB()->exec('alter table `export` add `'.$required_field_info['code'].'` varchar(200) null');
			}
		}

        return 'columns updated';
	}

	/**
	 * Таблица для экспорта - стартовые данные (сессии и время)
	 */
	private static function exportTableUpdateMainInfo($table_name) {

		// список сессий
		self::getDB()->exec(
            ' insert into `'.$table_name.'` (`session_id`) '.
            ' select distinct `session_id` from `data` '.
            ' where not (`session_id` in (select `session_id` from `'.$table_name.'`))'
           );

		// время
		self::getDB()->exec(
			' update `'.$table_name.'`, (select `session_id`, max(`stamp`) as `maxtime` from `data` group by `session_id`) `mtime` '.
			' set `'.$table_name.'`.`stamp` = `mtime`.`maxtime` '.
			' where `mtime`.`session_id` = `'.$table_name.'`.`session_id`'
		);

        return 'primary data updated';
	}

   	/**
	 * Таблица для экспорта - одна колонка с данными
	 */
	private static function exportTableUpdateData($table_name, $code) {
        self::getDB()->exec(
            ' update `'.$table_name.'`, `data` '.
            ' set `'.$table_name.'`.`'.$code.'` = `data`.`value` '.
            ' where `data`.`session_id` = `'.$table_name.'`.`session_id` and `data`.`param` = \''.$code.'\''
        );
    }

    public static function exportGetFieldList($input, &$return_metadata, $DB) {
        $result = array();
		foreach (self::getDB()->query('select `code` from `modules`')->fetchAll(PDO::FETCH_ASSOC) as $required_field_info) {
			$result[] = $required_field_info['code'];
		}
        return implode(',', $result);
    }



	/**
	 * Приблуда для работы с формой-апдейтером
	 */
	public static function exportOneStep($input, &$return_metadata, $DB) {

        $table_name = 'export';

        switch ($input['step']) {

            case 'recreate':
                return self::exportTableReCreate($table_name);
                break;

            case 'addcolumns':
                return self::exportTableUpdateColumns($table_name);
                break;

            case 'maininfo':
                return self::exportTableUpdateMainInfo($table_name);
                break;

            case 'updatedata':
                $test_field_name = $input['field'];
                foreach ($DB->query('describe `'.$table_name.'`')->fetchAll(PDO::FETCH_ASSOC) as $existing_field_info) {
                    if ($existing_field_info['Field'] == $test_field_name) {
                        self::exportTableUpdateData($table_name, $test_field_name);
                        return $test_field_name;
                        break;
                    }
                }
                return 'ERR: field not found';
                break;
        }
		return 'bad action';
	}



	/**
	 * Поиск похожих
	 */
	public static function showSimilarVisits($input, &$return_metadata, $DB) {

		// сюда сложим найденные записи
		$elems = array();

		// подготовим коэффициенты
		$stat_info = $DB->query('select * from stats')->fetchAll(PDO::FETCH_ASSOC);
		$weights = array();
		foreach ($stat_info as $stat_elem) {
			$weights[$stat_elem['code']] = ($stat_elem['data_diff'] * 100 / $stat_elem['data_total']);
		}

		// сначала по кукису - это 100% попадание, если клиент не спер чей-то кукис
		$current_elem = $DB->query('select * from export where session_id = \''.$input['row_id'].'\'')->fetch(PDO::FETCH_ASSOC);

		// тащим все записи, будем сравнивать
		$query = $DB->query('select * from export limit 0, 30000');
		//$query = $DB->query('select * from export where session_id = \'4bsni4hmhil9cnr6dv7jb776e1\' limit 0, 30000');

		while ($test_elem = $query->fetch(PDO::FETCH_ASSOC)) {
			$similarity = self::getSimilarity($current_elem, $test_elem, $weights);
			if ($similarity > 0.25) {
				$elems[$test_elem['session_id']] = array(
					'sim'  => sprintf('%3.3f', $similarity),
					'data' => $test_elem,
				);
			}
		}
		
		uasort($elems, function($a, $b) { if ($b['sim']<$a['sim']) return -1; if ($b['sim']==$a['sim']) return 0; return 1; });
		$elems = array_slice($elems, 0, 10);
		
		$xml = array_to_xml(
			array('reference' => $current_elem, 'similars'  => $elems),
			array('compare-data',	'', 'elem')
		);
		$return_metadata = array('type' => 'html');
		return XSLTransform($xml->saveXML(), __DIR__.'/../xsl/similars.xsl');
	}


	/**
	 * Определение похожести двух элементов
	 * На входе два сравниваемых массива и набор весовых коэффициентов в формате code=>weight
	 */
	public static function getSimilarity($ref_data, $test_data, $modules) {

		$weight100 = array_sum($modules);

		$total = 0;
		foreach ($ref_data as $code => $ref_value) {
			if (array_key_exists($code, $test_data) && array_key_exists($code, $modules) && ($ref_value == $test_data[$code])) {
				$total += $modules[$code];
			}
		}
		return $total / $weight100;
	}

}
