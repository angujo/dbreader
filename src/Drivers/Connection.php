<?php


namespace Angujo\DBReader\Drivers;


class Connection
{
    private static $pdo;
    private $dbms;

    /**
     * Connection constructor.
     * @throws ReaderException
     */
    private function __construct()
    {
        if (!Config::getDsnString()) throw new ReaderException('Invalid DNS connection!');
        self::$pdo = new \PDO(Config::getDsnString(), Config::username(), Config::password());
    }
}