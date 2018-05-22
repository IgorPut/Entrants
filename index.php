<!DOCTYPE html>
<?php
/*
 * Подключаем и выполняем необходимые файлы    
 */
    require_once("Includes/db.php"); //Класс подключения и выполнения запросов к базе данных
    require_once("Includes/global.php"); //Глобальные константы
    require_once("Includes/table.php"); //Класс описания таблицы
    
// Устраняем запрос на повторную отправку формы при возврате на страницу из формы редактирования
    session_cache_limiter('nocashe'); 
    
//Стартуем сессию
    session_start();
    
    if (array_key_exists('editForm', $_SESSION))
        unset ($_SESSION['editForm']);
    
/*
 * Объявление переменных и констант
 */
    
//Переменные для обработки формы регистрации
    $logonSuccess = TRUE; //Проверка данных регистрации.
    $emailIsEmpty = false; //Флаг, пустой ли e-mail в popup форме.
    $emailIsFalse = FALSE; //Флаг корректности e-mail в popup форме.
    $codewordIsEmpty = false; //Флаг, пустое ли "кодовое слово" в popup форме.
    $email=''; //Переменная для поля e-mail в popup форме
    
/*
 * Проверка учетных данных абитуриента, введенных в popup форму
 * В случае успеха - переход к форме редактирования данных (editForm.php)
 */
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $hiddenPopup = '';
        
    /** Проверка, заполнено ли поле "email" в форме */
        $classEmail = '';
        if ($_POST["email"]=="") {
            $emailIsEmpty = true;
            $classEmail = CLASS_BAD;
        } else {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailIsFalse = TRUE;
                $classEmail = CLASS_BAD;
            }
        } 
        
    /** Проверка, заполнено ли поле "Кодовое слово" в форме */
       $classCodeword = '';
        if ($_POST['codeword']=='') { 
            $codewordIsEmpty = true;
            $classCodeword = CLASS_BAD;
        }
        else 
            $codeword = filter_input(INPUT_POST, 'codeword', FILTER_SANITIZE_STRING);
        
    /** 
     * Если поля ввода не пустые и e-mail корректный, ищем абитуриента с введенными данными в базе данных. 
     * Функция verify_entrant_credentials($email, $codeword) при успешном поиске возвращает все данные по абитуриенту
     * в виде массива "поле БД"=>"значение" и дополнительный ключ массива 'check' со значением 'true'.
     * Если поиск неуспешен, возвращаемый массив состоит из одного элемента: ключа 'check' со значением 'false'.
     * Возвращаемый массив присваивается глобальной переменной $_SESSION['entrant'].
     * В случае успешного поиска, обновляются значения кук и выполняется переход к форме редактирования editForm.php.
     * Глобальной переменной $_SESSION['editForm'] присваивается значение 'EDIT',
     * т.е. устанавливается флаг, что форма editForm.php будет открыта в режиме редактирования
     */
        if ((!$emailIsEmpty) and (!$emailIsFalse) and (!$codewordIsEmpty)) {
            //Сохраняем в сессии данные пользователя.
            $_SESSION['entrant'] = EntrantsDB::getInstance()->
                verify_entrant_credentials($email, $codeword);
            $logonSuccess = $_SESSION['entrant']['check'];
            if ($logonSuccess == true) {
                $_SESSION['editForm'] = EDIT; //Режим редактирования данных абитуриента 
                //Сохраняем имя и email пользователя в куках после успешной регистрации
                setcookie("email", $_SESSION['entrant']['e_mail'], time()+3600*24*365*10);
                setcookie("codeword", $_SESSION['entrant']['codeword'], time()+3600*24*365*10);
                header('Location: editForm.php');//Переход к форме редактирования
                exit;
            } else unset($_SESSION['entrant']);//Удаляем данные пользователя, если неуспешная регистрация
        }
    } else $hiddenPopup = CLASS_HIDDEN; //Скрываем popup форму
