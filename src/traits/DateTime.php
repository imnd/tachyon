<?php

namespace tachyon\traits;

use ReflectionException;
use tachyon\dic\Container;
use tachyon\exceptions\ContainerException;
use tachyon\components\Lang;

/**
 * Трейт аутентификации
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait DateTime
{
    private array $_months = [
        'ru' => [
            'short' => [
                'nom' => ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
                'gen' => ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
            ],
            'long' => [
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

    /**
     * @param        $date
     * @param        $glue string
     * @param string $length
     * @param string $case
     *
     * @return string
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function convDateToReadable(
        $date,
        $glue = ' ',
        $length = 'long',
        $case = 'gen'
    ): string {
        if (empty($date)) {
            return '';
        }
        $dateArr = explode('-', $date);
        $dateArr = array_reverse($dateArr);
        $dateArr[1] = $this->_months[app()->get(Lang::class)->getLanguage(
        )][$length][$case][(int)$dateArr[1] - 1];
        return implode($glue, $dateArr) . ' г.';
    }

    /**
     * @param null $date
     *
     * @return string
     */
    public function getDay($date = null): string
    {
        $dateArr = explode('-', $date ?? $this->date);
        return $dateArr[2];
    }

    /**
     * @param null   $date
     * @param string $length
     * @param string $case
     *
     * @return string
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function getMonth($date = null, $length = 'long', $case = 'gen')
    {
        $dateArr = explode('-', $date ?? $this->date);
        return $this->_months[
            app()
                ->get(Lang::class)
                ->getLanguage()
        ][$length][$case][(int)$dateArr[1] - 1];
    }

    /**
     * @param null $date
     *
     * @return string
     */
    public function getYear($date = null): string
    {
        $dateArr = explode('-', $date ?? $this->date);
        return $dateArr[0];
    }

    public function timestampToDateTime($timestamp): string
    {
        $date = new \DateTime;
        $date->setTimestamp($timestamp);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Возвращает первый и последний день года.
     *
     * @return array
     */
    public function getYearBorders(): array
    {
        $curYear = date('Y');
        return [
            'first' => "$curYear-01-01",
            'last' => "$curYear-12-31",
        ];
    }

    /**
     * Устанавливает диапазон первый и последний день года.
     *
     * @param array $conditions
     *
     * @return array
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
