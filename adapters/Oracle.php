<?php


require_once(__DIR__ . '/../adapter_interface.php');


/**
 *  Адаптер oracle для oracle server
 */
class OracleAdapter implements IDBAdapter {

    /**
     * Признак авто сохранения в базу
     * @var bool
     */
    private static $auto_commit = true;


    /**
     * Подключение к базе
     *
     * @param string $host          Название хоста для подключения
     * @param string|int $port      Номер порта для подключения
     * @param string $basename      Название базы данных
     * @param string $user          Логин пользователя
     * @param string $pass          Пароль пользователя
     * @param string $charset       Кодировка подключения
     * @param string $time_zone     Временная зона
     *
     * @return resource
     *      Идентификатор соединения Oracle
     */
    public function connect ($host, $port = '', $basename, $user, $pass = '', $charset = 'utf8', $time_zone = '') {

        $connection_string = $host;

        if ($port) {
            $connection_string .= ':' . $port;
        }

        if ($basename) {
            $connection_string .= '/' . $basename;
        }

        $link = oci_connect($user, $pass, $connection_string, $charset);

        if ($link && $time_zone) {
            $stmt = $this->prepare($link, "SET time_zone = :time_zone");
            $this->bindValue($stmt, ':time_zone', $time_zone);
            $stmt->execute();
        }

		return $link;
	}


	/**
     * Получение описания последней ошибки
     *
	 * @param resource $link
     *      Идентификатор соединения Oracle
     *
	 * @return string
     *      Текст ошибки
	 */
	public function connectError ($link) {
		
		$error = oci_error($link);

		if ($error !== false) {
			return $error['message'] . "\n" . $error['sqltext'];
		}

		return false;
	}


	/**
     * Получение описания последней ошибки
     *
	 * @param resource $link
     *      Идентификатор соединения Oracle
     *
	 * @return string
     *      Текст ошибки
	 */
	public function error ($link) {
		
		$error = oci_error($link);

		if ($error !== false) {
			return $error['message'] . "\n" . $error['sqltext'];
		}

		return false;
	}


    /**
     * Подготовка запроса к выполнению
     *
     * @param resource $link
     *      Идентификатор соединения Oracle
     * @param string $query
     *      Текст запроса
     *
     * @return resource|bool
     *      Идентификатор выражения OCI8 или FALSE в случае ошибки
     */
    public function prepare ($link, $query) {

        return oci_parse($link, $query);
    }


    /**
     * @param resource $stmt
     *      Корректный идентификатор выражения OCI8
     * @param string $parameter
     *      Название параметра
     * @param string $value
     *      Значение связываемое с запросом
     * @param int $data_type
     *      Тип значения переменной $value.
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function bindValue ($stmt, $parameter, $value, $data_type = SQLT_CHR) {

        return oci_bind_by_name($stmt, $parameter, $value, $data_type);
    }


    /**
     * Выполняет подготовленный запрос
     *
     * @param resource $stmt
     *      Корректный идентификатор выражения OCI8
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function execute ($stmt) {

        if (self::$auto_commit) {
            $mode = OCI_COMMIT_ON_SUCCESS;
        } else {
            $mode = OCI_NO_AUTO_COMMIT;
        }

        return oci_execute($stmt, $mode);
    }


    /**
     * Начало транзакции
     *
     * @param resource $link
     *      Идентификатор соединения Oracle
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function beginTransaction ($link) {

        return self::$auto_commit = false;
    }


    /**
     * Фиксирует транзакцию
     *
     * @param resource $link
     *      Идентификатор соединения Oracle
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function commit ($link) {

        $is_commit = oci_commit($link);
        self::$auto_commit = true;

        return $is_commit;
    }


    /**
     * Откатывает изменения в базе данных сделанные в рамках текущей транзакции,
     *
     * @param resource $link
     *      Идентификатор соединения Oracle
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function rollback ($link) {

        $is_rollback = oci_rollback($link);
        self::$auto_commit = true;

        return $is_rollback;
    }


	/**
     * Выполняет запрос к базе данных
     *
	 * @param resource $link
     *      Идентификатор соединения Oracle
	 * @param string $query
     *      Текст запроса
     *
	 * @return mixed
	 */
	public function query ($link, $query) {

		$stmt = oci_parse($link, $query);
		return $stmt && oci_execute($stmt) ? $stmt : false;
	}


	/**
     * Возващает резельтат запроса с первым значением поля,
     * из первой строки запроса
     *
	 * @param resource $statement
     *      Корректный идентификатор выражения OCI8
     *
	 * @return string
     *      Первое значение из запроса,
     *      либо false в случае ошибки
	 */
	public function fetchOne ($statement) {

		$row    = oci_fetch_assoc($statement);
		$return = '';
		if (is_array($row)) {
			$return = current($row);
		}

		return $return;
	}


	/**
     * Возващает результат запроса с указанным столбцом
     * из результата запроса
     *
	 * @param resource $statement
     *      Корректный идентификатор выражения OCI8
	 * @param int $column_number
     *      Номер необходимого столбца
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchCol ($statement, $column_number = 1) {

        $column = array();

        while ($row = oci_fetch_row($statement)) {
            $column[] = $row[$column_number - 1];
        }

        return $column;
	}


    /**
     * Возващает результат запроса в виде одномерного массива
     * ключами которого выступает первое поле из запроса, а значениями второе поле
     *
     * @param resource $statement
     *      Корректный идентификатор выражения OCI8
     *
     * @return array
     *      Результирующий массив данных
     */
    public function fetchPairs ($statement) {

        $pairs = array();

        while ($tmp = oci_fetch_assoc($statement)) {
            $pairs[current($tmp)] = next($tmp);
        }

        return $pairs;
    }


	/**
     * Возващает результат запроса с первой строкой
     * из результата запроса
     *
	 * @param resource $statement
     *      Корректный идентификатор выражения OCI8
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchRow ($statement) {

		return oci_fetch_assoc($statement);
	}
	

	/**
     * Возващает результат запроса со всеми записями
     *
	 * @param resource $statement
     *      Корректный идентификатор выражения OCI8
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchAll ($statement) {

		oci_fetch_all($statement, $res);
        
        return $res;
	}


	/**
     * -= ВСЕГДА ВОЗВРАЩАЕТ 0 =-
     * Возвращает ID последней вставленной строки либо последнее значение,
     * которое выдал объект последовательности.
     *
	 * @param resource $link
     *      Идентификатор соединения Oracle
	 * @param string $table_name
     *      Название таблицы из которой нужно получить последний ID
     *
	 * @return int
     *      Вернет строку представляющую ID последней добавленной в базу записи.
	 */
	public function lastInsertId ($link, $table_name = null) {

		return 0;
	}


	/**
     * Возвращает количество строк, измененных в процессе выполнения запроса
     *
	 * @param resource $stmt
     *      Корректный идентификатор выражения OCI8
     *
	 * @return int|bool
     *      Возвращает число затронутых строк в виде integer, либо FALSE при ошибке.
     */
	public function affectedRows ($stmt) {

        return oci_num_rows($stmt);
	}


	/**
     * Закрытие соединения с базой
     *
	 * @param resource $link
     *      Идентификатор соединения Oracle
     *
	 * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
	 */
	public function close ($link) {

		return oci_close($link); 
	}
}
