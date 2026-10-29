<?php

// FST Application Framework, Version 6.2
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

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
 * of the derived class represents creation of a new record.
 * 
 * Due to PHP 8.2 deprecating dynamically created properties, this class
 * received a major overhaul with FST 6.2. Functionality remains backward
 * compatible, with these exceptions: 1) Database fields are now accessed
 * via __get and __set, not dynamically created properties. Any declared
 * database field properties will generate a UsageException upon first
 * instantiation. 2) Methods read and write are now used to access and update
 * database values bypassing __get and __set. These methods are not backward
 * compatible. Previously used for conversions of field values between
 * database and instantiated object, such situations should now be handled
 * with getters and setters, which may use read and write to access the
 * underlying field value.
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
	static protected ?string $database = null; /// Database alias name

	/**
	 * Defines the database table name.
	 *
	 * Derived classes may override this property to define the table
	 * name. If the derived class does not override this property, the
	 * table name is determined by preceeding all uppercase
	 * characters in the name (except the first character) with an underscore,
	 * converting all characters to lowercase, and adding 's'.
	 */
	static protected ?string $table = null; /// Table name

	/**
	 * Defines the name of the primary key field.
	 *
	 * Derived classes may override this property to define the name of the
	 * primary key field. If the derived class does not override this
	 * property, 'id' is used.
	 */
	static protected string $key = 'id'; /// Primary key field

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
	static protected array $referenced_by = [];

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
	static protected array $references = [];

	/** @ignore */
	private ?string $__id = null; // ID value of record, or null if not in database
	/** @ignore */
	private bool $__readonly = false; // Read-only flag
	/** @ignore */
	private array $__rec = []; // Database record
	/** @ignore */
	private array $__refs = []; // Any referenced objects

	/** @ignore */
	static private array $__rec_init = []; // Initialization records for the models (array of arrays)

	/**
	 * Constructs a new document.
	 * 
	 * @param mixed $properties Associative array or object of initial properties (optional)
	 */
	public function __construct (mixed $properties=null) {
		
		// Determine the calling class
		$cls = get_called_class();

		// If first object contructed of class, query database for schema
		if (!array_key_exists($cls, static::$__rec_init)) {
			static::$__rec_init[$cls] = [];
			$qr = MySQL::query("describe `" . static::_table() . "`", static::$database);
			foreach ($qr as $row)
				static::$__rec_init[$cls][$row->Field] = $row->Default;
		}

		// Initialize the record
		$this->__rec = static::$__rec_init[$cls];
		if ($properties)
			$this->set($properties);
	}

	// Magic method to define functions for retrieval of objects of another
	// derived class that refernce this class. Optional arguments to the
	// function are the WHERE clause, the ORDER BY clause, and columns.
	/** @ignore */
	public function __call (string $fcn, array $args) {
		if (!array_key_exists($fcn, static::$referenced_by))
			throw new UsageException("Call to undefined method: $fcn");

		// If this object has no id (document is not saved), no other
		// documents will reference it.
		if (!isset($this->__id))
			return [];

		// Get class name, foreign key, and default sort order
		list($cls, $key, $srt) = explode(":", static::$referenced_by[$fcn] . "::");

		// If foreign key not explicitly given, derive from class name
		if (!$key)
			$key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', get_called_class())) . '_id';

		// If default sort order is not explicitly given, set to null
		if (!$srt)
			$srt = null;

		// Build the WHERE clause
		$whr = [ "`$key`=?", $this->__id ];
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
	public function __clone () { $this->__id = null; }

	// Magic method to get properties of the object that are not already
	// defined. Property '_id' may be retrieved which is a copy of the
	// primary key value, if the object has been previously saved in the
	// table, but will be null if not saved. If the property requested
	// is a referenced property, an object of the referenced class is
	// created and returned, or null is returned if none is referenced.
	// If the property has a getter (method "get_" followed by a property
	// name, that function is called to retrieve the property.
	/** @ignore */
	public function __get (string $fld) {

		// May get primary key value via _id. Note that this is the primary
		// key value on the saved record. If a new record and primary key
		// as been set via its property, this will return null. If a saved
		// record but primary key value has been changed via its property,
		// this will return the (old) primary key value on the saved record.
		if ($fld == '_id')
			return $this->__id;

		// If a getter exists for field, call it
		if (method_exists($this, "get_$fld"))
			return $this->{"get_$fld"}();

		// If getting a referenced document, return object for document.
		if (array_key_exists($fld, static::$references)) {
			// If previously retrieved, will be in $_refs array
			if (array_key_exists($fld, $this->__refs))
				return $this->__refs[$fld];
			// Get class name and foreign key for referenced document
			list($cls, $key) = explode(":", static::$references[$fld] . ":");
			// If foreign key not explicitly given, derive from class name
			if (!$key)
				$key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $cls)) . '_id';
			// Get document using find, and return it
			/** @disregard P1014 Undefined type */
			$doc = $cls::find_one([ '`' . $cls::$key . '`=?', $this->$key ]);
			// If document found, save in $_refs
			if ($doc)
				$this->__refs[$fld] = $doc;
			// Return document, or false if not found
			return $doc;
		}

		// If getting documents which reference this document, return an array of objects.
		if (array_key_exists($fld, static::$referenced_by)) {
			// If current document has no primary key value, return empty array
			if (!isset($this->__id))
				return [];
			// Get class name, foreign key, and default sort order
			list($cls, $key, $srt) = explode(":", static::$referenced_by[$fld] . "::");
			// If foreign key not explicitly given, derive from class name
			if (!$key)
				$key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', get_called_class())) . '_id';
			// If default sort order not given, set to null
			if (!$srt)
				$srt = null;
			// Find documents related to this table
			return $cls::find_all([ "`$key`=?", $this->__id ], $srt);
		}

		// Return value from record, if exists
		if (array_key_exists($fld, $this->__rec))
			return $this->__rec[$fld];

		// Attempt to retrieve invalid property
		throw new UsageException('Invalid property ' . static::_table() . ".$fld");
	}

	// Magic method to indicate whether the _id property is set or getter
	// exists and has non-null return value
	// TODO: Should this consider referenced documents? Possible is set if foreign key is set?
	/** @ignore */
	public function __isset (string $fld) {
		if ($fld == '_id')
			return isset($this->__id);
		if (method_exists($this, "get_$fld"))
			return !is_null($this->{"get_$fld"}());
		return isset($this->__rec[$fld]);
	}

	// Magic method to set field value or referenced document. This method
	// throws an exception on any attempt to set the _id property,
	// or any properties of referenced documents.
	// When setting referenced objects, the object being assigned must be
	// of the designated referenced class, else an exception is thrown.
	/** @ignore */
	public function __set (string $fld, mixed $val) {

		// If the field being assigned is a referenced field, value must be
		// a document object of the appropriate type, a foreign key value,
		// or null.
		if (array_key_exists($fld, static::$references)) {
			list($cls, $fkey) = explode(":", static::$references[$fld] . ":");
			if (!$fkey)
				$fkey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $cls)) . '_id';
			if (is_object($val)) {
				if (get_class($val) != $cls)
					throw new UsageException("Inconsistent object assigned to referenced field");
				// Set foreign key in this document
				/** @disregard P1014 Undefined type */
				$this->__rec[$fkey] = $val->{$cls::$key};
			}
			else if (is_scalar($val)) {
				// If a scalar value is set will assume it's a valid foreign
				// key value and just save the value. If previous referenced
				// document is cached, clear it.
				$this->__rec[$fkey] = $val;
				if (isset($this->__refs[$fld]))
					unset($this->__refs[$fld]);
			}
			else
				throw new UsageException('Invalid assignment to ' . static::_table() . ".$fld");
			return;
		}

		// If a setter exists for this property, call it and return
		if (method_exists($this, "set_$fld")) {
			$this->{"set_$fld"}($val);
			// TODO: This could potentially fail if not a DB field and no getter
			return $this->$fld;
		}

		// Ensure field name is valid
		if (!array_key_exists($fld, $this->__rec))
			throw new UsageException('Invalid assignment to ' . static::_table() . ".$fld, no such field");

		// Assign the property
		return $this->__rec[$fld] = $val;
	}

	/** @ignore */
	static public function __callStatic (string $fcn, array $args) {
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
			throw new UsageException("Call to undefined static method: $fcn");

		// Get field names for query
		$fields = explode('_and_', substr($fcn, strpos($fcn, '_by_') + 4));

		// Ensure match in number of arguments. Should be one argument for
		//	each field. For find_by_* and find_all_by_*, an additional
		//	argument may be specified for sorting.
		if (count($args) == count($fields))
			$sort = null;
		else {
			if ($method == 'count' || count($args) != count($fields) + 1)
				throw new UsageException("Incorrect number of arguments: $fcn");
			$sort = end($args);
			$args = array_slice($args, 0, -1);
		}

		// Build the where clause
		$where = array_merge([ implode(' and ', array_map(function (string $fld) { return "`$fld`=?"; }, $fields)) ], $args);

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
		MySQL::query([ 'delete from `' . static::_table() . '` ' . 'where `' . static::$key . '`=?', $this->__id ], static::$database);
		// Leave all object properties intact, but clear the protected
		// primary key value (indicating a new record).
		$this->__id = null;
	}

	/**
	 * Retrieve field value from database record
	 * 
	 * Retrieves value of a database field bypassing any getter function.
	 *
	 * @param string $fld Field name
	 * @return mixed Field value from database record
	 */
	protected function read (string $fld) {
		return $this->__rec[$fld];
	}

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
		if ($this->__readonly)
			throw new DatabaseException('Cannot save read-only document');

		// Changing the value of the primary key is not allowed.
		if ($this->__id && $this->__id != $this->{static::$key})
			throw new UsageException('Update of primary key ' . static::_table() . '.' . static::$key . ' is not allowed');

		// Convert all boolean values to 0/1, and convert all DateTime objects to string form.
		$rec = array_map(function (mixed $val) {
			if (is_bool($val))
				return $val ? 1 : 0;
			if (is_object($val) && get_class($val) == 'DateTime')
				return $val->format('Y-m-d H:i:s');
			return $val;
		}, $this->__rec);

		// Prepare the statement. If a new record, this will be an insert, otherwise an update.
		if ($this->__id === null) {
			// Build SQL statement for insert
			$sql = 'insert into `' . static::_table() . '` ' . '(`' . implode('`,`', array_keys($rec)) . '`) ' . 'values (' . implode(',', array_fill(0, count($rec), '?')) . ')';
		}
		else {
			// Remove primary key value from update fields
			unset($rec[static::$key]);

			// Build SQL statement for update
			$sql = 'update `' . static::_table() . '` ' . 'set ' . implode(',', array_map(function (string $fld) { return "`$fld`=?"; }, array_keys($rec))) . ' where `' . static::$key . '`=?';

			// Include primary key value for query execution
			$rec[] = $this->__id;
		}

		// Execute the query
		MySQL::query(array_merge([ $sql ], array_values($rec)), static::$database);

		// Set primary key value, if an insert
		if (!$this->__id) {
			if (isset($this->{static::$key}))
				$this->__id = $this->{static::$key};
			else
				$this->__id = $this->{static::$key} = MySQL::insert_id(static::$database);
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
	public function set (mixed $properties) {

		if (!is_array($properties) && !is_object($properties))
			throw new UsageException('Argument to MySQL::set must be array or object');

		foreach (is_object($properties) ? get_object_vars($properties) : $properties as $k=>$v)
			if (preg_match('/^[a-z]\w*$/i', $k))
				$this->$k = $v;
}

	/**
	 * Save field value to database record
	 * 
	 * Sets value of a database field bypassing any setter function.
	 *
	 * @param string $fld Field name
	 * @param mixed $val Value to be assigned
	 */
	protected function write (string $fld, mixed $val) {
		$this->__rec[$fld] = $val;
	}

	/**
	 * Get count of records.
	 * 
	 * @param mixed $whr WHERE query clause as string or array (optional)
	 * @return int Number of records
	 */
	static public function count (mixed $whr=null) {
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
		$qry = $args ? array_merge([ $sql ], $args) : $sql;
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
	 * @return mixed[] Array of distinct values for the given field
	 */
	static public function distinct (string $fld, mixed $whr=null) {

		// Build the SELECT statement
		$sql = 'select distinct `' . $fld . '` from `' . static::_table() . '`';

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
		$rows = MySQL::query($args ? array_merge([ $sql ], $args) : $sql, static::$database);

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
	 * @return MySQLModel Object of the called class
	 */
	static public function find (mixed $id) {
		$doc = static::find_one([ '`' . static::$key . '`=?', $id ]);
		if (!$doc)
			throw new NotFoundException(static::_table() . '.' . static::$key . "=$id");
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
	 * @return MySQLModel|null Object of called class, or null
	 */
	static public function find_one (mixed $whr, ?string $srt=null, ?int $skp=null, ?string $cols=null) {
		$objs = static::find_all($whr, $srt, 1, $skp, $cols);
		return count($objs) ? $objs[0] : null;
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
	 * @return MySQLModel[] Array of objects represeting requested records
	 */
	static public function find_all (mixed $whr=null, ?string $srt=null, ?int $lim=null, ?int $skp=null, ?string $cols=null) {

		// Columns to be retrieved
		$cols = $cols === null ? '*' : implode(',', array_map(function (string $c) { return '`' . trim($c) . '`'; }, explode(',', $cols)));

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
		$rows = MySQL::query($args ? array_merge([ $sql ], $args) : $sql, static::$database);

		// Create array of objects, with each row from the database passed
		//  as an array to the read method for post-processing.
		$cls = get_called_class();
		$objs = [];
		foreach ($rows as $row) {
			$obj = new $cls();
			$obj->set(get_object_vars($row));
			$obj->__id = $obj->{static::$key};
			$obj->__readonly = $cols != '*';
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
	static public function find_where_in (mixed $sql) {
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
		return static::$table ? static::$table : strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', get_called_class())) . 's';
	}
}
