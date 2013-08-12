<?php
/**
 * @author Шабунин Игорь <mailforshinji@gmail.com>
 *
 * @version 0.1
 * @since 2013-07-10
 */

require_once('exceptions.php');


/**
 *  Класс, для использования базы данных
 */
class DataBase {


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
     * @param string $adapter_name
     *      Название адаптера
     * @param string $host
     *      Название хоста для подключения
     * @param string $basename
     *      Название базы данных
     * @param string $user
     *      Логин пользователя
     * @param string $pass
     *      Пароль пользователя
     * @return DataBase
     *      Класс работы с базой
     * @throws NotSetAdapterDataBaseException
     *      Не указан адаптер
     * @throws FileNotExistsDataBaseException
     *      Не найден файл адаптера
     * @throws IncorrectAdapterImplementationDataBaseException
     *      Класс адаптер не навледует интэрфейс для адаптеров
     * @throws ErrorConnectDataBaseException
     *      Ошибка подключения к базе данных
     */
    function __construct ($adapter_name, $host, $basename, $user, $pass = '') {
        
        if ( ! $adapter_name) {
            throw new NotSetAdapterDataBaseException();
        }

        $adapter_file = __DIR__.'/adapters/'.$adapter_name.'.php';
        if ( ! file_exists($adapter_file)) {
            throw new FileNotExistsDataBaseException($adapter_file);
        }

        require_once($adapter_file);
        
        $adapterClassName = $adapter_name.'Adapter';
        $this->adapter    = new $adapterClassName();

        if ( ! in_array("IDataBaseAdapter", class_implements($this->adapter))) {
            throw new IncorrectAdapterImplementationDataBaseException($adapter_name);
        }

        $DBLink = $this->adapter->connect($host, $basename, $user, $pass);  


        if ( ! $DBLink) {
            throw new ErrorConnectDataBaseException($this->adapter->connect_error(), $this->adapter->connect_errno());
        }

        $this->db = $DBLink;
    }


    /**
     * Вызов метода в дискрипторе подключения
     * 
     * @param string $method_name
     *     Название метода
     * @param array $args
     *     Входные аргументы для метода переданном в $name
     * @return mixed
     *     Значение возвращаемое вызванным методам дискриптора
     * @throws MethodNotExistsDataBaseException
     *      Запрашиваемый метод не найден
     */
    public function __call ($method_name, $args) {

        if (method_exists($this->db, $method_name)) {
            return call_user_func_array(array($this->db, $method_name), $args);
        
        } else {
            throw new MethodNotExistsDataBaseException($method_name);
        }
    }


    /**
     * Экранирование переменной
     * 
     * @param string|array $escape
     *        Строкавая массив строк переменная каторую 
     *        нежно заэкранирывать
     * @return string|array
     *        Возвращает экранирыванную строку 
     *        или массив строк
     */
    public function escape ($escape) {

        if (is_string($escape)) {
            $escape = $this->adapter->escapeString($this->db, $escape);

        } elseif (is_array($escape)) {
            foreach ($escape as $key=>$value) {
                $escape[$key] = $this->adapter->escapeString($this->db, $escape[$key]);
            }
        }

        return $escape;
    }


    /**
     * Выполняет запрос
     * 
     * @param string $sql 
     *        Строка содержащая запрос к базе
     * @param string|array $escape_params
     *        Параметры входящие в запрос каторые 
     *        нужно заэкранирывать
     * @return resource
     *        Содердит результат запроса
     */
    public function query ($sql, $escape_params = '') {
        
        if ($escape_params) {
            $sql = $this->escapeQuery($sql, $escape_params);
        }

        $result = $this->adapter->query($this->db, $sql);

        if ($result === false) {
            $error = $this->adapter->error($this->db);
            
            if ($error) {
                echo $error;
            }
        }

        return $result;
    }


