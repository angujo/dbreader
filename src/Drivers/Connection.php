<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;

/**
 * Class Connection
 * @package Angujo\DBReader\Drivers
 *
 * @method static Database|null currentDatabase();
 * @method static Database[] getSchemas();
 * @method static DBTable[] getTables($db_name);
 * @method static DBColumn[] getColumns($db_name, $table_name = null);
 * @method static ForeignKey[] getReferencedForeignKeys($db_name, $table_name);
 * @method static ForeignKey[] getReferencingForeignKeys($db_name, $table_name);
 */
class Connection
{
    private static $me;
    private $dbms;

    /**
     * Connection constructor.
     * @throws ReaderException
     */
    private function __construct($skip = false)
    {
        if (!$skip) {
            if (!Config::getDsnString()) throw new ReaderException('Invalid DNS connection!');
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

    public static function setPDO(\PDO $PDO, $dbms = 'postgres')
    {
        (self::$me = self::$me ?: new self(true))->dbms($PDO, $dbms);
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws ReaderException
     */
    public static function __callStatic($method, $args)
    {
        self::$me = self::$me ?: new self();
        if (method_exists(self::$me->dbms, $method) && is_callable([self::$me->dbms, $method])) return call_user_func_array([self::$me->dbms, $method], $args);
        throw new ReaderException('Invalid Query method!');
    }
}