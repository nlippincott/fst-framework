<?php

// FST Application Framework, Version 6.1
// Copyright (c) 2004-26, Norman Lippincott Jr, Saylorsburg PA USA
// All Rights Reserved
//
// The FST Application Framework, and its associated libraries, may
// be used only with the expressed permission of the copyright holder.
// Usage without permission is strictly prohibited.

namespace FST;

/**
 * Database and connection management for MongoDB.
 *
 * A class for managing Mongo databases and providing connections via the
 * MongoDB Manager class. Allows definition of database aliases to multiple
 * databases.
 *
 * This library was inspired by and largely modeled after the PHP
 * ActiveRecord library, which is no longer being maintained. Although
 * that libarary specifically targeted relational databases, the ideas
 * and mechanisms are applied here to MongoDB.
 */
final class Mongo {

	/** @ignore */
	static private $_databases = []; // Mongo database connections
	/** @ignore */
	static private $_database_default = false; // Default database connection
	/** @ignore */
	static private $_manager = null; // Mongo database manager
	/** @ignore */
	static private $_uri = 'mongodb://localhost'; // Authentication URI

	/**
	 * Provides authorization string to database manager.
	 *
	 * Provides the username, password, and authentication database to be
	 * used for database connections. Optionally, the hostname of the
	 * authentication database may be provided (default is localhost).
	 *
	 * @param string $user Database username
	 * @param string $pass Database password
	 * @param string $auth Authenticaion database
	 * @param string $host Authentication database host name (optional)
	 */
	static public function auth ($user, $pass, $auth, $host='localhost')
		{ static::$_uri = "mongodb://$user:$pass@$host/$auth"; }

	/**
	 * Specifies a database with a database alias name.
	 *
	 * Specifies the database alias to be used to access the given database.
	 * If the database name is not provided, the given alias is used also
	 * as the database name. The first database defined with this function
	 * becomes the default database. MongoModel classes will indicate a
	 * database alias to specify the database in which the documents
	 * reside.
	 *
	 * @param $alias string Database alias name
	 * @param $database string Database name (optional)
	 */
	static public function database ($alias, $database=false) {
		if (!$database)
			$database = $alias;
		if (array_key_exists($alias, static::$_databases))
			throw new DatabaseException('Database already defined');
		static::$_databases[$alias] = $database;
		if (!static::$_database_default)
			static::$_database_default = $database;
	}

	/**
	 * Get database name.
	 *
	 * Used to retrieve actual database name for a given alias name. This
	 * function is provided for usage by the MongoModel base class.
	 *
	 * @param $alias string Database alias
	 * @return string Database name
	 */
	static public function _db ($alias=false) {
		if (!$alias && static::$_database_default)
			return static::$_database_default;
		if (!$alias || !array_key_exists($alias, static::$_databases))
			throw new DatabaseException('Database not defined');
		return static::$_databases[$alias];
	}

	/**
	 * Get database manager object.
	 *
	 * Used to retrieve the database manager object. This function is provided
	 * for usage by the MongoModel base class.
	 *
	 * @return \MongoDB\Manager A \\MongoDB\\Manager object
	 */
	static public function _mgr () {
		if (!isset(static::$_manager))
			static::$_manager = new \MongoDB\Driver\Manager(static::$_uri);
		return static::$_manager;
	}
}

/**
 * Base class to represent documents in a Mongo collection.
 *
 * Each collection is represented by a class that is derived from this
 * abstract base class. Derived classes specify the database in which the
 * class resides (or uses the default database if not specified), and the
 * collection in which documents reside (or collection name may be derived
 * from the class name).
 *
 * The MongoModel::find static method is used to create objects of this class
 * to represent individual documents from the collection. Instantiation of a
 * new object of the derived class represents creation of a new document.
 * Document properties correspond to properties of the object. Each object
 * has an id property, corresponding to the string representation of the
 * document id. This base class includes methods MongoModel::save and
 * MongoModel::delete for saving and removing documents respectively.
 */
abstract class MongoModel {

	/**
	 * Defines the collection name.
	 *
	 * Derived classes may override this property to define the collection
	 * name. If the derived class does not override this property, the
	 * collection name is determined by preceeding all uppercase
	 * characters in the name (except the first character) with an underscore,
	 * converting all characters to lowercase, and adding 's'.
	 */
	static protected $collection = false;

