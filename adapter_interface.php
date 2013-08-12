<?php


interface IDataBaseAdapter {

	public function connect ($host, $basename, $user, $pass);

	public function connectError ($link);

	public function connectErrno ($link);

	public function error ($link);

	public function errno ($link);

	public function escapeString ($link, $escapestr);

	public function query ($link, $query);

	public function multyQuery ($link, $query);

	public function fetchOne ($result);

	public function fetchRow ($result);

	public function fetchAll ($result);

	public function getNumRows ($result);

	public function getLastId ($link, $table_name = '');

	public function closeConnect ($link);

	public function free ($result);

}