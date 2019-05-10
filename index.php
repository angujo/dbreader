<?php
include 'vendor/autoload.php';

use Angujo\DBReader\Drivers\Connection;
use Angujo\DBReader\Models\DBTable;

echo '<pre>';
$db = Connection::currentDatabase();

/*print_r($db->tables->take(5)->map(function (DBTable $table) {
    return $table->columns;
})->all());*/

print_r(array_map(function (DBTable $table) {
    $table->with(['columns']);
    return $table->columns->map(function (\Angujo\DBReader\Models\DBColumn $column) { return $column->data_type; });
}, $db->tables->all()));