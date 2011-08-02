<?php
/**
 * Oracle layer for DBO.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.model.datasources.dbo
 * @since         CakePHP v 1.2.0.4041
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('DboSource', 'Model/Datasource');

/**
 * Oracle layer for DBO.
 *
 * Long description for class
 *
 * @package       cake.libs.model.datasources.dbo
 */
class Oracle extends DboSource {

/**
 * Driver description
 *
 * @var string
 */
	public $description = "Oracle DBO Driver";

/**
* Starting quote character for quoted identifiers
*
* @var string
*/
	public $startQuote = "";
	
/**
 * Ending quote character for quoted identifiers
 *
 * @var string
 */
	public $endQuote = "";
	
/**
 * Creates a map between field aliases and numeric indexes.  Workaround for the
 * SQL Server driver's 30-character column name limitation.
 *
 * @var array
 */
	protected $_fieldMappings = array();
	
/**
 * Storing the last affected value
 *
 * @var mixed
 */
	protected $_lastAffected = false;
	
/**
 * Base configuration settings for Oracle driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => 'xe',
		'nls_sort' => '',
		'nls_comp' => ''
	);
	
/**
 * Column definitions
 *
 * @var array
 * @access public
 */
	public $columns = array(
		'primary_key' => array('name' => ''),
		'string' => array('name' => 'varchar2', 'limit' => '255'),
		'text' => array('name' => 'varchar2'),
		'integer' => array('name' => 'number'),
		'float' => array('name' => 'float'),
		'datetime' => array('name' => 'date', 'format' => 'Y-m-d H:i:s'),
		'timestamp' => array('name' => 'date', 'format' => 'Y-m-d H:i:s'),
		'time' => array('name' => 'date', 'format' => 'Y-m-d H:i:s'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d H:i:s'),
		'binary' => array('name' => 'bytea'),
		'boolean' => array('name' => 'boolean'),
		'number' => array('name' => 'number'),
		'inet' => array('name' => 'inet'));
	

/**
 * Index of basic SQL commands
 *
 * @var array
 */
	protected $_commands = array(
			'begin'    => 'BEGIN',
			'commit'   => 'COMMIT',
			'rollback' => 'ROLLBACK'
	);
	
/**
 * Define if the last query had error
 *
 * @var string
 */
	private $__lastQueryHadError = false;
	
/**
 * Magic column name used to provide pagination support for SQLServer 2008
 * which lacks proper limit/offset support.
 */
	const ROW_COUNTER = '_cake_page_rownum_';
	
/**
 * The version of SQLServer being used.  If greater than 11
 * Normal limit offset statements will be used
 *
 * @var string
 */
	protected $_version;

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	public function connect() {
		$config = $this->config;
		$this->connected = false;
		$host = $config['host'] . (!empty($config['port']) ? ':' . $config['port'] : '');
		$dbname = $config['database'];
		$charset = !empty($config['charset']) ? $config['charset'] : '';
		
		try {
			if (!$config['persistent']) {
				$this->_connection = new PDO(
					'oci:host=' . $host . ';dbname='. $dbname . ';charset=' . $charset,
					$config['login'], 
					$config['password']);
			} else {
				$this->_connection = new PDO('oci:host=' . $host . ';dbname='. $dbname . ';charset=' . $charset,
					$config['login'], 
					$config['password'],
					array(PDO::ATTR_PERSISTENT => true));
			}
			
			if (!empty($config['nls_sort'])) {
				$this->execute('ALTER SESSION SET NLS_SORT='.$config['nls_sort']);
			}
			
			if (!empty($config['nls_comp'])) {
				$this->execute('ALTER SESSION SET NLS_COMP='.$config['nls_comp']);
			}
			$this->_execute("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");
			$this->connected = true;
		} catch (PDOException $pdoe) {
			$this->connected = false;
			throw $pdoe;
		}
		return $this->connected;
	}
	
/**
* Check whether the MySQL extension is installed/loaded
*
* @return boolean
*/
	public function enabled() {
		return in_array('oci', PDO::getAvailableDrivers());
	}

/**
* Returns an array of sources (tables) in the database.
*
* @return array Array of tablenames in the database
*/
	public function listSources() {
		$cache = parent::listSources();
		if ($cache !== null) {
			return $cache;
		}
		$result = $this->_execute("SELECT view_name AS name FROM all_views UNION SELECT table_name AS name FROM all_tables");
		if (!$result) {
			$result->closeCursor();
			return array();
		} else {
			$tables = array();
	
			while ($line = $result->fetch()) {
				$tables[] = $line[0];
			}
	
			$result->closeCursor();
			parent::listSources($tables);
			return $tables;
		}
	}
	
	/**
	* Returns an array of the fields in given table name.
	*
	* @param Model $model Model object to describe
	* @return array Fields in table. Keys are name and type
	*/
	public function describe($model) {
		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}
		$fields = array();
		$table = $this->fullTableName($model, false);
		$cols = $this->_execute(
				"SELECT 
					COLUMN_NAME as \"Field\",
					DATA_TYPE as \"Type\", 
					DATA_LENGTH as \"Length\", 
					NULLABLE As \"Null\", 
					DATA_DEFAULT as \"Default\", 
					(SELECT NVL2(cols.column_name, 1, 0) FROM all_constraints cons, all_cons_columns cols WHERE cons.constraint_type = 'P' AND cons.constraint_name = cols.constraint_name AND cons.owner = cols.owner AND cols.column_name = atc.column_name AND cols.table_name = '" . $table . "') \"Key\",
					DATA_SCALE as \"Size\" 
				FROM all_tab_columns atc 
				WHERE TABLE_NAME = '" . $table . "'"
		);
		if (!$cols) {
			throw new CakeException(__d('cake_dev', 'Could not describe table for %s', $model->name));
		}
	
		foreach ($cols as $column) {
			$field = $column->Field;
			$fields[$field] = array(
					'type' => $this->column($column),
					'null' => ($column->Null === 'YES' ? true : false),
					'default' => preg_replace("/^[(]{1,2}'?([^')]*)?'?[)]{1,2}$/", "$1", $column->Default),
					'length' => $this->length($column),
					'key' => ($column->Key == '1') ? 'primary' : false
			);
	
			if ($fields[$field]['default'] === 'null') {
				$fields[$field]['default'] = null;
			} else {
				$this->value($fields[$field]['default'], $fields[$field]['type']);
			}
	
			if ($fields[$field]['key'] !== false && $fields[$field]['type'] == 'integer') {
				$fields[$field]['length'] = 11;
			} elseif ($fields[$field]['key'] === false) {
				unset($fields[$field]['key']);
			}
			if (in_array($fields[$field]['type'], array('date', 'time', 'datetime', 'timestamp'))) {
				$fields[$field]['length'] = null;
			}
			if ($fields[$field]['type'] == 'float' && !empty($column->Size)) {
				$fields[$field]['length'] = $fields[$field]['length'] . ',' . $column->Size;
			}
		}
		$this->__cacheDescription($table, $fields);
		$cols->closeCursor();
		return $fields;
	}

/**
* Generates the fields list of an SQL query.
*
* @param Model $model
* @param string $alias Alias tablename
* @param mixed $fields
* @return array
*/
	public function fields($model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$fields = parent::fields($model, $alias, $fields, false);
		$count = count($fields);
	
		if ($count >= 1 && strpos($fields[0], 'COUNT(*)') === false) {
			$result = array();
			for ($i = 0; $i < $count; $i++) {
				$prepend = '';
	
				if (strpos($fields[$i], 'DISTINCT') !== false) {
					$prepend = 'DISTINCT ';
					$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
				}
	
				if (!preg_match('/\s+AS\s+/i', $fields[$i])) {
					if (substr($fields[$i], -1) == '*') {
						if (strpos($fields[$i], '.') !== false && $fields[$i] != $alias . '.*') {
							$build = explode('.', $fields[$i]);
							$AssociatedModel = $model->{$build[0]};
						} else {
							$AssociatedModel = $model;
						}
	
						$_fields = $this->fields($AssociatedModel, $AssociatedModel->alias, array_keys($AssociatedModel->schema()));
						$result = array_merge($result, $_fields);
						continue;
					}
	
					if (strpos($fields[$i], '.') === false) {
						$this->_fieldMappings[$alias . '__' . $fields[$i]] = $alias . '.' . $fields[$i];
						$fieldName  = $this->name($alias . '.' . $fields[$i]);
						$fieldAlias = $this->name($alias . '__' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$this->_fieldMappings[$build[0] . '__' .$build[1]] = $fields[$i];
						$fieldName  = $this->name($build[0] . '.' . $build[1]);
						$fieldAlias = $this->name(preg_replace("/^\[(.+)\]$/", "$1", $build[0]) . '__' . $build[1]);
					}
					if ($model->getColumnType($fields[$i]) == 'datetime') {
						$fieldName = "TO_CHAR({$fieldName}, 'YYYY-MM-DD HH24:MI:SS')";
					}
					$fields[$i] =  "{$fieldName} AS {$fieldAlias}";
				}
				$result[] = $prepend . $fields[$i];
			}
			return $result;
		} else {
			return $fields;
		}
	}

	
/**
* Returns a limit statement in the correct format for the particular database.
*
* @param integer $limit Limit of results returned
* @param integer $offset Offset from which to start results
* @return string SQL limit/offset statement
*/
	public function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'top') || strpos(strtolower($limit), 'top') === 0) {
				$rt = ' TOP';
			}
			$rt .= ' ' . $limit;
			if (is_int($offset) && $offset > 0) {
				$rt = ' OFFSET ' . intval($offset)  . ' ROWS FETCH FIRST ' . intval($limit) . ' ROWS ONLY';
			}
			return $rt;
		}
		return null;
	}
