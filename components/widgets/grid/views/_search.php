<style>
    .search-form input.button {
        margin-bottom: 0 !important;
        margin-top: 10px;
    }
</style>
<?php
if (!empty($searchFields)) {
    $this->get('FormBuilder')
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
            'fieldValues' => $widget->getController()->getGet(),
        ));
}