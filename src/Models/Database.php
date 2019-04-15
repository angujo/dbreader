<?php


namespace Angujo\DBReader\Models;

use Angujo\DBReader\Drivers\Connection;
use Angujo\DBReader\Drivers\ReaderException;
use Tightenco\Collect\Support\Collection;

/**
 * Class Database
 * @package Angujo\DBReader\Models
 *
 * @property string $name;
 * @property DBTable[]|Collection $tables;
 */
class Database
{
    private $values = [];

    public function __construct($name)
    {
        $this->values['name'] = $name;
    }

    private function tables()
    {
        if (!empty($this->values['tables'])) return $this->values['tables'];
        return $this->values['tables'] = Connection::getTables($this);
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) return $this->{$name}();
        if (isset($this->values[$name])) return $this->values[$name];
        throw new ReaderException('Invalid Database property!');
    }

    public function __isset($name)
    {
        return method_exists($this, $name) || isset($this->values[$name]);
    }
}