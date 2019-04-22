<?php
include 'vendor/autoload.php';

use Angujo\DBReader\Drivers\Connection;

echo '<pre>';
$db = Connection::currentDatabase();

print_r($db->tables->take(5)->map(function (\Angujo\DBReader\Models\DBTable $table) {
   return $table->with(['foreign_keys_one_to_one','foreign_keys_one_to_many','database']);
})->all());