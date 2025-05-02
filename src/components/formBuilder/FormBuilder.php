<?php

namespace tachyon\components\formBuilder;

use tachyon\{
    Config,
    helpers\ClassHelper,
    View
};
use tachyon\components\{
    AssetManager,
    Csrf,
    Html,
    Message
};

/**
 * Построитель форм
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2010 IMND
 */
class FormBuilder
{
    /**
     * включать ли компонент защиты от csrf-атак
     */
    private bool $_csrfCheck;
    /**
     * Настройки формы
     */
    private array $_options = [
        // сабмитить или посылать ajax-запрос
        'ajax' => false,
        // путь к шаблону
        'viewsPath' => '../vendor/tachyon/components/formBuilder/views/',
        // имя файла шаблона
        'view' => 'form',
        // id по умолчанию
        'defId' => 'imnd',
        // форма последняя (если несколько на странице)
        'final' => true,
        // аттрибуты тэга form
        'attrs' => [
            'method'  => 'GET',
            'enctype' => 'multipart/form-data',
        ],
        // тэг по умолчанию
        'tagDef' => 'input',
        // текстовые подписи
        'text' => [
            'ru' => [
                'submitCaption' => 'Отправить',
                'required' => 'Поля отмеченные * обязательны для заполнения',
            ],
            'en' => [
                'submitCaption' => 'Submit',
                'required' => '* required fields',
            ],
        ],
    ];
    /**
     * Счетчик формы
     */
    private int $_formCnt = 0;
    /**
     * Список полей типа дэйтпикер
     */
    private array $_dateFieldNames = [];

    public function __construct(
        protected Config $config,
        protected AssetManager $assetManager,
        protected Html $html,
        protected Csrf $csrf,
        protected View $view,
        protected Message $msg,
    ) {
        $this->_csrfCheck = $config->get('csrf_check') ?? false;
        // текстовые
        $this->_options['text'] = $this->_options['text'][$config->get('lang')];
    }

