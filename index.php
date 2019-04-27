<?php
include 'vendor/autoload.php';

use Angujo\DBReader\Drivers\Connection;
use Angujo\DBReader\Models\DBTable;

echo '<pre>';
$db = Connection::currentDatabase();

/*print_r($db->tables->take(5)->map(function (DBTable $table) {
    return $table->columns;
})->all());*/

print_r($db->tables->all());