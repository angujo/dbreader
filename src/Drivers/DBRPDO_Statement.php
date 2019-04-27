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
        if (!$replaced) {
            return $q;
        }
        return preg_replace_callback('/:([0-9a-z_]+)/i', array($this, '_debugReplace'), $q);
    }

    protected function _debugReplace($m)
    {
        $v = $this->debugValues[$m[1]];
        if ($v === null) {
            return "NULL";
        }
        if (!is_numeric($v)) {
            $v = str_replace("'", "''", $v);
        }
        return "'" . $v . "'";
    }
}