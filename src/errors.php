<style>
    body {
        font-family: sans-serif;
        padding: 30px;
    }
    .error {
        padding: 8px 12px;
        color: #FFF;
        background-color: #FF0000;
        width: fit-content;
    }
    table {
        border-collapse: collapse;
        color: #AAA;
    }
    th {
        background: #D0D0D0;
        color: #000;
        text-align: center !important;
        font-weight: bold;
    }
    td, th {
        border: 1px solid #D0D0D0;
        height: 16px;
        padding: 4px 8px;
    }
    td {
        background: #F7F8FA;
    }
    .app td {
        color: #000;
        background: #FFF;
    }
</style>

<?php /** @var $e */?>

<p class="error">
    Error <?=$e->getCode()?>: <?=$e->getMessage()?>
</p>

<h3>Stack trace:</h3>
<table>
    <tr class="heading">
        <th>File</th>
        <th>Line</th>
        <th>Method</th>
    </tr>
    <?php foreach ($e->getTrace() as $item) {
        if (isset($item['class']) && strpos($item['class'], 'app\\') !== false ) {
            $class = 'app';
        } else {
            $class = '';
        }
        ?>
        <tr class="<?=$class?>">
            <td><?=$item['file']?></td>
            <td><?=$item['line']?>&nbsp;&nbsp;</td>
            <td><?=(isset($item['class']) ? $item['class'] . '::' : '')?><?=$item['function']?>()</td>
        </tr>
    <?php }?>
</table>
