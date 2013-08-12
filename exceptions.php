<?php


/**
 * Ошибка подключения к базе
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-05-31
 */
class ErrorConnectDataBaseException extends Exception {

	public function __construct ($error_description, $error_number) {

		$this->message = "Error connect to database: $error_number $error_description ";
	}
}


/**
 * Файл не найден
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-06-02
 */
class FileNotExistsDataBaseException extends Exception {

	public function __construct ($path_to_file) {

		$this->message = "File \"{$path_to_file}\" not found";
	}
}


/**
 * Некорректно реализация адаптера базы данных
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-06-02
 */
class IncorrectAdapterImplementationDataBaseException extends Exception {

	public function __construct ($adapter) {

		$this->message = "Incorrect adapter implementation \"$adapter\"";
	}
}


/**
 * Ошибка подключения к базе
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-06-02
 */
class NotSetAdapterDataBaseException extends Exception {

	public function __construct () {

		$this->message = "Not set adapter to connect to the database";
	}
}


/**
 * Ошибка выбора базы данных
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-06-02
 */
class ErrorSelectDataBaseExeption extends Exception {

	public function __construct ($database) {

		$this->message = "Error select database \"$database\"";
	}
}


/**
 * Ошибка выполнения запроса
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-06-02
 */
class ErrorQueryExecutionDataBaseException extends Exception {

	public function __construct ($error_description, $error_number) {

		$this->message = "Error query execution: $error_number $error_description ";
	}
}


/**
 * Ошибка, метод не существует
 *
 * @author Shabunin Igor <mailforshinji@gmail.com>
 * @package database
 *
 * @version 0.1
 * @since 2013-07-10
 */
class MethodNotExistsDataBaseException extends Exception {

	public function __construct ($method_name) {

		$this->message = "Method \"$method_name\" not exists";
	}
}

