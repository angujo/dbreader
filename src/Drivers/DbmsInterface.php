<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Tightenco\Collect\Support\Collection;

interface DbmsInterface
{
    /**
     * @return Collection|Database[]
     */
    public function getDatabases();

    /**
     * @param string|Database $db_name
     * @return DBTable[]|Collection
     */
    public function getTables($db_name);

    /**
     * @param string|Database $db_name
     * @param string|DBTable $table_name
     * @return DBColumn[]|Collection
     */
    public function getColumns($db_name, $table_name);
}