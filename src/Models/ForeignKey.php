<?php


namespace Angujo\DBReader\Models;

/**
 * Class ForeignKey
 * @package Angujo\DBReader\Models
 *
 * @property string table_schema
 * @property string name
 * @property string table_name
 * @property string column_name
 * @property string foreign_table_schema
 * @property string foreign_table_name
 * @property string foreign_column_name
 *
 * @property DBTable $table
 * @property DBTable $foreign_table
 * @property DBColumn $column
 * @property DBColumn $foreign_column
 * @property Database $database
 * @property Database $foreign_database
 *
 */
class ForeignKey extends PropertyReader
{
    public function __construct($details)
    {
        parent::__construct($details);
    }

    protected function database()
    {
        return Database::get($this->table_schema);
    }

    protected function foreign_database()
    {
        return Database::get($this->foreign_table_schema);
    }

    /**
     * @return DBTable
     */
    protected function table()
    {
        return Database::getTable($this->table_schema, $this->table_name);
    }

    protected function foreign_table()
    {
        return Database::getTable($this->foreign_table_schema, $this->foreign_table_name);
    }

    protected function column()
    {
        return Database::getColumn($this->table_schema, $this->table_name, $this->column_name);
    }

    protected function foreign_column()
    {
        return Database::getColumn($this->foreign_table_schema, $this->foreign_table_name, $this->foreign_column_name);
    }
}