<?php
/**
 * Created for dbreader.
 * User: Angujo Barrack
 * Date: 2019-10-23
 * Time: 3:58 AM
 */

namespace Angujo\DBReader\Models;

/**
 * Class DBIndex
 * @package Angujo\DBReader\Models
 *
 * @property string   $schema_name
 * @property string   $table_name
 * @property string   $name
 * @property string   $column_name
 * @property string   $is_primary
 * @property string   $is_unique
 *
 * @property string   $table_reference
 * @property string   $column_reference
 * @property string   $reference
 *
 * @property Database $database
 * @property Schema   $schema
 * @property DBTable  $table
 */
class DBIndex extends PropertyReader
{
    public function __construct(array $details)
    {
        parent::__construct($details);
    }

    protected function database()
    {
        return Schema::get($this->schema_name)->database;
    }

    protected function schema()
    {
        return Schema::get($this->schema_name);
    }

    protected function table()
    {
        return Schema::getTable($this->schema_name, $this->table_name);
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