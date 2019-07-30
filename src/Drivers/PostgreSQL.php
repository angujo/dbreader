<?php


namespace Angujo\DBReader\Drivers;


use Angujo\DBReader\Models\Database;
use Angujo\DBReader\Models\DBColumn;
use Angujo\DBReader\Models\DBConstraint;
use Angujo\DBReader\Models\DBTable;
use Angujo\DBReader\Models\ForeignKey;
use Angujo\DBReader\Models\Schema;

class PostgreSQL extends Dbms
{
    private $_schema;
    protected $functions = ['now', 'nextval', 'currval', 'setval',];
    private static $tables_query = 'SELECT t.schemaname schema_name, t.tablename "name", true is_table, false is_view FROM pg_catalog.pg_tables t';
    private static $views_query = 'SELECT t.schemaname schema_name, t.viewname "name", false is_table, true is_view FROM pg_catalog.pg_views t';
    private static $columns_query = 'select n.nspname schema_name,t.relname table_name, ty.typname data_type, c.attname "name", c.attlen "length", c.attnotnull = false is_nullable, c.attnum ordinal, cmt.description "comment", '.
    'pg_get_expr(d.adbin, d.adrelid)::information_schema.character_data "default", st.oid is not null is_auto_increment '.
    'from pg_catalog.pg_attribute c '.
    'join pg_catalog.pg_class t on t.oid=c.attrelid join pg_catalog.pg_namespace n on n.oid=t.relnamespace '.
    'join pg_catalog.pg_type ty on ty.oid=c.atttypid left join pg_catalog.pg_attrdef d on d.adnum=c.attnum and d.adrelid=t.oid '.
    'left join pg_catalog.pg_description cmt on cmt.objoid=t.oid and cmt.objsubid=c.attnum '.
    'left join pg_catalog.pg_type st on pg_get_serial_sequence(t.relname,c.attname)=n.nspname||\'.\'||st.typname '.
    'where not c.attisdropped and NOT pg_is_other_temp_schema(n.oid) AND c.attnum > 0 AND '.
    '(t.relkind = ANY (ARRAY[\'r\'::"char", \'v\'::"char", \'f\'::"char", \'p\'::"char"]))';
    private static $constraints_query = 'select n.nspname schema_name, t.relname table_name, a.attname column_name, c.conname "name", '.
    '\'f\'::character=c.contype is_foreign_key, \'u\'::character=c.contype is_unique_key, \'p\'::character=c.contype is_primary_key '.
    'from pg_catalog.pg_constraint c join pg_catalog.pg_namespace n on n.oid=c.connamespace join pg_catalog.pg_class t on t.oid=conrelid '.
    'join pg_catalog.pg_attribute a on a.attrelid=t.oid and (a.attnum = any (c.conkey)) '.
    'where (c.contype = any (array[\'p\'::character, \'u\'::character]))';
    private static $toone_foreign_queries = 'select n.nspname schema_name, t.relname table_name, a.attname column_name, c.conname "name", rn.nspname foreign_schema_name, rt.relname foreign_table_name, '.
    'ra.attname foreign_column_name '.
    'from pg_catalog.pg_constraint c join pg_catalog.pg_namespace n on n.oid=c.connamespace join pg_catalog.pg_class t on t.oid=c.conrelid '.
    'join pg_catalog.pg_attribute a on a.attrelid=t.oid and a.attnum = any (c.conkey) join pg_catalog.pg_class rt on rt.oid=c.confrelid '.
    'join pg_catalog.pg_namespace rn on rn.oid=rt.relnamespace join pg_catalog.pg_attribute ra on ra.attrelid=rt.oid and ra.attnum = any (c.confkey) '.
    'left join pg_catalog.pg_constraint uc on n.oid=uc.connamespace and a.attnum = any (uc.conkey) and t.oid=uc.conrelid and not uc.contype = any (array[\'u\'::character,\'p\'::character]) '.
    'where (c.contype =\'f\'::character)';
    private static $tomany_foreign_queries = 'select rn.nspname schema_name, rt.relname table_name, ra.attname column_name, c.conname "name", n.nspname foreign_schema_name, t.relname foreign_table_name, a.attname foreign_column_name '.
    'from pg_catalog.pg_constraint c '.
    'join pg_catalog.pg_namespace n on n.oid=c.connamespace join pg_catalog.pg_class t on t.oid=c.conrelid join pg_catalog.pg_attribute a on a.attrelid=t.oid and a.attnum = any (c.conkey) '.
    'join pg_catalog.pg_class rt on rt.oid=c.confrelid join pg_catalog.pg_namespace rn on rn.oid=rt.relnamespace '.
    'join pg_catalog.pg_attribute ra on ra.attrelid=rt.oid and ra.attnum = any (c.confkey) '.
    'join pg_catalog.pg_constraint uc on rn.oid=uc.connamespace and ra.attnum = any (uc.conkey) and rt.oid=uc.conrelid and uc.contype = any (array[\'u\'::character,\'p\'::character]) '.
    'where (c.contype =\'f\'::character)';

