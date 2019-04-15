<?php
include 'vendor/autoload.php';

use Angujo\DBReader\Drivers\Connection;

echo '<pre>';
$db=Connection::currentDatabase();

print_r($db->tables->all());