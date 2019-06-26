<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\DataType;

/**
 * Class DBColumn
 *
 * @package Angujo\DBReader\Models
 *
 * @property string                 $schema_name
 * @property string|string[]        $data_type
 * @property string                 $table_name
 * @property string                 $name
 * @property int                    $ordinal
 * @property string|double|int|null $default
 * @property boolean                $is_nullable
 * @property null|int               $length
 * @property string                 $comment
 *
 * @property boolean                $is_primary
 * @property boolean                $is_auto_increment
 * @property string                 $reference
 *
 * @property DataType               $type
 * @property DBTable                $table
 * @property Database               $database
 * @property Schema                 $schema
 * @property ForeignKey[]           $foreign_keys
 * @property ForeignKey             $foreign_key
 */
class DBColumn extends PropertyReader
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

    protected function foreign_keys()
    {
        return $this->schema->getColumnForeignKeys($this);
    }

    protected function foreign_key()
    {
        $fk = $this->foreign_keys;
        return array_shift($fk);
    }

    /**
     * @return DataType
     * @throws \Angujo\DBReader\Drivers\ReaderException
     */
    protected function type()
    {
        return $this->attributes['type'] = isset($this->attributes['type']) ? $this->attributes['type'] : new DataType($this->attributes['data_type']);
    }

    protected function reference()
    {
        return implode('.', [$this->schema_name, $this->table_name, $this->name]);
    }
}