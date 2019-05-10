<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\DataType;

/**
 * Class DBColumn
 * @package Angujo\DBReader\Models
 *
 * @property string $schema_name
 * @property string $table_name
 * @property string $name
 * @property int $ordinal_position
 * @property string|double|int $default
 * @property boolean $is_nullable
 * @property null|int $character_maximum_length
 * @property null|int $character_octet_length
 * @property null|int $numeric_precision
 * @property null|int $numeric_scale
 * @property string $datetime_precision
 * @property string $charset
 * @property string $collation_name
 * @property string $column_type
 * @property string $column_key
 * @property string $extra
 * @property string $comment
 * @property string $generation_expression
 * @property boolean $is_primary
 * @property boolean $is_auto_increment
 * @property int $decimal_places
 *
 * @property DataType $data_type
 * @property DBTable $table
 * @property Database $database
 */
class DBColumn extends PropertyReader
{
    public function __construct(array $details)
    {
        parent::__construct($details);
    }

    public function database()
    {
        return Database::get($this->schema_name);
    }

    public function table()
    {
        return Database::getTable($this->schema_name, $this->table_name);
    }

    /**
     * @return DataType
     * @throws \Angujo\DBReader\Drivers\ReaderException
     */
    protected function data_type()
    {
        return $this->attributes['data_type'] = isset($this->attributes['data_type']) && is_object($this->attributes['data_type']) ? $this->attributes['data_type'] : new DataType($this->attributes['_data_type']);
    }

}