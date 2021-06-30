<p><?=$this->i18n('fieldsRequired')?></p>
<div id="errors" class="row">
    <?php if (count($model->getErrors()) > 0) {?>
        <h4><?=$this->i18n('errorsToCorrect')?>: </h4>
        <?php foreach ($model->getErrors() as $key => $error) {?>
            <p class="error"><b><?=$model->getAttributeName($key)?></b>: <?=$error?></p>
        <?php }?>
    <?php }?>
</div>