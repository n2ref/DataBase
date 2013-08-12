<?php

require_once(__DIR__.'/../adapter_interface.php');
require_once(__DIR__.'/../exceptions.php');

/**
 *  Adapter mssql for mssql server
 */
class MssqlAdapter implements IDataBaseAdapter {
	

	/**
	 * Connect to database
	 * 
	 * @param string $host
	 *     The host name that holds your database
	 * @param string $basename
	 *     Name of the database to which you want 
	 *     to connect
	 * @param string $user
	 *     User name under which it will connect 
	 *     to the database
	 * @param string $pass 
	 *     Password to connect to the database 
	 *     for the specified user
	 * @return resource
	 *     Returns a MS SQL link identifier
	 */
	public function connect ($host, $basename, $user, $pass) {
		
		$link = mssql_connect($host, $user, $pass);

		if ( ! mssql_select_db($basename, $link)) {
			throw new ErrorSelectDataBaseExeption($basename);
		}

		return $link;
	}


	/**
	 * Returns the last message from the server
	 * 
	 * @param resource $link
	 *      Connection identifier
	 * @return string
	 *     Returns last error message from server, 
	 *     or an empty string if no error messages are returned from MSSQL. 
	 */
	public function connectError ($link) {

		return mssql_get_last_message();
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
	 * Returns the last message from the server
	 * 
	 * @return string
	 *     Returns last error message from server, 
	 *     or an empty string if no error messages are returned from MSSQL. 
	 */
	public function error ($link = null) {

		return mssql_get_last_message();
	}


	/**
	 * Solutions of this method was not found
	 * 
	 * @return int
	 *     0 (zero)
	 */
	public function errno ($link = null) {

		return 0;
	}

	/**
	 * Escapes special characters in a string for use in an SQL statement
	 * 
	 * @param resource $link
	 *      MS SQL link identifier
	 * @param string $escapestr
	 *      The string that is to be escaped. 
	 * @return string
	 *      Returns the escaped string. 
	 */
	public function escapeString ($link, $escapestr) {

	    if ( ! is_numeric($escapestr)) {
	    	if (get_magic_quotes_gpc()) {
		        $escapestr = stripslashes($escapestr);
		    }

	        $escapestr = mysql_real_escape_string($escapestr);
	    }

	    return $escapestr;
	}


	/**
	 * Send MS SQL query
	 * 
	 * @param resource $link
	 *      A MS SQL link identifier
	 * @param string $query
	 *      An SQL query. 
	 * @return mixed
	 *      Returns a MS SQL result resource on success, 
	 *      TRUE if no rows were returned, 
	 *      or FALSE on error. 
	 */
	public function query ($link, $query) {

		return mssql_query($query, $link);
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param resource $link
	 *      A MS SQL link identifier 
	 * @param string $query
	 *      An SQL query. 
	 * @return bool
	 *      Returns a MS SQL result resource on success, 
	 *      TRUE if no rows were returned, 
	 *      or FALSE on error. . 
	 */
	public function multyQuery ($link, $query) {

		return mssql_query($query, $link);
	}



	/**
	 * Gets the first value of the first row of the query result
	 * 
	 * @param resource $result
	 *      The result resource that is being evaluated.
	 * @return string
	 *      Returns an associative array that corresponds to the fetched row, 
	 *      or FALSE if there are no more rows. 
	 */
	public function fetchOne ($result) {

		$row    = mssql_fetch_assoc($result);
		$return = '';
		if (is_array($row)) {
			foreach ($row as $value) {
				$return = $value;
				break;
			}			
		}

		return $row === false ? false : $return;
	}


	/**
	 * Returns an associative array of the current row in the result
	 * 
	 * @param resource $result
	 *     The result resource that is being evaluated
	 * @return array
	 *     Returns an associative array that corresponds to the fetched row, 
	 *     or FALSE if there are no more rows. 
	 */
	public function fetchRow ($result) {

		return mssql_fetch_assoc($result);
	}


	/**
	 * Fetches all result rows as an associative array
	 * 
	 * @param resource $result
	 *     The result resource that is being evaluated
	 * @return array
	 *     Returns an associative array of strings that corresponds to the fetched row, 
	 *     or FALSE if there are no more rows. 
	 */
	public function fetchAll ($result) {

		$rows = array();
		while ($row = mssql_fetch_assoc($result)) {
		    $rows[] = $row;
		}

        return count($rows) > 0 ? $rows : false;
	}


	/**
	 * Get number of rows in result
	 * 
	 * @param resource $result
	 *     The result resource that is being evaluated
	 * @return int
	 *      Returns the number of rows, as an integer. 
	 */
	public function getNumRows ($result) {

		return mssql_num_rows($result);
	}


	/**
	 * Get the ID generated in the last query
	 * 
	 * @param resource $link
	 *     A MS SQL link identifier 
	 * @return int
	 *     The ID generated for an AUTO_INCREMENT column by the previous query on success, 
	 *     0 if the previous query does not generate an AUTO_INCREMENT value, 
	 *     or FALSE if no MySQL connection was established. 
	 */
	public function getLastId ($link, $table_name = null) {

		$id  = 0;
		$res = mssql_query('SELECT @@IDENTITY as id', $link);
		
		if ($row = mssql_fetch_array($res, MYSQL_ASSOC)) {
			$id = $row["id"];
		}

		return $id;
	}


	/**
	 * Closes a previously opened database connection
	 * 
	 * @param resource $link
	 *     A MS SQL link identifier 
	 * @return bool
	 *     Returns TRUE on success or FALSE on failure.
	 */
	public function closeConnect ($link) {

		return mssql_close($link); 
	}

	
	/**
	 * Free result memory
	 * 
	 * @param resource $result
	 *     The result resource that is being freed
	 * @return bool
	 *     Returns TRUE on success or FALSE on failure.
	 */
	public function free ($result) {

		return mssql_free_result($result);
	}
}