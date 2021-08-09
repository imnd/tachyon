<?php

namespace tachyon\components\widgets\grid;

use tachyon\dic\Container,
    tachyon\Config,
    tachyon\components\Message,
    tachyon\components\Csrf,
    tachyon\components\widgets\Widget,
    tachyon\db\activeRecord\ActiveRecord
;

/**
 * Отображает в виде таблицы результат выборки
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Grid extends Widget
{
    /**
     * @var ActiveRecord $model
     */
    protected $model;
    /**
     * Поля таблицы
     *
     * @var $columns array
     */
    protected $columns = [];
    /**
     * Записи отображаемые в таблице
     *
     * @var $items array
     */
    protected $items = [];
    /**
     * кнопки
     *
     * @var $buttons array
     */
    protected $buttons = [];
    /**
     * поля по которым фильтруется содержимое
     *
     * @var $searchFields array
     */
    protected $searchFields = [];
    /**
     * поля по которым выводится сумма внизу таблицы
     *
     * @var $sumFields array
     */
    protected $sumFields = [];
    /**
     * сортируется ли таблица
     *
     * @var $sortable array
     */
    protected $sortable = false;
    /**
     * включать ли компонент защиты от csrf-атак
     */
    protected $csrfJson = '';
    protected $confirmMsgs = [
        'deactivate' => 'деактивировать?',
        'delete' => 'удалить?',
    ];
    /**
     * Имя первичного ключа модели таблицы
     *
     * @var $pkName string
     */
    protected $pkName;
    /**
     * Имя модели таблицы
     *
     * @var $modelName string
     */
    protected $modelName;

    /**
     * @var Config $config
     */
    protected $config;
    /**
     * @var Message $msg
     */
    protected $msg;
    /**
     * @var Csrf $csrf
     */
    protected $csrf;

    /**
     * @return void
     */
    public function __construct(Config $config, Message $msg, Csrf $csrf, ...$params)
    {
        $this->config = $config;
        $this->msg = $msg;
        $this->csrf = $csrf;
        parent::__construct(...$params);
    }

    public function run()
    {
        $this->assetManager->publishFolder(
            'images',
            'assets' . $this->getAssetsPublicPath(),
            $this->getAssetsSourcePath()
        );
        if (true === $this->config->get('csrf_check')) {
            // компонент защиты от csrf-атак
            $this->csrfJson = '"' . $this->csrf->getTokenId() . '":"' . $this->csrf->getTokenVal() . '",';
        }
        if (is_null($this->model)) {
            $this->model = app()->get($this->modelName);
        } else {
            $this->modelName = $this->model->getClassName();
        }
        $this->pkName = $this->model->getPkName();
        $sumArr = [];
        foreach ($this->sumFields as $sumField) {
            $sumArr[$sumField] = 0;
        }
        foreach ($this->buttons as $key => &$button) {
            if (is_string($button)) {
                $action = $button;
                $btnOptions = [];
            } else {
                $action = $button['action'] ?? $key;
                $btnOptions = $button;
            }
            if (!isset($btnOptions['captioned'])) {
                $btnOptions['captioned'] = false;
            }
            if (!isset($btnOptions['htmlOptions'])) {
                $btnOptions['htmlOptions'] = [];
            }
            $btnOptions['htmlOptions']['class'] = "button-$action";
            $btnOptions['htmlOptions']['title'] = $btnOptions['title'] ?? $this->msg->i18n($action);
            if (isset($btnOptions['vars'])) {
                $action .= '/' . implode(
                        '/',
                        array_map(
                            function ($k, $v) {
                                return "$k/$v";
                            },
                            array_keys($btnOptions['vars']),
                            array_values($btnOptions['vars'])
                        )
                    );
            }
            $button = [
                'action' => $action,
                'captioned' => $btnOptions['captioned'],
                'htmlOptions' => $btnOptions['htmlOptions'],
            ];
            unset($btnOptions['captioned']);
            unset($btnOptions['htmlOptions']);
            $button['options'] = $btnOptions;
        }
        $this->display(
            'grid',
            [
                'model' => $this->model,
                'columns' => $this->columns,
                'items' => $this->items,
                'sortable' => $this->sortable,
                'buttons' => $this->buttons,
                'searchFields' => $this->searchFields,
                'sumFields' => $this->sumFields,
                'sumArr' => $sumArr,
                'csrfJson' => $this->csrfJson,
            ]
        );
    }

    # геттеры

    /**
     * Путь до ресурсов
     *
     * @return string
     */
    public function getAssetsSourcePath()
    {
        return __DIR__ . '/assets';
    }

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
     *
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
     *
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
     *
     * @return string
     */
    public function getBtnId($action, array $item)
    {
        return "$action-" . $this->getRowId($item);
    }

    /**
     * @param $action string
     * @param $item array
     *
     * @return string
     */
    public function getActionUrl($action, array $item = null)
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
