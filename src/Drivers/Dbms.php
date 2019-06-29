<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;
use Angujo\DBReader\Models\Schema;

/**
 * Class Dbms
 *
 * @package Angujo\DBReader\Drivers
 *
 */
abstract class Dbms implements DbmsInterface
{
    /**
     * @var \PDO
     */
    protected $connection;
    /**
     * @var Database
     */
    protected $current_db;
    /**
     * @var Database[]
     */
    protected $databases = [];
    /**
     * @var DBTable
     */
    protected $tables = [];
    /**
     * @var DBColumn[]
     */
    protected $columns = [];

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    protected function mapTables(array $tables)
    {
        return array_combine(array_map(function (DBTable $table) { return $table->schema_name.'.'.$table->name; }, $tables), $tables);
    }

    protected function mapColumns(array $columns)
    {
        return array_combine(array_map(function (DBColumn $column) { return implode('.', [$column->schema_name, $column->table_name, $column->name]); }, $columns), $columns);
    }

    protected function mapForeignKeys(array $keys)
    {
        return array_combine(array_map(function (ForeignKey $foreignKey) { return implode('.', [$foreignKey->schema_name, $foreignKey->table_name, $foreignKey->name]); }, $keys), $keys);
    }

    public function changeDatabase($db_name)
    {
        $this->current_db = new Database($db_name);
        return $this->current_db;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas()
    {
        return [new Schema($this->currentDatabase(true), $this->currentDatabase(true))];
    }
}