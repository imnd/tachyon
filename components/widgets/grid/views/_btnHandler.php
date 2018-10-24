<?=$this->assetManager->coreJs("ajax")?>
<?php
foreach ($buttons as $key => $button) {
    $action = $button['action'];
    if (in_array($action, array('delete'))) {
        if (!isset($button['options']['callback']))
            $button['options']['callback'] = 'remove';
        if (!isset($button['options']['type']))
            $button['options']['type'] = 'ajax';
    }
    if (isset($button['options']['type']) && $button['options']['type']==='ajax') {
        $confirmMsgs = $widget->getConfirmMsgs();
        $confirmMsg = isset($button['options']['confirmMsg']) ? $button['options']['confirmMsg'] : isset($confirmMsgs[$action]) ? $confirmMsgs[$action] : 'уверены?';
        foreach ($items as $item) {
        ?>
        <script>
            dom.findById('<?=$widget->getBtnId($action, $item)?>').addEventListener("click", function(e) {
                e.preventDefault();
                if (confirm("<?=$confirmMsg?>")!==true)
                    return false;

                ajax.post(
                    '<?=$widget->getActionUrl($action, $item)?>',
                    {<?=$csrfJson?>},
                    function(data) {
                        if (data.success==true) {
                            <?php if (isset($button['options']['callback'])) {?>
                                dom.findById('<?=$widget->getRowId($item)?>').<?=$button['options']['callback']?>();
                            <?php }
                            if (!empty($widget->callback)) {
                                echo $widget->callback . ';';
                            }?>
                        }
                    }
                );
                return false;
            });
        </script>
        <?php 
        }
    }
}
