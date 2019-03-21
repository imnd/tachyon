<?php
namespace tachyon\helpers;

use tachyon\dic\Container;

/**
 * Содержит полезные функции для работы с датой и временем
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class DateTimeHelper
{
    /**
     * @return string
     */
    public static function getDay($date)
    {
        $dateArr = explode('-', $date);

        return $dateArr[2];
    }

    /**
     * @return string
     */
    public static function getMonth($date, $length='long', $case='gen')
    {
        $months = ['ru' => [
            'short' => [
                'nom' => ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
                'gen' => ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
            ],
            'long' => [
                'nom' => ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'],
                'gen' => ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря']
            ]
        ]];

        $dateArr = explode('-', $date);
        return $months[(new Container)->get('\tachyon\components\Lang')->getLanguage()][$length][$case][(int)$dateArr[1] - 1];
    }

    /**
     * @return string
     */
    public static function getYear($date)
    {
        $dateArr = explode('-', $date);
        return $dateArr[0];
    }

    public static function timestampToDateTime($val)
    {
        $date = new DateTime;
        $date->setTimestamp($val);
        return $date->format('Y-m-d H:i:s');
    }
}
