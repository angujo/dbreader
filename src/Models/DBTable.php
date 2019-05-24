<?php

namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\Connection;
use Tightenco\Collect\Support\Collection;

/**
 * Class DBTable
 * @package Angujo\DBReader\Models
 *
 * @property string $schema_name;
 * @property string $name;
 * @property string $query_name;
 * @property string $engine;
 * @property string $version;
 * @property string $row_format;
 * @property integer $table_rows;
 * @property integer $auto_increment;
 * @property boolean $is_table;
 * @property boolean $is_view;
 * @property boolean $has_schema;
 *
 * @property Database $database
 * @property ForeignKey[]|Collection $foreign_keys_one_to_one
 * @property ForeignKey[]|Collection $foreign_keys_one_to_many
 * @property DBColumn[]|Collection $columns
 * @property DBColumn[]|Collection $primary_columns
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
        return $this->columns()->filter(function (DBColumn $column) { return $column->is_primary; });
    }

    protected function database()
    {
        return Database::get($this->schema_name);
    }

    protected function foreign_keys_one_to_one()
    {
        return Connection::getReferencedForeignKeys($this->schema_name, $this->name);
    }

    protected function foreign_keys_one_to_many()
    {
        return Connection::getReferencingForeignKeys($this->schema_name, $this->name);
    }

    protected function columns()
    {
        return Database::getColumns($this->schema_name, $this->name);
    }

    protected function query_name()
    {
        return $this->schema_name . '.' . $this->name;
    }

    protected function schema_name()
    {
        return $this->getDetail('table_schema');
    }

    protected function name()
    {
        return $this->getDetail('table_name');
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
}