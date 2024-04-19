<?php

// FST Application Framework, Version 6.0
// Copyright (c) 2004-22, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Database and connection management for MySQL Databases.
 *
 * A class for managing MySQL databases accessible via the PHP PDO library.
 * Allows definition of database aliases to multiple databases.
 *
 * This library was built and tested with a MySQL database using the PDO
 * library. It *may* work with other PDO compatible databases, but is not
 * tested with databases other than MySQL.
 *
 * This library was introduced in FST version 5.4 and is to be
 * considered experimental. It is being developed as an alternative to the
 * now-unmaintained PHP ActiveRecord library, which is not part of but often
 * used with the FST Application Framework.
 */
final class MySQL {

	/** @ignore */
	static private $_databases = array(); // PDO Database connections
	/** @ignore */
	static private $_database_default = false; // Default database connection
	/** @ignore */
	static private $_user = null; // Username for database access
	/** @ignore */
	static private $_user_pass = null; // Password for database access

	/**
	 * Provides user authorization information to the database manager.
	 *
	 * Provides the username and password to be used for database connections.
	 * All databases connections made after calling this function will use
	 * the same user credentials. If multiple databases are used with different
	 * credentials, this function may be called subsequent times to change
	 * the authentication information.
	 *
	 * @param string $user Database username
	 * @param string $pass Database password
	 */
	static public function auth ($user, $pass)
		{ static::$_user = $user; static::$_user_pass = $pass; }

	/**
	 * Specifies a database with a database alias name.
	 *
	 * Specifies the database alias to be used to access the given database.
	 * If the database name is not provided, the given alias is used also
	 * as the database name. If a host name is not given, 'localhost' is
	 * used. The first database defined with this function becomes the default
	 * database.
	 *
	 * @param string $alias Database alias name
	 * @param string $database Database name (optional)
	 * @param string $host Database host name/address (default localhost)
	 * @param string $engine Database engine (default mysql)
	 */
	static public function database ($alias,
			$database=null, $host='localhost', $engine='mysql') {
		if (!$database)
			$database = $alias;
		if (array_key_exists($alias, static::$_databases))
			throw new UsageException('Database already defined');
		try {
			static::$_databases[$alias] =
				new \PDO("$engine:host=$host;dbname=$database",
					static::$_user, static::$_user_pass,
					array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
		}
		catch (\PDOException $e) {
			throw new DatabaseException($e->getMessage());
		}
		if (!static::$_database_default)
			static::$_database_default = static::$_databases[$alias];
	}

	/**
	 * Get auto-increment id of last insert.
	 *
	 * Typically called on the same database immediately following a call to
	 * MySQL::query with the same database, returns the value of the id of
	 * the last insert query.
	 *
	 * @param string $alias Database alias (optional)
	 * @return string Id of the most recent insert
	 */
	static public function insert_id ($alias=null)
		{ return static::_db($alias)->lastInsertId(); }

	/**
	 * Executes a query against a database.
	 *
	 * This function executes a query against the database associated with
	 * the given alias. If the query is a SELECT query, an array of annonymous
	 * objects is returned with each object representing a row of the result
	 * set. If not a SELECT query, an integer is returned indicating the
	 * number of rows affected by the query. Since SELECT query results are
	 * returned as objects, one should take care that column names in the
	 * query are valid PHP identifiers.
	 *
	 * The $sql parameter may be given as a string or an array. If an array,
	 * the first element is the SQL statement with placeholders, and the
	 * remaining elements are arguments.
	 *
	 * If the $alias parameter is not given, the default database is used.
	 *
	 * @param mixed $sql An SQL query
	 * @param string $alias Database alias (optional)
	 * @return mixed Query results
	 */
	static public function query ($sql, $alias=null) {
		try {
			if (is_array($sql)) {
				$stmt = static::_db($alias)->prepare($sql[0]);
				$stmt->execute(array_slice($sql, 1));
			}
			else
				$stmt = static::_db($alias)->query($sql);
		}
		catch (\PDOException $e) {
			throw new DatabaseException($e->getMessage());
		}
		try {
			// If the query has a result set, return it as an array of
			// anonymous objects. If no result set, this will throw an
			// exception.
			return $stmt->fetchAll(\PDO::FETCH_OBJ);
		}
		catch (\PDOException $e) {
			// No result set, return number of rows affected by the query.
			return $stmt->rowCount();
		}
	}

	/**
	 * Get PDO object for database.
	 *
	 * Used to retrieve PDO database connection object for a given alias name.
	 * If an alias is not given, the default (first) database connection
	 * object is returned.
	 *
	 * @param string $alias Database alias (optional)
	 * @return object PDO database object
	 */
	static public function _db ($alias=null) {
		if (!$alias && static::$_database_default)
			return static::$_database_default;
		if (!$alias || !array_key_exists($alias, static::$_databases))
			throw new DatabaseException('Database alias ' .
				($alias ? $alias : '<default>') . ' not defined');
		return static::$_databases[$alias];
	}
}

/**
 * Base class to represent records in a MySQL database table.
 *
 * Each table is represented by a class that is derived from this abstract
 * base class. Derived classes may specify the database alias and table in
 * which the records reside. If a database alias is not provided, the default
 * database is used. If the table name is not provided, the table name is
 * derived from the class name.
 *
 * The find static method is used to create objects of this class to
 * represent individual records from the table. Instantiation of a new object
 * of the derived class represents creation of a new record. Object properties,
 * other than those starting with an underscore, correspond to fields of the
 * record.
 */
abstract class MySQLModel {

