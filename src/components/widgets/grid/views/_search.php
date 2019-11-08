<style>
    .search-form input.button {
        margin-bottom: 0 !important;
        margin-top: 10px;
    }
</style>
<?php
use tachyon\dic\Container,
    tachyon\components\html\FormBuilder;

if (!empty($searchFields)) {
    (new Container)->get(FormBuilder::class)
        ->build([
            'options' => [
                'action' => "/{$widget->getController()->getId()}",
                'class' => 'search-form',
                'submitCaption' => $this->i18n('search'),
                'view' => 'searchForm',
                'viewsPath' => $widget->getViewPath(),
            ],
            'model' => $model,
            'fields' => $searchFields,
            'fieldValues' => $widget->getController()->getGet(),
        ]);
}