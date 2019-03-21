<?php
namespace tachyon\behaviours;

use tachyon\components\Lang;

/**
 * Содержит полезные функции для работы с датой и временем
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class DateTime
{
    private $_months = ['ru' => [
        'short' => [
            'nom' => array('янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'),
            'gen' => array('янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'),
        ],
        'long' => [
            'nom' => array('январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'),
            'gen' => array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря')
        ]
    ]];

    /**
     * @var tachyon\components\Lang
     */
    protected $lang;

    public function __construct(Lang $lang)
    {
        $this->lang = $lang;
    }

    /**
     * @param $glue string
     * @param $mode string (long | short)
     * @return string
     */
    public function convDateToReadable($date, $glue=' ', $mode='long'): string
    {
        if (empty($date)) {
            return '';
        }
        $dateArr = explode('-', $date);
        $dateArr = array_reverse($dateArr);

        if ($mode==='long') {
            $dateArr[1] = $this->_months[$this->lang->getLanguage()]['long']['gen'][(int)$dateArr[1] - 1];
        }

        return implode($glue, $dateArr) . ' г.';
    }
}