	/**
	 * Defines the database name.
	 *
	 * Derived classes may override this property to specify the database
	 * in which the table resides. If the derived class does not
	 * override this property, the default database is used. The database
	 * must have been defined using MySQL::database prior to instantion of
	 * the first object from the derived class.
	 */
	static protected $database = null; /// Database alias name

	/**
	 * Defines the database table name.
	 *
	 * Derived classes may override this property to define the table
	 * name. If the derived class does not override this property, the
	 * table name is determined by preceeding all uppercase
	 * characters in the name (except the first character) with an underscore,
	 * converting all characters to lowercase, and adding 's'.
	 */
	static protected $table = null; /// Table name

	/**
	 * Defines the name of the primary key field.
	 *
	 * Derived classes may override this property to define the name of the
	 * primary key field. If the derived class does not override this
	 * property, 'id' is used.
	 */
	static protected $key = 'id'; /// Primary key field

	/** 
	 * Defines documents which reference this document.
	 *
	 * Derived classes may specify an associative array to define other
	 * derived classes that link to the current document (current document
	 * is the one side of a one-to-many relationship, or current document
	 * is related to another in a one-to-one relationship in which the other
	 * document contains the foreign key). Key values are used as virtual
	 * properties and functions of objects (via the __get and __call magic
	 * methods) to retrieve all documents of the other class that reference
	 * this document.
	 * 
	 * Values of the associative array include at least one or as many as
	 * three components separated by colons. The first (required) component
	 * is the class name of the document to which the current document is
	 * related. The second (optional) component is the name of the foreign
	 * key in the related document. If a foreign key is not provided, default
	 * rules for foreign key names applies. The third (optional) component
	 * is the default sort order of the related documents.
	 */
	static protected $referenced_by = array();

	/**
	 * Defines referenced documents.
	 *
	 * Derived classes may specify an associative array to link to documents
	 * from other classes. Key values are used as virtual properties of
	 * objects (via the __get magic method) to connect to a document from
	 * another table. Values of the associative array are the class
	 * name corresponding to the referenced collection, optionally followed
	 * by a colon (":") and the name of the foreign key in this
	 * document. If the foreign key is not provided, the name of the class,
	 * converted according to class-to-table-name rules, followed by "_id"
	 * is used.
	 */
	static protected $references = array();

