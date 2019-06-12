<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;
use Angujo\DBReader\Models\Schema;

class MySQL extends Dbms
{
    /**
     * @param Database|string $db
     *
     * @return DBTable[]
     */
    public function getTables($db)
    {
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('SELECT * FROM information_schema.TABLES t WHERE t.TABLE_SCHEMA= :db');
        $stmt->execute(['db' => $db ?: $this->currentDatabase(true)]);
        return $this->mapTables(array_map(function($details) use ($db){ return new DBTable($details); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * One to one
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($table_name, $schema = null)
    {
        $params = [':ts' => is_string($schema) ? $schema : $this->currentDatabase(true)];
        if (is_string($table_name)) {
            $params[':tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA, cu.TABLE_NAME, cu.COLUMN_NAME, cu.REFERENCED_TABLE_SCHEMA foreign_table_schema, cu.REFERENCED_TABLE_NAME foreign_table_name, cu.REFERENCED_COLUMN_NAME foreign_column_name, false unique_column from information_schema.KEY_COLUMN_USAGE cu where cu.TABLE_SCHEMA=:ts and cu.REFERENCED_TABLE_NAME is not null '.(is_string($table_name) ? ' and cu.TABLE_NAME=:tn ' : ''));
        $stmt->execute($params);
        $ustmt = $this->connection->prepare('select cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA foreign_table_schema, cu.TABLE_NAME foreign_table_name, cu.COLUMN_NAME foreign_column_name, cu.REFERENCED_TABLE_SCHEMA table_schema, cu.REFERENCED_TABLE_NAME table_name, cu.REFERENCED_COLUMN_NAME column_name, true unique_column from information_schema.KEY_COLUMN_USAGE cu join information_schema.`COLUMNS` c on c.COLUMN_KEY=\'UNI\' and c.TABLE_NAME=cu.TABLE_NAME and c.TABLE_SCHEMA=cu.TABLE_SCHEMA and c.COLUMN_NAME=cu.COLUMN_NAME where cu.TABLE_SCHEMA=:ts'.(is_string($table_name) ? ' and cu.REFERENCED_TABLE_NAME=:tn ' : ''));
        $ustmt->execute($params);
        //echo $stmt->_debugQuery(true), "\n";
        return $this->mapForeignKeys(array_map(function($details){ return new ForeignKey($details, false); }, array_merge($ustmt->fetchAll(\PDO::FETCH_ASSOC), $stmt->fetchAll(\PDO::FETCH_ASSOC))));
    }

    /**
     * One to many
     *
     * @param $db_name
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencingForeignKeys($table_name, $db_name = null)
    {
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select false unique_column, cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA foreign_table_schema, cu.TABLE_NAME foreign_table_name, cu.COLUMN_NAME foreign_column_name, cu.REFERENCED_TABLE_SCHEMA table_schema, cu.REFERENCED_TABLE_NAME table_name, cu.REFERENCED_COLUMN_NAME column_name from information_schema.KEY_COLUMN_USAGE cu JOIN information_schema.COLUMNS c ON c.COLUMN_KEY <> \'UNI\' and c.TABLE_NAME=cu.TABLE_NAME and c.TABLE_SCHEMA=cu.TABLE_SCHEMA and c.COLUMN_NAME=cu.REFERENCED_COLUMN_NAME where cu.REFERENCED_TABLE_SCHEMA=:ts and cu.REFERENCED_TABLE_NAME = :tn;');
        $stmt->execute(['ts' => is_string($db_name) ? $db_name : $this->currentDatabase(true), 'tn' => $table_name]);
        // echo $stmt->_debugQuery(true),"\n";
        return $this->mapForeignKeys(array_map(function($details){ return new ForeignKey($details, true); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * @param Database|string $schema
     * @param null            $table_name
     *
     * @return DBColumn[]
     */
    public function getColumns($schema = null, $table_name = null)
    {
        $params = ['ts' => $schema ?: $this->currentDatabase(true),];
        if (is_string($table_name)) {
            $params['tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select * from information_schema.`COLUMNS` c '.
                                           'where c.TABLE_SCHEMA=:ts '.(is_string($table_name) ? ' and c.TABLE_NAME = :tn' : ''));
        $stmt->execute($params);
        return $this->mapColumns(array_map(function($details){ return new DBColumn($this->mapColumnsData($details)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    protected function mapColumnsData(array $data)
    {
        $_data              = $data;
        $_data['data_type'] = [];
        foreach ($data as $name => $datum) {
            if (0 === strcasecmp('table_schema', $name)) {
                $_data['schema_name'] = $datum;
            } elseif (0 === strcasecmp('column_name', $name)) {
                $_data['name'] = $datum;
            } elseif (0 === strcasecmp('column_default', $name)) {
                $_data['default'] = $datum;
                if (0 === strcasecmp('null', $datum)) {
                    $_data['default'] = $data['column_default'] = null;
                }
            } elseif (0 === strcasecmp('character_set_name', $name)) {
                $_data['charset'] = $datum;
            } elseif (0 === strcasecmp('column_comment', $name)) {
                $_data['comment'] = $datum;
            } elseif (0 === strcasecmp('data_type', $name) || 0 === strcasecmp('column_type', $name)) {
                $_data['data_type'][] = $datum;
            } elseif (0 === strcasecmp('numeric_scale', $name)) {
                $_data['decimal_places'] = $datum;
            } elseif (0 === strcasecmp('column_key', $name)) {
                $_data['is_primary'] = 0 === strcasecmp('pri', $datum);
            } elseif (0 === strcasecmp('extra', $name)) {
                $_data['is_auto_increment'] = false !== stripos($datum, 'auto_increment');
            }
        }
        return $_data;
    }

    /**
     * @param bool $name
     *
     * @return Database|string
     */
    public function currentDatabase($name = false)
    {
        $this->current_db = $this->current_db ?: new Database($this->connection->query('SELECT database();')->fetchColumn());
        return true === $name && $this->current_db ? $this->current_db->name : $this->current_db;
    }
}