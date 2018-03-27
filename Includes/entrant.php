<?php

class Entrant {
    private static $instance = null;
    
    public $maxLengthSurname = 10;
    public $maxScores = 500;
    public $averageAge = 20;
    public $minAge = 15;
    public $maxAge = 50;
    
    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function checkLengthSurname($length) {
        if ($length > $this->maxLengthSurname)
            return FALSE;
        else
            return TRUE;
    }

    public function checkScores($scores) {
        if ($scores > $this->maxScores)
            return FALSE;
        else
            return TRUE;
    }
}