	/** @ignore */
	private $_id = null;
	/** @ignore */
	private $_readonly = false;
	/** @ignore */
	private $_refs = array();

	/**
	 * Constructs a new document.
	 * 
	 * @param mixed $properties Associative array or object of initial properties (optional)
	 */
	public function __construct ($properties=null)
		{ if ($properties) $this->set($properties); }

	// Magic method to define functions for retrieval of objects of another
	// derived class that refernce this class. Optional arguments to the
	// function are the WHERE clause, the ORDER BY clause, and columns.
	/** @ignore */
	public function __call ($fcn, $args) {
		if (!array_key_exists($fcn, static::$referenced_by))
			throw new UsageException("Call to undefined method: $fcn");

		// If this object has no id (document is not saved), no other
		// documents will reference it.
		if (!isset($this->_id))
			return array();

		// Get class name, foreign key, and default sort order
		list($cls, $key, $srt) = explode(":", static::$referenced_by[$fcn] . "::");

		// If foreign key not explicitly given, derive from class name
		if (!$key)
			$key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $cls)) . '_id';

		// If default sort order is not explicitly given, set to null
		if (!$srt)
			$srt = null;

		// Build the WHERE clause
		$whr = array("`$key`=?", $this->_id);
		if (count($args) > 0) {
			if (is_array($args[0])) {
				$whr[0] .= ' and (' . $args[0][0] . ')';
				$whr = array_merge($whr, array_slice($args[0], 1));
			}
			else if ($args[0])
				$whr[0] .= ' and (' . $args[0] . ')';
		}

		// Set sort order if given by parameter
		if (count($args) > 1)
			$srt = $args[1];

		// Get columns if specified
		$cols = count($args) > 2 ? $args[2] : null;