/**
 * Builds final SQL statement
 *
 * @param string $type Query type
 * @param array $data Query data
 * @return string
 */
	public function renderStatement($type, $data) {
		switch (strtolower($type)) {
			case 'select':
				extract($data);
				$fields = trim($fields);

				if (strpos($limit, 'TOP') !== false && strpos($fields, 'DISTINCT ') === 0) {
					$limit = 'DISTINCT ' . trim($limit);
					$fields = substr($fields, 9);
				}
				
				// hack order as SQLServer requires an order if there is a limit.
 				if ($limit && !$order) {
 					$orderFields = split('AS',substr($fields, 9));
 					$order = 'ORDER BY ' . trim($orderFields[0]);
 				}
				// For older versions use the subquery version of pagination.
				if (preg_match('/FETCH\sFIRST\s+([0-9]+)/i', $limit, $offset)) {
					preg_match('/OFFSET\s*(\d+)\s*.*?(\d+)\s*ROWS/', $limit, $limitOffset);
					$limit = 'TOP ' . intval($limitOffset[2]);
					$page = intval($limitOffset[1] / $limitOffset[2]);
					$offset = intval($limitOffset[2] * $page);
					$offsetUp = $offset+$limitOffset[2];
					$rowCounter = self::ROW_COUNTER;
					return "
						SELECT * FROM (
							SELECT {$fields}, ROW_NUMBER() OVER ({$order}) AS {$rowCounter}
							FROM {$table} {$alias} {$joins} {$conditions} {$group}
						) AS _cake_paging_
						WHERE _cake_paging_.{$rowCounter} BETWEEN {$offset} AND {$offsetUp}
						ORDER BY _cake_paging_.{$rowCounter}
					";
				} elseif (strpos($limit, 'FETCH') !== false) {
					return "SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order} {$limit}";
				} else {
					return "SELECT {$limit} {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order}";
				}
			break;
			case "schema":
				extract($data);

				foreach ($indexes as $i => $index) {
					if (preg_match('/PRIMARY KEY/', $index)) {
						unset($indexes[$i]);
						break;
					}
				}

				foreach (array('columns', 'indexes') as $var) {
					if (is_array(${$var})) {
						${$var} = "\t" . implode(",\n\t", array_filter(${$var}));
					}
				}
				return "CREATE TABLE {$table} (\n{$columns});\n{$indexes}";
			break;
			default:
				return parent::renderStatement($type, $data);
			break;
		}
	}
	
