<?php

namespace tachyon\helpers;

use tachyon\db\activeRecord\ActiveRecord;

/**
 * @author imndsu@gmail.com
 */
class DateTimeHelper
{
    private const MONTHS = [
        'ru' => [
            'short' => [
                'nom' => ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
                'gen' => ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
            ],
            'long'  => [
                'nom' => ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь',],
                'gen' => ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',],
            ],
        ],
    ];

    public static function convDateToReadable(
        string $date,
        string $glue = ' ',
        string $length = 'long',
        string $case = 'gen'
    ): string {
        if (empty($date)) {
            return '';
        }
        $dateArr = explode('-', $date);
        $dateArr = array_reverse($dateArr);
        $dateArr[1] = self::MONTHS[lang()->getLanguage()][$length][$case][(int)$dateArr[1] - 1];

        return implode($glue, $dateArr) . ' г.';
    }

    public static function convertDate(string $date, $from = 'Y-m-d', $to = 'd m Y'): string
    {
        $date = new \DateTime($date);
        return $date->format($to);
    }

    public static function getDay(string | ActiveRecord $data): string
    {
        $date = is_string($data) ? $data : $data->date;
        $date = new \DateTime($date);
        return $date->format('d');
    }

    public static function getMonth(
        string | ActiveRecord $data,
        string $length = 'long',
        string $case = 'gen'
    ): string {
        $date = is_string($data) ? $data : $data->date;
        $date = new \DateTime($date);
        $month = $date->format('m');

        return self::MONTHS[lang()->getLanguage()][$length][$case][$month - 1];
    }

    public static function getYear(string | ActiveRecord $data): string
    {
        $date = is_string($data) ? $data : $data->date;
        $date = new \DateTime($date);

        return $date->format('Y');
    }

    public static function timestampToDateTime(int $timestamp, $format = 'Y-m-d H:i:s'): string
    {
        $date = new \DateTime;
        $date->setTimestamp($timestamp);
        return $date->format($format);
    }

    /**
     * Returns the first and the last day of the year.
     */
    public static function getYearBorders(): array
    {
        $year = date('Y');
        return [
            'first' => "$year-01-01",
            'last'  => "$year-12-31",
        ];
    }
}
