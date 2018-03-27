<?php

/**
 * Description of db
 *
 * @author lwo_putickij_iv
 */

class EntrantsDB extends PDO {

    // экземпляр класса. Инициализируем пустым значением
    private static $instance = null;
    // Объявляем переменную для хранения экземпляров класса PDO
    private $con = null; 
    
    //Константы для подключения к базе данных
    const DSN = "mysql:host=localhost;dbname=entrants;charset=utf8";
    const USER = "putickij";
    const PASS = "igorluda";

//Этот метод д.б. статическим и возвращать экземпляр объекта, если оюъект
 //еще не существует.
    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

 // Устраняем возможность дублирования объектов
    public function __clone() {
//        trigger_error('Клогирование запрещено', E_USER_ERROR);
        throw new RuntimeException("Клонирование запрещено.", 101);
    }

//Выполняем соединение с БД
    public function __construct() {
        try {
            $this->con = new PDO(self::DSN, self::USER, self::PASS, array(
                                        PDO::ATTR_PERSISTENT => true,
                                        PDO::MYSQL_ATTR_INIT_COMMAND =>
                                            "SET CHARACTER SET 'utf8'",
                                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                                    ));
        } catch (PDOException $e) {
            print "Ошибка соединения!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    //Функция получения текста поиска для запроса.
    private function searchText($search, $listOfFields) {
        if ($search != ''){
            $search = '%' . $search . '%';
            $searchText = "WHERE concat(" . $listOfFields . ") LIKE '" . $search . "'";
        } else $searchText = '';
        return $searchText;

    }
    //Кол-во записей в таблице абитуриентов
    public function get_rows($search, $listOfFields) {
        //Формирование текста поискового фильтра
        $searchText = $this->searchText($search, $listOfFields);
        $stmt = $this->con->query("SELECT * FROM list ". $searchText);
        return $stmt->rowCount();
    }
    
    // Список абитуриентов для таблицы
    public function get_list($recordsOnPage, $offset, $order, $direction, $search, $listOfFields) {
        //Формирование текста поискового фильтра
        $searchText = $this->searchText($search, $listOfFields);
        $stmt = $this->con->query("SELECT * FROM list " . $searchText . " ORDER BY " . $order .  
        " " . $direction . " LIMIT " . $recordsOnPage . " OFFSET " . $offset);
        return $stmt;
    }
 
    //Проверка существования email
    public function check_email($email) {
        $query = "SELECT 1 FROM list WHERE e_mail = :e_mail_bp"; //Текст запроса
        $stmt = $this->con->prepare($query); //Подготавливаем запрос
        $stmt->bindParam(":e_mail_bp", $email, PDO::PARAM_STR); //Подставляем значение параметра
        $stmt->execute(); //Выполняем запрос
        $row = $stmt->fetch(PDO::FETCH_ASSOC); //Получаем массив с данными запроса

        //Если массив создан, значит e-mail найден, проверка на уникальность e-maik не прошла. Возвращаем FALSE
        if ($row) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    //Новый абитуриент
    public function create_entrant ($name, $surname, $sex, $group, $email, $balls, 
            $yearOfBirth, $resident, $codeword){
        $nameSql = $this->con->quote($name);
        $surnameSql = $this->con->quote($surname);
        $sexSql = $this->con->quote($sex);
        $groupSql = $this->con->quote($group);
        $emailSql = $this->con->quote($email);
        $ballsSql = $this->con->quote($balls);
        $yearOfBirthSql = $this->con->quote($yearOfBirth);
        $residentSql = $this->con->quote($resident);
        $codewordSql = $this->con->quote($codeword);
        $this->con->query("INSERT INTO list (name, surname, sex, group_numb, e_mail, total_scores, yearOfBirth, 
                resident, codeword) VALUES (" 
                . $nameSql . ", " . $surnameSql . ", " . $sexSql . ", " . $groupSql . ", " . $emailSql . ", " . $ballsSql . 
                ", " . $yearOfBirthSql . ", " . $residentSql . ", " . $codewordSql . ")");
        
    }

//Проверка, что комбинация "e-mail- кодовое слово" существует
    public function verify_entrant_credentials($email, $codeword)
    {
        $query = "
            SELECT *
            from list
            where e_mail = ?
            and	codeword = ?
           ";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->bindParam(2, $codeword, PDO::PARAM_STR);
        $stmt->execute();

        //Получаем массив.
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        /*
         * Если комбинация "e-mail- кодовое слово" найдена, массив будет содержать данные абитуриента
         *а также добавляем ключ 'check' со значением true, т.е. клиент найден.
         * В противном случае массив содержит только ключ 'check' со значением false.
         */
        if ($row) {
            $row['check'] = true;
        } else {
            $row['check'] = false;
        }
        return $row;
    }

    //Обновление записи
    public function update_entrant($name, $surname, $sex, $group, $email, $balls, 
            $yearOfBirth, $resident, $codeword, $oldEmail){
        $nameSql = $this->con->quote($name);
        $surnameSql = $this->con->quote($surname);
        $sexSql = $this->con->quote($sex);
        $groupSql = $this->con->quote($group);
        $emailSql = $this->con->quote($email);
        $ballsSql = $this->con->quote($balls);
        $yearOfBirthSql = $this->con->quote($yearOfBirth);
        $residentSql = $this->con->quote($resident);
        $codewordSql = $this->con->quote($codeword);
        $oldEmailSql = $this->con->quote($oldEmail);
        $this->con->query("UPDATE list SET name= " . $nameSql . ", surname=" . $surnameSql . 
                ", sex=" . $sexSql . ", group_numb=" . $groupSql . ", e_mail=" . $emailSql . 
                ", total_scores=" . $ballsSql . ", yearOfBirth= " . $yearOfBirthSql . 
                ", resident=" . $residentSql . ", codeword=" . $codewordSql . 
                " WHERE e_mail = " . $oldEmailSql);
    }    
    
    //Удаление записи
    public function delete_entrant($email)
    {
        $query = "
            DELETE FROM list
            WHERE e_mail = :email_bv
            ";

        $stmt = $this->con->prepare($query);
        $stmt->bindValue(":email_bv", $email, PDO::PARAM_STR);
        $stmt->execute();
    }
    
}
