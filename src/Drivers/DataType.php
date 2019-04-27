<?php


namespace Angujo\DBReader\Drivers;


/**
 * Class DataType
 * @package Angujo\DBReader\Drivers
 *
 * @property bool $isBoolean
 * @property bool $isBool
 * @property bool $isCharacter
 * @property bool $isChar
 * @property bool $isNumeric
 * @property bool $isDecimal
 * @property bool $isReal
 * @property bool $isFloat4
 * @property bool $isDouble
 * @property bool $isFloat8
 * @property bool $isInteger
 * @property bool $isInt
 * @property bool $isInt4
 * @property bool $isSmallInt
 * @property bool $isInt2
 * @property bool $isBigInt
 * @property bool $isInt8
 * @property bool $isSmallSerial
 * @property bool $isSerial2
 * @property bool $isSerial
 * @property bool $isSerial4
 * @property bool $isBigSerial
 * @property bool $isSerial8
 * @property bool $isVarBit
 * @property bool $isVarchar
 * @property bool $isBit
 * @property bool $isBox
 * @property bool $isByteA
 * @property bool $isCidr
 * @property bool $isCircle
 * @property bool $isDate
 * @property bool $isInet
 * @property bool $isInterval
 * @property bool $isJson
 * @property bool $isJsonb
 * @property bool $isLine
 * @property bool $isLSeg
 * @property bool $isMacAddr
 * @property bool $isMacAddr8
 * @property bool $isMoney
 * @property bool $isPath
 * @property bool $isPg_lsn
 * @property bool $isPoint
 * @property bool $isPolygon
 * @property bool $isText
 * @property bool $isTime
 * @property bool $isTimeTz
 * @property bool $isTimestamp
 * @property bool $isTimestampTz
 * @property bool $isTsQuery
 * @property bool $isTsVector
 * @property bool $isTxId_snapshot
 * @property bool $isUuid
 * @property bool $isXml
 * @property bool $isTinyText
 * @property bool $isMediumText
 * @property bool $isLongText
 * @property bool $isTinyInt
 * @property bool $isMediumInt
 * @property bool $isFloat
 * @property bool $isDateTime
 * @property bool $isEnum
 * @property bool $isSet
 */
class DataType
{
    protected $type_names = [];
    protected $group = null;

    /**
     * DataType constructor.
     * @param string ...$types
     * @throws ReaderException
     */
    public function __construct(...$types)
    {
        $this->type_names = array_map(function ($v) { return $this->extract($v); }, Helper::array_flatten($types));
        foreach ($this->type_names as $type_name) {
            if (null !== $this->group && 0 !== strcasecmp($this->groupName($type_name), $this->group)) throw new ReaderException('Invalid type and alias: ' . implode(', ', $this->type_names), 406);
            $this->group = $this->groupName($type_name);
        }
    }

    /**
     * @param $name
     * @return bool
     * @throws ReaderException
     */
    public function __get($name)
    {
        if (0 !== stripos($name, 'is')) throw new ReaderException('Invalid data type query: ' . $name, 406);
        return 0 === strcasecmp($this->groupName($this->removeQuiz($name)), $this->group);
    }

    protected function removeQuiz($name)
    {
        return preg_replace("/^(is)/", '', $name);
    }

    public function __isset($name)
    {
        return null !== $this->groupName($this->removeQuiz($name));
    }

    /**
     * @param $name
     * @param $val
     * @throws ReaderException
     */
    public function __set($name, $val)
    {
        throw new ReaderException('Data attributes can only be constructed!', 406);
    }

    /**
     * @param $type
     * @return string|null
     */
    protected function extract($type)
    {
        return preg_replace("/(\((.*?)\))/", '', $type);
    }

    /**
     * @param $type
     * @return bool
     */
    protected function groupName($type)
    {
        switch (strtolower(trim($type))) {
            case 'boolean':
            case 'bool':
                return 'bool';
            case 'character':
            case 'char':
                return 'char';
            case 'numeric':
            case 'decimal':
            case 'money':
                return 'decimal';
            case 'real':
            case 'float4':
            case 'float':
                return 'float';
            case 'double':
            case 'float8':
                return 'double';
            case 'integer':
            case 'int':
            case 'mediumint':
                return 'int';
            case 'int4':
            case 'smallint':
                return 'smallint';
            case 'int2':
            case 'tinyint':
                return 'tinyint';
            case 'bigint':
            case 'int8':
                return 'bigint';
            case 'smallserial':
            case 'serial2':
                return 'smallserial';
            case 'serial':
            case 'serial4':
                return 'serial';
            case 'bigserial':
            case 'serial8':
                return 'bigserial';
            case 'timestamp':
            case 'datetime':
            case 'timestamptz':
                return 'timestamp';
            case 'time':
            case 'timetz':
                return 'time';
            case 'macaddr':
            case 'macaddr8':
                return 'macaddr';
            case 'varchar':
            case 'varbit':
            case 'bit':
            case 'box':
            case 'bytea':
            case 'cidr':
            case 'circle':
            case 'date':
            case 'inet':
            case 'interval':
            case 'json':
            case 'jsonb':
            case 'line':
            case 'lseg':
            case 'path':
            case 'pg_lsn':
            case 'point':
            case 'polygon':
            case 'tsquery':
            case 'tsvector':
            case 'txid_snapshot':
            case 'uuid':
            case 'xml':
            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'enum':
            case 'set':
                return strtolower(trim($type));
        }
        return null;
    }
}