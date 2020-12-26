<?php

include 'exportPsqlToMySQL.class.php';

$export = new exportPsqlToMySQL();
print_r($export->export());
echo PHP_EOL;