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
 * Form builder
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2010 IMND
 */
class FormBuilder
{
    /**
     * whether to enable csrf protection component
     */
    private bool $_csrfCheck;
    /**
     * Form settings
     */
    private array $_options = [
        // submit or send ajax request
        'ajax' => false,
        // path to template
        'viewsPath' => '../vendor/tachyon/components/formBuilder/views/',
        // template file name
        'view' => 'form',
        // default id
        'defId' => 'imnd',
        // whether this is the last form (if multiple on page)
        'final' => true,
        // form tag attributes
        'attrs' => [
            'method'  => 'GET',
            'enctype' => 'multipart/form-data',
        ],
        // default tag
        'tagDef' => 'input',
        // text captions
        'text' => [
            'submitCaption' => 'Submit',
            'required' => '* required fields',
        ],
    ];
    /**
     * Form counter
     */
    private int $_formCnt = 0;

    public function __construct(
        protected Config $config,
        protected AssetManager $assetManager,
        protected Html $html,
        protected Csrf $csrf,
        protected View $view,
        protected Message $msg,
    ) {
        $this->_csrfCheck = $config->get('csrf_check') ?? false;
    }

    /**
     * Form rendering
     */
    public function build(array $params = []): void
    {
        // Custom options
        $options = $params['options'] ?? [];
        $this->_options = array_merge($this->_options, $options);
        $this->_options['attrs']['class'] = $this->_options['class'] ?? null;
        $this->_options['attrs']['action'] = $this->_options['action'] ?? '';
        $this->_options['attrs']['method'] = $this->_options['method'] ?? 'GET';
        $this->_options['text']['submitCaption'] = $this->_options['submitCaption'] ?? null;
        $this->_options['final'] = $this->_options['final'] ?? true;
        // generate a unique id and unique name for each form if not set in $options
        $this->_options['attrs']['id'] = $this->_options['attrs']['name'] = $this->_options['defId'] . '_frm_' . $this->_formCnt++;
        // initialize path for rendering
        $this->view->setViewsPath($this->_options['viewsPath']);
        $formId = $this->_options['attrs']['id']; // for coding convenience
        $requiredFields = false;
        $controls = [];
        // if form fields are defined via model
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
                $controls[] = $control;
                $requiredFields = $requiredFields || $required;
            }
        }
        // if form fields are defined directly by an array
        if (!empty($params['controls'])) {
            // TODO: add validation check for $requiredFields
            // TODO: handling for select, check, etc.
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
                        // handler for field labels (to disappear)
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
        // reminder about required fields
        if ($requiredFields && !empty($this->_options['notice'])) {
            $elements[] = [
                'tag' => 'div',
                'attrs' => ['class' => 'msg clear'],
                'contents' => t($this->_options['text']['required']),
            ];
        }
        $elements = array_merge($elements, compact('controls'));
        // submit button
        $elements['submit'] = [
            'attrs' => [
                'type' => $this->_options['ajax'] ? 'button' : 'submit',
                'class' => 'button',
                'id' => "submit_$formId",
                'value' => t($this->_options['submitCaption']),
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
        // enable validation script
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
     * Outputs form scripts and styles
     */
    private function _renderScripts(): void
    {
        $assetsSourcePath = __DIR__ . '/assets';
        $assetsPublicPath = lcfirst(ClassHelper::getClassName($this));
        $this->assetManager->coreJs('obj');
        echo
            $this->assetManager->css('style', $assetsPublicPath, $assetsSourcePath),
            // validation script
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
