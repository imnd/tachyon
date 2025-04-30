<?php

namespace tachyon\traits;

/**
 * @author imndsu@gmail.com
 */
trait DateTime
{
    private const MONTHS = [
        'ru' => [
            'short' => [
                'nom' => ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
                'gen' => ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
            ],
            'long'  => [
                'nom' => [
                    'январь',
                    'февраль',
                    'март',
                    'апрель',
                    'май',
                    'июнь',
                    'июль',
                    'август',
                    'сентябрь',
                    'октябрь',
                    'ноябрь',
                    'декабрь',
                ],
                'gen' => [
                    'января',
                    'февраля',
                    'марта',
                    'апреля',
                    'мая',
                    'июня',
                    'июля',
                    'августа',
                    'сентября',
                    'октября',
                    'ноября',
                    'декабря',
                ],
            ],
        ],
    ];

    public function convDateToReadable(
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

    public function getDay(string $date = null): string
    {
        $dateArr = explode('-', $date ?? $this->date);
        return $dateArr[2];
    }

    public function getMonth(
        string $date = null,
        string $length = 'long',
        string $case = 'gen'
    ): string {
        $dateArr = explode('-', $date ?? $this->date);
        return self::MONTHS[lang()->getLanguage()][$length][$case][(int)$dateArr[1] - 1];
    }

    public function getYear(string $date = null): string
    {
        $dateArr = explode('-', $date ?? $this->date);
        return $dateArr[0];
    }

    public function timestampToDateTime(int $timestamp): string
    {
        $date = new \DateTime;
        $date->setTimestamp($timestamp);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns the first and last day of the year.
     */
    public function getYearBorders(): array
    {
        $curYear = date('Y');
        return [
            'first' => "$curYear-01-01",
            'last'  => "$curYear-12-31",
        ];
    }

    /**
     * Sets the range of the first and last day of the year
     */
    public function setYearBorders(array $conditions = []): array
    {
        if (!isset($conditions['dateFrom'])) {
            $conditions['dateFrom'] = $this->getYearBorders()['first'];
        }
        if (!isset($conditions['dateTo'])) {
            $conditions['dateTo'] = $this->getYearBorders()['last'];
        }
        $where = array_merge(
            $this->gt($conditions, 'date', 'dateFrom'),
            $this->lt($conditions, 'date', 'dateTo')
        );
        unset($conditions['dateFrom'], $conditions['dateTo']);

        return array_merge($where, $conditions);
    }
}
