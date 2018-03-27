<?php

class TableList {
    const ASC = 'ASC';
    const DESC = 'DESC';
    public $RECORDS_ONPAGE = 6;
    public $fields = [
                       'name' => [
                                    'caption' => 'Имя',
                                    'direction' => self::ASC
                                ],
                       'surname' => [
                                    'caption' => 'Фамилия',
                                    'direction' => self::ASC
                                    ],
                       'sex' => [
                                    'caption' => 'Пол',
                                    'direction' => self::ASC,
                                    'align' => 'center'
                                ],
                       'group_numb' => [
                                    'caption' => '№<br>группы',
                                    'direction' => self::ASC,
                                    'align' => 'center'
                                        ],
                       'e_mail' => [
                                    'caption' => 'e-mail',
                                    'direction' => self::ASC
                                    ],
                       'total_scores' => [
                                    'caption' => 'Кол-во набранных<br>баллов',
                                    'direction' => self::DESC,
                                    'align' => 'center'
                                        ],
                       'yearOfBirth' => [
                                    'caption' => 'Год<br>рождения',
                                    'direction' => self::ASC,
                                    'align' => 'center'
                                        ],
                       'resident' => [
                                    'caption' => 'Местный/<br>Иногородний',
                                    'direction' => self::DESC
                                    ],
                   ];

    public $orderFirst = 'total_scores'; //Начальное поле сортировки таблицы
    public $directionFirst = self::DESC; //Начальное направление сортировки
}

$list = new TableList();