/* Конец POST*/
    
    
/*
 * Проверка по сессии и кукам данных регистрации
 * Сначала проверяется есть ли данные абитуриента в сессии (имя). 
 * Если есть - формируем строку приветствия.
 * Если нету, проверяется существование куков емэйл и кодового слова. 
 * Если данные регистрации в куках найдены проверяем, есть ли абитуриент с такими данными 
 * (функция verify_entrant_credentials($email, $codeword).
 * Если такой абитуриент есть - формируется строка приветствия
 * в противном случае данная строка приветствия скрыта.
 * При успешном поиске данных регистрации также отображаем <div> с сылкой на редактирование
 * своих данных (переменная $hiddenEdit, задающая класс для этого <div>).
 */
    $hello1 = ''; //Строка с отображением данных пользователя. До проверки данных регистрации она пустая. 
                  //Если данные регистрации не будут найдены, она останется пустой
                
    $hello2 = "Приветствуем Вас на сайте абитуриентов!";
    if ((array_key_exists('entrant', $_SESSION)) and (array_key_exists('name', $_SESSION['entrant']))){
        $hello1 = "Добро пожаловать, " . $_SESSION['entrant']['name'] . ". ";
        $hiddenCorrect = '';//Отображаем строку со ссылкой на редактирование своих данных
    }
    elseif ((array_key_exists('email', $_COOKIE)) and (array_key_exists('codeword', $_COOKIE))){
        $_SESSION['entrant'] = EntrantsDB::getInstance()->
            verify_entrant_credentials(filter_input(INPUT_COOKIE, 'email', FILTER_SANITIZE_EMAIL), 
                    filter_input(INPUT_COOKIE, 'codeword', FILTER_SANITIZE_STRING));
        $entrantFound = $_SESSION['entrant']['check'];
        if ($entrantFound == true) {
            $hello1 = "Добро пожаловать, " . $_SESSION['entrant']['name'] . ". ";
            $hiddenCorrect = '';//Отображаем строку со ссылкой на редактирование своих данных
        } else $hiddenCorrect = CLASS_DISPLAYNONE;//Скрываем строку со ссылкой на редактирование своих данных
    } else $hiddenCorrect = CLASS_DISPLAYNONE;//Скрываем строку со ссылкой на редактирование своих данных
 
/*
 * Обработка ссылкок добавления абитуриента (ссылка "Добавьте себя) и 
 * редактирования своих данных (ссылка "Исправьте")
 */
    if (array_key_exists('add', $_GET)) {
        $_SESSION['editForm'] = CREATE;//Устанавливается флаг добавления абитуриента
        header('Location: editForm.php');//Переход к форме редактирования
        exit;
    }
    if (array_key_exists('correct', $_GET)) {
        //Проверяем данные регистрации
        $_SESSION['entrant'] = EntrantsDB::getInstance()->
            verify_entrant_credentials($_SESSION['entrant']['e_mail'], $_SESSION['entrant']['codeword']);
        $entrantFound = $_SESSION['entrant']['check'];
        if ($entrantFound == true) {
            $_SESSION['editForm'] = EDIT; //Режим редактирования данных абитуриента 
            header('Location: editForm.php');//Переход к форме редактирования
            exit;
        } else {
            $hiddenPopup = ''; //Отображается форма регистрации 
            if (array_key_exists('email', $_COOKIE))
                $email = filter_input(INPUT_COOKIE, 'email', FILTER_SANITIZE_EMAIL);
        }
    }

/*
 * Обработка нажатия кнопки Изменить в таблице. По нажатию открывается форма регистрации
 */
    if (array_key_exists('editEntrant', $_GET)) {
        $hiddenPopup = ''; //Форма отображается
        $email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
    }
    
 /*
 * СОРТИРОВКА
 */
    /* 
     * При первом старте сесси устанавливаем начальный ключ и направление сортировки
     * из объекта $list, созданного в table.php
    */
    if (!array_key_exists('order', $_SESSION)) {
            $_SESSION['order'] = $list->orderFirst; //поле сортировки
            $_SESSION['direction'] = $list->directionFirst; //Направление
    }

    /*
     * Обработка ссылок сортировки таблицы (клик по заголовку столбца)
    */
    if (array_key_exists('orderBy', $_GET)) {
        $orderBy = filter_input(INPUT_GET, 'orderBy', FILTER_SANITIZE_STRING);
        //Если повторный клик по тому же заголовку
        if ($_SESSION['order'] == $orderBy)
            $_SESSION['direction'] = ($_SESSION['direction'] === $list::DESC) ? $list::ASC : $list::DESC;
        else {
            $_SESSION['order'] = $orderBy;
            $_SESSION['direction'] = $list->fields[$orderBy]['direction'];
        }
    }

