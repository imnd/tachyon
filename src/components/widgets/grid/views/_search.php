<style>
    .search-form input.button {
        margin-bottom: 0 !important;
        margin-top: 10px;
    }
</style>
<?php
use tachyon\{
    dic\Container,
    components\formBuilder\FormBuilder,
    Request
};

if (!empty($searchFields)) {
    app()->get(FormBuilder::class)
        ->build([
            'options' => [
                'action' => "/{$widget->getController()->getId()}",
                'class' => 'search-form',
                'submitCaption' => t('search'),
                'view' => 'searchForm',
                'viewsPath' => $widget->getViewPath(),
            ],
            'model' => $model,
            'fields' => $searchFields,
            'fieldValues' => $this->request->getGet(),
        ]);
}
