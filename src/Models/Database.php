<?php


namespace Angujo\DBReader\Models;

use Angujo\DBReader\Drivers\Connection;
use Tightenco\Collect\Support\Collection;

/**
 * Class Database
 * @package Angujo\DBReader\Models
 *
 * @property string $name;
 * @property DBTable[]|Collection $tables;
 * @property DBColumn[]|Collection $columns;
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
     * @return DBTable[]|Collection
     */
    protected function tables()
    {
        if (!empty($this->attributes['tables'])) return $this->attributes['tables'];
        $tables = Connection::getTables($this);
        return $this->attributes['tables'] = collect(array_combine($tables->pluck('name')->all(), $tables->all()));
    }

    /**
     * @return DBColumn[]|Collection
     */
    protected function columns()
    {
        if (!empty($this->attributes['columns'])) return $this->attributes['columns'];
        $columns = Connection::getColumns($this->name);
        return $this->attributes['columns'] = collect(array_combine($columns->map(function (DBColumn $column) { return $column->table_name . '.' . $column->name; })->all(), $columns->all()));
    }

    /**
     * @param string $schema_name
     * @param string $table_name
     * @return Collection|DBColumn[]
     */
    public static function getColumns($schema_name, $table_name)
    {
        return self::get($schema_name)->columns->filter(function (DBColumn $column, $key) use ($table_name) { return 0 === stripos($key, $table_name . '.'); });
    }

    /**
     * @param $schema_name
     * @param $table_name
     * @param $column_name
     * @return null|DBColumn
     */
    public static function getColumn($schema_name, $table_name, $column_name)
    {
        return self::get($schema_name)->columns->first(function (DBColumn $column, $key) use ($table_name, $column_name) { return 0 === strcasecmp($key, $table_name . '.' . $column_name); });
    }

    /**
     * @param $schema_name
     * @param $table_name
     * @return DBTable|null
     */
    public static function getTable($schema_name, $table_name)
    {
        return self::get($schema_name)->tables->first(function (DBTable $table) use ($table_name) { return 0 === strcasecmp($table->name, $table_name); });
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