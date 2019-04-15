<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Symfony\Component\VarDumper\Cloner\Data;
use Tightenco\Collect\Support\Collection;

/**
 * Class Connection
 * @package Angujo\DBReader\Drivers
 *
 * @method static Database|null currentDatabase();
 * @method static Collection|DBTable[] getTables($db_name);
 * @method static Collection|DBColumn[] getColumns($db_name, $table_name);
 */
class Connection
{
    private static $me;
    private $dbms;

    /**
     * Connection constructor.
     * @throws ReaderException
     */
    private function __construct()
    {
        if (!Config::getDsnString()) throw new ReaderException('Invalid DNS connection!');
        $pdo = new \PDO(Config::getDsnString(), Config::username(), Config::password());

        switch (Config::dbms()) {
            case 'mysql':
                $this->dbms = new MySQL($pdo);
                break;
            case 'postgres':
            case 'pgsql':
                $this->dbms = new PostgreSQL($pdo);
                break;
        }
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