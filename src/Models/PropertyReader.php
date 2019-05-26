<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\Helper;
use Angujo\DBReader\Drivers\ReaderException;
use Tightenco\Collect\Contracts\Support\Arrayable;

abstract class PropertyReader implements Arrayable
{
    protected $attributes = [];

    protected function __construct(array $details)
    {
        $this->attributes = array_change_key_case($details, CASE_LOWER);
    }

    /**
     * @param $name
     * @return mixed
     * @throws ReaderException
     */
    public function __get($name)
    {
        if (array_key_exists(strtolower($name), $this->attributes)) return $this->getDetail($name);
        if (method_exists($this, $name)) return $this->{$name}();
        throw new ReaderException('Invalid property "' . $name . '" in ' . static::class . '!');
    }

    public function __isset($name)
    {
        return method_exists($this, $name) || isset($this->values[$name]);
    }

    /**
     * @param $name
     * @throws ReaderException
     */
    public function __set($name, $val)
    {
        throw new ReaderException('Not allowed! Cannot assign READ_ONLY attribute!');
    }

    protected function getDetail($column_name)
    {
        return array_key_exists(strtolower($column_name), $this->attributes) ? $this->attributes[$column_name] : null;
    }

    /**
     * @param string|array ...$params
     * @return static
     */
    public function with(...$params)
    {
        if (empty($params)) return $this;
        $res = [];
        $elements = Helper::array_flatten($params);
        foreach ($elements as $element) {
            $elmts = explode('.', $element);
            $relt = array_shift($elmts);
            $elmts = implode('.', $elmts);
            if (array_key_exists($relt, $res)) {
                $res[$relt][] = $elmts;
            } else $res[$relt] = [$elmts];
        }
        $res = array_map(function ($av) { return array_filter(array_unique($av)); }, $res);
        foreach ($res as $key => $re) {
            $this->attributes[$key] = $this->implementWith($this->{$key}, $re);
        }
        return $this;
    }

    /**
     * @param PropertyReader|array $object
     * @param string $param
     * @return mixed
     */
    private function implementWith($object, $param = null)
    {
        if (!$param) return $object;
        if (is_object($object) && is_a($object, PropertyReader::class)) {
             return $object->with($param);
        } elseif (is_array($object)) {
            return array_map(function ($val) use ($param) { return $this->implementWith($val, $param); }, $object);
        }
        return $object;
    }

    public function toArray()
    {
        return $this->attributes;
    }
}