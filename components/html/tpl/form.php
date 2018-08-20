<?php 
/** @var tachyon\components\html\FormBuilder $form */
$html = $form->getHtml();
?>
<?php $this->display('errors', compact('model'))?>
<div class="form">
    <form method="<?=$attrs['method']?>" action="<?=$attrs['action']?>">
    <?php foreach ($elements['controls'] as $control) {?>
        <div class="row">
            <label><?=$control['label']?><?php if ($control['required']) {?>*<?php }?>:</label>
            <?=$html->{$control['tag']}($control)?>
        </div>
    <?php }?>
    <?=$html->input($elements['submit'])?>
    <div class="clear"></div>
    </form>
</div>