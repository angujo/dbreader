<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;
use Angujo\DBReader\Models\Schema;

/**
 * Class Connection
 *
 * @package Angujo\DBReader\Drivers
 *
 * @method static Database|string|null currentDatabase($name=false);
 * @method static Database changeDatabase($db_name);
 * @method static Schema[] getSchemas();
 * @method static DBTable[] getTables($schema_name);
 * @method static DBColumn[] getColumns($schema_name = null, $table_name = null);
 * @method static ForeignKey[] getReferencedForeignKeys($table_name, $schema_name = null);
 * @method static ForeignKey[] getReferencingForeignKeys($table_name, $schema_name = null);
 */
class Connection
{
    private static $me;
    /**
     * @var Dbms
     */
    private $dbms;

    /**
     * Connection constructor.
     *
     * @param bool $skip
     *
     * @throws ReaderException
     */
    private function __construct($skip = false)
    {
        if (!$skip) {
            if (!Config::getDsnString()) {
                throw new ReaderException('Invalid DNS connection!');
            }
            $this->dbms(new \PDO(Config::getDsnString(), Config::username(), Config::password(), Config::options()));
        }
    }

    private function dbms(\PDO $pdo, $dbms = null)
    {
        $dbms = $dbms ?: Config::dbms();
        switch ($dbms) {
            case 'mysql':
                $this->dbms = new MySQL($pdo);
                break;
            case 'postgres':
            case 'pgsql':
                $this->dbms = new PostgreSQL($pdo);
                break;
        }
    }

    public static function fromConfig()
    {
        (self::$me = self::$me ?: new self(true))->dbms(new \PDO(Config::getDsnString(), Config::username(), Config::password(), Config::options()), Config::dbms());
    }

    public static function setPDO(\PDO $PDO, $dbms = 'postgres')
    {
        (self::$me = self::$me ?: new self(true))->dbms($PDO, $dbms);
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     * @throws ReaderException
     */
    public static function __callStatic($method, $args)
    {
        self::$me = self::$me ?: new self();
        if (method_exists(self::$me->dbms, $method) && is_callable([self::$me->dbms, $method])) {
            return call_user_func_array([self::$me->dbms, $method], $args);
        }
        throw new ReaderException('Invalid Query method!');
    }

    /**
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     * @throws ReaderException
     */
    public static function getForeignKeys($schema, $table_name = null)
    {
        self::$me = self::$me ?: new self();
        return array_merge(self::$me->dbms->getReferencedForeignKeys($table_name, $schema), self::$me->dbms->getReferencingForeignKeys($table_name, $schema));
    }
}