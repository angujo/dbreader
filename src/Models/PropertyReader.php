<?php


namespace Angujo\DBReader\Models;


use Angujo\DBReader\Drivers\Helper;
use Angujo\DBReader\Drivers\ReaderException;

abstract class PropertyReader
{
    protected $attributes = [];

    protected function __construct(array $details)
    {
        $this->attributes = $details;
    }

    /**
     * @param $name
     * @return mixed
     * @throws ReaderException
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) return $this->{$name}();
        if (isset($this->attributes[$name]) || isset($this->attributes[strtoupper($name)])) return $this->getDetail($name);
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
        return isset($this->attributes[$column_name]) ? $this->attributes[$column_name] : (isset($this->attributes[strtoupper($column_name)]) ? $this->attributes[strtoupper($column_name)] : null);
    }

    /**
     * @param string|array ...$params
     * @return static
     */
    public function with(...$params)
    {
        if (empty($params)) return $this;
        if (1 === count($params) && !is_array($params[0])) $params = [$params[0]];
        $elements = Helper::array_flatten($params);
        foreach ($elements as $element) {
            $elmts = explode('.', $element);
            $relt = array_shift($elmts);
            if ($this->{$relt} && !empty($elmts) && is_object($this->{$relt}) && is_a($this->{$relt}, PropertyReader::class)) $this->{$relt}->with(implode('.', $elmts));
        }
        return $this;
    }
}