<?php

require_once(__DIR__.'/../adapter_interface.php');

/**
 *  Adapter mysqli for mysql server
 */
class MysqliAdapter implements IDataBaseAdapter {


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
	 * @return mysqli
	 *      Object that represents the connection 
	 *      to the MySQL server
	 */
	public function connect ($host, $basename, $user, $pass = '') {
		
		return mysqli_connect($host, $user, $pass, $basename);
	}


	/**
	 * Description of the connection error
	 * 
	 * @param mysqli $link
	 *      Connection identifier
	 * @return string
	 *       A string that describes the error. 
	 *       NULL is returned if no error occurred. 
	 */
	public function connectError ($link) {
		
		return mysqli_connect_error();
	}

	
	/**
	 * Number of connection errors
	 * 
	 * @param mysqli $link
	 *      Connection identifier
	 * @return string
	 *       An error code value for the last call to mysqli_connect(), 
	 *       if it failed. zero means no error occurred. 
	 */
	public function connectErrno ($link) {
		
		return mysqli_connect_errno();
	}


	/**
	 * Returns the error code for the most recent function call
	 * 
	 * @param mysqli $link
	 *     A link identifier
	 * @return string
	 *     Returns the error text from the last MySQL function, 
	 *     or '' (empty string) if no error occurred. 
	 */
	public function error ($link) {
		
		return mysqli_error($link);
	}

	
	/**
	 * Returns the error code for the most recent function call
	 *
	 * @param mysqli $link
	 *     A link identifier
	 * @return int
	 *     An error code value for the last call, if it failed. 
	 *     zero means no error occurred. 
	 */
	public function errno ($link) {
		
		return mysqli_errno($link);
	}

	/**
	 * Escapes special characters in a string for use in an SQL statement, 
	 * taking into account the current charset of the connection
	 * 
	 * @param mysqli $link
	 *      A link identifier 
	 * @param string $escapestr
	 *      The string to be escaped
	 * @return string
	 *      Returns an escaped string. 
	 */
	public function escapeString ($link, $escapestr) {

		return mysqli_real_escape_string($link, $escapestr);
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param mysqli $link
	 *      A link identifier 
	 * @param string $query
	 *      The query string
	 * @return mixed
	 *      Returns FALSE on failure. 
	 *      For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() 
	 *      will return a mysqli_result object. 
	 *      For other successful queries mysqli_query() will return TRUE. 
	 */
	public function query ($link, $query) {

		return mysqli_query($link, $query);
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param mysqli $link
	 *      A link identifier 
	 * @param string $query
	 *      The query, as a string
	 * @return bool
	 *      Returns FALSE if the first statement failed. 
	 *      To retrieve subsequent errors from other statements 
	 *      you have to call mysqli_next_result() first. 
	 */
	public function multyQuery ($link, $query) {

		return mysqli_multi_query($link, $query);
	}


	/**
	 * Gets the first value of the first row of the query result
	 * 
	 * @param mysqli_result $result
	 *      A result set identifier
	 * @return string
	 *      Returns the first value of the 
	 *      first row of the query result
	 */
	public function fetchOne ($result) {

		$row    = mysqli_fetch_assoc($result);	
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
	 * Get a result row as an enumerated array
	 * 
	 * @param mysqli_result $result
	 *     A result set identifier
	 * @return mixed
	 *     Returns an array of strings that corresponds to the fetched row 
	 *     or NULL if there are no more rows in result set. 
	 */
	public function fetchRow ($result) {

		return mysqli_fetch_assoc($result);
	}
	

	/**
	 * Fetches all result rows as an associative array
	 * 
	 * @param mysqli_result $result
	 *     A result set identifier
	 * @return array
	 *     Returns an array of associative
	 */
	public function fetchAll ($result) {

		$res = array();

		# Compatibility layer with PHP < 5.3
		if (function_exists('mysqli_fetch_all')) { 
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        } else {
        	while ($tmp = mysqli_fetch_array($result)) {
        		$res[] = $tmp;
        	}
        }

        return $res;
	}


	/**
	 * Gets the number of rows in a result
	 * 
	 * @param mysqli_result $result
	 *     A result set identifier
	 * @return int|string
	 *     Returns number of rows in the result set.
	 *     If the number of rows is greater than MAXINT, the number will be returned as a string. 
	 */
	public function getNumRows ($result) {

		return mysqli_num_rows($result);
	}


	/**
	 * Returns the auto generated id used in the last query
	 * 
	 * @param mysqli $link
	 *     A link identifier 
	 * @return mixed
	 *     The value of the AUTO_INCREMENT field that was updated by the previous query. 
	 *     Returns zero if there was no previous query on the connection or 
	 *     if the query did not update an AUTO_INCREMENT value. 
	 */
	public function getLastId ($link, $table_name = null) {

		return mysqli_insert_id($link);
	}


	/**
	 * Closes a previously opened database connection
	 * 
	 * @param mysqli $link
	 *      A link identifier
	 * @return bool
	 *      Returns TRUE on success or FALSE on failure.
	 */
	public function closeConnect ($link) {

		return mysqli_close($link); 
	}


	/**
	 * Frees the memory associated with a result
	 * 
	 * @param mysqli_result $result
	 *     A result set identifier
	 * @return void
	 */
	public function free ($result) {

		mysqli_free_result($result);
	}
}
