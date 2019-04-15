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
 * @method static string options(array $opts = [])
 */
class Config
{
    /**
     * @var static
     */
    private static $me;
    /**
     * @var \Tightenco\Collect\Support\Collection|array
     */
    private $params;
    /**
     * @var array
     */
    private $keys = ['database' => null, 'host' => null, 'username' => null, 'password' => null, 'charset' => null, 'options' => [], 'port' => null, 'dbms' => null];

    private function __construct()
    {
        $this->params = collect($this->keys)->merge(collect(require(__DIR__ . '../../configs.php'))->filter(function ($val, $key) { return array_key_exists($key, $this->keys); }))
            ->map(function ($val, $key) {
                if (0 === strcasecmp($key, 'options')) $val[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
                if (0 === strcasecmp('dbms', $key) && !in_array($val, ['mysql', 'postgres'])) throw new ReaderException('Invalid Database Management System!');
                return $val;
            });
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
            self::$me->params->transform(function ($val, $key) use ($method, $args) {
                if (0 === strcasecmp($key, 'options')) {
                    if (!is_array($args[0])) throw new ReaderException('Options parameter should be array!');
                    $val = array_merge($val, $args[0]);
                } elseif (0 === strcasecmp($key, $method)) $val = $args[0];
                return $val;
            });
        }
        if (!($val = self::$me->params->first(function ($v, $k) use ($method) { return 0 === strcasecmp($k, $method); }))) return null;
        return $val;
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