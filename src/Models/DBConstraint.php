<?php


namespace Angujo\DBReader\Models;


/**
 * Class DBConstraint
 *
 * @package Angujo\DBReader\Models
 *
 * @property string   $name
 * @property string   $schema_name
 * @property string   $table_name
 * @property string   $column_name
 * @property string   $table_reference
 * @property string   $column_reference
 * @property string   $reference
 * @property boolean  $is_primary_key
 * @property boolean  $is_unique_key
 * @property boolean  $is_foreign_key
 *
 * @property DBTable  $table
 * @property Schema   $schema
 * @property DBColumn $column
 */
class DBConstraint extends PropertyReader
{
    public function __construct(array $details)
    {
        parent::__construct($details);
    }

    protected function table()
    {
        return Schema::getTable($this->schema_name, $this->table_name);
    }

    protected function schema()
    {
        return Schema::get($this->schema_name);
    }

    protected function column()
    {
        return $this->table->getColumn($this->column_name);
    }

    public function table_reference()
    {
        return implode('.', [$this->schema_name, $this->table_name]);
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