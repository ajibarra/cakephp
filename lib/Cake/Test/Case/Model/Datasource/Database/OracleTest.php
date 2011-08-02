<?php
/**
 * DboOracleTest file
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
 * @package       cake.libs
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Oracle', 'Model/Datasource/Database');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';



/**
* SqlserverTestDb class
*
* @package       cake.tests.cases.libs.model.datasources.dbo
*/
class OracleTestDb extends Oracle {

	/**
	 * simulated property
	 *
	 * @var array
	 */
	public $simulated = array();

	/**
	 * execute results stack
	 *
	 * @var array
	 */
	public $executeResultsStack = array();

	/**
	 * execute method
	 *
	 * @param mixed $sql
	 * @return mixed
	 */
	protected function _execute($sql) {
		$this->simulated[] = $sql;
		return empty($this->executeResultsStack) ? null : array_pop($this->executeResultsStack);
	}

	/**
	 * fetchAll method
	 *
	 * @param mixed $sql
	 * @return void
	 */
	protected function _matchRecords($model, $conditions = null) {
		return $this->conditions(array('id' => array(1, 2)));
	}

	/**
	 * getLastQuery method
	 *
	 * @return string
	 */
	public function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}

	/**
	 * getPrimaryKey method
	 *
	 * @param mixed $model
	 * @return string
	 */
	public function getPrimaryKey($model) {
		return parent::_getPrimaryKey($model);
	}

	/**
	 * clearFieldMappings method
	 *
	 * @return void
	 */
	public function clearFieldMappings() {
		$this->_fieldMappings = array();
	}

	/**
	 * describe method
	 *
	 * @param object $model
	 * @return void
	 */
	public function describe($model) {
		return empty($this->describe) ? parent::describe($model) : $this->describe;
	}
}

/**
 * SqlserverTestModel class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class OracleTestModel extends Model {

	/**
	 * name property
	 *
	 * @var string 'SqlserverTestModel'
	 */
	public $name = 'OracleTestModel';

	/**
	 * useTable property
	 *
	 * @var bool false
	 */
	public $useTable = false;

	/**
	 * _schema property
	 *
	 * @var array
	 */
	protected $_schema = array(
		'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
		'client_id'	=> array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
		'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'login'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'passwd'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_1'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_2'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
		'zip_code'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'city'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'country'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'phone'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'fax'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'url'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'comments'	=> array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
		'last_login'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'created'	=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

	/**
	 * belongsTo property
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'OracleClientTestModel' => array(
			'foreignKey' => 'client_id'
	)
	);
	/**
	 * find method
	 *
	 * @param mixed $conditions
	 * @param mixed $fields
	 * @param mixed $order
	 * @param mixed $recursive
	 * @return void
	 */
	public function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
}

/**
 * SqlserverClientTestModel class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class OracleClientTestModel extends Model {
	/**
	 * name property
	 *
	 * @var string 'SqlserverAssociatedTestModel'
	 */
	public $name = 'OracleClientTestModel';

	/**
	 * useTable property
	 *
	 * @var bool false
	 */
	public $useTable = false;

	/**
	 * _schema property
	 *
	 * @var array
	 */
	protected $_schema = array(
		'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
		'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'created'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
}

/**
 * SqlserverTestResultIterator class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class OracleTestResultIterator extends ArrayIterator {
	/**
	 * closeCursor method
	 *
	 * @return void
	 */
	public function closeCursor() {
	}
}
/**
 * DboOracleTest class
 *
 * @package       cake.tests.cases.libs.model.datasources.dbo
 */
class OracleTest extends CakeTestCase {


/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!($this->Dbo instanceof Oracle)) {
			$this->markTestSkipped('Please configure the test datasource to use SQL Server.');
		}
		$this->db = new OracleTestDb($this->Dbo->config);
		$this->model = new OracleTestModel();
	}

/**
* tearDown method
*
* @return void
*/
	public function tearDown() {
		unset($this->Dbo);
		unset($this->model);
	}
	
/**
* testConnect method
*
* @access public
* @return void
*/
	public function testConnect() {
		$config = $this->db->config;
		$old_pw = $this->db->config['password'];
		$this->db->config['password'] = 'keepmeout';
		try {
			$this->db->connect();
			$this->fail();
		} catch (PDOException $pdoe) {
			$r = '/ORA-01017: invalid username\/password; logon denied/';
			$this->assertPattern($r, $pdoe->getMessage());
		}
		$this->db->config['password'] = $old_pw;
		$this->assertTrue($this->db->connect());
	}
	
