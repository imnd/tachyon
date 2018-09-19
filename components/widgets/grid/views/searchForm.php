<?php 
/** @var tachyon\components\html\FormBuilder $form */
$html = $form->getHtml();
?>
<?=$html->div($elements['errors'])?>
<form class="<?=$attrs["class"]?>" method="<?=$attrs["method"]?>" id="<?=$attrs["id"]?>" <?php if (isset($attrs["action"])) {?>action="<?=$attrs["action"]?>"<?php }?>>
    <?php foreach ($elements['controls'] as $key => $control) {?>
        <div class="control">
            <label><?=$control['label']?>:</label>
            <?=$html->{$control['tag']}($control)?>
        </div>
    <?php }
    echo $html->input($elements['submit']);
    if ($form->getCsrfCheck())
        echo $html->input($elements['csrf']);
    ?>
    <div class="clear"></div>
</form>