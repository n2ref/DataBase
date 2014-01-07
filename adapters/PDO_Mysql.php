<?php


require_once (__DIR__ . '/../adapter_interface.php');


/**
 *  Адаптер PDO_Mysql
 */
class PDO_MysqlAdapter implements IDBAdapter {


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
     * @return PDO
     *      Возвращает объект PDO в случае успеха.
     */
    public function connect ($host, $port, $basename, $user, $pass = '', $charset = 'UTF8', $time_zone = '') {

		$link = new PDO("mysql:host=$host;port=$port;dbname=$basename;charset=$charset", $user, $pass);

        if ($link && $time_zone) {
            $stmt = $this->prepare($link, "SET time_zone = :time_zone");
            $stmt->bindValue(':time_zone', $time_zone);
            $stmt->execute();
        }

        return $link;
	}


    /**
     * Получение описания последней ошибки
     *
     * @param PDO $link
     *      Объект PDO
     *
     * @return string
     *      Текст ошибки
     */
    public function connectError ($link = null) {

        $error = $link->errorInfo();

		return $error[2];
	}


    /**
     * Получение описания последней ошибки
     *
     * @param PDOStatement $stmt
     *      Объект PDOStatement
     *
     * @return string
     *      Текст ошибки
     */
	public function error ($stmt) {

        $error = $stmt->errorInfo();

        return $error[2];
	}


    /**
     * Подготовка запроса к выполнению
     *
     * @param PDO $link
     *      Объект PDO
     * @param string $query
     *
     * @return PDOStatement|bool
     *      Если СУБД успешно подготовила запрос,
     *      PDO::prepare() возвращает объект PDOStatement.
     *      Если подготовить запрос не удалось, PDO::prepare() возвращает FALSE
     *      или выбрасывает исключение PDOException (зависит от текущего режима обработки ошибок).
     */
    public function prepare ($link, $query) {

		return $link->prepare($query);
	}


    /**
     * Задает значение именованной или неименованной
     * псевдопеременной в подготовленном SQL запросе.
     *
     * @param PDOStatement $stmt
     *      Объект PDOStatement
     * @param mixed $parameter
     *      Название параметра
     * @param mixed $value
     *      Значение связываемое с запросом
     * @param int $data_type
     *      Тип значения переменной $value
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function bindValue ($stmt, $parameter, $value,  $data_type = PDO::PARAM_STR) {

		return $stmt->bindValue($parameter, $value, $data_type);
	}


    /**
     * Запускает подготовленный запрос на выполнение
     *
     * @param PDOStatement $stmt
     *      Объект PDOStatement
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function execute ($stmt) {

        $stmt->closeCursor();
        $result = $stmt->execute();


        return $result;
    }


    /**
     * Возвращает количество строк,
     * которые были затронуты в ходе выполнения последнего запроса DELETE, INSERT или UPDATE,
     * запущенного соответствующим объектом
     *
     * @param PDOStatement $stmt
     *      Экземпляр PDOStatement
     *
     * @return int
     *      Количество строк измененных последним запросом
     */
    public function affectedRows ($stmt) {

        return $stmt->rowCount();
    }


	/**
     * Выполняет SQL запрос и возвращает результирующий набор
     * в виде объекта PDOStatement
     *
     * @param PDO $link
     *      Объект PDO
	 * @param string $query
     *      Текст SQL запроса для подготовки и выполнения.
     *
     * @return PDOStatement|bool
     *      Возвращает объект PDOStatement
     *      или FALSE, если запрос выполнить не удалось.
     */
	public function query ($link, $query) {

		return $link->query($query);
	}


    /**
     * Выключает режим автоматической фиксации транзакции.
     *
     * @param PDO $link
     *      Объект PDO
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function beginTransaction ($link) {

        return $link->beginTransaction();
	}


    /**
     * Фиксирует транзакцию, возвращая соединение с базой данных
     * в режим автоматической фиксации до тех пор,
     * пока следующий вызов PDO::beginTransaction() не начнет новую транзакцию.
     *
     * @param PDO $link
     *      Объект PDO
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function commit ($link) {

		return $link->commit();
	}


    /**
     * Откатывает изменения в базе данных сделанные в рамках текущей транзакции,
     * которая была создана методом PDO::beginTransaction().
     * Если активной транзакции нет, будет выброшено исключение PDOException.
     *
     * @param PDO $link
     *      Объект PDO
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function rollback ($link) {

		return $link->rollBack();
	}


	/**
     * Возващает резельтат запроса с первым значением поля,
     * из первой строки запроса
     *
	 * @param PDOStatement $stmt
     *      Объект PDOStatement
	 *
	 * @return string
	 *      Первое значение из запроса,
     *      либо false в случае ошибки
	 */
	public function fetchOne ($stmt) {

		$row    = $stmt->fetch(PDO::FETCH_ASSOC);
		$return = '';
		if (is_array($row)) {
            $return = current($row);
        }

		return $return;
	}


	/**
     * Возващает результат запроса с первой строкой
     * из результата запроса
     *
	 * @param PDOStatement $result
     *      Объект PDOStatement
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchRow ($result) {

		return $result->fetch(PDO::FETCH_ASSOC);
	}


	/**
     * Возващает результат запроса с указанным столбцом
     * из результата запроса
     *
	 * @param PDOStatement $result
     *      Объект PDOStatement
	 * @param int $column_number
     *      Номер столбца, данные которого необходимо извлечь.
     *
     * @return array
     *      Результирующий массив данных
	 */
	public function fetchCol ($result, $column_number = 0) {

        $column        = array();
        $column_number = $column_number > 0
            ? $column_number - 1
            : 0;

        while ($col = $result->fetchColumn($column_number)) {
            $column[] = $col;
        }

        return $column;
	}


	/**
     * Возващает результат запроса в виде одномерного массива
     * ключами которого выступает первое поле из запроса, а значениями второе поле
     *
	 * @param PDOStatement $result
     *      Объект PDOStatement
	 *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchPairs ($result) {

        $pairs = array();

        while ($tmp = $result->fetch(PDO::FETCH_ASSOC)) {
            $pairs[current($tmp)] = next($tmp);
        }

        return $pairs;
	}
	

	/**
     * Возващает результат запроса со всеми записями
     *
	 * @param PDOStatement $stmt
     *      Объект PDOStatement
     *
	 * @return array
     *      Результирующий массив данных
	 */
	public function fetchAll ($stmt) {

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}


	/**
	 * Возвращает ID последней вставленной строки либо последнее значение,
     * которое выдал объект последовательности.
     *
	 * @param PDO $link
     *      Объект PDO
     * @param string $table_name
     *      Имя объекта последовательности, который должен выдать ID.
     *
     * @return string
	 *      Вернет строку представляющую ID последней добавленной в базу записи.
     */
	public function lastInsertId ($link, $table_name = null) {

		return $link->lastInsertId($table_name);
	}


	/**
	 * Закрытие соединения с базой
	 * 
	 * @param PDO $link
     *      Объект PDO
     *
	 * @return void
	 */
	public function close ($link) {

        $link = null;
	}
}
