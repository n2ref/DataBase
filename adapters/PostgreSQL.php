<?php

require_once(__DIR__.'/../adapter_interface.php');

/**
 *  Adapter postgre_sql for mysql server
 */
class PostgreSQLAdapter implements IDataBaseAdapter {


	/**
	 * Connect to database
	 * 
	 * @param string $host
	 *      The host name that holds your database
	 * @param string $basename
	 *      Name of the database to which you want 
	 *      to connect
	 * @param string $user
	 *      User name under which it will connect 
	 *      to the database
	 * @param string $pass 
	 *      Password to connect to the database 
	 *      for the specified user
	 * @return resource
	 *      Object that represents the connection 
	 *      to the MySQL server
	 */
	public function connect ($host, $basename, $user, $pass = '') {
		
		$str_pass = $pass ? "password=$pass": '';

		return pg_connect("host=$host dbname=$basename user=$user $str_pass");
	}


	/**
	 * Description of the connection error
	 * 
	 * @param resource $link
	 *      Connection identifier
	 * @return string
	 *       Get the last error message string of a connection  
	 */
	public function connectError ($link) {
		
		return pg_last_error();
	}

	
	/**
	 * Solutions of this method was not found
	 * 
	 * @param resource $link
	 *      Connection identifier
	 * @return int
	 *     0 (zero)
	 */
	public function connectErrno ($link) {
		
		return 0;
	}

	/**
	 * Returns the error code for the most recent function call
	 * 
	 * @param resource $link
	 *     PostgreSQL database connection resource
	 * @return string
	 *      Get the last error message string of a connection 
	 */
	public function error ($link) {
		
		return pg_last_error($link);
	}

	
	/**
	 * Solutions of this method was not found
	 * 
	 * @return int
	 *     0 (zero)
	 */
	public function errno ($link) {
		
		return 0;
	}


	/**
	 * Escapes special characters in a string for use in an SQL statement, 
	 * taking into account the current charset of the connection
	 * 
	 * @param resource $link
	 *      PostgreSQL database connection resource
	 * @param string $escapestr
	 *      The string to be escaped
	 * @return string
	 *      Returns an escaped string. 
	 */
	public function escapeString ($link, $escapestr) {

		return pg_escape_string($link, $escapestr);
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param resource $link
	 *      PostgreSQL database connection resource
	 * @param string $query
	 *      The query string
	 * @return resource
	 *      A query result resource on success or FALSE on failure. 
	 */
	public function query ($link, $query) {

		return pg_query($link, $query);
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param resource $link
	 *      A link identifier 
	 * @param string $query
	 *      The query, as a string
	 * @return bool
	 *      A query result resource on success or FALSE on failure.  
	 */
	public function multyQuery ($link, $query) {

		return pg_query($link, $query);
	}


	/**
	 * Gets the first value of the first row of the query result
	 * 
	 * @param resource $result
	 *      PostgreSQL query result resource
	 * @return string
	 *      Returns the first value of the 
	 *      first row of the query result
	 */
	public function fetchOne ($result) {

		$row    = pg_fetch_assoc($result);
		$return = '';
		if (is_array($row)) {
			foreach ($row as $value) {
				$return = $value;
				break;
			}			
		}

		return $return;
	}


	/**
	 * Fetch a row as an associative array
	 * 
	 * @param resource $result
	 *     PostgreSQL query result resource
	 * @return array
	 *     An array indexed associatively (by field name). 
	 *     Each value in the array is represented as a string. 
	 *     Database NULL values are returned as NULL.
	 *     FALSE is returned if row exceeds the number of rows in the set, 
	 *     there are no more rows, or on any other error. 
	 */
	public function fetchRow ($result) {

		return pg_fetch_assoc($result);
	}
	

	/**
	 * Fetches all rows from a result as an array
	 * 
	 * @param resource $result
	 *     PostgreSQL query result resource
	 * @return array
	 *     Returns an array of associative
	 */
	public function fetchAll ($result) {

        return pg_fetch_all($result);
	}


	/**
	 * Returns the number of rows in a result
	 * 
	 * @param resource $result
	 *     PostgreSQL query result resource
	 * @return int
	 *     Returns the number of rows in a result
	 */
	public function getNumRows ($result) {

		return pg_num_rows($result);
	}


	/**
	 * Returns last id
	 * 
	 * @param resource $link
	 *     A link identifier 
	 * @param string $table_name
	 *     Table
	 * @return int|bool
	 *     Returns last id or FALSE on failure.
	 */
	public function getLastId ($link, $table_name) {

		$ret      = pg_query($link, "SELECT * FROM " . $table_name . " LIMIT 1");
		$campo_id = pg_field_name($ret, 0);

		$retorno  = pg_query($link, "SELECT currval('".$table_name."_".$campo_id."_seq')");
		
        if (pg_num_rows($ret) > 0) {
            $s_dados = pg_fetch_all($retorno);
            extract($s_dados[0], EXTR_OVERWRITE);
          
            return $currval;
           
        } else {
            return false;
        } 
	}


	/**
	 * Closes a PostgreSQL connection
	 * 
	 * @param resource $link
	 *      PostgreSQL database connection resource.
	 * @return bool
	 *      Returns TRUE on success or FALSE on failure.
	 */
	public function closeConnect ($link) {

		return pg_close($link); 
	}


	/**
	 * Free result memory
	 * 
	 * @param resource $result
	 *     PostgreSQL query result resource
	 * @return bool
	 *     Returns TRUE on success or FALSE on failure.
	 */
	public function free ($result) {

		pg_free_result($result);
	}
}
