<?php


require_once(__DIR__ . '/../adapter_interface.php');


/**
 *  Адаптер mysqli для mysql server
 */
class MysqliAdapter implements IDBAdapter {


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
     * @return mysqli
     *      Возвращает объект, представляющий подключение к серверу MySQL.
     */
    public function connect ($host, $port = '', $basename, $user, $pass = '', $charset = 'utf8', $time_zone = '') {

        $link = mysqli_connect($host, $user, $pass, $basename, $port);

        if ($link) {
            mysqli_set_charset($link, $charset);

            if ($time_zone) {
                $stmt = $this->prepare($link, "SET time_zone = ?");
                $stmt->bind_param('s', $time_zone);
                $stmt->execute();
            }
        }

        return $link;
    }


	/**
     * Получение описания последней ошибки
     *
	 * @param mysqli|null $link
     *      Объект mysqli
     *
	 * @return string
     *      Текст ошибки
	 */
	public function connectError ($link = null) {
		
		return mysqli_connect_error();
	}


	/**
     * Получение описания последней ошибки
     *
	 * @param mysqli $link
     *      Объект mysqli
     *
	 * @return string
     *      Текст ошибки
	 */
	public function error ($link) {
		
		return mysqli_error($link);
	}


    /**
     * Подготовка запроса к выполнению
     *
     * @param mysqli $link
     *      Объект mysqli
     * @param string $query
     *      Текст запроса
     *
     * @return mysqli_stmt|bool
     *      Возвращает объект запроса или FALSE в случае ошибки.
     */
    public function prepare ($link, $query) {

		return mysqli_prepare($link, $query);
	}


    /**
     * Привязка переменных к параметрам подготавливаемого запроса
     *
     * @param mysqli_stmt $stmt
     *      Объект mysqli_stmt
     * @param string $parameter
     *      Название параметра (в данном адаптаре не используется)
     * @param string $value
     *      Значение связываемое с запросом
     * @param string $data_type
     *      Тип значения переменной $value.
     *      Может быть:
     *      s - string
     *      d - double
     *      i - integer
     *      b - blob
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function bindValue ($stmt, $parameter = '', $value, $data_type = 's') {

		return mysqli_stmt_bind_param($stmt, $data_type, $value);
	}


    /**
     * Выполняет подготовленный запрос
     *
     * @param mysqli_stmt $stmt
     *      Объект mysqli_stmt
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function execute ($stmt) {

		return mysqli_stmt_execute($stmt);
	}


	/**
     * Выполняет запрос к базе данных
     *
     * @param mysqli $link
     *      Объект mysqli
	 * @param string $query
     *      Текст запроса
     *
	 * @return mysqli_result|bool
     *      Возвращает FALSE в случае неудачи.
     *      В случае успешного выполнения запросов SELECT, SHOW, DESCRIBE или EXPLAIN
     *      mysqli_query() вернет объект mysqli_result.
     *      Для остальных успешных запросов mysqli_query() вернет TRUE.
     */
	public function query ($link, $query) {

		return mysqli_query($link, $query);
	}


    /**
     * Начало транзакции
     *
     * @param mysqli $link
     *      Объект mysqli
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function beginTransaction ($link) {

        if (function_exists('mysqli_begin_transaction')) {
            return mysqli_begin_transaction($link);
        } else {
            return mysqli_autocommit($link, false);
        }
	}


    /**
     * Фиксирует транзакцию
     *
     * @param mysqli $link
     *      Объект mysqli
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function commit ($link) {

		return mysqli_commit($link);
	}


    /**
     * Откатывает изменения в базе данных сделанные в рамках текущей транзакции,
     *
     * @param mysqli $link
     *      Объект mysqli
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function rollback ($link) {

		return mysqli_rollback($link);
	}


	/**
     * Возващает резельтат запроса с первым значением поля,
     * из первой строки запроса
     *
	 * @param mysqli_result $result
     *      Объект mysqli_result
     *
	 * @return string
     *      Первое значение из запроса,
     *      либо false в случае ошибки
	 */
	public function fetchOne ($result) {

		$row    = mysqli_fetch_assoc($result);	
		$return = '';
		if (is_array($row)) {
			return current($row);
		}

		return $return;
	}


	/**
     * Возващает результат запроса с первой строкой
     * из результата запроса
     *
	 * @param mysqli_result $result
     *      Объект mysqli_result
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchRow ($result) {

		return mysqli_fetch_assoc($result);
	}


	/**
     * Возващает результат запроса с указанным столбцом
     * из результата запроса
     *
	 * @param mysqli_result $result
     *      Объект mysqli_result
	 * @param int $column_number
     *      Номер необходимого столбца
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchCol ($result, $column_number = 0) {

        $column        = array();
        $column_number = $column_number > 0
            ? $column_number - 1
            : 0;

        // compatibility layer with PHP < 5.3
        if (function_exists('mysqli_fetch_all')) {
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            foreach ($res as $r) {
                $i = 0;
                foreach ($r as $value) {
                    if ($i++ == $column_number) {
                        $column[] = $value;
                        break;
                    }
                }
            }

        } else {
            while ($tmp = mysqli_fetch_array($result)) {
                $i = 0;
                foreach ($tmp as $value) {
                    if ($i++ == $column_number) {
                        $column[] = $value;
                        break;
                    }
                }
            }
        }

        return $column;
	}


	/**
     * Возващает результат запроса в виде одномерного массива
     * ключами которого выступает первое поле из запроса, а значениями второе поле
     *
	 * @param mysqli_result $result
     *      Объект mysqli_result
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchPairs ($result) {

        $pairs = array();

        // compatibility layer with PHP < 5.3
        if (function_exists('mysqli_fetch_all')) {
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            foreach ($res as $r) {
                $pairs[current($r)] = next($r);
            }

        } else {
            while ($tmp = mysqli_fetch_array($result)) {
                $pairs[current($tmp)] = next($tmp);
            }
        }

        return $pairs;
	}
	

	/**
     * Возващает результат запроса со всеми записями
     *
	 * @param mysqli_result $result
     *      Объект mysqli_result
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchAll ($result) {

		$result = array();

	    // compatibility layer with PHP < 5.3
		if (function_exists('mysqli_fetch_all')) {
            $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        } else {
        	while ($tmp = mysqli_fetch_array($result)) {
                $result[] = $tmp;
        	}
        }

        return $result;
	}


	/**
     * Возвращает количество строк, измененных в процессе выполнения запроса
     *
	 * @param mysqli_result $link
     *      Объект mysqli_result
     *
	 * @return int|bool
     *      Возвращает число затронутых строк в виде integer, либо FALSE при ошибке.
     */
	public function affectedRows ($link) {

        $count = mysqli_affected_rows($link);
		return $count == -1 ? false : $count;
	}


	/**
     * Возвращает ID последней вставленной строки либо последнее значение,
     * которое выдал объект последовательности.
     *
	 * @param mysqli $link
     *      Объект mysqli
	 * @param string|null $table_name
     *
	 * @return int|string
     *      Вернет строку представляющую ID последней добавленной в базу записи.
	 */
	public function lastInsertId ($link, $table_name = null) {

		return mysqli_insert_id($link);
	}


	/**
     * Закрытие соединения с базой
     *
     * @param mysqli $link
     *      Объект mysqli
     *
	 * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
	 */
	public function close ($link) {

		return mysqli_close($link); 
	}
}
