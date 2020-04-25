<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBConstraint;
use Angujo\DBReader\Models\DBIndex;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;

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
        $stmt = $this->connection->prepare('SELECT TABLE_SCHEMA schema_name, TABLE_NAME name, TABLE_SCHEMA db_name FROM information_schema.TABLES t WHERE t.TABLE_SCHEMA= :db');
        $stmt->execute(['db' => $db ?: $this->currentDatabase(true)]);
        return $this->mapTables(array_map(function ($details) use ($db) { return new DBTable(array_merge(['db_name' => $this->currentDatabase(true)], $details)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * One to one
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($schema, $table_name = null)
    {
        $params = [':ts' => is_string($schema) ? $schema : $this->currentDatabase(true)];
        if (is_string($table_name)) {
            $params[':tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA schema_name , cu.TABLE_NAME, cu.COLUMN_NAME, cu.REFERENCED_TABLE_SCHEMA foreign_schema_name, cu.REFERENCED_TABLE_NAME foreign_table_name, cu.REFERENCED_COLUMN_NAME foreign_column_name, false unique_column from information_schema.KEY_COLUMN_USAGE cu where cu.TABLE_SCHEMA=:ts and cu.REFERENCED_TABLE_NAME is not null '.(is_string($table_name) ? ' and cu.TABLE_NAME=:tn ' : ''));
        $stmt->execute($params);
        //echo $stmt->_debugQuery(true), "\n";
        $ustmt = $this->connection->prepare('select cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA foreign_schema_name, cu.TABLE_NAME foreign_table_name, cu.COLUMN_NAME foreign_column_name, cu.REFERENCED_TABLE_SCHEMA schema_name, cu.REFERENCED_TABLE_NAME table_name, cu.REFERENCED_COLUMN_NAME column_name, true unique_column from information_schema.KEY_COLUMN_USAGE cu join information_schema.`COLUMNS` c on c.COLUMN_KEY=\'UNI\' and c.TABLE_NAME=cu.TABLE_NAME and c.TABLE_SCHEMA=cu.TABLE_SCHEMA and c.COLUMN_NAME=cu.COLUMN_NAME where cu.TABLE_SCHEMA=:ts'.(is_string($table_name) ? ' and cu.REFERENCED_TABLE_NAME=:tn ' : ''));
        $ustmt->execute($params);
        //echo $stmt->_debugQuery(true), "\n";
        return $this->mapForeignKeys(array_map(function ($details) { return new ForeignKey($details, false); }, array_merge($ustmt->fetchAll(\PDO::FETCH_ASSOC), $stmt->fetchAll(\PDO::FETCH_ASSOC))));
    }

    /**
     * One to many
     *
     * @param $db_name
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencingForeignKeys($db_name, $table_name = null)
    {
        $p = ['ts' => is_string($db_name) ? $db_name : $this->currentDatabase(true)];
        if ($table_name) {
            $p[':tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select false unique_column, cu.CONSTRAINT_NAME name, cu.TABLE_SCHEMA foreign_schema_name, cu.TABLE_NAME foreign_table_name, cu.COLUMN_NAME foreign_column_name, cu.REFERENCED_TABLE_SCHEMA schema_name, cu.REFERENCED_TABLE_NAME table_name, cu.REFERENCED_COLUMN_NAME column_name from information_schema.KEY_COLUMN_USAGE cu JOIN information_schema.COLUMNS c ON c.COLUMN_KEY <> \'UNI\' and c.TABLE_NAME=cu.TABLE_NAME and c.TABLE_SCHEMA=cu.TABLE_SCHEMA and c.COLUMN_NAME=cu.REFERENCED_COLUMN_NAME where cu.REFERENCED_TABLE_SCHEMA=:ts'.($table_name ? ' and cu.REFERENCED_TABLE_NAME = :tn' : ''));
        $stmt->execute($p);
        //echo $stmt->_debugQuery(true), "\n";
        return $this->mapForeignKeys(array_map(function ($details) { return new ForeignKey($details, true); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
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
        $stmt = $this->connection->prepare('select table_schema schema_name,data_type,table_name,column_name name,ordinal_position ordinal, column_default `default`, is_nullable,character_maximum_length length,column_comment `comment`  from information_schema.`COLUMNS` c '.
            'where c.TABLE_SCHEMA=:ts '.(is_string($table_name) ? ' and c.TABLE_NAME = :tn' : ''));
        $stmt->execute($params);
        return $this->mapColumns(array_map(function ($details) { return new DBColumn($this->mapColumnsData($details)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
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

    /**
     * Get Constraints
     *
     * @param string      $schema
     * @param string|null $table_name
     *
     * @return DBConstraint[]
     */
    public function getConstraints($schema, $table_name = null)
    {
        $params = ['ts' => $schema ?: $this->currentDatabase(true),];
        if (is_string($table_name)) {
            $params['tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select tc.CONSTRAINT_NAME name, tc.TABLE_SCHEMA schema_name, null check_source, tc.table_name, kcu.column_name, \'PRIMARY KEY\'=tc.CONSTRAINT_TYPE is_primary_key, \'UNIQUE\'=tc.CONSTRAINT_TYPE is_unique_key, \'FOREIGN KEY\'=tc.CONSTRAINT_TYPE is_foreign_key from information_schema.KEY_COLUMN_USAGE kcu join information_schema.TABLE_CONSTRAINTS tc on tc.TABLE_SCHEMA =kcu.TABLE_SCHEMA and tc.TABLE_NAME =kcu.TABLE_NAME and tc.CONSTRAINT_NAME =kcu.CONSTRAINT_NAME '.
            'where tc.TABLE_SCHEMA=:ts '.(is_string($table_name) ? ' and tc.TABLE_NAME = :tn' : ''));
        $stmt->execute($params);
        return $this->mergeConstraints(array_map(function ($details) { return new DBConstraint($details); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * @param string      $schema
     * @param null|string $table_name
     * @return DBIndex[]
     */
    public function getIndices($schema, $table_name = null)
    {
        $params = ['ts' => $schema ?: $this->currentDatabase(true),];
        if (is_string($table_name)) {
            $params['tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select s.TABLE_SCHEMA schema_name, s.TABLE_NAME table_name, s.INDEX_NAME name, s.column_name, \'PRIMARY KEY\'=tc.CONSTRAINT_TYPE is_primary, s.NON_UNIQUE=0 is_unique from information_schema.STATISTICS s join information_schema.KEY_COLUMN_USAGE kcu on kcu.COLUMN_NAME =s.COLUMN_NAME and kcu.TABLE_NAME =s.TABLE_NAME and kcu.TABLE_SCHEMA =s.TABLE_SCHEMA join information_schema.TABLE_CONSTRAINTS tc on kcu.CONSTRAINT_NAME =tc.CONSTRAINT_NAME and kcu.TABLE_NAME =tc.TABLE_NAME  and kcu.TABLE_SCHEMA =tc.TABLE_SCHEMA '.
            'where s.TABLE_SCHEMA=:ts '.(is_string($table_name) ? ' and s.TABLE_NAME = :tn' : ''));
        $stmt->execute($params);
        return array_map(function ($details) { return new DBIndex($details); }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
}