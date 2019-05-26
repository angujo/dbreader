<?php


namespace Angujo\DBReader\Models;

use Angujo\DBReader\Drivers\Connection;

/**
 * Class Database
 * @package Angujo\DBReader\Models
 *
 * @property string $name;
 * @property DBTable[] $tables;
 * @property DBColumn[] $columns;
 */
class Database extends PropertyReader
{
    /**
     * @var static[]
     */
    private static $me = [];

    public function __construct($name)
    {
        $this->attributes['name'] = $name;
        parent::__construct(['name' => $name]);
        self::$me[$name] = $this;
    }

    /**
     * @return DBTable[]
     */
    protected function tables()
    {
        if (!empty($this->attributes['tables'])) return $this->attributes['tables'];
        $tables = Connection::getTables($this);
        return $this->attributes['tables'] = (array_combine(array_map(function (DBTable $table) { return $table->name; }, $tables), $tables));
    }

    /**
     * @return DBColumn[]
     */
    protected function columns()
    {
        if (!empty($this->attributes['columns'])) return $this->attributes['columns'];
        $columns = Connection::getColumns($this->name);
        return $this->attributes['columns'] = (array_combine(array_map(function (DBColumn $column) { return $column->table_name . '.' . $column->name; }, $columns), $columns));
    }

    /**
     * @param string $schema_name
     * @param string $table_name
     * @return DBColumn[]
     */
    public static function getColumns($schema_name, $table_name)
    {
        return array_filter(self::get($schema_name)->columns, function (DBColumn $column, $key) use ($table_name) { return 0 === stripos($key, $table_name . '.'); });
    }

    /**
     * @param $schema_name
     * @param $table_name
     * @param $column_name
     * @return null|DBColumn
     */
    public static function getColumn($schema_name, $table_name, $column_name)
    {
        $cols = array_filter(self::get($schema_name)->columns, function (DBColumn $column, $key) use ($table_name, $column_name) { return 0 === strcasecmp($key, $table_name . '.' . $column_name); });
        return !empty($cols) ? array_shift($cols) : null;
    }

    /**
     * @param $schema_name
     * @param $table_name
     * @return DBTable|null
     */
    public static function getTable($schema_name, $table_name)
    {
        $tabs = array_filter(self::get($schema_name)->tables, function (DBTable $table) use ($table_name) { return 0 === strcasecmp($table->name, $table_name); });
        return !empty($tabs) ? array_shift($tabs) : null;
    }

    /**
     * @param $name
     * @return Database
     */
    public static function get($name)
    {
        return isset(self::$me[$name]) ? self::$me[$name] : new self($name);
    }
}