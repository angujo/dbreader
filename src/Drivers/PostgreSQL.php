<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Tightenco\Collect\Support\Collection;

class PostgreSQL extends Dbms
{

    /**
     * @return Collection|Database[]
     */
    public function getSchemas()
    {
        // TODO: Implement getDatabases() method.
    }

    /**
     * @param string|Database $db
     * @return DBTable[]|Collection
     */
    public function getTables($db)
    {
        $stmt = $this->connection->prepare('select * from information_schema."tables" t where t.table_schema not in (\'information_schema\',\'pg_catalog\')');
        $stmt->execute();
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) use ($db) { return new DBTable($db, $details); });
    }

    /**
     * @param string|Database $db_name
     * @param string|DBTable $table_name
     * @return DBColumn[]|Collection
     */
    public function getColumns($db_name, $table_name)
    {
        // TODO: Implement getColumns() method.
    }

    /**
     * @return Database
     */
    public function currentDatabase()
    {
        if ($this->current_db) return $this->current_db;
        return $this->current_db = new Database($this->connection->query('SELECT current_database();')->fetchColumn());
    }
}