/**
* testFields method
*
* @return void
*/
	public function testFields() {
		$fields = array(
			'OracleTestModel.id AS OracleTestModel__id',
			'OracleTestModel.client_id AS OracleTestModel__client_id',
			'OracleTestModel.name AS OracleTestModel__name',
			'OracleTestModel.login AS OracleTestModel__login',
			'OracleTestModel.passwd AS OracleTestModel__passwd',
			'OracleTestModel.addr_1 AS OracleTestModel__addr_1',
			'OracleTestModel.addr_2 AS OracleTestModel__addr_2',
			'OracleTestModel.zip_code AS OracleTestModel__zip_code',
			'OracleTestModel.city AS OracleTestModel__city',
			'OracleTestModel.country AS OracleTestModel__country',
			'OracleTestModel.phone AS OracleTestModel__phone',
			'OracleTestModel.fax AS OracleTestModel__fax',
			'OracleTestModel.url AS OracleTestModel__url',
			'OracleTestModel.email AS OracleTestModel__email',
			'OracleTestModel.comments AS OracleTestModel__comments',
			'TO_CHAR(OracleTestModel.last_login, \'YYYY-MM-DD HH24:MI:SS\') AS OracleTestModel__last_login',
			'OracleTestModel.created AS OracleTestModel__created',
			'TO_CHAR(OracleTestModel.updated, \'YYYY-MM-DD HH24:MI:SS\') AS OracleTestModel__updated',
		);
	
		$result = $this->db->fields($this->model);
		$expected = $fields;
		$this->assertEqual($expected, $result);
	
		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, 'OracleTestModel.*');
		$expected = $fields;
		$this->assertEqual($expected, $result);
	
		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, array('*', 'AnotherModel.id', 'AnotherModel.name'));
		$expected = array_merge($fields, array(
				'AnotherModel.id AS AnotherModel__id',
				'AnotherModel.name AS AnotherModel__name'));
		$this->assertEqual($expected, $result);
	
		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, array('*', 'OracleClientTestModel.*'));
		$expected = array_merge($fields, array(
				'OracleClientTestModel.id AS OracleClientTestModel__id',
				'OracleClientTestModel.name AS OracleClientTestModel__name',
				'OracleClientTestModel.email AS OracleClientTestModel__email',
				'TO_CHAR(OracleClientTestModel.created, \'YYYY-MM-DD HH24:MI:SS\') AS OracleClientTestModel__created',
				'TO_CHAR(OracleClientTestModel.updated, \'YYYY-MM-DD HH24:MI:SS\') AS OracleClientTestModel__updated'));
		$this->assertEqual($expected, $result);
	}
	
/**
* testDistinctFields method
*
* @return void
*/
	public function testDistinctFields() {
		$result = $this->db->fields($this->model, null, array('DISTINCT Car.country_code'));
		$expected = array('DISTINCT Car.country_code AS Car__country_code');
		$this->assertEqual($expected, $result);
	
		$result = $this->db->fields($this->model, null, 'DISTINCT Car.country_code');
		$expected = array('DISTINCT Car.country_code AS Car__country_code');
		$this->assertEqual($expected, $result);
	}
	
/**
* testDistinctWithLimit method
*
* @return void
*/
	public function testDistinctWithLimit() {
		$this->db->read($this->model, array(
				'fields' => array('DISTINCT OracleTestModel.city', 'OracleTestModel.country'),
				'limit' => 5
		));
		$result = $this->db->getLastQuery();
		$this->assertPattern('/^SELECT DISTINCT TOP 5/', $result);
	}
	
/**
* testConnect method
*
* @access public
* @return void
*/
// 	public function testListSources() {
// 		try {
// 			$this->db->connect();
// 			$tables = $this->db->listSources();
//			Checking tables length
// 			$this->assertTrue(count($tables) > 0);
//			Checking a system table
// 			$this->assertTrue(in_array('USER_TABLES', $tables));
//			Checking a system view
// 			$this->assertTrue(in_array('USER_INDEXES', $tables));
// 		} catch (PDOException $pdoe) {
// 			$this->fail($pdoe->getMessage());
// 		}
		
// 	}

}
