<?php


namespace Angujo\DBReader\Models;

/**
 * Class ForeignKey
 * @package Angujo\DBReader\Models
 *
 * @property string   $table_schema
 * @property string   $name
 * @property string   $table_name
 * @property string   $column_name
 * @property string   $foreign_table_schema
 * @property string   $foreign_table_name
 * @property string   $foreign_column_name
 * @property string   $column_reference
 * @property string   $table_reference
 * @property bool     $unique_column
 *
 * @property DBTable  $table
 * @property DBTable  $foreign_table
 * @property DBColumn $column
 * @property DBColumn $foreign_column
 * @property Database $database
 * @property Database $foreign_database
 * @property Schema   $schema
 * @property Schema   $foreign_schema
 *
 */
class ForeignKey extends PropertyReader
{
    /**
     * Should only hold 1=One to one or 2=One to many
     * @var null|int
     */
    public $relation = null;

    public function __construct($details, $many = null)
    {
        parent::__construct($details);
        $this->relation = true === $many ? 2 : (false === $many ? 1 : null);
    }

    public function isOneToOne()
    {
        return $this->relation === 1;
    }

    public function isOneToMany()
    {
        return $this->relation === 2;
    }

    protected function database()
    {
        return $this->schema->database;
    }

    protected function foreign_database()
    {
        return $this->foreign_schema->database;
    }

    protected function schema()
    {
        return Schema::get($this->table_schema);
    }

    protected function foreign_schema()
    {
        return Schema::get($this->foreign_table_schema);
    }

    /**
     * @return DBTable
     */
    protected function table()
    {
        return Schema::getTable($this->table_schema, $this->table_name);
    }

    protected function foreign_table()
    {
        return Schema::getTable($this->foreign_table_schema, $this->foreign_table_name);
    }

    protected function column()
    {
        return Schema::getColumn($this->table_schema, $this->table_name, $this->column_name);
    }

    protected function foreign_column()
    {
        return Schema::getColumn($this->foreign_table_schema, $this->foreign_table_name, $this->foreign_column_name);
    }

    protected function column_reference() { return $this->table_schema.'.'.$this->table_name.'.'.$this->column_name; }

    protected function table_reference() { return $this->table_schema.'.'.$this->table_name; }
}