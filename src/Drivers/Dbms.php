<?php


namespace Angujo\DBReader\Drivers;


interface Dbms
{
    public function getDatabases();

    public function getTables($db_name);

    public function getColumns($db_name, $table_name);
}