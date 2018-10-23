<?php
namespace tachyon\components\html;

/**
 * Построитель форм
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class FormBuilder extends \tachyon\Component
{
    use \tachyon\dic\Html;
    use \tachyon\dic\Csrf;
    use \tachyon\dic\View;

    /**
     * включать ли компонент защиты от csrf-атак
     */
    private $_csrfCheck = false;
    /**
     * Настройки формы
     * @var $_options array
     */
    private $_options = array(
        // сабмитить или посылать ajax-запрос
        'ajax' => false,
        // путь к шаблону
        'viewPath' => '../vendor/tachyon/components/html/tpl/',
        // имя файла шаблона
        'view' => 'form',
        // id по умолчанию
        'defId' => 'imnd',
        // форма последняя (если несколько на странице)
        'final' => true,
        // аттрибуты тэга form
        'attrs' => array(
            'method' => 'GET',
            'enctype' => 'multipart/form-data',
        ),
        // тэг по умолчанию
        'tagDef' => 'input',
        // текстовые подписи
        'text' => array(
            'ru' => array(
                'submitCaption' => 'Отправить',
                'required' => 'Поля отмеченные * обязательны для заполнения',
            ),
            'en' => array(
                'submitCaption' => 'Submit',
                'required' => '* required fields',
            )
        )
    );
    /**
     * @var integer $_tokenId
     */
    private $_tokenId;
    private $_tokenVal;
    /**
     * Счетчик формы
     * @var integer $_formCnt
     */
    private $_formCnt = 0;
    /**
     * Список полей типа дэйтпикер
     * @var array $_dateFieldNames
     */
    private $_dateFieldNames = array();

    /**
     * __construct
     * Инициализация
     * 
     * @param $options array 
     */
    public function __construct($options=array())
    {
        if (true===$this->_csrfCheck = $this->get('config')->getOption('csrf_check')) {
            $this->_tokenId = $this->csrf->getTokenId();
            $this->_tokenVal = $this->csrf->getTokenVal();
        }
        // текстовые
        $this->_options['text'] = $this->_options['text'][$this->get('config')->getOption('lang')];
	}

	/**
     * build
     * Отрисовка формы
     * 
     * @param $params array 
     */
    public function build($params=array())
    {
        // Custom опции
        $options = isset($params['options']) ? $params['options'] : array();
        $attrs = $this->_options['attrs'];
        $this->_options = array_merge($this->_options, $options);

        $this->_options['attrs']['class'] = $this->_options['class'] ?? null;
        $this->_options['attrs']['action'] = $this->_options['action'] ?? '';
        $this->_options['attrs']['method'] = $this->_options['method'] ?? null;
        $this->_options['text']['submitCaption'] = $this->_options['submitCaption'] ?? null;
        $this->_options['final'] = $this->_options['final'] ?? true;

        // генерируем для каждой формы уникальный id и уникальный name если он не задан в $options
        $this->_options['attrs']['id'] = $this->_options['attrs']['name'] = $this->_options['defId'] . '_frm_' . $this->_formCnt++;
        // инициализируем путь для отображения
        $this->view->setViewsPath($this->_options['viewPath']);

        $formId = $this->_options['attrs']['id']; // для удобства записи
        $requiredFields = false;
        $elements = array();
        $controls = array();
		// если поля формы определяются ч/з модель
        if (!empty($params['model']) && !empty($params['fields'])) {
            $fieldValues = key_exists('fieldValues', $params) ? $params['fieldValues'] : array();
            $model = $params['model'];
            foreach ($params['fields'] as $key => $options) {
                if (is_numeric($key)) {
                    $field = $options;
                    if (!$fieldTag = $model->getAttributeType($field))
                        $fieldTag = 'input';
                } else {
                    $field = $key;
                    if (is_array($options) && isset($options['listData']))
                        $fieldTag = 'select';
                    else
                        $fieldTag = 'input';
                }
                $attrs = array(
                    'name' => $field,
                    'value' => isset($fieldValues[$field]) ? $fieldValues[$field] : (!is_null($model->$field) ? $model->$field : ''),
                );
                if ($required = $model->isRequired($field))
                    $attrs['class'] = 'required';

                $control = array(
                    'label' => $model->getAttributeName($field),
                    'tag' => $fieldTag,
                    'type' =>is_array($options) && isset($options['type']) ? $options['type'] : '',
                    'model' => $model,
                    'required' => $required,
                    'attrs' => $attrs,
                );
                if ($fieldTag==='input')
                    $control['attrs']['type'] = 'text';
                elseif ($fieldTag==='select')
                    $control['options'] = (!empty($options['listData'])) ? $options['listData'] : array();

                if ($control['type']==='date')
                    $this->_dateFieldNames[] = $control['attrs']['name'];

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
                if (!isset($control['attrs']['type']) && $control['tag']==='input')
                    $control['attrs']['type'] = 'text';
                if (isset($control['disabled']))
                    $control['attrs']['class'] = 'disabled';
            }
            if (!isset($this->_options['labels']) || $this->_options['labels']!=='outer')
                 foreach ($params['controls'] as &$control) {
                    if ($control['tag']==='input') {
                        $ctrlVal = $control['label'];
                        $control['attrs']['value'] = $ctrlVal;
                        // обработчик для подписей в полях (чтобы исчезали)
                        $control['attrs']['onfocus'] = str_replace('capt_value', $ctrlVal, "if(this.value==='capt_value') this.value=''");
                        $control['attrs']['onblur'] = str_replace('capt_value', $ctrlVal, "if(this.value=='') this.value='capt_value'");
                    }
                    $control['label'] = '';
                }
                
            $controls = array_merge($controls, $params['controls']);
        }
		$elements = array();
        // напоминание об обязательных полях
		if ($requiredFields && !empty($this->_options['notice']))
			$elements[] = array(
                'tag' => 'div',
                'attrs' => array('class' => 'msg clear'),
                'contents' => $this->_options['text']['required']
            );
            
        $elements = array_merge($elements, compact('controls'));
        // кнопка submit
        $elements['submit'] = array(
            'attrs' => array(
                'type' => $this->_options['ajax'] ? 'button' : 'submit',
                'class' => 'button',
                'id' => "submit_$formId",
                'value' => $this->_options['submitCaption'],
            )
        );
        $elements['errors'] = array(
            'tag' => 'div',
            'attrs' => array(
                'class' => 'error',
                'id' => 'errors_list'
            ),
        );
        // csrf token
        if ($this->_csrfCheck)
            $elements['csrf'] = array(
                'attrs' => array(
                    'type' => 'hidden',
                    'id' => 'csrf',
                    'name' => $this->_tokenId,
                    'value' => $this->_tokenVal,
                ),
            );

        if ($this->_options['final'])
            $this->_outputScripts();

        $this->view->display($this->_options['view'], array(
            'form' => $this,
            'model' => isset($model) ? $model : null,
            'elements' => $elements,
            'attrs' => $this->_options['attrs']
        ));
        // включаем дэйтпикеры
        if (!empty($this->_dateFieldNames)) {
            $this->view->widget(array(
                'class' => 'Datepicker',
                'controller' => $this,
                'fieldNames' => $this->_dateFieldNames,
            ));
        }
        // включаем скрипт валидации
        if (
               !empty($params['model'])
            && !empty($params['fields'])
            && $this->_options['ajax']
        ) {
            $formHandler = isset($this->_options['formHandler']) ? $this->_options['formHandler'] : "dom.findById('$formId').submit();";
            echo $this->jsCode("
            dom.findById('submit_$formId').onclick = function() {
                validation.msgContainerId = 'errors_list';
                if (validation.run(" . $model->getValidationFieldsJs($params['fields']) . ")) {
                    $formHandler;
                }
                return false;
            };");
        }
	}

    /**
     * _outputScripts
     * Выводим скрипты и стили формы
     */
    private function _outputScripts()
    {
        $assetsPath = $this->getAssetsPath();
        ?>
        <link rel="stylesheet" href="<?=$assetsPath?>style.css">
        <!-- Скрипт валидации -->
        <?=\tachyon\helpers\AssetHelper::getCore("obj.js")?>
        <script type="text/javascript" src="<?=$assetsPath?>validation.js"></script>
        <?php
    }

    public function setCsrfCheck($csrfCheck)
    {
        $this->_csrfCheck = (bool)$csrfCheck;
    }

    public function getCsrfCheck()
    {
        return $this->_csrfCheck;
    }

    /**
     * Путь до ресурсов
     * 
     * @return string
     */
    public function getAssetsPath()
    {
        return '/assets/' . lcfirst($this->getClassName()) . '/';
    }

    public function getHtml()
    {
        return $this->html;
    }
}
