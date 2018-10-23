<?php /** @var \tachyon\components\widgets\Widget $widget */?>
<link rel="stylesheet" href="<?=$widget->getAssetsPath()?>style.css" type="text/css" media="screen"/>
<?php
// search form
$this->display('_search', compact('model', 'searchFields', 'widget'));

if (empty($items)) {?>
    <p>Список пуст</p>
<?php } else {?>
<table class="data-grid" id="<?=$widget->getController()->getId()?>">
    <thead>
    <tr>
        <?php
        $columnNames = array();
        $tableFields = $model->getTableFields();
        foreach ($columns as $key => $options) {
            if (is_numeric($key)) {
                if (is_array($options)) {
                    if (isset($options['name']))
                        $fieldName = $options['name'];
                    else
                        throw new \Exception('Не определено имя поля');
                } else
                    $fieldName = $options;
            } else
                $fieldName = $key;

            $sortable = in_array($fieldName, $tableFields);
            if ($sortable)
                $columnNames[] = $fieldName;
        ?>
        <th id="<?=$fieldName?>"<?php if ($sortable) {?> class="sortable-column"<?php }?>><?=$model->getAttributeName($fieldName)?></th>
        <?php }
        if (!empty($buttons)) {?>
            <th class="buttons" colspan="<?=count($buttons)?>">Операции</th>
        <?php }?>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($items as $item) {?>
    <tr id="<?=$widget->getRowId($item)?>" class="editable">
        <?php foreach ($columns as $key => $value) {
            if (is_numeric($key)) {?>
                <td><?=$item[$value]?></td>
            <?php } else {?>
                <td><?=eval("return $value;")?></td>
            <?php }
        }
        // кнопки
        foreach ($buttons as $button) {
            $button['htmlOptions']['id'] = $widget->getBtnId($button['action'], $item);
            if (empty($button['htmlOptions']['href']))
                $button['htmlOptions']['href'] = $widget->getActionUrl($button['action'], $item);
            
            $button['htmlOptions']['href'] = eval('return "' . $button['htmlOptions']['href'] . '";');
            ?>
            <td><a <?php foreach ($button['htmlOptions'] as $key=> $value) {echo $key?>="<?=$value?>" <?php }?>>
                <?php if ($button['captioned']) {echo $button['htmlOptions']['title'];}?>
            </a></td>
        <?php
        }
        foreach ($sumFields as $sumField)
            $sumArr[$sumField] += $item[$sumField];
        ?>
    </tr>
    <?php
    }
    if (!empty($sumFields)) {
    ?>
    <tr>
        <td colspan="<?=count($columns)?>"><div style="float: left;"><b>Всего записей:</b> <?=count($items)?></div><div style="float: right;"><b>Итого:</b></div></td>
    </tr>
    <tr>
        <?php foreach ($columns as $column) {?>
            <td><?php if (in_array($column, $sumFields)) {echo $sumArr[$column];}?></td>
        <?php }?>
    </tr>
    <?php }?>
    </tbody>
</table>

<?php $this->display('_btnHandler', compact('buttons', 'items', 'csrfJson', 'widget'))?>

<?=\tachyon\helpers\AssetHelper::getCore("ajax.js")?>
<script type="text/javascript" src="<?=$widget->getAssetsPath()?>sort.js"></script>
<script>
    // включаем обработчики
    window.onload = function() {
        bindSortHandlers("<?=$widget->getActionUrl('index')?>", <?=json_encode($widget->getColumns())?>, "<?=$widget->getId()?>");
    };
</script>
<?php }?>
