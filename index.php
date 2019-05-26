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
    //$table->with(['columns']);
    return array_map(function ( $column) { return $column; },array_merge($table->foreign_keys_one_to_one,$table->foreign_keys_one_to_many));
}, $db->tables));