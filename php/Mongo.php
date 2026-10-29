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
	static private array $_databases = []; // Mongo database connections
	/** @ignore */
	static private ?string $_database_default = null; // Default database connection
	/** @ignore */
	static private ?\MongoDB\Driver\Manager $_manager = null; // Mongo database manager
	/** @ignore */
	static private $_uri = 'mongodb://localhost'; // Authentication URI

	/**
	 * Provides authorization string to database manager.
	 *
	 * Provides the username, password, and authentication database to be
	 * used for database connections. Optionally, the database name and
	 * hostname of the authentication database may be provided (defaults
	 * are admin and localhost respectively).
	 *
	 * @param string $user Database username
	 * @param string $pass Database password
	 * @param string $auth Authenticaion database (optional)
	 * @param string $host Authentication database host name (optional)
	 */
	static public function auth (string $user, string $pass, string $auth='admin', string $host='localhost')
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
	static public function database (string $alias, ?string $database=null) {
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
	 * @param string $alias string Database alias
	 * @return string Database name
	 */
	static public function _db (?string $alias=null) {
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
	 * @return \MongoDB\Driver\Manager A \\MongoDB\\Manager object
	 */
	static public function _mgr () {
		if (!isset(static::$_manager))
			static::$_manager = new \MongoDB\Driver\Manager(static::$_uri);
		return static::$_manager;
	}
}
