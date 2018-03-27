<!DOCTYPE html>
<?php
    require_once("Includes/db.php");
    require_once("Includes/global.php");//Глобальные константы
    require_once("Includes/entrant.php");//Класс свойств и методов абитуриента

/*
 * Стартуем сессию
 */
    session_start();
    
/*
 * При попытке запуска editForm.php из адресной строки - возвращаемся на начальную страницу
 */
    if (!array_key_exists("editForm", $_SESSION)){
        header('Location: index.php');
        exit;
    }
    
/*
 * Объявляем массив для хранения данных абитуриента. 
 * Содержит данные из базы при открытии формы в режиме редактирования,
 * заполняется значениями по-умолчанию пр открытии формы в режиме добавления.
 * При попытке сохранения даннных формы заполняется значениями из ее поляей.
 */
    $entrant =[]; 
    
    //Константы    
    const RESIDENT = 'Местный';
    const NO_RESIDENT = "Иногородний";
    
/* 
 * Объявляем переменные, используемые для анализа правильности заполнения формы
 * Начальное состояние всех флагов соответствует корректным данным,
 * чтобы первоначально форма выводилась без сообщений об ошибках
 */
    $surnameIsValid = TRUE; //Флаг корректности фамилии
    $emailIsUnique = TRUE; //Флаг уникальности e-mail
    $emailIsFalse = FALSE; //Флаг корректности e-mail
    $codewordIsValid = TRUE; //Флаг совпадения паролей
    $scoresIsValid = TRUE; //Флаг корректности набранных баллов
        
    $saved = FALSE; //Признак успешного выполнения сохранения данных
    
    if ($_SESSION['editForm'] == EDIT) {
        $hello = $_SESSION['entrant']['name'] . ", Вы можете откорректировать свои данные.";
    }
    else {
        $hello = "Здравствуйте, новый абитуриент! Введите свои данные в форму.";
    }
/** Обработка POST. */
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        //Обработка нажатия кнопки "Удалить"
        if (array_key_exists("delete", $_POST)) {
            //Находим по e-mail и удаляем запись в таблице 
            EntrantsDB::getInstance()->delete_entrant($_SESSION['entrant']['e_mail']);
            //Ликвидируем глобальную переменную с данными удаленного абитуриента
            unset($_SESSION['entrant']);
            //Возвращаемся на главную страницу
            header('Location: index.php' ); 
            exit;
        }else
        {
            //Сохраняем введенные значения в массиве $entrant[]
            $entrant = [
                'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
                'surname' => filter_input(INPUT_POST, 'surname', FILTER_SANITIZE_STRING),
                'sex' => filter_input(INPUT_POST, 'sex', FILTER_SANITIZE_STRING),
                'group' => filter_input(INPUT_POST, 'group', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                'balls' => filter_input(INPUT_POST, 'balls', FILTER_SANITIZE_NUMBER_INT),
                'yearOfBirth' => filter_input(INPUT_POST, 'yearOfBirth', FILTER_SANITIZE_NUMBER_INT),
                'code' => filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING),
                'code2' => filter_input(INPUT_POST, 'code2', FILTER_SANITIZE_STRING),
            ];
            if ($_POST['resident'] == 1) $entrant['resident'] = RESIDENT;
            else $entrant['resident'] = NO_RESIDENT;

            //Проверка, что длина фамилии не превышает 100 символов
            $lengthSurname = mb_strlen($entrant['surname']);
            $surnameIsValid = Entrant::getInstance()->checkLengthSurname($lengthSurname);//Принимает значения True или False
            
            //Проверка корректности и уникальности e-mail        
            if (!filter_var($entrant['email'], FILTER_VALIDATE_EMAIL)) {
                $emailIsFalse = TRUE;
            //Уникальность проверяется в случае редактирования формы при изменении e-mal или в случае добавления новой записи
            } elseif ((($_SESSION['editForm'] == EDIT) and ($entrant['email'] != $_SESSION['entrant']['e_mail']))
                or ($_SESSION['editForm'] == CREATE)) {
                    $emailIsUnique = EntrantsDB::getInstance()->check_email($entrant['email']);
                }

            //Проверка корректности набранных баллов. Принимает значения TRUE или FALSE
            $scoresIsValid = Entrant::getInstance()->checkScores($entrant['balls']);
            
            //Проверка совпадения паролей
            if ($entrant["code"]!=$entrant["code2"]) {
                $codewordIsValid = false;
            }

        /*
         * Проверяем, все ли флаги корректны.
         * Если данные корректны выполняем изменение или добавление записи, 
         * в зависимости от флага $_SESSION['editForm']
         */
            if (!$emailIsFalse && $emailIsUnique && $codewordIsValid && $scoresIsValid && $surnameIsValid) {
                //Обновление данных или добавление данных
                if ($_SESSION['editForm'] == EDIT) {
                    EntrantsDB::getInstance()->update_entrant($entrant['name'], $entrant['surname'], $entrant['sex'], 
                        $entrant['group'], $entrant['email'], $entrant['balls'], $entrant['yearOfBirth'],
                            $entrant['resident'], $entrant['code'], $_SESSION['entrant']['e_mail']);
                } else {
                    EntrantsDB::getInstance()->create_entrant($entrant['name'], $entrant['surname'], $entrant['sex'], 
                        $entrant['group'], $entrant['email'], $entrant['balls'], $entrant['yearOfBirth'],
                            $entrant['resident'], $entrant['code']);
                }
                //Сохраняем в сессии данные пользователя
                $_SESSION['entrant'] = (EntrantsDB::getInstance()->
                    verify_entrant_credentials($entrant['email'], $entrant['code']));
                //Если форма открыта в режиме добавления, меняем его на режим редактирования
                if ($_SESSION['editForm'] == CREATE){
                    $hello = $_SESSION['entrant']['name'] . ", Вы успешно добавили себя в список. "
                            . "Можете откорректировать свои данные.";
                    $_SESSION['editForm'] = EDIT;
                }
                //Сохраняем в куках данные пользователя
                setcookie("email", $entrant['email'], time()+3600*24*365*10);
                setcookie("codeword", $entrant['code'], time()+3600*24*365*10);
                //Устанавливаем флаг успешного сохранения. Как следствие - появляется мигающая надпись "Сохранено"
                $saved = TRUE;
                $hello = $_SESSION['entrant']['name'] . ", Вы можете откорректировать свои данные.";
            }
        }
    }
    /* Конец POST*/   
    /* Если не POST*/
    else {
        /* Если режим редактирования*/    
        if ($_SESSION['editForm'] == EDIT) {
            $entrant = [
                'name' => $_SESSION['entrant']['name'],     'surname' => $_SESSION['entrant']['surname'],
                'sex' => $_SESSION['entrant']['sex'],       'group' => $_SESSION['entrant']['group_numb'],
                'email' => $_SESSION['entrant']['e_mail'],  'balls' => $_SESSION['entrant']['total_scores'],
                'yearOfBirth' => $_SESSION['entrant']['yearOfBirth'],  'resident' => $_SESSION['entrant']['resident'],
                'code' => $_SESSION['entrant']['codeword'], 'code2' => $_SESSION['entrant']['codeword'],
            ];
        }
    /*Если режим добавления*/
        else {
            unset($_SESSION['entrant']);
            $entrant = [
                'name' => NULL,     'surname' => NULL,
                'sex' => 'Ж',       'group' => NULL,
                'email' => NULL,    'balls' => NULL,
                'yearOfBirth' => date("Y") - Entrant::getInstance()->averageAge,
                'resident' => NO_RESIDENT,
                'code' => NULL,     'code2' => NULL,
            ];
        }
    } 
