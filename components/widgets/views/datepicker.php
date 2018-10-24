<link rel="stylesheet" href="<?=$assetsPath?>css/pikaday.css">
<script src="<?=$assetsPath?>moment.js"></script>
<script src="<?=$assetsPath?>pikaday.js"></script>
<script>
    <?php foreach ($fieldNames as $fieldName) {?>
        var <?=$id?> = new Pikaday({
            field: dom.findByName("<?=$fieldName?>"),
            format: "<?=$format?>",
            firstDay: 1,
            minDate: new Date(2000, 0, 1),
            maxDate: new Date(2020, 12, 31),
            yearRange: [2000, 2020],
        });
    <?php }?>
</script>