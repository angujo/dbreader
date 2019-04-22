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

    protected function tables()
    {
        if (!empty($this->attributes['tables'])) return $this->attributes['tables'];
        return $this->attributes['tables'] = Connection::getTables($this);
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