    /**
     * Экранирование переменных в строке запроса
     * 
     * @param string $sql 
     *      Строка содержащая запрос к базе
     * @param string|array $escape_params
     *      Параметры входящие в запрос каторые
     *      нужно заэкранирывать
     * @return string
     *      Экранированаая строка
     */
    public function escapeQuery ($sql, $escape_params) {
        
        if (is_string($escape_params) || is_numeric($escape_params)) {
            $escape_params = $this->adapter->escapeString($this->db, $escape_params);
            $sql           = str_replace('?', $escape_params, $sql);

        } elseif (is_array($escape_params)) {
            foreach ($escape_params as $key=>$value) {
                $value = $this->adapter->escapeString($this->db, $value);
                $sql   = str_replace(':'.$key, $value, $sql);
            }
        }

        return $sql;
    }


    /**
     * Возващает резельтат запроса со всеми записями
     * 
     * @param string $sql
     *      Строка запроса каторый
     *      нелбходимо выполнить
     * @param string $escape_params
     *      Параметры входящие в запрос каторые
     *      нужно заэкранирывать
     * @return array|bool
     *      Результирующий массив данных
     *      Либо false или true если не запрашивались данные
     */
    public function fetchAll ($sql, $escape_params = '') {
        
        if ($escape_params) {
             $sql = $this->escapeQuery($sql, $escape_params);
        }

        $result = $this->adapter->query($this->db, $sql);
        
        if ($result === false) {
            if (@DEBUG_SERVER) {
                $this->printLastError();
            }
            
            return false;
        }
        
        $rows = $this->adapter->fetchAll($result);

        return $rows;
    }


    /**
     * Возващает резельтат запроса с первой строкой 
     * из результата запроса
     * 
     * @param string $sql
     *      Строка запроса каторый
     *      нелбходимо выполнить
     * @param string|array $escape_params
     *      Параметры входящие в запрос каторые
     *      нужно заэкранирывать
     * @return array|bool
     *      Результирующий массив данных
     *      Либо false или true если не запрашивались данные
     */
    public function fetchRow ($sql, $escape_params = '') {

        if ($escape_params) {
            $sql = $this->escapeQuery($sql, $escape_params);
        }


        $result = $this->adapter->query($this->db, $sql);
        if ($result === false) {
            if (@DEBUG_SERVER) {
                $this->printLastError();
            }

            return false;
        }
        
        $row = $this->adapter->fetchRow($result);
        return $row;
    }


    /**
     * Возващает резельтат запроса с первым значением поля 
     * из первой строки запроса
     * 
     * @param string $sql
     *        Строка запроса каторый 
     *        нелбходимо выполнить
     * @param string|array $escape_params
     *        Параметры входящие в запрос каторые 
     *        нужно заэкранирывать
     * @return string|bool
     *      Результирующая строка данных
     *      Либо false или true если не запрашивались данные
     */
    public function fetchOne ($sql, $escape_params = '') {

        if ($escape_params) {
            $sql = $this->escapeQuery($sql, $escape_params);
        }

        $result = $this->adapter->query($this->db, $sql);

        if ($result === false) {
            if (@DEBUG_SERVER) {
                $this->printLastError();
            }
            
            return false;
        }
        
        $one = $this->adapter->fetchOne($result);
        return $one;
    }


    /**
     * Освобождение памяти после использования запроса
     * 
     * @param resource $result
     *        Результат запроса
     */
    public function free ($result) {

        if ($result) {
            $this->adapter->free($result);
        }
    } 


    /**
     * Возврашение идентифиикатора последней 
     * добаленной записи
     * 
     * @param string $table_name
     *     Название таблицы из которой 
     *     нужно вернуть идентифкатор.
     *     Используется только с базами данных postgre sql
     * @return int
     *      Идентификатор последней добавленной записи
     */
    public function getLastId ($table_name = null) {

        return $this->adapter->getLastId($this->db, $table_name);
    }


    /**
     * Рспеатка последней ошибки
     * 
     * @return void
     */
    public function printLastError () {
        $error = $this->adapter->error($this->db);
                
        if ($error) {
            print($error);
        }
    }


    /**
     * Закрытие соединения с базой
     */
    public function closeConnect () {
        
        $this->adapter->closeConnect($this->db); 
    }
}


?>
