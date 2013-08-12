<?php

require_once(__DIR__.'/../adapter_interface.php');

/**
 *  Adapter oracle for oracle server
 */
class OracleAdapter implements IDataBaseAdapter {


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
	 * @return resource|bool
	 *      Returns a connection identifier or FALSE on error. 
	 */
	public function connect ($host, $basename, $user, $pass = '') {
		
		return oci_connect($user, $pass, $host);
	}


	/**
	 * Returns the last error found
	 *
	 * @param resource $link
	 *      Connection identifier
	 * @return string|bool
	 *      The Oracle error text or false
	 */
	public function connectError ($link) {
		
		$error = oci_error($link);

		if ($error !== false) {
			return $error['message'] . "\n" . $error['sqltext'];
		}

		return false;
	}

	
	/**
	 * Number of connection errors
	 *
	 * @param resource $link
	 *      Connection identifier
	 * @return int|bool
	 *       The Oracle error number or false
	 */
	public function connectErrno ($link) {
		
		$error = oci_error($link);

		if ($error !== false) {
			return $error['code'];
		}

		return false;
	}


	/**
	 * Returns the last error found
	 * 
	 * @param resource $link
	 *     Connection identifier
	 * @return string
	 *     The Oracle error text or false
	 */
	public function error ($link) {
		
		$error = oci_error($link);

		if ($error !== false) {
			return $error['message'] . "\n" . $error['sqltext'];
		}

		return false;
	}

	
	/**
	 * Returns the last error found
	 *
	 * @param resource $link
	 *    Connection identifier
	 * @return int
	 *     The Oracle error number or false 
	 */
	public function errno ($link) {
		
		$error = oci_error($link);

		if ($error !== false) {
			return $error['code'];
		}

		return false;
	}


	/**
	 * Escapes special characters in a string for use in an SQL statement, 
	 * taking into account the current charset of the connection
	 * 
	 * @param resource $link
	 *      A valid OCI statement identifier. 
	 * @param string $escapestr
	 *      The string to be escaped
	 * @return string
	 *      Returns an escaped string. 
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
	 * Performs a query on the database
	 * 
	 * @param resource $link
	 *      A valid OCI statement identifier. 
	 * @param string $query
	 *      The query string
	 * @return mixed
	 *      Returns a statement handle on success, or FALSE on error. 
	 */
	public function query ($link, $query) {

		$stid = oci_parse($link, $query);
		return $stid && oci_execute($stid) ? $stid : false;
	}


	/**
	 * Performs a query on the database
	 * 
	 * @param resource $link
	 *      A valid OCI statement identifier. 
	 * @param string $query
	 *      The query, as a string
	 * @return bool
	 *      Returns a statement handle on success, or FALSE on error.  
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

			$stid = oci_parse($link, $sql_query);		
			
			if ($stid && oci_execute($stid)) {
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

		$row    = oci_fetch_assoc($result);
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
	 * Returns the next row from a query as an associative array
	 * 
	 * @param resource $result
	 *     A result set identifier
	 * @return array|bool
	 *    Returns an associative array. 
	 *    If there are no more rows in the statement then FALSE is returned. 
	 */
	public function fetchRow ($result) {

		return oci_fetch_assoc($result);
	}
	

	/**
	 * Fetches multiple rows from a query into a two-dimensional array
	 * 
	 * @param resource $result
	 *     A result set identifier
	 * @return array
	 *     Returns an array of associative
	 */
	public function fetchAll ($result) {

		oci_fetch_all($result, $res);
        
        return $res;
	}


	/**
	 * Returns number of rows affected during statement execution
	 * 
	 * @param resource $result
	 *     A valid OCI statement identifier. 
	 * @return int|bool
	 *     Returns the number of rows affected as an integer, 
	 *     or FALSE on errors. 
	 */
	public function getNumRows ($result) {

		return oci_num_rows($result);
	}


	/**
	 * Solutions of this method was not found
	 * 
	 * @param resource $link
	 *      Connection identifier
	 * @return int
	 *     0 (zero)
	 */
	public function getLastId ($link, $table_name = null) {

		return 0;
	}


	/**
	 * Closes an Oracle connection
	 * 
	 * @param resource $link
	 *      An Oracle connection identifier
	 * @return bool
	 *      Returns TRUE on success or FALSE on failure.
	 */
	public function closeConnect ($link) {

		return oci_close($link); 
	}


	/**
	 * Frees all resources associated with statement or cursor
	 * 
	 * @param resource $result
	 *     A valid OCI statement identifier. 
	 * @return bool
	 *     Returns TRUE on success or FALSE on failure. 
	 */
	public function free ($result) {

		return oci_free_statement($result);
	}
}
