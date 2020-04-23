<?php


namespace Angujo\DBReader\Models;


/**
 * Class DBConstraint
 *
 * @package Angujo\DBReader\Models
 *
 * @property string     $name
 * @property string     $schema_name
 * @property string     $check_source
 * @property string     $table_name
 * @property string     $column_name
 * @property string     $table_reference
 * @property string     $column_reference
 * @property string[]   $column_references
 * @property string     $reference
 * @property boolean    $is_primary_key
 * @property boolean    $is_unique_key
 * @property boolean    $is_foreign_key
 * @property boolean    $is_check
 * @property boolean    $has_multiple_columns
 *
 * @property DBTable    $table
 * @property Schema     $schema
 * @property DBColumn   $column
 * @property DBColumn[] $columns
 */
class DBConstraint extends PropertyReader
{
    /** @var string[] */
    public $column_names = [];

    public function __construct(array $details)
    {
        parent::__construct($details);
        $this->column_names[] = $this->column_name;
    }

    protected function table()
    {
        return Schema::getTable($this->schema_name, $this->table_name);
    }

    protected function schema()
    {
        return Schema::get($this->schema_name);
    }

    public function addColumnName($column_name)
    {
        $this->column_names[] = $column_name;
        $this->column_names   = array_unique($this->column_names);
        return $this;
    }

    protected function has_multiple_columns()
    {
        return count($this->column_names) > 1;
    }

    protected function column()
    {
        return $this->table->getColumn($this->column_name);
    }

    protected function columns()
    {
        return array_map(function($cn){ return $this->table->getColumn($cn); }, $this->column_names);
    }

    public function table_reference()
    {
        return implode('.', [$this->schema_name, $this->table_name]);
    }

    public function column_references()
    {
        return array_map(function($cn){ return [$this->schema_name, $this->table_name, $cn]; }, $this->column_names);
    }

    public function column_reference()
    {
        return implode('.', [$this->schema_name, $this->table_name, $this->column_name]);
    }

    public function reference()
    {
        return implode('.', [$this->schema_name, $this->table_name, $this->column_name, $this->name]);
    }
}