    /**
     * Отрисовка формы
     */
    public function build(array $params = []): void
    {
        // Custom опции
        $options = $params['options'] ?? [];
        $this->_options = array_merge($this->_options, $options);
        $this->_options['attrs']['class'] = $this->_options['class'] ?? null;
        $this->_options['attrs']['action'] = $this->_options['action'] ?? '';
        $this->_options['attrs']['method'] = $this->_options['method'] ?? 'GET';
        $this->_options['text']['submitCaption'] = $this->_options['submitCaption'] ?? null;
        $this->_options['final'] = $this->_options['final'] ?? true;
        // генерируем для каждой формы уникальный id и уникальный name если он не задан в $options
        $this->_options['attrs']['id'] = $this->_options['attrs']['name'] = $this->_options['defId'] . '_frm_' . $this->_formCnt++;
        // инициализируем путь для отображения
        $this->view->setViewsPath($this->_options['viewsPath']);
        $formId = $this->_options['attrs']['id']; // для удобства записи
        $requiredFields = false;
        $controls = [];
        // если поля формы определяются ч/з модель
        if (!empty($params['model']) && !empty($params['fields'])) {
            $fieldValues = array_key_exists('fieldValues', $params) ? $params['fieldValues'] : [];
            $model = $params['model'];
            foreach ($params['fields'] as $key => $options) {
                if (is_numeric($key)) {
                    $field = $options;
                    if (!$fieldTag = $model->getAttributeType($field)) {
                        $fieldTag = 'input';
                    }
                } else {
                    $field = $key;
                    if (is_array($options) && isset($options['listData'])) {
                        $fieldTag = 'select';
                    } else {
                        $fieldTag = 'input';
                    }
                }
                $attrs = [
                    'name' => $field,
                    'value' => $fieldValues[$field] ?? ($model->$field ?: ''),
                ];
                if ($required = $model->isRequired($field)) {
                    $attrs['class'] = 'required';
                }
                $control = [
                    'label' => $model->getAttributeName($field),
                    'tag' => $fieldTag,
                    'type' => is_array($options) && ($options['type'] ?? ''),
                    'model' => $model,
                    'required' => $required,
                    'attrs' => $attrs,
                ];
                if ($fieldTag === 'input') {
                    $control['attrs']['type'] = 'text';
                } elseif ($fieldTag === 'select') {
                    $control['options'] = (!empty($options['listData'])) ? $options['listData'] : [];
                }
                if ($control['type'] === 'date') {
                    $this->_dateFieldNames[] = $control['attrs']['name'];
                }
                $controls[] = $control;
                $requiredFields = $requiredFields || $required;
            }
        }
        // если поля формы определяются напрямую массивом
        if (!empty($params['controls'])) {
            // TODO: приделать проверку на $requiredFields
            // TODO: обработка для select, check и пр.
            foreach ($params['controls'] as &$control) {
                $control['tag'] = isset($control['tag']) ? $control['tag'] : $this->_options['tagDef'];
                if (!isset($control['attrs']['type']) && $control['tag'] === 'input') {
                    $control['attrs']['type'] = 'text';
                }
                if (isset($control['disabled'])) {
                    $control['attrs']['class'] = 'disabled';
                }
            }
            if (!isset($this->_options['labels']) || $this->_options['labels'] !== 'outer') {
                foreach ($params['controls'] as &$control) {
                    if ($control['tag'] === 'input') {
                        $ctrlVal = $control['label'];
                        $control['attrs']['value'] = $ctrlVal;
                        // обработчик для подписей в полях (чтобы исчезали)
                        $control['attrs']['onfocus'] = str_replace(
                            'capt_value',
                            $ctrlVal,
                            "if(this.value==='capt_value') this.value=''"
                        );
                        $control['attrs']['onblur'] = str_replace(
                            'capt_value',
                            $ctrlVal,
                            "if(this.value=='') this.value='capt_value'"
                        );
                    }
                    $control['label'] = '';
                }
            }
            $controls = array_merge($controls, $params['controls']);
        }
        $elements = [];
        // напоминание об обязательных полях
        if ($requiredFields && !empty($this->_options['notice'])) {
            $elements[] = [
                'tag' => 'div',
                'attrs' => ['class' => 'msg clear'],
                'contents' => $this->_options['text']['required'],
            ];
        }
        $elements = array_merge($elements, compact('controls'));
        // кнопка submit
        $elements['submit'] = [
            'attrs' => [
                'type' => $this->_options['ajax'] ? 'button' : 'submit',
                'class' => 'button',
                'id' => "submit_$formId",
                'value' => $this->_options['submitCaption'],
            ],
        ];
        $elements['errors'] = [
            'tag' => 'div',
            'attrs' => [
                'class' => 'error',
                'id' => 'errors_list',
            ],
        ];
        // csrf token
        if ($this->_csrfCheck) {
            $elements['csrf'] = [
                'attrs' => [
                    'type' => 'hidden',
                    'id' => 'csrf',
                    'name' => $this->csrf->getTokenId(),
                    'value' => $this->csrf->getTokenVal(),
                ],
            ];
        }
        if ($this->_options['final']) {
            $this->_renderScripts();
        }
        $this->view->display(
            $this->_options['view'],
            [
                'form' => $this,
                'model' => $model ?? null,
                'elements' => $elements,
                'attrs' => $this->_options['attrs'],
            ]
        );
        // включаем скрипт валидации
        if (
               !empty($params['model'])
            && !empty($params['fields'])
            && $this->_options['ajax']
        ) {
            $formHandler = isset($this->_options['formHandler']) ? $this->_options['formHandler'] : "dom.findById('$formId').submit();";
            echo "<script>
            dom.findById('submit_$formId').onclick = function() {
                validation.msgContainerId = 'errors_list';
                if (validation.run(" . $model->getValidationFieldsJs($params['fields']) . ")) {
                    $formHandler;
                }
                return false;
            };
            </script>";
        }
    }

    /**
     * Выводим скрипты и стили формы
     */
    private function _renderScripts(): void
    {
        $assetsSourcePath = __DIR__ . '/assets';
        $assetsPublicPath = lcfirst(ClassHelper::getClassName($this));
        $this->assetManager->coreJs('obj');
        echo
            $this->assetManager->css('style', $assetsPublicPath, $assetsSourcePath),
            // скрипт валидации
            $this->assetManager->js('validation', $assetsPublicPath, $assetsSourcePath);
    }

    public function getCsrfCheck(): bool
    {
        return $this->_csrfCheck;
    }

    public function getHtml(): Html
    {
        return $this->html;
    }
}
