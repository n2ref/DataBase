<?php


/**
 * Interface IDBAdapter
 */
interface IDBAdapter {

	public function connect ($host, $port, $basename, $user, $pass, $charset, $timezone);

	public function connectError ($link);

	public function error ($link);

	public function query ($link, $query);

	public function prepare ($link, $query);

    public function bindValue ($stmt, $parameter, $value,  $data_type);

    public function execute ($stmt);

	public function fetchOne ($result);

	public function fetchRow ($result);

	public function fetchPairs ($result);

	public function fetchCol ($result, $col);

	public function fetchAll ($result);

	public function affectedRows ($link);

	public function lastInsertId ($link, $table_name);

	public function beginTransaction ($link);

	public function commit ($link);

	public function rollback ($link);

    public function close ($link);
}