	/**
	 * Defines the database name.
	 *
	 * Derived classes may override this property to specify the database
	 * in which the collection resides. If the derived class does not
	 * override this property, the default database is used. The database
	 * must have been defined using Mongo::database prior to instantion of
	 * the first object from the derived class.
	 */
	static protected $database = false;

	/**
	 * Defines documents which reference this document.
	 *
	 * Derived classes may specify an associative array to define other
	 * derived classes that link to the current document. Key values are
	 * used as virtual properties and functions of objects (via the __get
	 * and __call magic methods) to retrieve all documents of another
	 * collection that reference this document.
	 
	 * Values of the associative array include at least one or as many as
	 * three components separated by colons. The first (required) component
	 * is the class name of the document which references this document. The
	 * second (optional) component is the name of the virtual property in
	 * the related document. If the property name is not provided, default
	 * rules for class-to-property name rules apply. The third (optional)
	 * component is the default sort order of the related documents.
	 *
	 * Sort order, if specified, must be given as a comma-separated list
	 * of attributes. Attribute names may be preceeded by a minus sign to
	 * indicating a descending sort by that attribute.
	 */
	static protected $referenced_by = [];

	/**
	 * Defines referenced documents.
	 *
	 * Derived classes may specify an associative array to link to documents
	 * from other collections. Key values are used as virtual properties of
	 * objects (via the __get magic method) to connect to a document from
	 * another collection. Values of the associative array are the class name
	 * corresponding to the referenced collection.
	 */
	static protected $references = [];

	/** @ignore */
	private $_id = null;
	/** @ignore */
	private $_id_refs = [];
	/** @ignore */
	private $_docs = [];
	/** @ignore */
	private $_readonly = false;

	/**
	 * Constructs a new document.
	 *
	 * Creates a new (unsaved) document. Initial properties may optionally
	 * be specified as an associative array. Each key in the array is assigned
	 * as a property of the object.
	 *
	 * @param mixed $properties Associative array or object of initial properties (optional)
	 */
	public function __construct ($properties=null)
		{ if ($properties) $this->set($properties); }

	// Magic method to define function for retrieval of objects of another
	// derived class that reference a document from this class. Optional
	// arguments to the function are the query, sort criteria, and projection.
	/** @ignore */
	public function __call ($fcn, $args) {
		if (!array_key_exists($fcn, static::$referenced_by))
			throw new UsageException("Call to undefined method: $fcn");

		// If this object has no id (document is not saved), no other
		// documents will reference it.
		if (!isset($this->_id))
			return [];

		// Get class name, foreign property, and default sort order
		list($cls, $fld, $srt) = explode(":", static::$referenced_by[$fcn] . "::");

		// If foreign property not explicitly given, derive from class name
		if (!$fld)
			$fld = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', get_called_class()));

		// If default sort order is not explicitly given, set to null
		if (!$srt)
			$srt = null;

		// Optional arguments to this function are query, sort and projection.
		$qry = count($args) > 0 && $args[0] !== null ? $args[0] : [];
		$srt = count($args) > 1 ? $args[1] : $srt;
		$prj = count($args) > 2 ? $args[2] : null;

		// Query must be an array. Other arguments may be array or string and
		// are validated by self::find_all.
		if (!is_array($qry))
			throw new UsageException("Invalid query parameter");

		// Include this object in criteria for the query
		$qry[$fld] = $this;

		return $cls::find_all($qry, $srt, null, null, $prj);
	}

	// Provides for cloning of objects. A cloned object is a copy but is
	// unsaved in the collection.
	/** @ignore */
	public function __clone () { $this->_id = null; }

	// Magic method to get properties of the object that are not directly
	// saved in the document. Property 'id' is the string representation of
	// the _id property (or null if document is not saved). If the requested
	// property has a getter (method "get_" followed by property name), that
	// function is called to retrieve the property. If the property is a
	// referenced property, an object of the referenced class is created
	// and returned if a document is referenced, or null if none referenced.
	/** @ignore */
	public function __get ($fld) {

		// If getting '_id', return Mongo Object ID
		if ($fld == '_id')
			return $this->_id;

		// If getting 'id', return string version of Mongo ID
		if ($fld == 'id') {
			if (!isset($this->_id))
				return null;
			return $this->_id->__toString();
		}

		// If getter exists for field, call it
		if (method_exists($this, "get_$fld"))
			return call_user_func([ $this, "get_$fld" ]);

		// If getting a referenced document, return object for document.
		if (array_key_exists($fld, static::$references)) {
			if (array_key_exists($fld, $this->_docs))
				return $this->_docs[$fld];
			if (isset($this->_id_refs[$fld])) {
				$cls = static::$references[$fld];
				try {
					$obj = $cls::find("{$this->_id_refs[$fld]}");
					$this->_docs[$fld] = $obj;
					return $obj;
				}
				catch (NotFoundException $e) { }
			}
			return null;
		}

		// If getting documents which reference this document, call the
		// function to get documents without parameters.
		if (array_key_exists($fld, static::$referenced_by))
			return $this->$fld();

		// Field does not exist, return null.
		return null;
	}