/**
 * Generates and executes an SQL INSERT statement for given model, fields, and values.
 * Removes Identity (primary key) column from update data before returning to parent, if
 * value is empty.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return array
 */
	public function create($model, $fields = null, $values = null) {
		if (!empty($values)) {
			$fields = array_combine($fields, $values);
		}
		$primaryKey = $this->_getPrimaryKey($model);
	
		if (array_key_exists($primaryKey, $fields)) {
			if (empty($fields[$primaryKey])) {
				unset($fields[$primaryKey]);
			} else {
				$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($model) . ' ON');
			}
		}
		$result = parent::create($model, array_keys($fields), array_values($fields));
		if (array_key_exists($primaryKey, $fields) && !empty($fields[$primaryKey])) {
			$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($model) . ' OFF');
		}
		return $result;
	}
/**
* Returns a quoted and escaped string of $data for use in an SQL statement.
*
* @param string $data String to be prepared for use in an SQL statement
* @param string $column The column into which this data will be inserted
* @return string Quoted and escaped data
*/
	public function value($data, $column = null) {
		if (is_array($data) || is_object($data)) {
			return parent::value($data, $column);
		} elseif (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'), true)) {
			return $data;
		}
	
		if (empty($column)) {
			$column = $this->introspectType($data);
		}
	
		switch ($column) {
			case 'string':
			case 'text':
				return 'N' . $this->_connection->quote($data, PDO::PARAM_STR);
			default:
				return parent::value($data, $column);
		}
	}

/**
* Makes sure it will return the primary key
*
* @param mixed $model Model instance of table name
* @return string
*/
	protected function _getPrimaryKey($model) {
		if (!is_object($model)) {
			$model = new Model(false, $model);
		}
		$schema = $this->describe($model);
		foreach ($schema as $field => $props) {
			if (isset($props['key']) && $props['key'] == 'primary') {
				return $field;
			}
		}
		return null;
	}
/**
 * Sets the encoding language of the session
 *
 * @param string $lang language constant
 * @return bool
 */
	public function setEncoding($lang) {
		if (!$this->_execute('ALTER SESSION SET NLS_LANGUAGE='.$lang)) {
			return false;
		}
		return true;
	}

/**
 * Gets the current encoding language
 *
 * @return string language constant
 */
	public function getEncoding() {
		$sql = 'SELECT VALUE FROM NLS_SESSION_PARAMETERS WHERE PARAMETER=\'NLS_LANGUAGE\'';
		
		if (!$this->_execute($sql)) {
			return false;
		}

		if (!$row = $this->fetchRow()) {
			return false;
		}
		return $row[0]['VALUE'];
	}

}
