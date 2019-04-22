<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;

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
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) use ($db) { return new DBTable($details); });
    }

    public function getReferencedForeignKeys($db_name, $table_name)
    {
        $stmt = $this->connection->prepare('select cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA, cu.TABLE_NAME, cu.COLUMN_NAME, cu.REFERENCED_TABLE_SCHEMA foreign_table_schema, cu.REFERENCED_TABLE_NAME foreign_table_name, cu.REFERENCED_COLUMN_NAME foreign_column_name from information_schema.KEY_COLUMN_USAGE cu where cu.TABLE_SCHEMA=:ts and cu.TABLE_NAME=:tn and cu.REFERENCED_TABLE_NAME is not null;');
        $stmt->execute([':ts' => $db_name, ':tn' => $table_name]);
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) { return new ForeignKey($details); });
    }

    public function getReferencingForeignKeys($db_name, $table_name)
    {
        $stmt = $this->connection->prepare('select cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA foreign_table_schema, cu.TABLE_NAME foreign_table_name, cu.COLUMN_NAME foreign_column_name, cu.REFERENCED_TABLE_SCHEMA table_schema, cu.REFERENCED_TABLE_NAME table_name, cu.REFERENCED_COLUMN_NAME column_name from information_schema.KEY_COLUMN_USAGE cu where cu.REFERENCED_TABLE_SCHEMA=:ts and cu.REFERENCED_TABLE_NAME = :tn;');
        $stmt->execute([':ts' => $db_name, ':tn' => $table_name]);
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) { return new ForeignKey($details); });
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