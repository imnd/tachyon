<?php
namespace tachyon\components\widgets\grid;

/**
 * class Grid
 * Отображает в виде таблицы результат выборки
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Grid extends \tachyon\components\widgets\Widget
{
    use \tachyon\dic\Message;
    use \tachyon\dic\Csrf;

    /**
     * @var \tachyon\db\models\TableModel $model
     */
    protected $model;
    /**
     * Поля таблицы
     * @var $columns array
     */
    protected $columns = array();
    /**
     * Записи отображаемые в таблице
     * @var $items array
     */
    protected $items = array();
    /**
     * кнопки
     * @var $buttons array
     */
    protected $buttons = array();
    /**
     * поля по которым фильтруется содержимое
     * @var $searchFields array
     */
    protected $searchFields = array();
    /**
     * поля по которым выводится сумма внизу таблицы
     * @var $sumFields array
     */
    protected $sumFields = array();
    /**
     * сортируется ли таблица
     * @var $sortable array
     */
    protected $sortable = false;

    /**
     * включать ли компонент защиты от csrf-атак
     */
    protected $csrfJson = '';
    protected $confirmMsgs = array(
        'deactivate' => 'деактивировать?',
        'delete' => 'удалить?',
    );
    /**
     * Имя первичного ключа модели таблицы
     * @var $pkName string
     */
    protected $pkName;
    /**
     * Имя модели таблицы
     * @var $modelName string
     */
    protected $modelName;

    public function run()
    {
        if (true===$this->config->getOption('csrf_check')) {
            // компонент защиты от csrf-атак
            $this->csrfJson = '"' . $this->csrf->getTokenId() . '":"' . $this->csrf->getTokenVal() . '",';
        }
        if (is_null($this->model)) {
            $this->model = \tachyon\dic\Container::getInstanceOf($this->modelName);
        } else {
            $this->modelName = $this->model->getClassName();
        }
        $this->pkName = $this->model->getPrimKey();

        $sumArr = array();
        foreach ($this->sumFields as $sumField)
            $sumArr[$sumField] = 0;

        foreach ($this->buttons as $key => &$button) {
            if (is_string($button)) {
                $action = $button;
                $btnOptions = array();
            } else {
                if (isset($button['action']))
                    $action = $button['action'];
                else
                    $action = $key;
                $btnOptions = $button;
            }
            if (!isset($btnOptions['captioned']))
                $btnOptions['captioned'] = false;

            if (!isset($btnOptions['htmlOptions']))
                $btnOptions['htmlOptions'] = array();

            $btnOptions['htmlOptions']['class'] = "button-$action";

            if (isset($btnOptions['title']))
                $btnOptions['htmlOptions']['title'] = $btnOptions['title'];
            else
                $btnOptions['htmlOptions']['title'] = $this->getMsg()->i18n($action);

            if (isset($btnOptions['vars']))
                $action .= '/' . implode('/', array_map(
                    create_function('$k,$v', 'return "$k/$v";'),
                    array_keys($btnOptions['vars']),
                    array_values($btnOptions['vars'])
                ));

            $button = array(
                'action' => $action,
                'captioned' => $btnOptions['captioned'],
                'htmlOptions' => $btnOptions['htmlOptions'],
            );
            unset($btnOptions['captioned']);
            unset($btnOptions['htmlOptions']);
            $button['options'] = $btnOptions;
        }

        $this->display('grid', array(
            'model' => $this->model,
            'columns' => $this->columns,
            'items' => $this->items,
            'sortable' => $this->sortable,
            'buttons' => $this->buttons,
            'searchFields' => $this->searchFields,
            'sumFields' => $this->sumFields,
            'sumArr' => $sumArr,
            'csrfJson' => $this->csrfJson,
        ));
    }

    # геттеры

    public function getConfirmMsgs()
    {
        return $this->confirmMsgs;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param $item array
     * @return string
     */
    public function getPk(array $item)
    {
        return $item[$this->pkName];
    }

    /**
     * Вычисляет id строки таблицы для манипулирования ей
     * 
     * @param $item array
     * @return string
     */
    public function getRowId(array $item)
    {
        return "row-{$this->id}-{$item[$this->pkName]}";
    }

    /**
     * getBtnId
     * 
     * @param $action string
     * @param $item array
     * @return string
     */
    public function getBtnId($action, array $item)
    {
        return "$action-" . $this->getRowId($item);
    }

    /**
     * @param $action string
     * @param $item array
     * @return string
     */
    public function getActionUrl($action, array $item=null)
    {
        return "/{$this->controller->getId()}/$action" . (is_null($item) ? '' : '/' . $this->getPk($item));
    }

    /**
     * @return string
     */
    public function getColumns()
    {
        return $this->columns;
    }

}