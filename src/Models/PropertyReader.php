<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\Helper;
use Angujo\DBReader\Drivers\ReaderException;

/**
 * Class PropertyReader
 *
 * @package Angujo\DBReader\Models
 *          Allows access to variables and methods through property names
 */
abstract class PropertyReader
{
    /**
     * Attributes with keys as property names
     * @var array
     */
    protected $attributes = [];

    /**
     * PropertyReader constructor.
     *
     * @param array $details
     */
    protected function __construct(array $details)
    {
        $this->attributes = array_change_key_case($details, CASE_LOWER);
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws ReaderException
     */
    public function __get($name)
    {
        if (array_key_exists(strtolower($name), $this->attributes)) {
            return $this->getDetail($name);
        }
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
        throw new ReaderException('Invalid property "'.$name.'" in '.static::class.'!');
    }

    /**
     * By any chance we are checking
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return method_exists($this, $name) || isset($this->values[$name]);
    }

    /**
     * We don't allow setting up properties
     * @param $name
     *
     * @throws ReaderException
     */
    public function __set($name, $val)
    {
        throw new ReaderException('Not allowed! Cannot assign READ_ONLY attribute!');
    }

    /**
     * Quicker way to through sources and get the property value
     * @param      $column_name
     * @param null $default
     *
     * @return mixed|null
     */
    protected function getDetail($column_name, $default = null)
    {
        return array_key_exists(strtolower($column_name), $this->attributes) ? $this->attributes[$column_name] : $default;
    }

    /**
     * Load any other relationship and avail it
     *
     * @param string|array ...$params
     *
     * @return static
     */
    public function with(...$params)
    {
        if (empty($params)) {
            return $this;
        }
        $res      = [];
        $elements = Helper::array_flatten($params);
        foreach ($elements as $element) {
            $elmts = explode('.', $element);
            $relt  = array_shift($elmts);
            $elmts = implode('.', $elmts);
            if (array_key_exists($relt, $res)) {
                $res[$relt][] = $elmts;
            } else {
                $res[$relt] = [$elmts];
            }
        }
        $res = array_map(function($av){ return array_filter(array_unique($av)); }, $res);
        foreach ($res as $key => $re) {
            $this->attributes[$key] = $this->implementWith($this->{$key}, $re);
        }
        return $this;
    }

    /**
     * Load any other relationship and avail it
     * @param PropertyReader|array $object
     * @param string               $param
     *
     * @return mixed
     */
    private function implementWith($object, $param = null)
    {
        if (!$param) {
            return $object;
        }
        if (is_object($object) && is_a($object, PropertyReader::class)) {
            return $object->with($param);
        } elseif (is_array($object)) {
            return array_map(function($val) use ($param){ return $this->implementWith($val, $param); }, $object);
        }
        return $object;
    }

    /**
     * Get all loaded properties so far,
     * as Array
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }
}