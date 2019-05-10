<?php


namespace Angujo\DBReader\Drivers;


class DBRPDO_Statement extends \PDOStatement
{
    protected $debugValues = [];

    protected function __construct() { }

    public function execute($params = null)
    {
        $this->debugValues = $params;
        return parent::execute($params);
    }

    /**
     * @param $param
     * @param $value
     * @param int $data_type
     * @return bool|void
     */
    public function bindValue($param, $value, $data_type = \PDO::PARAM_STR)
    {
        $this->debugValues[$param] = $value;
        return parent::bindValue($param, $value, $data_type);
    }

    public function _debugQuery($replaced = true)
    {
        $q = $this->queryString;
        return $replaced ? preg_replace_callback('/:([0-9a-z_]+)/i',
            function ($m) {
                foreach ($m as $key) {
                    if (!isset($this->debugValues[$key])) continue;
                    if (null === $this->debugValues[$key]) return "NULL";
                    return "'" . (is_numeric($this->debugValues[$key]) ? $this->debugValues[$key] : str_replace("'", "''", $this->debugValues[$key])) . "'";
                }
                return $m[0];
            },
            $q) : $q;
    }
}