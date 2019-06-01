<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\DBTable;

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

    protected function mapTables(array $tables)
    {
        return array_combine(array_map(function(DBTable $table){ return $table->schema_name.'.'.$table->name; }, $tables), $tables);
    }
}