	// Magic method to indicate whether the id property is set or getter
	// exists and has non-null return value
	/** @ignore */
	public function __isset ($fld) {
		switch ($fld) {
		case 'id':
		case '_id':
			return isset($this->_id);
		}
		return method_exists($this, "get_$fld") && !is_null(call_user_func([ $this, "get_$fld" ]));
	}

	// Magic method to set properties that are not already set. This method
	// throws an exception on any attempt to set the id or _id properties,
	// or properties beginning with "_id_" that refer to referenced objects.
	// When setting referenced objects, the object being assigned must be
	// of the designated referenced class, else an exception is thrown.
	/** @ignore */
	public function __set ($fld, $val) {

		// May not assign 'id', '_id', or '_id_refs'
		if ($fld == 'id' || $fld == '_id' || preg_match('/^_id_/', $fld))
			throw new UsageException('Assignment to id member is not allowed');

		// May not assign '_docs'
		if ($fld == '_docs')
			throw new UsageException('Assignment to _docs member is not allowed');

		// If the field being assigned is a referenced field, value must be
		//	a document object or an object id, in which case the object id
		//	is saved. The object (or object to which the id refers) must be
		//	of the correct collection (or an exception is thrown). Also, the
		//	value may be specified as false, null, or blank, to remove an
		//	existing reference.
		if (array_key_exists($fld, static::$references)) {
			// If $val is null, remove reference if exists.
			if (!$val) {
				if (isset($this->_docs[$fld]))
					unset($this->_docs[$fld]);
				if (isset($this->_id_refs[$fld]))
					unset($this->_id_refs[$fld]);
				return null;
			}
			// If $val is a string, attempt to convert to doc of expected type
			if (is_string($val)) {
				try {
					$cls = static::$references[$fld];
					$val = $cls::find($val);
				}
				catch (NotFoundException $e) { $val = null; }
			}
			// $val must be an object of type specified in $references.
			if (!is_object($val) ||
					get_class($val) != static::$references[$fld])
				throw new UsageException("Inconsistent object assigned to referenced field");
			// $val must be a saved object (i.e. must have an _id)
			if (!isset($val->_id))
				throw new UsageException("Unsaved object assigned to referenced field");
			$this->_id_refs[$fld] = $val->_id;
			$this->_docs[$fld] = $val;
			return $val;
		}

		// If a setter exists for this object, call it and return
		if (method_exists($this, "set_$fld")) {
			call_user_func([ $this, "set_$fld" ], $val);
			return $this->$fld;
		}

		// Create public property, set, and return
		return $this->$fld = $val;
	}

	// Magic method to unset referenced properties
	/** @ignore */
	public function __unset ($fld) {

		// If a referenced object, remove its id from referenced id's.
		if (array_key_exists($fld, static::$references))
			unset($this->_id_refs[$fld]);
	}

	/** @ignore */
	static public function __callStatic ($fcn, $args) {
		// Implements 'find_by_*' and 'find_all_by_*' and 'count_by_*' static
		//	methods.

		// Determine mode (command), 'first', 'all', or 'count'.
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

		// Build the query
		$query = array_combine($fields, $args);

		// Execute query, return results.
		if ($method == 'count')
			return static::count($query);
		return static::$method($query, $sort);
	}

	/**
	 * Delete current document from the collection.
	 *
	 * The document is removed from the collection. Upon deletion, the object
	 * properties remain intact and represent an unsaved document.
	 */
	public function delete () {
		// Remove the current document
		if ($this->_id) {
			$write = new \MongoDB\Driver\BulkWrite();
			$write->delete([ '_id'=>$this->_id ]);
			Mongo::_mgr()->executeBulkWrite(static::_database() . '.' . static::_collection(), $write);
		}
		// Initialize document id
		$this->_id = null;
	}