?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Редактирование данных</title>
        <link href="src/entrants.css" type="text/css" rel="stylesheet" media="all" />
    </head>
    <body>
        <header>
            <div class="hello"><?php echo $hello;?></div>
            <a href="index.php">(Вернуться к списку)</a>
        </header>
        <div class="form">
            <form action="editForm.php" method="POST">
            <?php if ($saved): ?>
                <img src="images/saved.png" class="save" alt=""/>
            <?php endif; ?>

                <!--Имя-->
                <p>
                    <label>Ваше имя: </label>
                    <input type="text" name='name' value="<?= $entrant["name"] ?>" required/>
                </p>

                <!--Фамилия-->
            <?php if (!$surnameIsValid): ?>
                <p>
                    <label>Фамилия (максимум <?= Entrant::getInstance()->maxLengthSurname ?> символов): </label>
                    <input type="text" name="surname" class="bad" value="<?php echo $entrant["surname"];?>" required/>
                </p>
                <span class="error"> Слишком длинная фамилия</span>
            <?php else: ?>
                <p>
                    <label>Фамилия (максимум <?= Entrant::getInstance()->maxLengthSurname ?> символов): </label>
                    <input type="text" name="surname" value="<?php echo $entrant["surname"];?>" required/>
                </p>
            <?php endif; ?> 

                <!--Пол-->
                <p>
                    <label>Пол:</label>
            <?php if ($entrant["sex"] == "М"): ?>
                    <label class="radio"><input type="radio" name="sex" value="М" checked /> М </label>
                    <label class="radio"><input type="radio" name="sex" value="Ж" /> Ж </label>
            <?php else: ?>
                    <label class="radio"><input type="radio" name="sex" value="М" /> М </label>
                    <label class="radio"><input type="radio" name="sex" value="Ж" checked /> Ж </label>
            <?php endif; ?>
                </p>

                <!--Группа-->
                <p>
                    <label>Группа (от 2 до 5 цифр или букв): </label><input type="text" pattern="[а-яА-ЯёЁa-zA-Z0-9]{2,5}" 
                                        name="group" value="<?= $entrant["group"] ?>" required/>
                </p>

                <!--E-mail-->
            <?php if ($emailIsFalse): ?>
                <p>
                    <label>E-mail: </label>
                    <input type="text" name="email" class="bad" value="<?php echo $entrant["email"]; ?>" required/>
                </p>
                    <span class="error"> Некорректный e-mail</span>
            <?php elseif (!$emailIsUnique): ?>
                <p>
                    <label>E-mail: </label>
                    <input type="text" name="email" class="bad" value="<?php echo $entrant["email"]; ?>" required/>
                </p>
                    <span class="error"> Абитуриент с таким e-mail уже существует.</span>
            <?php else: ?>             
                <p>
                    <label>E-mail: </label>
                    <input type="text" name="email" value="<?php echo $entrant["email"]; ?>" required/>
                </p>
            <?php endif; ?>

                <!--Баллы-->
            <?php if ($scoresIsValid): ?>
                <p>
                    <label>Кол-во набранных баллов (не более <?= Entrant::getInstance()->maxScores ?>): </label>
                    <input type="text" pattern="[0-9]{1,3}" name="balls" value="<?= $entrant["balls"] ?>" required/>
                </p>
            <?php else: ?>
                <p>
                    <label>Кол-во набранных баллов (не более <?= Entrant::getInstance()->maxScores ?>): </label>
                    <input type="text" pattern="[0-9]{1,3}" class="bad" name="balls" value="<?= $entrant["balls"] ?>" required/>
                </p>
                    <span class="error">Неверное кол-во набранных баллов</span>
            <?php endif; ?>

                <!--Год рождения-->
                <p>
                    <label>Год рождения: </label>
                    <select name="yearOfBirth"> 
            <?php 
                //Создаем список выбора
                $yearBirth = date("Y") - Entrant::getInstance()->minAge; //Максимальный год рождения для формирования списка выбора
                $numberOfLines = Entrant::getInstance()->maxAge - Entrant::getInstance()->minAge; //Число строк в списке
                for ($i = 0; $i <= $numberOfLines; $i++): // Цикл для создания строк выбора 
                    $new_year = $yearBirth - $i; // Формируем новое значение
                    if ($new_year != $entrant['yearOfBirth']): //Для установки выделенного значения
            ?>
                        <option label="<?= $new_year ?>" value="<?= $new_year ?>"></option>
            <?php   else: ?>
                        <!-- Выделенное значение -->
                        <option selected value="<?= $new_year ?>"><?= $new_year ?></option>
            <?php
                    endif;
                endfor; 
            ?> 
                    </select>
                </p> 

                <!--Местный/Иногородний-->
                <p>
                    <label>Резидент: </label>
            <?php if ($entrant["resident"] == RESIDENT): ?>
                    <label class="radio"><input type="radio" name="resident" value="1" checked/> <?= RESIDENT ?></label>
                    <label class="radio"><input type="radio" name="resident" value="2"/> <?= NO_RESIDENT ?></label>
            <?php else: ?>
                    <label class="radio"><input type="radio" name="resident" value="1"/> <?= RESIDENT ?></label>
                    <label class="radio"><input type="radio" name="resident" value="2" checked/> <?= NO_RESIDENT ?></label>
            <?php endif; ?>    
                </p>

                <!--Кодовые слова (пароль)-->
            <?php if ($codewordIsValid): ?>
                <p>
                    <label>Кодовое слово: </label><input type="text" name="code" 
                                                         value="<?= $entrant["code"] ?>" required/>
                </p>    
                <p>
                    <label>Повторите кодовое слово: </label><input type="text" name="code2" 
                                                         value="<?= $entrant["code2"] ?>" required/>
                </p>
            <?php else: ?>
                <p>
                    <label>Кодовое слово: </label><input class="bad" type="text" name="code" 
                                                         value="<?= $entrant["code"] ?>" required/>
                </p>    
                <p>
                    <label>Повторите кодовое слово: </label><input class="bad" type="text" name="code2" 
                                                         value="<?= $entrant["code2"] ?>" required/>
                    <span class="error">Кодовые слова не совпадают!</span>
                </p>
            <?php endif; ?>

                <!--Кнопки)-->            
                <input type="submit" value="Сохранить" name="save"/>
            <?php if ($_SESSION['editForm'] == EDIT): ?>
                <input type="submit" value="Удалить" name="delete" />
            <?php else: ?>
                <input type="submit" value="Удалить" name="delete" disabled />
            <?php endif; ?>
            </form>
            <form class="center" action="index.php" method="get">
                <button class="close">Вернуться к списку</button>
            </form>
        </div><!--конец класса form-->
    </body>
</html>

