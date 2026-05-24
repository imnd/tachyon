<?php

namespace tachyon\components;

use tachyon\Config;
use tachyon\helpers\ClassHelper;
use tachyon\Model;

/**
 * HTML Code Builder
 *
 * @author imndsu@gmail.com
 */
class Html
{
    public function __construct(protected Config $config)
    {
    }

    public function div(array $options = [], string $type = 'dual'): string
    {
        return $this->tag('div', $options, $type);
    }

    # FORM

    public function formOpen(array $options = []): string
    {
        $options['attrs']['method'] = $options['method'] ?? null;
        return $this->tag('form', $options, 'open');
    }

    public function formClose(): string
    {
        return $this->tag('form', [], 'close');
    }

    public function form(array $options = []): string
    {
        return $this->tag('form', $options, 'dual');
    }

    public function textarea(array $options = []): string
    {
        $options = $this->_setOptions($options);
        return $this->tag('textarea', $options, 'dual');
    }

    public function submit(string $value = ''): string
    {
        return $this->input(
            [
                'attrs' => [
                    'type' => 'submit',
                    'value' => $value,
                ],
            ]
        );
    }

    public function button(string $value = ''): string
    {
        return $this->input(
            [
                'attrs' => [
                    'type' => 'button',
                    'value' => $value,
                ],
            ]
        );
    }

    public function hidden(array $options = []): string
    {
        $options = $this->_setOptions($options);
        $options['attrs']['type'] = 'hidden';
        return $this->input($options);
    }

    public function hiddenEx(Model $model, array $options = []): string
    {
        if (!is_array($options)) {
            $options = [
                'attrs' => [],
                'name' => $options,
            ];
        }
        if (!isset($options['attrs'])) {
            $options['attrs'] = [];
        }
        $options['attrs']['type'] = 'hidden';
        return $this->inputEx($model, $options);
    }

    public function label(string $text, string $for = ''): string
    {
        return $this->tag(
            'label',
            [
                'attrs' => compact('for'),
                'contents' => $text,
            ],
            'dual'
        );
    }

    public function labelEx(Model $model, string $for): string
    {
        return $this->tag(
            'label',
            [
                'attrs' => compact('for'),
                'contents' => $model->getAttributeName($for),
            ],
            'dual'
        );
    }

    public function error(Model $model, string $name): string
    {
        return $this->tag(
            'span',
            [
                'attrs' => ['class' => 'error'],
                'contents' => $model->getError($name),
            ],
            'dual'
        );
    }

    public function errorSummary(Model $model): string
    {
        return $this->tag(
            'span',
            [
                'attrs' => ['class' => 'error'],
                'contents' => $model->getErrorsSummary(),
            ],
            'dual'
        );
    }

    public function input(array $options = []): string
    {
        $options = $this->_setOptions($options);
        if (!empty($options['multiple'])) {
            $options['attrs']['name'] .= '[]';
        }
        if (isset($options['readonly'])) {
            $options['attrs']['readonly'] = 'readonly';
        }
        return $this->tag('input', $options, 'single');
    }

    public function inputEx(Model $model, array $options = []): string
    {
        [$options, $name] = $this->_check($options);
        if (!isset($options['value'])) {
            $options['value'] = $model->$name;
        }
        $options['attrs']['name'] = ClassHelper::getClassName($model) . "[$name]";
        if (!empty($options['multiple'])) {
            $options['attrs']['name'] .= '[]';
        }
        if (isset($options['readonly'])) {
            $options['attrs']['readonly'] = 'readonly';
        }
        return $this->tag('input', $options, 'single');
    }

    public function select(array $options = [], string $num = null): string
    {
        $options = $this->_setOptions($options, ['name']);
        $name = $options['attrs']['name'];
        if (!is_null($num)) {
            $options['attrs']['name'] .= "[$num]";
        }
        $contents = '';
        if (isset($options['options'])) {
            $name = str_replace('[]', '', $name);
            foreach ($options['options'] as $option) {
                if (
                       isset($options['model'])
                    && $options['model']->$name === $option['value']
                ) {
                    $option['attrs']['selected'] = 'selected';
                }
                $contents .= $this->tag('option', $option, 'dual');
            }
        }
        return $this->tag(
            'select',
            [
                'attrs' => $options['attrs'] ?? [],
                'contents' => $contents,
            ],
            'dual'
        );
    }

    public function selectEx(Model $model, array $options = []): string
    {
        [$options, $name] = $this->_check($options);
        $contents = '';
        if (isset($options['options'])) {
            foreach ($options['options'] as $option) {
                if ($model->$name === $option['value']) {
                    $option['attrs']['selected'] = 'selected';
                }
                $contents .= $this->tag('option', $option, 'dual');
            }
        }
        $options['attrs']['name'] = $model->getClassName() . "[$name]";
        if (!empty($options['multiple'])) {
            $options['attrs']['name'] .= '[]';
        }
        return $this->tag(
            'select',
            [
                'attrs' => $options['attrs'] ?? [],
                'contents' => $contents,
            ],
            'dual'
        );
    }

    public function checkbox(array $options = []): string
    {
        $options = $this->_setOptions($options);
        $options['attrs']['type'] = 'checkbox';
        if (!isset($options['value']) && isset($options['model'])) {
            $options['value'] = $options['model']->{$options['attrs']['name']};
        }
        if (isset($options['value']) && $options['value'] == 1) {
            $options['attrs']['checked'] = 'checked';
        }
        return $this->tag('input', $options, 'single');
    }

    private function _setOptions(array $options, array $attrs = ['name', 'value']): array
    {
        if (!is_array($options)) {
            $options = ['attrs' => ['name' => $options]];
        } elseif (!isset($options['attrs'])) {
            $options['attrs'] = [];
        }
        foreach ($attrs as $attr) {
            $options['attrs'][$attr] = $options['attrs'][$attr] ?? null;
        }
        return $options;
    }

    /**
     * returns html tag
     */
    public function tag(string $name, array $options, string $type): string
    {
        $output = '<';
        if ($type === 'close') {
            $output .= '/';
        }
        $output .= "$name ";
        $attrs = $options['attrs'] ?? [];
        foreach ($attrs as $key => $value) {
            $output .= " $key='" . htmlspecialchars($value) . "'";
        }
        if (isset($options['value'])) {
            $output .= " value='"  . htmlspecialchars($options['value']) . "'";
        }
        if ($type === 'dual') {
            $contents = $options['contents'] ?? ($options['attrs']['value'] ?? '');
            $output .= ">" . htmlspecialchars($contents) . "</$name>";
        } elseif ($type === 'single') {
            $output .= '/>';
        } else {
            $output .= '>';
        }
        if (!empty($options['template'])) {
            // initialize the path for display
            $this->owner->setViewsPath($this->config->get('base_path') . '/tachyon/components/formBuilder/tpl');
            $model = $options['model'];
            return $this->owner->display($options['template'], compact('output', 'attrs', 'model'), true);
        }
        return $output;
    }

    private function _check(array $options): array
    {
        if (is_array($options) && isset($options['name'])) {
            $name = $options['name'];
            unset($options['name']);
        } else {
            $name = $options;
            $options = [];
        }
        if (!isset($options['attrs'])) {
            $options['attrs'] = [];
        }
        return [$options, $name];
    }
}
