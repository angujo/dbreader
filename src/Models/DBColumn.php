<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\DataType;
use Angujo\DBReader\Drivers\ReaderException;

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
 * @property boolean                $is_unique
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
 * @property DBConstraint[]         $unique_constraints
 * @property DBConstraint|null      $unique_constraint
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
     * @throws ReaderException
     */
    protected function type()
    {
        return new DataType($this->attributes['data_type']);
    }

    protected function reference()
    {
        return implode('.', [$this->schema_name, $this->table_name, $this->name]);
    }

    protected function is_primary()
    {
        return count(array_filter($this->table->constraints, function(DBConstraint $constraint){ return $constraint->is_primary_key && 0 === strcasecmp($this->reference, $constraint->column_reference); })) > 0;
    }

    protected function is_unique()
    {
        return count($this->unique_constraints) > 0;
    }

    protected function unique_constraint()
    {
        return $this->is_unique ? current($this->unique_constraints) : null;
    }

    protected function unique_constraints()
    {
        return array_filter($this->table->constraints, function(DBConstraint $constraint){ return $constraint->is_unique_key && 0 === strcasecmp($this->reference, $constraint->column_reference); });
    }
}