/*
 * Поиск
 */
    //В $_SESSION создаем переменную search для, в кот. будет хранится значение поиска.
    //Первоначальное значение зададим пустое
    if (!array_key_exists('search', $_SESSION)) 
            $_SESSION['search'] = '';
    //Нажатие кнопки Найти
    if (array_key_exists('searchEntrant', $_GET) and array_key_exists('search', $_GET)) {
        $_SESSION['search'] = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
        unset($_SESSION['page']);
    }
    //Нажатие ссылки "Показать всех"
    if (array_key_exists('showAll', $_GET))
        $_SESSION['search'] = '';

    //В переменной $hiddenShowAll храним класс, отвечающий за отображение ссылки "Показать всех"
    //Если значение поиска пустое - ссылка отображается (значение класса не задано)
    $hiddenShowAll = ($_SESSION['search'] === '') ? CLASS_DISPLAYNONE : '';

/*
 * Обработка ссылок номера страницы.
 * В глобальную переменную $_SESSION['page'] заносим номер нажатой страницы
 * Затем рассчитываем смещение, т.е. с какой порядковой записи выводим данные (переменная $_SESSION['offset'])
 */    
    if (array_key_exists('page', $_GET)) {
        $_SESSION['page'] = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT); //Номер нажатой страницы
        if (!$_SESSION['page']) $_SESSION['page'] = 1;
        $_SESSION['offset'] = $_SESSION['page'] * $list->RECORDS_ONPAGE - $list->RECORDS_ONPAGE; //Смещение
    }
    
/*
 * При первом старте сесси, или после нажатия кнопки Найти (т.е. отсутствует $_SESSION['page']), 
 * текущей будет страница 1, с начальным смещением 0.    
 */
    if (!array_key_exists('page', $_SESSION)) {
            $_SESSION['page'] = 1; //№ текущей страницы
            $_SESSION['offset'] = 0; //Смещение в таблице
    }

/*
 * Подготавливаем таблицу к выводу с учетом сортировки, данных поиска, номера страницы
 */
    /*
     * Получение списка отображаемых полей в строковом виде через запятую. 
     *Для подстановки в sql запросы.
     */
    $listOfFields = ''; //Список полей
    $i=1;
    foreach ($list->fields as $key => $value) {
        $listOfFields .= ($i === 1) ? $key : ', ' . $key;
        $i++;
    }
    
    //Подсчет кол-ва записей в таблице с учетом поиска
    $numRecords = EntrantsDB::getInstance()->get_rows($_SESSION['search'], $listOfFields); 
    
    //Подсчет кол-ва страниц таблицы. Столько будет и ссылок на переход к страницам
    $numPages = ceil($numRecords/$list->RECORDS_ONPAGE);

/*
 * Массив для вывода списка абитуриентов. В фуенцию передаются: кол-во записей на страницу,
 * смещение, ключ сортировки, направление сортировки, значение для поиска и список полей.
 */
    $entrants = EntrantsDB::getInstance()->get_list($list->RECORDS_ONPAGE,
            $_SESSION['offset'], $_SESSION['order'], $_SESSION['direction'],
            $_SESSION['search'], $listOfFields);
    
