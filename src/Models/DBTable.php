<?php

namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\ReaderException;

/**
 * Class DBTable
 * @package Angujo\DBReader\Models
 *
 * @property string $schema_name;
 * @property string $name;
 * @property string $engine;
 * @property string $version;
 * @property string $row_format;
 * @property integer $table_rows;
 * @property integer $auto_increment;
 * @property boolean $is_table;
 * @property boolean $is_view;
 */
class DBTable
{
    private $details = [];
    private $database;

    public function __construct(Database $db, array $details)
    {
        $this->database = $db;
        $this->details = $details;
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

    protected function getDetail($column_name)
    {
        return isset($this->details[$column_name]) ? $this->details[$column_name] : null;
    }

    /**
     * @param $name
     * @return mixed
     * @throws ReaderException
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) return $this->{$name}();
        if (isset($this->details[$name])) return $this->details[$name];
        throw new ReaderException('Invalid Table property!');
    }

    public function __isset($name)
    {
        return method_exists($this, $name) || isset($this->values[$name]);
    }

    public function __set($name)
    {
        throw new ReaderException('Not allowed! Cannot assign READ_ONLY attribute!');
    }
}