	/**
	 * Post-process database fields after read.
	 *
	 * Derived classes may override this method to perform any post-processing
	 * to be done to the document's data immediately after it was read from
	 * the database, but before being passed to the set method to set the
	 * document's properties.
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
	 * @return mixed[] Associative array of field values for the object model
	 */
	protected function read ($rec) { return $rec; }

	/**
	 * Save current document to database.
	 *
	 * If the document was retrieved from the collection, replaces the
	 * document. If an unsaved document, a new document is added to the
	 * collection.
	 */
	public function save () {

		// Cannot save a read-only document
		if ($this->_readonly)
			throw new DatabaseException('Cannot save read-only document');

		// Create bulk write object
		$write = new \MongoDB\Driver\BulkWrite();

		// If an unsaved document, create a new empty document to get an id.
		//	Taking this approach because when trying to insert a new document
		//	that had reference fields set, such as _id_user, $write->insert
		//	would return null as the inserted id when, in fact, an id was
		//	actually created. Not sure if this is a bug in the driver or
		//	something I was doing wrong. Creating an empty document to get
		//	an id, however, worked consistently.
		if (!($this->_id))
			$this->_id = $write->insert([]);

		// Convert document to an array, remove all properties leading with
		// an underscore, and pass to the write method for pre-processing.
		$doc = $this->write(array_filter(get_object_vars($this), function ($key) {
			return substr($key, 0, 1) != '_';
		}, ARRAY_FILTER_USE_KEY));

		// Convert convert all DateTime objects to string form.
		$doc = array_map(function ($val) {
			if (is_object($val) && get_class($val) == 'DateTime')
				return $val->format('Y-m-d H:i:s');
			return $val;
		}, $doc);

		// Set entries for referenced documents.
		foreach ($this->_id_refs as $ref=>$obj_id)
			$doc["_id_$ref"] = $obj_id;

		// Save document
		$write->update([ '_id'=>$this->_id ], $doc);
		Mongo::_mgr()->executeBulkWrite(static::_database() . '.' . static::_collection(), $write);
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
			throw new UsageException('Argument to Mongo::set must be array or object');

		foreach (is_object($properties) ? get_object_vars($properties) : $properties as $k=>$v)
			if (preg_match('/^[a-z]\w*$/i', $k))
				$this->$k = $v;
	}

	/**
	 * Pre-process database fields before write.
	 *
	 * This method may be overridden in derived classes to perform any
	 * pre-processing that should occur just before writing the document to
	 * the database. The parameter given is an associative array of all
	 * properties of the document (not including those that lead with an
	 * underscore).
	 *
	 * The method should return an associative array
	 * of actual values to be written (by either INSERT or UPDATE).
	 *
	 * @param array $rec Associative array of field values from the model
	 * @return mixed[] Associative array of field values for the database
	 */
	protected function write ($rec) { return $rec; }

	/**
	 * Runs an aggregation against the collection.
	 *
	 * The given pipeline is run against the collection. Results from the
	 * aggregation are returned as an array of objects.
	 *
	 * @param array $pipeline A Mongo aggregation pipeline
	 * @return mixed[] Aggregation results
	 */
	static public function aggregate ($pipeline) {
		$cmd = new \MongoDB\Driver\Command([
			'aggregate'=>static::_collection(),
			'pipeline'=>$pipeline,
			'cursor'=>[ 'batchSize'=>0 ],
		]);
		$cur = Mongo::_mgr()->executeCommand(static::_database(), $cmd);
		return $cur->toArray();
	}

	/**
	 * Get count of documents.
	 *
	 * Determines the number of documents in the collection, optionally
	 * qualified by a query. If a query is not given, returns the number
	 * of documents in the collection.
	 *
	 * @param array $qry Query to qualify document in collection (optional)
	 * @return int Number of documents
	 */
	static public function count ($qry=null) {

		// Convert references in query.
		$qry = static::_query($qry);

		// Create pipeline and aggregate.
		$pipeline = count($qry) ? [ [ '$match'=>$qry ] ] : [];
		$pipeline[] = [ '$group'=>[ '_id'=>null, 'count'=>[ '$sum'=>1 ]]];
		$results = static::aggregate($pipeline);

		// Return count from aggregation results.
		return count($results) ? $results[0]->count : 0;
	}

