<?php


namespace Angujo\DBReader\Models;

use Angujo\DBReader\Drivers\Connection;

/**
 * Class Database
 *
 * @package Angujo\DBReader\Models
 *
 * @property string   $name
 * @property Schema[] $schemas
 */
class Database extends PropertyReader
{
    /**
     * @var static[]
     */
    private static $me = [];

    public function __construct($name)
    {
        parent::__construct(['name' => $name,]);
        self::$me[$name] = $this;
    }

    /**
     * @return Schema[]
     */
    protected function schemas()
    {
        if (isset($this->attributes['schemas'])) {
            return $this->attributes['schemas'];
        }
        return $this->attributes['schemas'] = Connection::getSchemas();
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}