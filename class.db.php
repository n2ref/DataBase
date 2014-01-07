<?php


require_once ('exceptions.php');


/**
 * @author Шабунин Игорь <mailforshinji@gmail.com>
 *
 * @version 0.9
 * @since 2014-01-06
 *
 * Класс, для использования базы данных
 */
class DB {


    /**
     * Дескриптор подключения к базе
     * @var mixed
     */
    private $db;
    

    /**
     * Класс адаптер для работы с базой данных
     * @var mixed
     */
    private $adapter;


    /**
     * Подключение к базе 
     *
     * @param string $adapter_name  Название адаптера
     * @param string $host          Название хоста для подключения
     * @param string|int $port      Номер порта для подключения
     * @param string $basename      Название базы данных
     * @param string $user          Логин пользователя
     * @param string $pass          Пароль пользователя
     * @param string $charset       Кодировка подключения
     * @param string $time_zone     Временная зона
     *
     * @return DB
     *      Класс работы с базой
     *
     * @throws Helly_DataBase_Exception
     *      Не указан адаптер,
     *      либо не найден файл адаптера,
     *      либо класс адаптер не навледует интэрфейс для адаптеров,
     *      либо ошибка подключения к базе данных
     */
    public function __construct ($adapter_name, $host, $port, $basename, $user, $pass = '', $charset = 'utf8', $time_zone = '') {

        if ( ! $adapter_name) {
            throw new Helly_DataBase_Exception("Not set adapter to connect to the database");
        }

        require_once(__DIR__.'/adapters/'.$adapter_name.'.php');

        $adapterClassName = $adapter_name.'Adapter';
        $this->adapter    = new $adapterClassName();

        if ( ! in_array("IDBAdapter", class_implements($this->adapter))) {
            throw new Helly_DataBase_Exception( "Incorrect adapter implementation '$adapter_name'");
        }

        $db = $this->adapter->connect($host, $port, $basename, $user, $pass, $charset, $time_zone);


        if ( ! $db) {
            throw new Helly_DataBase_Exception("Error connect to database: " . $this->adapter->connectError());
        }

        $this->db = $db;
    }


    /**
     * Вызов метода в дискрипторе подключения
     * 
     * @param string $method_name
     *     Название метода
     * @param array $args
     *     Входные аргументы для метода переданном в $name
     *
     * @return mixed
     *     Значение возвращаемое вызванным методам дискриптора
     *
     * @throws Helly_DataBase_Exception
     *      Запрашиваемый метод не найден
     */
    public function __call ($method_name, $args) {

        if (method_exists($this->db, $method_name)) {
            return call_user_func_array(array($this->db, $method_name), $args);
        
        } else {
            throw new Helly_DataBase_Exception("Method '$method_name' not exists");
        }
    }


    /**
     * Выполняет запрос
     * 
     * @param string $sql 
     *      Строка содержащая запрос к базе
     * @param string|array $bind_params
     *      Параметры входящие в запрос каторые
     *      нужно заэкранирывать
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function query ($sql, $bind_params = '') {

        $stmt = $this->adapter->prepare($this->db, $sql);

        if ($stmt) {
            if ($bind_params) $this->bindValue($stmt, $bind_params);
            $result = $this->adapter->execute($stmt);

            if ($result === false) {
                trigger_error($this->adapter->error($stmt), E_USER_WARNING);
            }

            return $result;
        }

        return false;
    }


    /**
     * Связывает параметр с заданным значением
     *
     * @param mixed $stmt
     *      Экземпляр запроса
     * @param string|array $values
     *      Значение или массив значений
     *      которые нужно привязать к запросу
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    private function bindValue ($stmt, $values) {

        $num_parameter = 1;

        if (is_string($values) || is_numeric($values)) {
            $this->adapter->bindValue($stmt, $num_parameter++, $values);

        } elseif (is_array($values)) {
            foreach ($values as $key=>$value) {
                if (is_string($key)) {
                    $this->adapter->bindValue($stmt, ':' . $key, $value);
                } else {
                    $this->adapter->bindValue($stmt, $num_parameter++, $value);
                }
            }
        }

        return true;
    }


    /**
     * Добавление записи или массива записей
     * в указанную таблицу
     *
     * @param string $table
     *      Название таблицы для добавления в нее данных
     * @param array $data
     *      Данные для добавления в таблицу
     *
     * @return mixed
     */
    public function insert ($table, array $data) {

        if ( ! trim($table) || empty($data)) {
            return false;
        }

        $queries = array();


        // формирование insert запросаов
        foreach ($data as $row) {
            if (is_array($row)) {
                $queries[] = $this->preProcessInsert($table, $row);
            }
        }
        $queries[] = $this->preProcessInsert($table, $data);



        // выполнение запросаов
        $this->beginTransaction();

        $error   = false;
        $id_rows = array();

        foreach ($queries as $query) {
            if ($this->query($query['sql'], $query['params'])) {
                $id_rows[] = $this->adapter->lastInsertId($this->db, $table);
            } else {
                $error = true;
                break;
            }
        }

        if ($error) {
            $this->rollback();
        } else {
            $this->commit();
        }

        return empty($id_rows)
            ? false
            : count($id_rows) > 1
                ? $id_rows
                : current($id_rows);
    }


