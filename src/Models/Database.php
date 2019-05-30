<?php


namespace Angujo\DBReader\Models;

use Angujo\DBReader\Drivers\Connection;

/**
 * Class Database
 *
 * @package Angujo\DBReader\Models
 *
 * @property string     $name   ;
 * @property DBTable[]  $tables ;
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
        if (!empty($this->attributes['tables'])) {
            return $this->attributes['tables'];
        }
        $tables = Connection::getTables($this);
        return $this->attributes['tables'] = (array_combine(array_map(function(DBTable $table){ return $table->name; }, $tables), $tables));
    }

    /**
     * @return DBColumn[]
     */
    protected function columns()
    {
        if (!empty($this->attributes['columns'])) {
            return $this->attributes['columns'];
        }
        $columns = Connection::getColumns($this->name);
        return $this->attributes['columns'] = (array_combine(array_map(function(DBColumn $column){ return $column->table_name.'.'.$column->name; }, $columns), $columns));
    }

    /**
     * @param null|string $table_name
     *
     * @param null        $key
     *
     * @param bool        $table_check
     *
     * @return ForeignKey[]|bool|null
     */
    public function foreignKeys($table_name = null, $key = null, $table_check = false)
    {
        if (is_string($table_name)) {
            if (!isset($this->attributes['foreign_keys_set']) || !in_array($table_name, $this->attributes['foreign_keys_set'])) {
                return $table_check ? null : [];
            }
            if ($table_check) {
                return true;
            }
            $determiner = $table_name.'.'.(null === $key ? '' : (int)$key.'.');
            return array_values(array_filter($this->attributes['foreign_keys'], function($k) use ($determiner){ return 0 === stripos($k, $determiner); }, ARRAY_FILTER_USE_KEY));
        }
        return null === $table_name ? $this->attributes['foreign_keys'] : [];
    }

    public function addForeignKey(ForeignKey $foreignKey)
    {
        if (!isset($this->attributes['foreign_keys_set']) || !in_array($foreignKey->table_name, $this->attributes['foreign_keys_set'])) {
            $this->attributes['foreign_keys_set'][] = $foreignKey->table_name;
        }
        $this->attributes['foreign_keys'][$foreignKey->table_name.'.'.(int)$foreignKey->isOneToOne().'.'.$foreignKey->name] = $foreignKey;
        return $this;
    }

    /**
     * @param string      $schema_name
     * @param null|string $table_name
     *
     * @return ForeignKey[]
     */
    public static function getForeignKeys($schema_name, $table_name = null)
    {
        return self::get($schema_name)->foreignKeys($table_name);
    }

    /**
     * @param string $schema_name
     * @param string $table_name
     *
     * @return DBColumn[]
     */
    public static function getColumns($schema_name, $table_name)
    {
        return array_filter(self::get($schema_name)->columns, function($key) use ($table_name){ return 0 === stripos($key, $table_name.'.'); }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param $schema_name
     * @param $table_name
     * @param $column_name
     *
     * @return null|DBColumn
     */
    public static function getColumn($schema_name, $table_name, $column_name)
    {
        $cols = array_filter(self::get($schema_name)->columns, function(DBColumn $column, $key) use ($table_name, $column_name){ return 0 === strcasecmp($key, $table_name.'.'.$column_name); });
        return !empty($cols) ? array_shift($cols) : null;
    }

    /**
     * @param $schema_name
     * @param $table_name
     *
     * @return DBTable|null
     */
    public static function getTable($schema_name, $table_name)
    {
        $tabs = array_filter(self::get($schema_name)->tables, function(DBTable $table) use ($table_name){ return 0 === strcasecmp($table->name, $table_name); });
        return !empty($tabs) ? array_shift($tabs) : null;
    }

    /**
     * @param $name
     *
     * @return Database
     */
    public static function get($name)
    {
        return isset(self::$me[$name]) ? self::$me[$name] : new self($name);
    }
}