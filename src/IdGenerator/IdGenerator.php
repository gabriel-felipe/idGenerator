<?php
namespace IdGenerator;
/*
    Classe para gerar id's sequenciais sobre demanda.
    o id é construído a partir da concatenação entre:
        um timestamp com custom epoch e precisão de milisegundos
        um número fixo de 2 digitos, reservados para se no futuro mais de um server necessitar gerar os ids
        um número sequencial de 5 digitos, para evitar colisão de ms.
*/
if (!defined("ID_GENERATOR_MACHINE_ID")) {
    define("ID_GENERATOR_MACHINE_ID",00);
}
if (!defined("ID_GENERATOR_CUSTOM_EPOCH")) {
    define("ID_GENERATOR_CUSTOM_EPOCH",1506204000);
}

class IdGenerator
{

    private static $counter = 0;
    private static $lastMs = false;
    private static $machineId = "00";
    private static $counterLimit = 9999;
    public static function getId()
    {

        $time = self::getTimestamp();
        if (self::getLastMs() === false) {
            self::setLastMs($time);
        }

        if (self::$counter === self::$counterLimit) {
            if (self::getLastMs() === $time) {
                usleep(1000);
                $time = self::getTimestamp();
            }
        }

        if (self::$lastMs !== $time) {
            self::$lastMs = $time;
        }

        $counter = self::getCounter($time);
        return $time.self::$machineId.$counter;
    }

    private static function getTimestamp()
    {
        $time = microtime(true);
        $time = explode(".",$time);
        $time[0] = $time[0] - ID_GENERATOR_CUSTOM_EPOCH;
        if (!isset($time[1])) {
            $time[1] = "000";
        } else {
            $time[1] = str_pad(substr($time[1],0,3),3,0);
        }

        $time = $time[0].$time[1];

        return $time;
    }

    private static function getCounter($time)
    {
        if (self::$counter < self::$counterLimit) {
            self::$counter++;
        } else {
            self::$counter = 1;
        }
        return str_pad(self::$counter,strlen(self::$counterLimit),"0",STR_PAD_LEFT);
    }

    private static function getLastMs()
    {
        return self::$lastMs;
    }

    private static function setLastMs($ms)
    {
        self::$lastMs = $ms;
    }
}
