<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBConstraint;
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

    /**
     * Constraints can have more than one column.
     * We need to ensure the columns are merged to one constraint
     *
     * @param DBConstraint[] $constraints
     *
     * @return DBConstraint[]
     */
    protected function mergeConstraints($constraints)
    {
        /** @var DBConstraint[] $tmp */
        $tmp = [];
        foreach ($constraints as $constraint) {
            $ref = "{$constraint->table_reference}.{$constraint->name}";
            if (isset($tmp[$ref])) {
                foreach ($tmp[$ref] as $_constraint) {
                    /** @var DBConstraint $_constraint */
                    $_constraint->addColumnName($constraint->column_name);
                    $constraint->addColumnName($_constraint->column_name);
                }
            }
            $tmp[$ref][] = $constraint;
        }
        $return=[];
        array_walk_recursive($tmp, function($a) use (&$return) { $return[] = $a; });
        return $return;
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