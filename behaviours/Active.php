<?php
namespace tachyon\behaviours;

use tachyon\Model;

/**
 * Управляет состоянием активности сущности
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Active
{
    protected $activeField = 'active';
    protected $activeState = 1;
    protected $inactiveState = 0;

    /**
     * Делает сущность активной
     * 
     * @param Model $model
     * @return void
     */
    public function activate($model)
    {
        $this->setState($model, $this->activeState);
    }

    /**
     * Делает сущность неактивной
     * 
     * @param Model $model
     * @return void
     */
    public function deactivate($model)
    {
        $this->setState($model, $this->inactiveState);
    }

    /**
     * Устанавливает состояние сущности
     * 
     * @param Model $model
     * @return void
     */
    protected function setState($model, $state)
    {
        $model->{$this->activeField} = $state;
    }
    
    /**
     * Текстовая расшифровка для значения поля БД "активность"
     * 
     * @param Model $model
     * @param array $item
     * @return string
     */
    public function getActiveText($model, array $item=null): string
    {
        $activeVal = is_null($item) ? $model->{$this->activeField} : $item[$this->activeField];
        return $activeVal==$this->activeState ? 'да' : 'нет';
    }

    # SETTERS

    /**
     * @param string $activeField
     * @return mixed
     */
    public function setActiveField(string $activeField)
    {
        $this->activeField = $activeField;
        return $this;
    }

    /**
     * @param string $activeState
     * @return mixed
     */
    public function setActiveState(string $activeState)
    {
        $this->activeState = $activeState;
        return $this;
    }

    /**
     * @param string $inactiveState
     * @return mixed
     */
    public function setInactiveState(string $inactiveState)
    {
        $this->inactiveState = $inactiveState;
        return $this;
    }
}
