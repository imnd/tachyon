<?php
if (!empty($searchFields)) {
    tachyon\dic\Container::getInstanceOf('FormBuilder')
        ->build(array(
            'options' => array(
                'action' => "/{$widget->getController()->getId()}",
                'class' => 'search-form',
                'submitCaption' => $this->i18n('search'),
                'view' => 'searchForm',
                'viewPath' => $widget->getViewPath(),
            ),
            'model' => $model,
            'fields' => $searchFields,
            'fieldValues' => $widget->getController()->getQuery('get'),
        ));
}