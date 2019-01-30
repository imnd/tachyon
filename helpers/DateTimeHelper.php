<?php
namespace tachyon\helpers;

/**
 * Содержит полезные функции для работы с датой и временем
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class DateTimeHelper
{
    /**
     * Возвращает первый и последний день года.
     * 
     * @return array
     */
    public static function getYearBorders()
    {
        $curYear = date('Y');
        return array(
            'first' => "$curYear-01-01",
            'last' =>"$curYear-12-31",
        );
    }

    /**
     * Устанавливает диапазон первый и последний день года.
     * 
     * @param \tachyon\db\activeRecord\ActiveRecord $model
     * @return void
     */
    public static function setYearBorders(&$model, $where)
    {
        if (!isset($where['dateFrom'])) {
            $where['dateFrom'] = DateTimeHelper::getYearBorders()['first'];
        }
        $model->gt($where, 'date', 'dateFrom');

        if (!isset($where['dateTo'])) {
            $where['dateTo'] = DateTimeHelper::getYearBorders()['last'];
        }
        $model->lt($where, 'date', 'dateTo');
    }

    /**
     * Текущая дата в стандартном формате
     * 
     * @return string
     */
    public static function getCurDate()
    {
        return date('Y-m-d');
    }

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
        $months = array('ru' => array(
            'short' => array(
                'nom' => array('янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'),
                'gen' => array('янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'),
            ),
            'long' => array(
                'nom' => array('январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'),
                'gen' => array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря')
            )
        ));

        $dateArr = explode('-', $date);
        return $months[\tachyon\dic\Container::getInstanceOf('Lang')->getLanguage()][$length][$case][(int)$dateArr[1] - 1];
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
