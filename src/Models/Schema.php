<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\Connection;
use Angujo\DBReader\Drivers\ReaderException;

/**
 * Class Schema
 *
 * @package Angujo\DBReader\Models
 *
 * @property string|null  $db_name
 * @property string       $name
 * @property DBTable[]    $tables
 * @property DBColumn[]   $columns
 * @property Database     $database
 * @property ForeignKey[] $foreign_keys
 */
class Schema extends PropertyReader
{

    private static $me = [];

    public function __construct($name, $db_name)
    {
        parent::__construct(['name' => $name, 'db_name' => $db_name]);
        self::$me[$db_name.'.'.$name] = $this;
    }

    /**
     * @return DBTable[]
     */
    protected function tables()
    {
        if (!empty($this->attributes['tables'])) {
            return $this->attributes['tables'];
        }
        return $this->attributes['tables'] = Connection::getTables($this->name);
    }

    /**
     * @return DBColumn[]
     */
    protected function columns()
    {
        if (!empty($this->attributes['columns'])) {
            return $this->attributes['columns'];
        }
        return $this->attributes['columns'] = Connection::getColumns($this->name);
    }

    public function getTableForeignKeys(DBTable$table)
    {
        return array_filter($this->foreign_keys, function (ForeignKey $foreignKey) use ($table) { return 0 === strcasecmp($foreignKey->table_reference, $table->reference); });
    }

    public function getColumnForeignKeys(DBColumn $column)
    {
        return array_filter($this->foreign_keys, function (ForeignKey $foreignKey) use ($column) { return 0 === strcasecmp($foreignKey->column_reference, $column->reference); });
    }

    /**
     * @return ForeignKey[]|bool|null
     * @throws ReaderException
     */
    protected function foreign_keys()
    {
        return $this->attributes['foreign_keys'] =$this->attributes['foreign_keys']?? Connection::getForeignKeys($this->name);
    }


    /**
     * @param string $schema_name
     * @param string $table_name
     *
     * @param null   $db_name
     *
     * @return DBColumn[]
     */
    public static function getColumns($schema_name, $table_name, $db_name = null)
    {
        return array_filter(self::get($schema_name, $db_name)->columns, function ($key) use ($table_name) { return 0 === stripos($key, $table_name.'.'); }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param      $schema_name
     * @param      $table_name
     * @param      $column_name
     *
     * @param null $db_name
     *
     * @return null|DBColumn
     */
    public static function getColumn($schema_name, $table_name, $column_name, $db_name = null)
    {
        $cols = array_filter(self::get($schema_name, $db_name)->columns, function ($key) use ($table_name, $column_name, $schema_name) { return 0 === strcasecmp($key, $schema_name.'.'.$table_name.'.'.$column_name); }, ARRAY_FILTER_USE_KEY);
        return !empty($cols) ? array_shift($cols) : null;
    }

    /**
     * @param      $schema_name
     * @param      $table_name
     *
     * @param null $db_name
     *
     * @return DBTable|null
     */
    public static function getTable($schema_name, $table_name, $db_name = null)
    {
        $tabs = array_filter(self::get($schema_name, $db_name)->tables, function (DBTable $table) use ($table_name) { return 0 === strcasecmp($table->name, $table_name); });
        return !empty($tabs) ? array_shift($tabs) : null;
    }

    /**
     * @param      $name
     *
     * @param null $db_name
     *
     * @return Schema
     */
    public static function get($name, $db_name = null)
    {
        $db_name = $db_name ?: Connection::currentDatabase();
        return isset(self::$me[$db_name.'.'.$name]) ? self::$me[$db_name.'.'.$name] : new self($name, $db_name);
    }

    protected function database()
    {
        return Database::get($this->db_name);
    }

    public function __toString()
    {
        return $this->db_name.'.'.$this->name;
    }
}