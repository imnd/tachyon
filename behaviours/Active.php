<?php
namespace tachyon\behaviours;

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
     * @param \tachyon\db\models\Model $model
     * @return boolean
     */
    public function activate($model)
    {
        return $this->setState($model, $this->activeState);
    }

    /**
     * Делает сущность неактивной
     * 
     * @param \tachyon\db\models\Model $model
     * @return boolean
     */
    public function deactivate($model)
    {
        return $this->setState($model, $this->inactiveState);
    }

    /**
     * Устанавливает состояние сущности
     * 
     * @param \tachyon\db\models\Model $model
     * @return boolean
     */
    protected function setState($model, $state)
    {
        return $model->saveAttrs(array($this->activeField => $state));
    }
    
    /**
     * Текстовая расшифровка для значения поля БД "активность"
     * 
     * @param \tachyon\db\models\Model $model
     * @param array $item
     * @return string
     */
    public function getActiveText($model, $item=null)
    {
        $activeVal = is_null($item) ? $model->{$this->activeField} : $item[$this->activeField];
        return $activeVal==$this->activeState ? 'да' : 'нет';
    }

    # SETTERS

    /**
     * @param string $activeField
     * @return void
     */
    public function setActiveField($activeField)
    {
        $this->activeField = $activeField;
    }

    /**
     * @param string 
     * @return void
     */
    public function setActiveState($activeState)
    {
        $this->activeState = $activeState;
    }

    /**
     * @param string 
     * @return void
     */
    public function setInactiveState($inactiveState)
    {
        $this->inactiveState = $inactiveState;
    }
}
