<p><?=t('fieldsRequired')?></p>
<div id="errors" class="row">
    <?php if (count($model->getErrors()) > 0) {?>
        <h4><?=t('errorsToCorrect')?>: </h4>
        <?php foreach ($model->getErrors() as $key => $error) {?>
            <p class="error"><b><?=$model->getAttributeName($key)?></b>: <?=$error?></p>
        <?php }?>
    <?php }?>
</div>
