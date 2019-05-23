<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\DataType;

/**
 * Class DBColumn
 * @package Angujo\DBReader\Models
 *
 * @property string $schema_name
 * @property string|string[] $data_type
 * @property string $table_name
 * @property string $name
 * @property int $ordinal_position
 * @property string|double|int $default
 * @property boolean $is_nullable
 * @property null|int $character_maximum_length
 * @property null|int $character_octet_length
 * @property null|int $numeric_precision
 * @property null|int $numeric_scale
 * @property int $precision
 * @property int $scale
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
 * @property DataType $type
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

    private function numeric()
    {
        if (!preg_match('/(\()(\d+)(,)?(\d+)?(\))/', $this->column_type, $output_array)) return [0, 0];
        return [isset($output_array[2]) && is_numeric($output_array[2]) ? $output_array[2] : 0, isset($output_array[4]) && is_numeric($output_array[4]) ? $output_array[4] : 0];
    }

    protected function precision()
    {
        return $this->numeric()[1];
    }

    protected function scale()
    {
        return $this->numeric()[0];
    }

    /**
     * @return DataType
     * @throws \Angujo\DBReader\Drivers\ReaderException
     */
    protected function type()
    {
        return $this->attributes['type'] = isset($this->attributes['type']) ? $this->attributes['type'] : new DataType($this->attributes['data_type']);
    }

}