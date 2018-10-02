<?php
namespace tachyon\components\widgets;

/**
 * class Datepicker
 * Отображает дэйтпикер
 * Использует стороннюю библиотеку Pikaday David Bushell datepicker
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Datepicker extends Widget
{
    protected $fieldNames;
    protected $format = 'YYYY-MM-DD';

    public function run()
    {
        $text = $this->display('datepicker', array(
            'assetsPath' => $this->getAssetsPath(),
            'id' => $this->id,
            'fieldNames' => $this->fieldNames,
            'format' => $this->format,
        ), true);

        if ($this->return)
            return $text;

        echo $text;
    }
}