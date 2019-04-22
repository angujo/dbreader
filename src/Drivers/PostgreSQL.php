<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;
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
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) use ($db) { return new DBTable($details); });
    }

    public function getReferencedForeignKeys($db_name, $table_name)
    {
        $stmt = $this->connection->prepare('SELECT tc.table_schema, tc.constraint_name "name", tc.table_name, kcu.column_name, ccu.table_schema AS foreign_table_schema, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema WHERE tc.constraint_type = \'FOREIGN KEY\' AND tc.table_schema=:ts AND tc.table_name=:tn;');
        $stmt->execute([':ts' => $db_name, ':tn' => $table_name]);
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) { return new ForeignKey($details); });
    }

    public function getReferencingForeignKeys($db_name, $table_name)
    {
        $stmt = $this->connection->prepare('SELECT tc.table_schema AS foreign_table_schema, tc.constraint_name "name", tc.table_name AS foreign_table_name, kcu.column_name AS foreign_column_name, ccu.table_schema, ccu.table_name, ccu.column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema WHERE tc.constraint_type = \'FOREIGN KEY\' AND ccu.table_schema=:ts AND ccu.table_name=:tn;');
        $stmt->execute([':ts' => $db_name, ':tn' => $table_name]);
        return collect($stmt->fetchAll(\PDO::FETCH_ASSOC))->map(function ($details) { return new ForeignKey($details); });
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