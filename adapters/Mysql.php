<?php

require_once(__DIR__.'/../adapter_interface.php');
require_once(__DIR__.'/../exeptions.php');

/**
 *  Adapter mysql for mysql server
 */
class MysqlAdapter implements IDataBaseAdapter {


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
	 *     The MySQL connection
	 */
	public function connect ($host, $basename, $user, $pass = '') {
		
		$link = mysql_connect($host, $user, $pass);

		if ( ! mysql_select_db($basename, $link)) {
			throw new ErrorSelectDataBaseExeption($basename);
		}

		return $link;
	}


	/**
	 * Returns the text of the error message from previous MySQL operation
	 *
	 * @param resource $link
	 *      Connection identifier
	 * @return string
	 *     Returns the error text from the last MySQL function, 
	 *     or '' (empty string) if no error occurred. 
	 */
	public function connectError ($link) {
		
		return mysql_error($link);
	}

	
	/**
	 * Returns the numerical value of the error message 
	 * from previous MySQL operation
	 *
	 * @param resource $link
	 *      Connection identifier
	 * @return int
	 *     Returns the error number from the last MySQL function, 
	 *     or 0 (zero) if no error occurred. 
	 */
	public function connectErrno ($link) {
		
		return mysql_errno($link);
	}


	/**
	 * Returns the text of the error message from previous MySQL operation
	 * 
	 * @return string
	 *     Returns the error text from the last MySQL function, 
	 *     or '' (empty string) if no error occurred. 
	 */
	public function error ($link = null) {
		
		return mysql_error();
	}

	
	/**
	 * Returns the numerical value of the error message 
	 * from previous MySQL operation
	 * 
	 * @return int
	 *     Returns the error number from the last MySQL function, 
	 *     or 0 (zero) if no error occurred. 
	 */
	public function errno ($link = null) {
		
		return mysql_errno();
	}


	/**
	 * Escapes special characters in a string for use in an SQL statement
	 * 
	 * @param resource $link
	 *      The MySQL connection
	 * @param string $escapestr
	 *      The string that is to be escaped. 
	 * @return string
	 *      Returns the escaped string, 
	 *      or FALSE on error. 
	 */
	public function escapeString ($link, $escapestr) {

		return mysql_real_escape_string($escapestr, $link);
	}


	/**
	 * Send a MySQL query
	 * 
	 * @param resource $link
	 *      The MySQL connection
	 * @param string $query
	 *      The query string
	 * @return mixed
	 *       For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset, 
	 *       mysql_query() returns a resource on success, or FALSE on error.
	 *       For other type of SQL statements, INSERT, UPDATE, DELETE, DROP, etc, 
	 *       mysql_query() returns TRUE on success or FALSE on error. 
	 */
	public function query ($link, $query) {

		return mysql_query($query, $link);
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param resource $link
	 *      A link identifier 
	 * @param string $query
	 *      The query, as a string
	 * @return bool
	 *      Returns FALSE if the first statement failed. 
	 *      To retrieve subsequent errors from other statements 
	 *      you have to call mysqli_next_result() first. 
	 */
	public function multyQuery ($link, $query) {

		$delimiter    = ';';
		$inString     = false;
		$escChar      = false;
		$sql          = '';
		$stringChar   = '';
		$sql_queries  = array();
		$sqlRows      = explode ("\n", $query);
		$delimiterLen = strlen ($delimiter);

		do {
			$sqlRow    = current($sqlRows) . "\n";
			$sqlRowLen = strlen($sqlRow);
			
			for ($i = 0; $i < $sqlRowLen; $i) {
				if ((substr(ltrim($sqlRow), $i, 2) === '--' || substr(ltrim($sqlRow), $i, 1) === '#') && 
					! $inString
				) {
					break;
				}

				$znak = substr($sqlRow, $i, 1);
				
				if ($znak === '\'' || $znak === '"') {
					if ($inString) {
						if ( ! $escChar && $znak === $stringChar) {
							$inString = false;
						}

					} else {
						$stringChar = $znak;
						$inString = true;
					}
				}

				if ($znak === '\\' && substr($sqlRow, $i - 1, 2) !== '\\\\' ) {
					$escChar = !$escChar;
				
				} else {
					$escChar = false;
				}
				
				if (substr($sqlRow, $i, $delimiterLen) === $delimiter) {
					if ( ! $inString) {
						$sql            = trim ( $sql );
						$delimiterMatch = array();

						if (preg_match('/^DELIMITER[[:space:]]*([^[:space:]] )$/i', $sql, $delimiterMatch)) {
							$delimiter    = $delimiterMatch [1];
							$delimiterLen = strlen ($delimiter);
						
						} else {
							$sql_queries[] = $sql;
						}

						$sql = '';
						continue;
					}
				}
				$sql .= $znak;
			}
		} while (next($sqlRows) !== false);


		foreach($sql_queries as $sql_query) {			
			if ( ! mysql_query($sql_query)) {
				throw new ErrorQueryExecutionDataBaseException($this->error, $this->errno);
			}
		}
		
		return true;
	}


	/**
	 * Gets the first value of the first row of the query result
	 * 
	 * @param resource $result
	 *      A result set identifier
	 * @return string
	 *      Returns the first value of the 
	 *      first row of the query result
	 */
	public function fetchOne ($result) {

		$row    = mysql_fetch_assoc($result);
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
	 * @param resource $result
	 *     The result resource that is being evaluated.
	 * @return array
	 *     Returns an associative array of strings that corresponds to the fetched row, 
	 *     or FALSE if there are no more rows. 
	 */
	public function fetchRow ($result) {

		return mysql_fetch_assoc($result);
	}
	

	/**
	 * Fetches all result rows as an associative array
	 * 
	 * @param mysqli_result $result
	 *     A result set identifier
	 * @return array
	 *     Returns an associative array of strings that corresponds to the fetched row, 
	 *     or FALSE if there are no more rows. 
	 */
	public function fetchAll ($result) {

		$rows = array();
		while ($row = mysql_fetch_assoc($result)) {
		    $rows[] = $row;
		}

        return count($rows) > 0 ? $rows : false;
	}


	/**
	 * Get number of rows in result
	 * 
	 * @param resource $result
	 *     The result resource that is being evaluated.
	 * @return int|bool
	 *      The number of rows in a result set on success 
	 *      or FALSE on failure.
	 */
	public function getNumRows ($result) {

		return mysql_num_rows($result);
	}


	/**
	 * Get the ID generated in the last query
	 * 
	 * @param resource $link
	 *     The MySQL connection.
	 * @return int
	 *     The ID generated for an AUTO_INCREMENT column by the previous query on success, 
	 *     0 if the previous query does not generate an AUTO_INCREMENT value, 
	 *     or FALSE if no MySQL connection was established. 
	 */
	public function getLastId ($link, $table_name = null) {

		return mysql_insert_id($link);
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

		return mysql_close($link); 
	}


	/**
	 * Free result memory
	 * 
	 * @param resource $result
	 *     The result resource that is being evaluated.
	 * @return bool
	 *     Returns TRUE on success or FALSE on failure.
	 */
	public function free ($result) {

		return mysql_free_result($result);
	}
}
