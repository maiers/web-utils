<!DOCTYPE html>
<?php 

    $lang = "en";
    $keys = parse_ini_file("../i18n/lang_$lang.ini");

    function _($key) {
        global $keys;
        if (array_key_exists($key, $keys)) {
            return $keys[$key];
        }
        return "__$key";
    }

?>
<html lang="<?= $lang ?>">
    <head>
        <title>TODO supply a title</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <ul>
            <li><?= _("lang_test_one") ?></li>
            <li><?= _("lang_test_de_only") ?></li>
            <li><?= _("lang_test_en_only") ?></li>
            <li><?= _("does_not_exist") ?></li>
        </ul>
    </body>
</html>