	/**
	 * Delete all documents matching query.
	 * 
	 * Deletes multiple documents in the collection. All documents qualified by
	 * the given query are deleted from the collection. A query is required,
	 * and an empty query is not permitted. To delete all documents in the
	 * collection, pass the query parameter as true.
	 *
	 * @param array $qry Query for qualifying documents
	 */
	static public function delete_all ($qry) {

		if ($qry === true)
			// Delete all documents in the collection
			$qry = [];
		else {
			// Ensure a query is given (invalid if no criteria).
			if (!is_array($qry) || !count($qry))
				throw new UsageException('Query required for delete_all');
			// Convert references in query.
			$qry = static::_query($qry);
		}

		// Delete documents matching query.
		$write = new \MongoDB\Driver\BulkWrite();
		$write->delete($qry);
		Mongo::_mgr()->executeBulkWrite(static::_database() . '.' . static::_collection(), $write);
	}

	/**
	 * Get distinct values for the given property.
	 *
	 * Determines the distinct values for the given property that are saved
	 * in the collection. A qualifying query may be specified, in which case
	 * only qualifying documents are considered.
	 *
	 * @param string $fld Property name
	 * @param array $qry Query for qualifying documents (optional)
	 * @return mixed[] Array of distinct values
	 */
	static public function distinct ($fld, $qry=false) {

		// Convert references in query.
		$qry = static::_query($qry);

		// Get distinct values
		$cmd = new \MongoDB\Driver\Command([ 'distinct'=>static::_collection(), 'key'=>$fld, 'query'=>$qry ]);
		$cur = Mongo::_mgr()->executeCommand(static::_database(), $cmd);
		return $cur->toArray()[0]->values;
	}

	/**
	 * Retrieve a document by its id value.
	 *
	 * This function retrieves a single document from the table with the
	 * given id value. If the document is not found, a NotFoundException
	 * is thrown.
	 *
	 * @param string $id Id value
	 * @return MongoModel Document object
	 */
	static public function find ($id) {

		// Query the document and get cursor.
		$qry = new \MongoDB\Driver\Query([ '_id'=>new \MongoDB\BSON\ObjectId($id) ]);
		$cur = Mongo::_mgr()->executeQuery(static::_database() . '.' . static::_collection(), $qry);

		// If no document found, throw exception.
		if (!$cur)
			throw new NotFoundException(static::_collection() . ':' . $id);
		$arr = $cur->toArray();
		if (!count($arr))
			throw new NotFoundException(static::_collection() . ':' . $id);

		// Build and return object from query results.
		$cls = get_called_class();
		$obj = new $cls();
		//$obj->_init($obj->read(get_object_vars($cur->toArray()[0])));
		$obj->_init($obj->read(get_object_vars($arr[0])));
		return $obj;
	}

	/**
	 * Retrieves a single document using a query.

	 * Retrieves a single document from the collection optionally with
	 * a condition. Parameters $srt and $skp may be specified for to control
	 * which document is retrieved among qualifying documents. Parameter
	 * $prj may be used to specify a projection. For specifying sort and
	 * projection options, see MongoModel::find_all.
	 *
	 * @param array $qry Query for qualifying documents (optional)
	 * @param mixed $srt Property for sorting results (optional)
	 * @param int $skp Number of leading documents to skip (optional)
	 * @param mixed $prj Projection properties (optional)
	 * @return MongoModel|null Document object or null
	 */
	static public function find_one ($qry=null, $srt=null, $skp=null, $prj=null) {
		$objs = static::find_all($qry, $srt, 1, $skp);
		return count($objs) ? $objs[0] : null;
	}

