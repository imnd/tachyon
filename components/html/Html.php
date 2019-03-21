<?php
namespace tachyon\components\html;

use tachyon\Config;

/**
 * Построитель html-кода
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Html
{
    /**
     * @var tachyon\Config $config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function div($options=array(), $type='dual')
    {
        return $this->tag('div', $options, $type);
    }

    # FORM

    public function formOpen($options=array())
    {
        $options['attrs']['method'] = $options['method'] ?? null;
        return $this->tag('form', $options, 'open');
    }
    
    public function formClose()
    {
        return $this->tag('form', array(), 'close');
    }
    
    public function form($options=array())
    {
        return $this->tag('form', $options, 'dual');
    }

    public function textarea($options=array())
    {
        $options = $this->_setOptions($options);
        return $this->tag('textarea', $options, 'dual');
    }

    public function submit($value='')
    {
        return $this->input(array('attrs' => array('type' => 'submit', 'value' => $value)));
    }

    public function button($value='')
    {
        return $this->input(array('attrs' => array('type' => 'button', 'value' => $value)));
    }

    public function hidden($options=array())
    {
        $options = $this->_setOptions($options);
        $options['attrs']['type'] = 'hidden';
        return $this->input($options);
    }
    
    public function hiddenEx($model, $options=array())
    {
        if (!is_array($options))
            $options = array(
                'attrs' => array(),
                'name' => $options,
            );
        if (!isset($options['attrs']))
            $options['attrs'] = array();
        
        $options['attrs']['type'] = 'hidden';
        return $this->inputEx($model, $options);
    }

    public function label($text, $for='')
    {
        return $this->tag('label', array(
            'attrs' => compact('for'),
            'contents' => $text
        ), 'dual');
    }

    public function labelEx($model, $for)
    {
        return $this->tag('label', array(
            'attrs' => compact('for'),
            'contents' => $model->getAttributeName($for)
        ), 'dual');
    }

    public function error($model, $name)
    {
        return $this->tag('span', array(
            'attrs' => array('class' => 'error'),
            'contents' => $model->getError($name)
        ), 'dual');
    }

    public function errorSummary($model)
    {
        return $this->tag('span', array(
            'attrs' => array('class' => 'error'),
            'contents' => $model->getErrorsSummary()
        ), 'dual');
    }

    public function input($options=array())
    {
        $options = $this->_setOptions($options);

        if (!empty($options['multiple']))
            $options['attrs']['name'] .= '[]';

        if (isset($options['readonly']))
            $options['attrs']['readonly'] = 'readonly';

        return $this->tag('input', $options, 'single');
    }
    
    public function inputEx($model, $options=array())
    {
        if (is_array($options) && isset($options['name'])) {
            $name = $options['name'];
            unset($options['name']);
        } else {
            $name = $options;
            $options = array();
        }
        if (!isset($options['attrs']))
            $options['attrs'] = array();

        if (!isset($options['value']))
            $options['value'] = $model->$name;

        $options['attrs']['name'] = $model->getClassName() . "[$name]";
        if (!empty($options['multiple']))
            $options['attrs']['name'] .= '[]';

        if (isset($options['readonly']))
            $options['attrs']['readonly'] = 'readonly';

        return $this->tag('input', $options, 'single');
    }

    public function select($options=array(), $num=null)
    {
        $options = $this->_setOptions($options, array('name'));

        $name = $options['attrs']['name'];

        if (!is_null($num))
            $options['attrs']['name'] .= "[$num]";

        $contents = '';
        if (isset($options['options'])) {
            $name = str_replace('[]', '' , $name);
            foreach ($options['options'] as $option) {
                if (isset($options['model']))
                    if ($options['model']->$name===$option['value'])
                        $option['attrs']['selected'] = 'selected';

                $contents .= $this->tag('option', $option, 'dual');
            }
        }
        return $this->tag('select', array(
            'attrs' => isset($options['attrs']) ? $options['attrs'] : array(),
            'contents' => $contents,
        ), 'dual');
    }

    public function selectEx($model, $options=array())
    {
        if (is_array($options) && isset($options['name'])) {
            $name = $options['name'];
            unset($options['name']);
        } else {
            $name = $options;
            $options = array();
        }
        if (!isset($options['attrs']))
            $options['attrs'] = array();

        $contents = '';
        if (isset($options['options'])) {
            foreach ($options['options'] as $option) {
                if ($model->$name===$option['value'])
                    $option['attrs']['selected'] = 'selected';

                $contents .= $this->tag('option', $option, 'dual');
            }
        }
        $options['attrs']['name'] = $model->getClassName() . "[$name]";
        if (!empty($options['multiple']))
            $options['attrs']['name'] .= '[]';

        return $this->tag('select', array(
            'attrs' =>isset($options['attrs']) ? $options['attrs'] : array(),
            'contents' => $contents,
        ), 'dual');
    }

    /**
     * checkbox
     * 
     * @param $options array 
     * @return string
     */
    public function checkbox($options=array())
    {
        $options = $this->_setOptions($options);
        $options['attrs']['type'] = 'checkbox';
        
        if (!isset($options['value']) && isset($options['model']))
            $options['value'] = $options['model']->{$options['attrs']['name']};

        if (isset($options['value']) && $options['value']==1)
            $options['attrs']['checked'] = 'checked';

        return $this->tag('input', $options, 'single');
    }

    /**
     * _setOptions
     * 
     * @param $options array 
     * @return array
     */
    private function _setOptions($options, $attrs=array('name', 'value'))
    {
        if (!is_array($options))
            $options = array('attrs' => array('name' => $options));
        elseif (!isset($options['attrs']))
            $options['attrs'] = array();

        foreach ($attrs as $attr)
            $options['attrs'][$attr] = $options['attrs'][$attr] ?? null;

        return $options;
    }

    /**
     * tag
     * возвращает html-тэг
     * 
     * @param $name string
     * @param $options array
     * @param $type string dual | single | open | close
     * @return string
     */
    public function tag($name, $options, $type)
    {
        $output = '<';
        if ($type==='close')
            $output .= '/';

        $output .= "$name ";
        
        $attrs = isset($options['attrs']) ? $options['attrs'] : array();
        foreach ($attrs as $key => $value)
            $output .= " $key=\"$value\"";

        if (isset($options['value']))
            $output .= " value=\"{$options['value']}\"";

        if ($type==='dual') {
            $contents = isset($options['contents']) ? $options['contents'] : (isset($options['attrs']['value']) ? $options['attrs']['value'] : '');
            $output .= ">$contents</$name>";
        } elseif ($type==='single')
            $output .= '/>';
        else
            $output .= '>';
        
        if (!empty($options['template'])) {
            // инициализируем путь для отображения
            $this->owner->setViewsPath($this->config->get('base_path') . '/tachyon/components/html/tpl');
            $model = $options['model'];
            return $this->owner->display($options['template'], compact('output', 'attrs', 'model'), true);
        }
        return $output;
    }
}
