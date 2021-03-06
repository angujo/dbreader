<?php


namespace Angujo\DBReader\Drivers;


/**
 * Class Config
 * @package Angujo\DBReader\Drivers
 *
 * @method static string host($host = null)
 * @method static string dbms($dbms = null)
 * @method static string port($port = null)
 * @method static string database($db = null)
 * @method static string username($user = null)
 * @method static string password($pass = null)
 * @method static array options(array $opts = [])
 */
class Config
{
    /**
     * @var static
     */
    private static $me;
    /**
     * @var array
     */
    private $params;
    /**
     * @var array
     */
    private $keys = ['database' => null, 'host' => null, 'username' => null, 'password' => null, 'charset' => null, 'options' => [], 'port' => null, 'dbms' => null];

    private function __construct()
    {
        $uconfigs = require(__DIR__ . '../../configs.php');
        $filters = array_filter(array_merge($this->keys, $uconfigs),
            function ($key) {
                return array_key_exists($key, $this->keys);
            }, ARRAY_FILTER_USE_KEY);
        array_walk($filters, function (&$val, $key) {
            if (0 === strcasecmp($key, 'options')) {
                $val[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
                $val[\PDO::ATTR_STATEMENT_CLASS] = [DBRPDO_Statement::class, []];
            }
            if (0 === strcasecmp('dbms', $key) && !in_array($val, ['mysql', 'postgres', 'pgsql'])) throw new ReaderException('Invalid Database Management System!');
            //  return $val;
        });
        $this->params = $filters;
    }

    /**
     * @param $dns_string
     * @param null $username
     * @param null $password
     * @throws ReaderException
     */
    public static function dsn($dns_string, $username = null, $password = null)
    {
        $dsn = explode(':', $dns_string);
        if (count($dsn) < 2) throw new ReaderException('Invalid DNS String!');
        self::dbms(array_shift($dsn));
        $dsn = explode(';', implode(':', $dsn));
        foreach ($dsn as $item) {
            $items = explode('=', $item);
            $name = array_shift($items);
            $value = implode('=', $items);
            if (0 === strcasecmp('host', $name) && count($h = explode(':', $value)) > 1) {
                self::host(array_shift($h));
                self::port(implode(':', $h));
            } else {
                forward_static_call(['Config', $name], $value);
            }
        }
        if (null !== $username) self::username($username);
        if (null !== $password) self::password($password);
    }

    public static function set($dbms, $host, $port, $database, $username, $password = null)
    {
        self::dbms($dbms);
        self::host($host);
        self::port($port);
        self::database($database);
        self::username($username);
        self::password($password);
    }

    /**
     * @param $method
     * @param $args
     * @return string|array|null
     * @throws ReaderException
     */
    public static function __callStatic($method, $args)
    {
        self::$me = self::$me ?: new self();
        if (!array_key_exists($method, self::$me->keys)) throw new ReaderException('Invalid configuration method!');
        if (!empty($args)) {
            array_walk(self::$me->params, function (&$val, $key) use ($method, $args) {
                if (0 === strcasecmp($key, 'options') && 0 === strcasecmp($key, $method)) {
                    if (!is_array($args[0])) throw new ReaderException('Options parameter should be array!');
                    $val = array_merge($val, $args[0]);
                } elseif (0 === strcasecmp($key, $method)) $val = $args[0];
                // return $val;
            });
        }
        return isset(self::$me->params[strtolower($method)]) ? self::$me->params[strtolower($method)] : null;
    }

    public static function getDsnString()
    {
        switch (self::dbms()) {
            case 'mysql':
                return 'mysql:dbname=' . self::database() . ';host=' . self::host() . ';port=' . self::port();
            case 'postgres':
            case 'pgsql':
                return 'pgsql:dbname=' . self::database() . ';host=' . self::host() . ';port=' . self::port();
            default:
                return null;
        }
    }
}