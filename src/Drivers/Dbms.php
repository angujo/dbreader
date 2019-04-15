<?php


namespace Angujo\DBReader\Drivers;


abstract class Dbms implements DbmsInterface
{
    protected $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }
}