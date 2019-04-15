<?php


namespace Angujo\DBReader\Drivers;


abstract class Dbms implements DbmsInterface
{
    protected $connection;
    protected $current_db;
    protected $databases = [];
    protected $tables = [];
    protected $columns = [];

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }
}