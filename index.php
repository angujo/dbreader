<?php
include 'vendor/autoload.php';

use Angujo\DBReader\Drivers\Connection;

echo '<pre>';

print_r(Connection::getTables('public'));