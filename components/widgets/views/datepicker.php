<?=
$this->owner->css("pikaday"),
$this->owner->js("moment"),
$this->owner->js("pikaday")
?>

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