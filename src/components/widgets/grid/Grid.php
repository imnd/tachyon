<?php

namespace tachyon\components\widgets\grid;

use
    tachyon\Config,
    tachyon\components\Message,
    tachyon\components\Csrf,
    tachyon\components\widgets\Widget,
    tachyon\db\activeRecord\ActiveRecord,
    tachyon\helpers\ClassHelper
;

/**
 * Displays selection result in a table format
 *
 * @author imndsu@gmail.com
 */
class Grid extends Widget
{
    /**
     * Table fields
     */
    protected array $columns = [];
    /**
     * Records displayed in table
     */
    protected array $items = [];
    /**
     * buttons
     */
    protected array $buttons = [];
    /**
     * fields by which content is filtered
     */
    protected array $searchFields = [];
    /**
     * fields by which sum is output at the bottom of the table
     */
    protected array $sumFields = [];
    /**
     * whether table is sorted
     */
    protected bool $sortable = false;
    /**
     * whether to enable csrf protection component
     */
    protected string $csrfJson = '';
    protected array $confirmMsgs = [
        'deactivate' => 'деактивировать?',
        'delete' => 'удалить?',
    ];
    /**
     * Table model primary key name
     */
    protected string $pkName;
    /**
     * Table model name
     */
    protected string $modelName;

    protected ActiveRecord $model;
    protected Config $config;
    protected Message $msg;
    protected Csrf $csrf;

    public function __construct(Config $config, Message $msg, Csrf $csrf, ...$params)
    {
        $this->config = $config;
        $this->msg = $msg;
        $this->csrf = $csrf;
        parent::__construct(...$params);
    }

    public function run(): void
    {
        $this->assetManager->publishFolder(
            'images',
            'assets' . $this->getAssetsPublicPath(),
            $this->getAssetsSourcePath()
        );
        if (true === $this->config->get('csrf_check')) {
            // csrf protection component
            $this->csrfJson = '"' . $this->csrf->getTokenId() . '":"' . $this->csrf->getTokenVal() . '",';
        }
        if (is_null($this->model)) {
            $this->model = app()->get($this->modelName);
        } else {
            $this->modelName = ClassHelper::getClassName($this->model);
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
            $btnOptions['htmlOptions']['title'] = $btnOptions['title'] ?? t($action);
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

    # getters

    /**
     * Path to resources
     *
     * @return string
     */
    public function getAssetsSourcePath(): string
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
     * Calculates table row id for manipulation
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
