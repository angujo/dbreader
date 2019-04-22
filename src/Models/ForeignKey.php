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
 * @property DBTable $table
 *
 */
class ForeignKey extends PropertyReader
{
    public function __construct($details)
    {
        parent::__construct($details);
    }

    /**
     * @return DBTable
     */
    protected function table()
    {
        if (isset($this->attributes['table'])) return $this->attributes['table'];
        return $this->attributes['table'] = Database::get($this->table_schema)->tables->first(function (DBTable $table) { return 0 === strcasecmp($this->table_name, $table->name); });
    }
}