	/**
	 * Retrieve document or documents.
	 *
	 * This function retrieves all documents in the
	 * collection as an array of objects, optionally qualified by the
	 * $query parameter using MongoDB query specification rules.
	 * 
	 * The number of documents retrieved and leading number of documents to
	 * exclude may be specified by the $lim and $skp parameters. These may
	 * be used to support paging in an application.
	 *
	 * Sort order of documents may be specified via the $srt parameter,
	 * given as a string or associative array.
	 * If given as a string, must be specified as a comma-separated
	 * list of attributes. Attribute names may be preceeded by a minus sign to
	 * indicating a descending sort by that attribute.
	 * If given as an associative array, use MongoDB sort conventions.
	 * 
	 * A projection may be specified via the $prj parameter, given as
	 * a string or associative array, in order to
	 * limit the attributes retrieved for each document.
	 * If given as a string, must be specified as a comma-separated list
	 * of attribute names to include with each document. Optionally, the
	 * string may begin with a minus sign to specify attributes to
	 * exclude from each document.
	 * If given as an associative array, use MongoDB projection conventions.
	 * 
	 * If a projection is specified, documents retrieved will be read-only.
	 *
	 * @param array $qry Query for qualifying documents (optional)
	 * @param mixed $srt Property for sorting results (optional)
	 * @param int $lim Limits number of documents retrieved (optional)
	 * @param int $skp Number of leading document to skip (optional)
	 * @param mixed $prj Projection properties (optional)
	 * @return MongoModel[] Array of document objects
	 */
	static public function find_all ($qry=null, $srt=null, $lim=null, $skp=null, $prj=null) {

		// Convert references in query.
		$qry = static::_query($qry);

		// Get the called class for returning objects.
		$cls = get_called_class();

		// Build options for query.
		$options = [];

		// Sort option.
		if ($srt) {
			if (is_string($srt)) {
				$tmp = array_map('trim', explode(',', $srt));
				$srt = [];
				foreach ($tmp as $attr)
					if (substr($attr, 0, 1) == '-')
						$srt[substr($attr, 1)] = -1;
					else
						$srt[$attr] = 1;
			}
			if (!is_array($srt))
				throw new UsageException('Sort option must be a string or array');
			$options['sort'] = $srt;
		}

		// Limit option.
		if ($lim) {
			$lim = (int)$lim;
			if ($lim <= 0)
				throw new UsageException('Limit option must be int > 0');
			$options['limit'] = $lim;
		}

		// Skip option.
		if ($skp) {
			$skp = (int)$skp;
			if (!$skp)
				throw new UsageException('Skip option must be int > 0');
			$options['skip'] = $skp;
		}

		// Project option.
		if ($prj) {
			if (is_string($prj)) {
				$incl = substr($prj, 0, 1) == '-' ? 0 : 1;
				if (!$incl)
					$prj = trim(substr($prj, 1));
				$tmp = array_map('trim', explode(',', $prj));
				$prj = [];
				foreach ($tmp as $attr)
					$prj[$attr] = $incl;
			}
			if (!is_array($prj))
				throw new UsageException('Projection option must be a string or array');
			$options['projection'] = $prj;
		}

		// Execute query.
		$qry = new \MongoDB\Driver\Query($qry, $options);
		$cur = Mongo::_mgr()->executeQuery(static::_database() . '.' . static::_collection(), $qry);

		// Build and return objects from query results
		$objs = [];
		foreach ($cur as $doc) {
			$obj = new $cls();
			$obj->_init($obj->read(get_object_vars($doc)));
			if ($prj !== null)
				$obj->_readonly = true;
			$objs[] = $obj;
		}
		return $objs;
	}

	// Helper method to initialize properties from an associative array.
	/** @ignore */
	private function _init ($doc=[]) {

		// Set object id.
		$this->_id = isset($doc['_id']) ? $doc['_id'] : null;

		// Set properties.
		foreach ($doc as $key=>$val) {
			// Referenced document, as described in static::$references?
			if (substr($key, 0, 4) == '_id_' && array_key_exists(substr($key, 4), static::$references))
				$this->_id_refs[substr($key, 4)] = $val;
			// All other properties, except '_id'.
			else if ($key != '_id')
				$this->$key = $val;
		}
	}

	// Helper function to determine the collection name
	/** @ignore */
	static private function _collection () {
		// If collection is not defined in the derived class, determine
		//	collection name based on the class name. Inserts underscore before
		//	all uppercase characters, except if first character, then
		//	convert to lowercase and add "s". For example, class "User"
		//	corresponds to collection "users", and class "AdminUser"
		//	corresponds to collection "admin_users".
		return static::$collection ? static::$collection : strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', get_called_class())) . 's';
	}

	// Helper function to return the database object
	/** @ignore */
	static private function _database ()
		{ return Mongo::_db(static::$database); }

	// Helper function to preprocess queries
	/** @ignore */
	static private function _query ($qry) {

		// Ensure query is given as an array or as null.
		if (!$qry) $qry = [];
		if (!is_array($qry))
			throw new UsageException('Query must be an array');

		// Convert any references in query.
		foreach ($qry as $k=>$v) {
			if (array_key_exists($k, static::$references)) {
				if (!is_object($v) || get_class($v) != static::$references[$k])
					throw new UsageException('Inconsistent document type in query');
				$qry["_id_$k"] = $v->_id;
				unset($qry[$k]);
			}
		}

		return $qry;
	}
}