    /**
     * Формирование insert запроса
     *
     * @param string $table
     *      Название таблицы
     * @param array $data
     *      Массив данных для добавления в таблицу
     *
     * @return array
     *      Массив со сформированным запросам на добавление
     *      и параметрами к нему
     */
    private function preProcessInsert ($table, array $data) {

        $query_data = array();

        $query_data['fields']       = array();
        $query_data['value_fields'] = array();
        $query_data['params']       = array();

        foreach ($data as $name=>$value) {
            if (is_string($value) || is_numeric($value)) {
                $query_data['fields'][]       = $name;
                $query_data['value_fields'][] = ':' . $name;
                $query_data['params'][$name]  = $value;
            }
        }

        $implode_fields       = implode(', ', $query_data['fields']);
        $implode_value_fields = implode(', ', $query_data['value_fields']);

        $query = array(
            'sql'    => "INSERT INTO {$table} ({$implode_fields}) VALUES ({$implode_value_fields})",
            'params' => $query_data['params']
        );

        return $query;
    }


    /**
     * Начало транзакции
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function beginTransaction () {

        return $this->adapter->beginTransaction($this->db);
    }


    /**
     * Откат транзакции
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function rollback () {

        return $this->adapter->rollback($this->db);
    }


    /**
     * Фиксирует транзакцию
     *
     * @return bool
     *      Возвращает TRUE в случае успешного завершения
     *      или FALSE в случае возникновения ошибки.
     */
    public function commit () {

        return $this->adapter->commit($this->db);
    }


    /**
     * Возващает результат запроса со всеми записями
     *
     * @param string $sql
     *      Запрос каторый необходимо выполнить
     * @param string|array $bind_params
     *      Параметры каторые нужно привязать к запросу
     *
     * @return array|bool
     *      Результирующий массив данных,
     *      либо false в случае ошибки
     */
    public function fetchAll ($sql, $bind_params = '') {

        $stmt = $this->adapter->prepare($this->db, $sql);

        if ($stmt) {
            if ($bind_params) $this->bindValue($stmt, $bind_params);
            $result = $this->adapter->execute($stmt);

            if ($result === false) {
                trigger_error($this->adapter->error($stmt), E_USER_WARNING);
                return false;
            }

            return $this->adapter->fetchAll($stmt);
        }

        return false;
    }


    /**
     * Возващает результат запроса с первой строкой
     * из результата запроса
     *
     * @param string $sql
     *      Запрос каторый необходимо выполнить
     * @param string|array $bind_params
     *      Параметры каторые нужно привязать к запросу
     *
     * @return array|bool
     *      Результирующий массив данных,
     *      либо false в случае ошибки
     */
    public function fetchRow ($sql, $bind_params = '') {

        $stmt = $this->adapter->prepare($this->db, $sql);

        if ($stmt) {
            if ($bind_params) $this->bindValue($stmt, $bind_params);
            $result = $this->adapter->execute($stmt);

            if ($result === false) {
                trigger_error($this->adapter->error($stmt), E_USER_WARNING);
                return false;
            }

            return $this->adapter->fetchRow($stmt);
        }

        return false;
    }


    /**
     * Возващает результат запроса с первым столбцом
     * из результата запроса
     *
     * @param string $sql
     *      Запрос который необходимо выполнить
     * @param string|array $bind_params
     *      Параметры каторые нужно привязать к запросу
     *
     * @return array|bool
     *      Результирующий массив данных,
     *      либо false в случае ошибки
     */
    public function fetchCol ($sql, $bind_params = '') {

        $stmt = $this->adapter->prepare($this->db, $sql);

        if ($stmt) {
            if ($bind_params) $this->bindValue($stmt, $bind_params);
            $result = $this->adapter->execute($stmt);

            if ($result === false) {
                trigger_error($this->adapter->error($stmt), E_USER_WARNING);
                return false;
            }

            return $this->adapter->fetchCol($stmt, 1);
        }

        return false;
    }


    /**
     * Возващает результат запроса в виде одномерного массива
     * ключами которого выступает первое поле из запроса, а значениями второе поле
     *
     * @param string $sql
     *      Запрос каторый необходимо выполнить
     * @param string|array $bind_params
     *      Параметры каторые нужно привязать к запросу
     *
     * @return array|bool
     *      Результирующий массив данных,
     *      либо false в случае ошибки
     */
    public function fetchPairs ($sql, $bind_params = '') {

        $stmt = $this->adapter->prepare($this->db, $sql);

        if ($stmt) {
            if ($bind_params) $this->bindValue($stmt, $bind_params);
            $result = $this->adapter->execute($stmt);

            if ($result === false) {
                trigger_error($this->adapter->error($stmt), E_USER_WARNING);
                return false;
            }

            return $this->adapter->fetchPairs($stmt);
        }

        return false;
    }


    /**
     * Возващает резельтат запроса с первым значением поля, из первой строки запроса
     *
     * @param string $sql
     *      Запрос каторый необходимо выполнить
     * @param string|array $bind_params
     *      Параметры каторые нужно привязать к запросу
     *
     * @return string
     *      Первое значение из запроса,
     *      либо false в случае ошибки
     */
    public function fetchOne ($sql, $bind_params = '') {

        $stmt = $this->adapter->prepare($this->db, $sql);

        if ($stmt) {

            if ($bind_params) $this->bindValue($stmt, $bind_params);
            $result = $this->adapter->execute($stmt);

            if ($result === false) {
                trigger_error($this->adapter->error($stmt), E_USER_WARNING);
                return false;
            }

            return $this->adapter->fetchOne($stmt);
        }

        return false;
    }


    /**
     * Возврашение идентифиикатора последней 
     * добаленной записи
     * 
     * @param string $table_name
     *     Название таблицы из которой 
     *     нужно вернуть идентифкатор.
     *     Используется только с базами данных postgre sql
     *
     * @return int
     *      Идентификатор последней добавленной записи
     */
    public function lastInsertId ($table_name = null) {

        return $this->adapter->lastInsertId($this->db, $table_name);
    }


    /**
     * Закрытие соединения с базой
     *
     * @return void
     */
    public function close () {
        
        $this->adapter->close($this->db);
    }
}

