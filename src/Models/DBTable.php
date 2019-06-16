<?php

namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\Connection;
use Angujo\DBReader\Drivers\ReaderException;

/**
 * Class DBTable
 *
 * @package Angujo\DBReader\Models
 *
 * @property string       $schema_name
 * @property string       $name
 * @property string       $db_name
 * @property string       $schema_naming
 * @property string       $query_name
 * @property string       $engine
 * @property string       $version
 * @property string       $reference
 * @property string       $row_format
 * @property integer      $table_rows
 * @property integer      $auto_increment
 * @property boolean      $is_table
 * @property boolean      $is_view
 * @property boolean      $has_schema
 *
 * @property Schema       $schema
 * @property ForeignKey[] $foreign_keys
 * @property ForeignKey[] $foreign_keys_one_to_one
 * @property ForeignKey[] $foreign_keys_one_to_many
 * @property DBColumn[]   $columns
 * @property DBColumn[]   $primary_columns
 */
class DBTable extends PropertyReader
{
    public function __construct(array $details, $withSchema = false)
    {
        $details['has_schema'] = (bool)$withSchema;
        parent::__construct($details);
    }

    protected function primary_columns()
    {
        return array_filter($this->columns(), function (DBColumn $column) { return $column->is_primary; });
    }

    protected function database()
    {
        return Database::get($this->db_name);
    }

    protected function schema()
    {
        return $this->database()->getSchema($this->schema_name);
    }

    /**
     * @return ForeignKey[]
     */
    protected function foreign_keys_one_to_one()
    {
        return array_filter($this->foreign_keys, function (ForeignKey $foreignKey) { return $foreignKey->isOneToOne(); });
    }

    /**
     * @return ForeignKey[]
     */
    protected function foreign_keys()
    {
        return $this->schema->getTableForeignKeys($this);
    }

    /**
     * @return ForeignKey[]
     */
    protected function foreign_keys_one_to_many()
    {
        return array_filter($this->foreign_keys, function (ForeignKey $foreignKey) { return $foreignKey->isOneToMany(); });
    }

    protected function columns()
    {
        return array_filter($this->schema->columns, function (DBColumn $column) {
            return 0 === strcasecmp($this->schema_name, $column->schema_name) && 0 === strcasecmp($this->name, $column->table_name);
        });
    }

    protected function query_name()
    {
        return $this->schema_name.'.'.$this->name;
    }

    protected function schema_name()
    {
        return $this->getDetail('table_schema');
    }

    protected function name()
    {
        return $this->getDetail('table_name');
    }

    protected function schema_naming()
    {
        return $this->schema_name.'.'.$this->name;
    }

    protected function reference()
    {
        return $this->schema_naming;
    }

    protected function engine()
    {
        return $this->getDetail('engine');
    }

    protected function version()
    {
        return $this->getDetail('version');
    }

    protected function row_format()
    {
        return $this->getDetail('row_format');
    }

    protected function table_rows()
    {
        return $this->getDetail('table_rows');
    }

    protected function auto_increment()
    {
        return $this->getDetail('auto_increment');
    }

    protected function is_table()
    {
        return 0 === strcasecmp('base table', $this->getDetail('table_type'));
    }

    protected function is_view()
    {
        return 0 === strcasecmp('view', $this->getDetail('table_type'));
    }

    /**
     * @param $name
     *
     * @return DBColumn|null
     */
    public function getColumn($name)
    {
        return count($cols = array_filter($this->columns, function (DBColumn $column) use ($name) { return 0 === strcasecmp($name, $column->name); })) ? array_shift($cols) : null;
    }

    /**
     * @param $name
     *
     * @return ForeignKey|null
     */
    public function getForeignKey($name)
    {
        return count($keys = array_filter($this->foreign_keys, function (ForeignKey $foreignKey) use ($name) { return 0 === strcasecmp($name, $foreignKey->name); })) ? array_shift($keys) : null;
    }
}