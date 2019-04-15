<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBTable;

class MySQL extends Dbms
{

    /**
     * @param Database|string $db
     * @return DBTable[]|\Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function getTables($db)
    {
        $stmt = $this->connection->prepare('SELECT * FROM information_schema.TABLES t WHERE t.TABLE_SCHEMA= :db');
        $stmt->execute(['db' => $db->name]);
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details)use ($db) { return new DBTable($db, $details);});
    }

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
        return $this->current_db = new Database($this->connection->query('SELECT database();')->fetchColumn());
    }
}