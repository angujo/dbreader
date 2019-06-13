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
 * @property Database   $database
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

    /**
     * @param null|string $table_name
     * @param null        $key
     * @param bool        $table_check
     *
     * @return ForeignKey[]|bool|null
     * @throws ReaderException
     */
    public function foreign_keys($table_name = null, $key = null, $table_check = false)
    {
        if (!isset($this->attributes['foreign_keys'])) {
            $this->attributes['foreign_keys'] = Connection::getForeignKeys($this->name);
        }
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
        return $this->attributes['foreign_keys'] = null === $table_name && isset($this->attributes['foreign_keys']) ? $this->attributes['foreign_keys'] : [];
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
     * @param null        $db_name
     *
     * @return ForeignKey[]
     * @throws ReaderException
     */
    public static function getForeignKeys($schema_name, $table_name = null, $db_name = null)
    {
        return self::get($schema_name, $db_name)->foreign_keys($table_name);
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
        return array_filter(self::get($schema_name, $db_name)->columns, function($key) use ($table_name){ return 0 === stripos($key, $table_name.'.'); }, ARRAY_FILTER_USE_KEY);
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
        $cols = array_filter(self::get($schema_name, $db_name)->columns, function($key) use ($table_name, $column_name){ return 0 === strcasecmp($key, $table_name.'.'.$column_name); }, ARRAY_FILTER_USE_KEY);
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
        $tabs = array_filter(self::get($schema_name, $db_name)->tables, function(DBTable $table) use ($table_name){ return 0 === strcasecmp($table->name, $table_name); });
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