    /**
     * One to One
     *
     * @param      $schema
     * @param null $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeys($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableToOneForeignKeys($schema, $table_name) : $this->schemaToOneForeignKeys($schema);
        return array_map(function($d){ return new ForeignKey($d, false); }, $data);
    }

    private function tableToOneForeignKeys($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$toone_foreign_queries, 'AND n.nspname = :ts', 'AND t.relname = :tn',]));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name,]);
        //echo $stmt->_debugQuery(true), "\n";
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function schemaToOneForeignKeys($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$toone_foreign_queries, 'AND n.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
        //echo $stmt->_debugQuery(true), "\n";
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * One to Many
     *
     * @param      $schema
     * @param null $table_name
     *
     * @return ForeignKey[]|array
     */
    public function getReferencingForeignKeys($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableToManyForeignKeys($schema, $table_name) : $this->schemaToManyForeignKeys($schema);
        return array_map(function($d){ return new ForeignKey($d, true); }, $data);
    }

    private function tableToManyForeignKeys($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$tomany_foreign_queries, 'AND n.nspname = :ts', 'AND t.relname = :tn',]));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name,]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function schemaToManyForeignKeys($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$tomany_foreign_queries, 'AND n.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param      $schema
     * @param null $table_name
     *
     * @return DBConstraint[]
     */
    public function getConstraints($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableConstraints($schema, $table_name) : $this->schemaConstraints($schema);
        return array_map(function($d){ return new DBConstraint($d); }, $data);
    }

    private function schemaConstraints($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$constraints_query, 'AND n.nspname = :ts',]));
        $stmt->execute([':ts' => $schema,]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function tableConstraints($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$constraints_query, 'AND n.nspname = :ts', 'AND t.relname = :tn']));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return Schema[]
     */
    public function getSchemas()
    {
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('select schema_name from information_schema.schemata where schema_name not like \'pg_%\' and schema_name not in (\'information_schema\') and catalog_name = :db');
        $stmt->execute([':db' => $this->currentDatabase(true)]);
        // echo $stmt->_debugQuery(true), "\n";
        return array_map(function($details){ return new Schema($details['schema_name'], $this->currentDatabase(true)); }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param null|string $schema
     *
     * @return DBTable[]
     */
    public function getTables($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$tables_query, 'WHERE schemaname = :ts']));
        $stmt->execute([':ts' => $schema]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // echo $stmt->_debugQuery(true),"\n";
        return $this->mapTables(array_map(function($details){
            return new DBTable(array_merge(['db_name' => $this->currentDatabase(true)], $details), true);
        }, $data));
    }

    public function getViews($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$views_query, 'WHERE schemaname = :ts']));
        $stmt->execute([':ts' => $schema]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //echo $stmt->_debugQuery(true),"\n";
        return $this->mapTables(array_map(function($details){
            return new DBTable(array_merge(['db_name' => $this->currentDatabase(true)], $details), true);
        }, $data));
    }

    /**
     * One to one
     *
     * @param string $schema
     * @param string $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencedForeignKeysxx($schema, $table_name = null)
    {
        $params = [':db' => $this->currentDatabase(true),];
        $ts     = ['', ''];
        if (is_string($table_name)) {
            $params[':tn'] = $table_name;
        }
        if (is_string($schema)) {
            $params[':ts'] = $schema;
            $ts            = [' AND tc.table_schema=:ts', ' AND ccu.table_schema=:ts'];
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare("SELECT tc.table_schema, tc.constraint_name \"name\", tc.table_name, kcu.column_name, ccu.table_schema AS foreign_table_schema, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name, false unique_column FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema WHERE tc.constraint_type = 'FOREIGN KEY' {$ts[0]} and tc.table_catalog=:db ".(is_string($table_name) ? ' AND tc.table_name=:tn' : '').
                                           'union '.
                                           "select ccu.table_schema, tc.constraint_name \"name\", ccu.table_name, ccu.column_name, tc.table_schema AS foreign_table_schema, tc.table_name AS foreign_table_name, kcu.column_name AS foreign_column_name, false unique_column FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema join (select kcu.table_catalog,kcu.table_schema, kcu.column_name, kcu.table_name from information_schema.key_column_usage kcu join information_schema.table_constraints tc on tc.table_name=kcu.table_name and tc.constraint_name=kcu.constraint_name AND tc.table_schema = kcu.table_schema and tc.constraint_type='UNIQUE') t on t.table_name=tc.table_name and t.table_catalog=tc.table_catalog and t.table_schema=tc.table_schema and t.column_name=kcu.column_name WHERE tc.constraint_type = 'FOREIGN KEY' {$ts[1]} AND ccu.table_catalog=:db ".(is_string($table_name) ? ' AND ccu.table_name=:tn' : ''));
        $stmt->execute($params);
        // echo $stmt->_debugQuery(true), "\n";
        return $this->mapForeignKeys(array_map(function($details){ return new ForeignKey($details, false); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * one to many
     *
     * @param $schema
     * @param $table_name
     *
     * @return ForeignKey[]
     */
    public function getReferencingForeignKeysxx($schema, $table_name = null)
    {
        $this->switchSchema($schema);
        $params = [':db' => $this->currentDatabase(true),];
        if (is_string($schema)) {
            $params[':ts'] = $schema;
        }
        if ($table_name && is_string($table_name)) {
            $params[':tn'] = $table_name;
        }
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare('SELECT false unique_column, tc.table_schema AS foreign_table_schema, tc.constraint_name "name", tc.table_name AS foreign_table_name, kcu.column_name AS foreign_column_name, '.
                                           'ccu.table_schema, ccu.table_name, ccu.column_name FROM information_schema.table_constraints AS tc '.
                                           'JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema '.
                                           'JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema '.
                                           'WHERE ccu.table_catalog=:db AND tc.constraint_type = \'FOREIGN KEY\' '.(is_string($schema) ? ' AND ccu.table_schema=:ts ' : '').
                                           (is_string($table_name) ? ' AND ccu.table_name=:tn ' : ''));
        $stmt->execute($params);
        //echo $stmt->_debugQuery(true),"\n";
        return $this->mapForeignKeys(array_map(function($details){ return new ForeignKey($details, true); }, $stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    /**
     * @param string|Database $schema
     * @param string|DBTable  $table_name
     *
     * @return DBColumn[]
     */
    public function getColumns($schema, $table_name = null)
    {
        $data = $table_name ? $this->tableColumns($schema, $table_name) : $this->schemaColumns($schema);
        return $this->mapColumns(array_map(function($details){ return new DBColumn($details); }, $data));
    }

    /**
     * @param string $schema
     *
     * @return array
     */
    protected function schemaColumns($schema)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$columns_query, 'AND n.nspname = :ts']));
        $stmt->execute([':ts' => $schema]);
        //  $q=$stmt->_debugQuery(true);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $schema
     * @param string $table_name
     *
     * @return array
     */
    protected function tableColumns($schema, $table_name)
    {
        $this->switchSchema($schema);
        /** @var DBRPDO_Statement $stmt */
        $stmt = $this->connection->prepare(implode(' ', [self::$columns_query, 'AND n.nspname = :ts', 'AND t.relname = :tn']));
        $stmt->execute([':ts' => $schema, ':tn' => $table_name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                $_data['default'] = $this->isAFunction($datum) ? null : $datum;
            } elseif (0 === strcasecmp('character_set_name', $name)) {
                $_data['charset'] = $datum;
            } elseif (0 === strcasecmp('data_type', $name) || 0 === strcasecmp('udt_name', $name)) {
                $_data['data_type'][] = $datum;
            } elseif (0 === strcasecmp('numeric_scale', $name)) {
                $_data['decimal_places'] = $datum;
            } elseif (0 === strcasecmp('constraint_type', $name)) {
                $_data['is_primary'] = 0 === strcasecmp('PRIMARY KEY', $datum);
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
        $this->current_db = $this->current_db ?: new Database($this->connection->query('SELECT current_database();')->fetchColumn());
        return true === $name && $this->current_db ? $this->current_db->name : $this->current_db;
    }

    public function isAFunction($name)
    {
        return in_array($name, ['current_timestamp']) || preg_match('/^(\w+)(\()(.*?)?(\))$/i', $name) || preg_match('/::/i', $name);
    }

    protected function switchSchema($schema)
    {
        if (empty($schema) || !is_string($schema) || 0 === strcasecmp($this->_schema, $schema)) {
            return;
        }
        $this->connection->exec("SET search_path TO \"$schema\"");
    }
}