		// Find documents related to this table
		return $cls::find_all($whr, $srt, null, null, $cols);
	}

	// Provides for cloning of objects. A cloned object is a copy but is
	// not saved.
	/** @ignore */
	public function __clone () { $this->_id = null; }

	// Magic method to get properties of the object that are not already
	// defined. Property '_id' may be retrieved which is a copy of the
	// primary key value, if the object has been previously saved in the
	// table, but will be null if not saved. If the property requested
	// is a referenced property, an object of the referenced class is
	// created and returned, or null is returned if none is referenced.
	// If the property has a getter (method "get_" followed by a property
	// name, that function is called to retrieve the property.
	/** @ignore */
	public function __get ($fld) {

		// May get primary key value via _id. Note that this is the primary
		// key value on the saved record. If a new record and primary key
		// as been set via its property, this will return null. If a saved
		// record but primary key value has been changed via its property,
		// this will return the (old) primary key value on the saved record.
		if ($fld == '_id')
			return $this->_id;

		// If a getter exists for field, call it
		if (method_exists($this, "get_$fld"))
			return call_user_func(array($this, "get_$fld"));

		// If getting a referenced document, return object for document.
		if (array_key_exists($fld, static::$references)) {
			// If previously retrieved, will be in $_refs array
			if (array_key_exists($fld, $this->_refs))
				return $this->_refs[$fld];
			// Get class name and foreign key for referenced document
			list($cls, $key) = explode(":", static::$references[$fld] . ":");
			// If foreign key not explicitly given, derive from class name
			if (!$key)
				$key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $cls)) . '_id';
			// Get document using find, and return it
			$doc = $cls::find_one(array('`' . $cls::$key . '`=?', $this->$key));
			// If document found, save in $_refs
			if ($doc)
				$this->_refs[$fld] = $doc;
			// Return document, or false if not found
			return $doc;
		}

		// If getting documents which reference this document, return an
		// array of objects.
		if (array_key_exists($fld, static::$referenced_by)) {
			// If current document has no primary key value, return empty array
			if (!isset($this->_id))
				return array();
			// Get class name, foreign key, and default sort order
			list($cls, $key, $srt) = explode(":", static::$referenced_by[$fld] . "::");
			// If foreign key not explicitly given, derive from class name
			if (!$key)
				$key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', get_called_class())) . '_id';
			// If default sort order not given, set to null
			if (!$srt)
				$srt = null;
			// Find documents related to this table
			return $cls::find_all(array("`$key`=?", $this->_id), $srt);
		}

		// Attempt to retrieve invalid property
		throw new UsageException(
			'Invalid property ' . static::_table() . ".$fld");
	}

	// Magic method to indicate whether the _id property is set or getter
	// exists and has non-null return value
	/** @ignore */
	public function __isset ($fld) {
		if ($fld == '_id')
			return isset($this->_id);
		return method_exists($this, "get_$fld") &&
			!is_null(call_user_func(array($this, "get_$fld")));
	}

	// Magic method to set properties that are not already set. This method
	// throws an exception on any attempt to set the _id propertie,
	// or any properties of referenced documents.
	// When setting referenced objects, the object being assigned must be
	// of the designated referenced class, else an exception is thrown.
	/** @ignore */
	public function __set ($fld, $val) {

		// May not assign reserved properties (_id, _refs)
		if (array_search($fld, array('_id', '_refs')) !== false)
			throw new UsageException('Update property ' .
				static::_table() . ".$fld not allowed");

		// If a setter exists for this field, call the setter.
		if (method_exists($this, "set_$fld"))
			return call_user_func(array($this, "set_$fld"), $val);

		// If the field being assigned is a referenced field, value must be
		// a document object of the appropriate type, a foreign key value,
		// or null.
		if (array_key_exists($fld, static::$references)) {
			list($cls, $fkey) = explode(":", static::$references[$fld] . ":");
			if (!$fkey)
				$fkey = strtolower(
					preg_replace('/(?<!^)[A-Z]/', '_$0', $cls)) . '_id';
			if (is_object($val)) {
				if (get_class($val) != $cls)
					throw new UsageException(
						"Inconsistent object assigned to referenced field");
				// Set foreign key in this document
				$this->$fkey = $val->{$cls::$key};
			}
			else if (is_scalar($val)) {
				// If a scalar value is set will assume it's a valid foreign
				// key value and just save the value. If previous referenced
				// document is cached, clear it.
				$this->$fkey = $val;
				if (isset($this->_refs[$fld]))
					unset($this->_refs[$fld]);
			}
			else
				throw new UsageException(
					'Invalid assignment to ' . static::_table() . ".$fld");
			return;
		}

		// If a setter exists for this property, call it and return
		if (method_exists($this, "set_$fld")) {
			call_user_func(array($this, "set_$fld"), $val);
			return $this->$fld;
		}

		// Assign the property (for the first time)
		return $this->$fld = $val;
	}

	/** @ignore */
	static public function __callStatic ($fcn, $args) {
		// Implements 'find_by_*', 'find_all_by_*', and 'count_by_*' static
		// methods.

		// Determine method to be called, find_one, find_all, or count
		if (substr($fcn, 0, 8) == 'find_by_')
			$method = 'find_one';
		else if (substr($fcn, 0, 12) == 'find_all_by_')
			$method = 'find_all';
		else if (substr($fcn, 0, 9) == 'count_by_')
			$method = 'count';
		else
			throw new UsageException(
				"Call to undefined static method: $fcn");

		// Get field names for query
		$fields = explode('_and_', substr($fcn, strpos($fcn, '_by_') + 4));

		// Ensure match in number of arguments. Should be one argument for
		//	each field. For find_by_* and find_all_by_*, an additional
		//	argument may be specified for sorting.
		if (count($args) == count($fields))
			$sort = null;
		else {
			if ($method == 'count' || count($args) != count($fields) + 1)
				throw new UsageException(
					"Incorrect number of arguments: $fcn");
			$sort = end($args);
			$args = array_slice($args, 0, -1);
		}

		// Build the where clause
		$where = array_merge(
			array(implode(' and ', array_map(
				function ($fld) { return "`$fld`=?"; }, $fields))),
			$args);

		// Execute query and return the results
		if ($method == 'count')
			return static::count($where);
		return static::$method($where, $sort);
	}

	/**
	 * Deletes this record from the database.
	 *
	 * The record which this object represents in the database is deleted.
	 * All properties for this object are left intact. Any attempt to save
	 * this object after deletion will result in an attempted insert.
	 */
	public function delete () {
		MySQL::query(array(
			'delete from `' . static::_table() . '` ' . 'where `' .
				static::$key . '`=?',
			$this->_id), static::$database);
		// Leave all object properties intact, but clear the protected
		// primary key value (indicating a new record).
		$this->_id = null;
	}

	/**
	 * Post-process database fields after read.
	 *
	 * Derived classes may override this method to perform any post-processing
	 * to be done to the document's data immediately after it is read from the
	 * database but before the record is passed to the set method to initialize
	 * the document's properties.
	 *
	 * The parameter given is an associative array of actual values read
	 * from the database, and should return an associative array of properties
	 * to be set for the document.
	 * 
	 * Note that the set method does not set properties that lead with an
	 * underscore. If such properties are needed for object context they
	 * may be set directly in this method.
	 *
	 * @param array $rec Associative array of field values from database
	 * @return array Associative array of field values for the object model
	 */
	protected function read ($rec) { return $rec; }

	/**
	 * Saves current document to the database.
	 *
	 * Saves the values of all object properties to the corresponding columns
	 * in the corresponding database table. Any object properties with a
	 * leading underscore are ignored. If any properties do not correspond to
	 * valid columns names, a DatabaseException exception is thrown.
	 */
	public function save () {
		// Check for read-only document.
		if ($this->_readonly)
			throw new DatabaseException('Cannot save read-only document');
		// Changing the value of the primary key is not allowed.
		if ($this->_id && $this->_id != $this->{static::$key})
			throw new UsageException('Update of primary key ' .
				static::_table() . '.' . static::$key . ' is not allowed');
		// Convert object to an array, remove all properties leading with
		// an underscore, and pass to the write method for pre-processing.
		$rec = $this->write(array_filter(get_object_vars($this),
			function ($key) { return substr($key, 0, 1) != '_'; },
			ARRAY_FILTER_USE_KEY));
		// Convert all boolean values to 0/1, and convert all DateTime objects
		// to string form.
		$rec = array_map(
			function ($val) {
					if (is_bool($val))
						return $val ? 1 : 0;
					if (is_object($val) && get_class($val) == 'DateTime')
						return $val->format('Y-m-d H:i:s');
					return $val;
				}, $rec);
		// Prepare the statement. If a new record, this will be an insert,
		// otherwise an update.
		if ($this->_id === null) {
			// Build SQL statement for insert
			$sql = 'insert into `' . static::_table() . '` ' .
				'(`' . implode('`,`', array_keys($rec)) . '`) ' .
				'values (' .
					implode(',', array_fill(0, count($rec), '?')) . ')';
		}
		else {
			// Remove primary key value from update fields
			unset($rec[static::$key]);
			// Build SQL statement for update
			$sql = 'update `' . static::_table() . '` ' .
				'set ' . implode(',', array_map(
					function ($fld) { return "`$fld`=?"; }, array_keys($rec))) .
				' where `' . static::$key . '`=?';
			// Include primary key value for query execution
			$rec[] = $this->_id;
		}

		// Execute the query
		MySQL::query(
			array_merge(array($sql), array_values($rec)), static::$database);

		// Set primary key value, if an insert
		if (!$this->_id) {
			if (isset($this->{static::$key}))
				$this->_id = $this->{static::$key};
			else
				$this->_id = $this->{static::$key} =
					MySQL::insert_id(static::$database);
		}
	}

	/**
	 * Sets document properties from associative array or object.
	 *
	 * Uses the given array or object to set properties for the current
	 * document. If an array, each key which is a valid property name,
	 * except those beginning with an underscore, is assigned to the
	 * corresponding value. If an object, the object is converted to an
	 * associative array and the array logic is used.
	 *
	 * @param mixed $properties Associative array or object of key/value pairs
	 */
	public function set ($properties) {

		if (!is_array($properties) && !is_object($properties))
			throw new UsageException(
				'Argument to MySQL::set must be array or object');

		foreach (is_object($properties) ?
				get_object_vars($properties) : $properties as $k=>$v)
			if (preg_match('/^[a-z]\w*$/i', $k))
				$this->$k = $v;
}

	/**
	 * Pre-process database fields before write.
	 *
	 * This method may be overridden in derived classes to perform any
	 * pre-processing that should occur just before writing the document to
	 * the database. The parameter given is an associative array of all
	 * properties of the document with properties having names beginning with
	 * an underscore removed, thus representing the data just about to be
	 * written to the database. The method should return an associative array
	 * of actual values to be written (by either INSERT or UPDATE).
	 *
	 * @param array $rec Associative array of field values from the model
	 * @return array Associative array of field values for the database
	 */
	protected function write ($rec) { return $rec; }

	/**
	 * Get count of records.
	 * 
	 * @param mixed $whr WHERE query clause as string or array (optional)
	 * @return int Number of records
	 */
	static public function count ($whr=null) {
		$sql = 'select count(*) as cnt from `' . static::_table() . '`';
		$args = false;
		if ($whr) {
			if (is_array($whr)) {
				$sql .= " where {$whr[0]}";
				$args = array_slice($whr, 1);
			}
			else
				$sql .= " where $whr";
		}

		// Execute query and return the count
		$qry = $args ? array_merge(array($sql), $args) : $sql;
		$rows = MySQL::query($qry, static::$database);
		return $rows[0]->cnt;
	}

	/**
	 * Retrieve distinct values of field
	 *
	 * Retrieves an array containing the distinct values for the given 
	 * field. An optional WHERE clause may be supplied to qualify the records
	 * from which the values are taken.
	 * 
	 * @param string $fld Field name
	 * @param mixed $whr WHERE query clause as string or array (optional)
	 * @return array Array of distinct values for the given field
	 */
	static public function distinct ($fld, $whr=null) {

		// Build the SELECT statement
		$sql = 'select distinct `' . $fld . '` from `' .
			static::_table() . '`';

		// Add WHERE clause and set up arguments, if specified
		$args = false;
		if ($whr) {
			if (is_array($whr)) {
				$sql .= " where {$whr[0]}";
				$args = array_slice($whr, 1);
			}
			else
				$sql .= " where $whr";
		}

		// Execute the query
		$rows = MySQL::query($args ?
			array_merge(array($sql), $args) : $sql, static::$database);

		// Retrieve and return array of distinct values
		$values = [];
		foreach ($rows as $r)
			$values[] = $r->$fld;
		return $values;
	}

	/**
	 * Retrieve a record by its primary key value.
	 *
	 * This function retrieves a single record from the table with the given
	 * primary key value. If the record is not found, a NotFoundException is
	 * thrown.
	 * 
	 * @param mixed $id Primary key value
	 * @return object Object of the called class
	 */
	static public function find ($id) {
		$doc = static::find_one(array('`' . static::$key . '`=?', $id));
		if (!$doc)
			throw new NotFoundException(
				static::_table() . '.' . static::$key . "=$id");
		return $doc;
	}

	/**
	 * Retrieves a single record using some condition.
	 *
	 * Retrieves a single record from the database according to the given
	 * condition. The condition may be given as a string or an array. If an
	 * array, the first element is the WHERE clause, and remaining elements
	 * are used for the placeholders in the WHERE clause. If an order
	 * is supplied, the order will be used as the
	 * ORDER BY clause, thus determining which record is retrieved if more
	 * than one record match the given condition. Optionally, a number of
	 * records matching the condition may be skipped.
	 *
	 * A comma-separated list of columns may be specified. If specified,
	 * only those columns will be retrieved from the database records.
	 * When this option is used, the document becomes read-only.
	 * 
	 * @param mixed $whr Condition to be used as the WHERE clause
	 * @param string $srt Order or record retrieval (optional)
	 * @param int $skp Number of records to skip (optional)
	 * @param string $cols Columns to be included (optional)
	 * @return mixed Object of called class, or false
	 */
	static public function find_one ($whr, $srt=null, $skp=null, $cols=null) {
		$objs = static::find_all($whr, $srt, 1, $skp, $cols);
		return count($objs) ? $objs[0] : false;
	}

	/**
	 * Retrieve records.
	 *
	 * Retrieves all records matching the given WHERE clause, or all records
	 * from the table if no WHERE clause is given. Optionally, a limit may
	 * be specified as well as number of leading records to skip.
	 *
	 * A comma-separated list of columns may be specified. If specified,
	 * only those columns will be retrieved from the database records.
	 * When this option is used, the document becomes read-only.
	 *
	 * @param mixed $whr WHERE query clause as string or array (optional)
	 * @param string $srt ORDER BY query clause (optional)
	 * @param int $lim Maximum number of records to return (optional)
	 * @param int $skp Number of records to skip (optional)
	 * @param string $cols Columns to be included (optional)
	 * @return array Array of objects represeting requested records
	 */
	static public function find_all (
			$whr=null, $srt=null, $lim=null, $skp=null, $cols=null) {

		// Columns to be retrieved
		$cols = $cols === null ? '*' :
			implode(',', array_map(function ($c) { return '`' . trim($c) . '`'; }, explode(',', $cols)));

		$sql = "select $cols from `" . static::_table() . '`';
		$args = false;

		// Add WHERE clause, if supplied
		if ($whr) {
			if (is_array($whr)) {
				$sql .= " where {$whr[0]}";
				$args = array_slice($whr, 1);
			}
			else
				$sql .= " where $whr";
		}

		// Add ORDER BY clause, if supplied
		if ($srt)
			$sql .= " order by $srt";

		// Add LIMIT clause, if supplied
		if ($lim)
			$sql .= " limit $lim";

		// Add OFFSET clause, if supplied
		if ($skp)
			$sql .= " offset $skp";

		// Execute the query
		$rows = MySQL::query($args ?
			array_merge(array($sql), $args) : $sql, static::$database);

		// Create array of objects, with each row from the database passed
		//  as an array to the read method for post-processing.
		$cls = get_called_class();
		$objs = array();
		foreach ($rows as $row) {
			$obj = new $cls();
			$obj->set($obj->read(get_object_vars($row)));
			$obj->_id = $obj->{static::$key};
			$obj->_readonly = $cols != '*';
			$objs[] = $obj;
		}

		return $objs;
	}

	/**
	 * Retrieve records based on subquery.
	 * 
	 * Retrieves all records such that the primary key matches the values
	 * in the result of the given SELECT query. Parameter $sql must be
	 * a SELECT query that gives a single column in its result.
	 *
	 * The $sql parameter may be given as a string or an array. If an array,
	 * the first element is the SQL statement with placeholders, and the
	 * remaining elements are arguments.
	 *
	 * @param mixed $sql An SQL query
	 */
	static public function find_where_in ($sql) {
		$where = is_array($sql) ? $sql : [ $sql ];
		$where[0] = '`' . static::$key . '` in (' . $where[0] . ')';
		return static::find_all($where);
	}

	// Helper function to return the table name
	/** @ignore */
	static private function _table () {
		// If table is not defined in the derived class, determine table
		// name based on the class name. Inserts underscore before all
		// uppercase characters, except if first character, then convert
		// to lowercase and add "s". For ecample, class "User" corresponds
		// to table "users", and class "AdminUser" corresponds to table
		// "admin_users".
		return static::$table ? static::$table :
			strtolower(preg_replace(
				'/(?<!^)[A-Z]/', '_$0', get_called_class())) . 's';
	}
}
