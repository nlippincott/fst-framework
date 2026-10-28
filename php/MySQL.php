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
 * Database and connection management for MySQL Databases.
 *
 * A class for managing MySQL databases accessible via the PHP PDO library.
 * Allows definition of database aliases to multiple databases.
 *
 * This library was built and tested with a MySQL database using the PDO
 * library. It *may* work with other PDO compatible databases, but is not
 * tested with databases other than MySQL.
 *
 * This library was inspired by and largely modeled after the PHP
 * ActiveRecord library, which is no longer being maintained.
 */
final class MySQL {

	/** @ignore */
	static private array $_databases = []; // PDO Database connections
	/** @ignore */
	static private ?\PDO $_database_default = null; // Default database connection
	/** @ignore */
	static private ?string $_user = null; // Username for database access
	/** @ignore */
	static private ?string $_user_pass = null; // Password for database access

	// Prevent instantiation of this class
	/** @ignore */
	private function __construct () { }

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
	static public function database (string $alias, ?string $database=null, string $host='localhost', string $engine='mysql') {
		if (!$database)
			$database = $alias;
		if (array_key_exists($alias, static::$_databases))
			throw new UsageException('Database already defined');
		try {
			static::$_databases[$alias] = new \PDO("$engine:host=$host;dbname=$database", static::$_user, static::$_user_pass, [ \PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION ]);
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
	static public function insert_id (?string $alias=null)
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
	 * @return object[]|int Query results
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
	 * @return \PDO PDO database object
	 */
	static public function _db (?string $alias=null) {
		if (!$alias && static::$_database_default)
			return static::$_database_default;
		if (!$alias || !array_key_exists($alias, static::$_databases))
			throw new DatabaseException('Database alias ' . ($alias ? $alias : '<default>') . ' not defined');
		return static::$_databases[$alias];
	}
}
