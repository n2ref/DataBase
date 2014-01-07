<?php


/**
 * Исключения для базы данных
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-12-31
 */
class Helly_DataBase_Exception extends Exception {

	public function __construct ($message) {

		$this->message = $message;
	}
}