//include('indexhtml.php');
?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Список абитуриентов</title>
        <link href="src/entrants.css" type="text/css" rel="stylesheet" media="all" />
    </head>
    <body>
        <header>
            <h1>Список абитуриентов</h1>
            <div class="hello"><?= $hello1 . $hello2 ?></div>
            <div class="<?= $hiddenCorrect ?>">Ваши данные не точны? 
                <a href="?correct">Исправьте!</a>
            </div>
            <div>
                Вас еще нету в списке? <a href="?add">Добавьте себя!</a>
            </div>
        </header>

        <section>
            <!--Форма поиска-->
            <form action="index.php" method="GET">
                <span style="margin-left: 250px">Поиск абитуриента: </span>
                <input type="text" name="search" value="" />
                <input type="submit" name="searchEntrant" value="Найти">
                <div class="<?= $hiddenShowAll ?>">
                    Показаны абитуриенты, найденные по запросу <q style="color:red"><?= $_SESSION['search'] ?></q>
                    <a href="?showAll">Показать всех</a>
                </div>
            </form>
            <table>
                <!--Выводим шапку таблицы-->
                <tr>
                <?php
                    foreach ($list->fields as $key => $value):
                        //Если поле не является ключом сортировки - выводим его наименование как есть
                        if ($key != $_SESSION['order']): ?>
                    <th><a href="?orderBy=<?= $key ?>"><?= $value['caption'] ?></a></th>
                        <!--В противном случае добавляем к наименованию символ направления сортировки-->
                <?php   elseif ($_SESSION['direction'] == $list::DESC): ?>
                            <th><a href="?orderBy=<?= $key ?>"><?= $value['caption'] ?> 
                                    <span class="green"> &or;</span></a></th>
                <?php   else: ?>
                            <th><a href="?orderBy=<?= $key ?>"><?= $value['caption'] ?> 
                                    <span class="green"> &and;</span></a></th>
                <?php
                        endif;
                    endforeach;
                ?>
                </tr>
                <!--Выводим информационные строки-->
                <?php 
                    while ($row = $entrants->fetch()): ?>
                        <tr>
                <?php   foreach ($list->fields as $key => $value): ?>
                            <td class="<?= $list->fields[$key]['align'] ?>"> <?= htmlentities($row[$key]) ?> </td>
                <?php   endforeach; ?>
                            <td>
                                <form name="editEntrant" action="index.php" method="GET">
                                    <input type="hidden" name="email" value="<?= htmlentities($row["e_mail"]) ?>">
                                    <input type="submit" name="editEntrant" value="Изменить">
                                </form>
                            </td>
                        </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <footer>
            <!--Отображение ссылок на номера страниц. Кол-во ссылок равно кол-ву страниц-->        
            <span>СТРАНИЦА: </span>
            <?php 
                for ($i=1; $i<=$numPages; $i++):
                    if ($i == $_SESSION['page']): ?>            
                        <a href="?page=<?= $i ?>">[<?= $i ?>]</a>
            <?php   else: ?>            
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php            
                    endif;   
                endfor;
            ?>
        </footer>
        
        <!--Popup окно-->
        <div class="popup <?= $hiddenPopup; ?>"  >
            <div>
                <form name="login" action="index.php" method="POST">
                    <p>
                        <label for="email">Ваш e-mail:</label>
                        <input class="<?= $classEmail ?>" id="email" type="text" name="email" value="<?= $email ?>"/>
                    <?php   if ($emailIsEmpty): ?>
                                <span class="error">Пожалуйста, введите Ваш e-mail!</span>
                    <?php   endif;
                            if ($emailIsFalse): ?>
                                <span class="error">Некорректный e-mail!</span>
                    <?php   endif; ?>
                    </p>
                    <p>
                        <label for="codeWord">Кодовое слово: </label>
                        <input class="<?= $classCodeword ?>" id="codeWord" type="password" name="codeword" value="" />
                    <?php   if ($codewordIsEmpty): ?>
                                <span class="error">Не введено кодовое слово!</span>
                    <?php   endif; ?>
                    </p>
                    <p>
                        <input type="submit" value="Перейти к редактированию"/>
                    <?php   if (!$logonSuccess): ?>
                                <span class="error">Абитуриента с такой комбинацией "e-mail
                                    - Кодовое слово" не найдено.</span>
                    <?php   endif; ?>
                    </p>
                </form>
                <form action="" method="get">
                    <button>Отмена</button>
                </form>                
            </div>
        </div>
    </body>
</html>


